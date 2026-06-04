<?php
// require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';


function escape($html) {
    return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

function isLoggedIn() {
    return !empty($_SESSION['user']) && !empty($_SESSION['otp_verified']);
}

function requireLogin() {
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
    if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
        header('Location: otp_verify.php');
        exit;
    }
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT_SECONDS)) {
        session_unset();
        session_destroy();
        header('Location: session_timeout.php');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function getRoleName($roleId) {
    $roles = [1 => 'Admin', 2 => 'Receptionist', 3 => 'Guest'];
    return $roles[$roleId] ?? 'Guest';
}

function userHasRole(array $allowedRoles) {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    return in_array((int)$user['role_id'], $allowedRoles, true); // ← add (int) cast
}

function requireRole(array $allowedRoles) {
    requireLogin(); // ← ensure login + OTP check runs first

    if (!isset($_SESSION['user']) || !in_array((int)$_SESSION['user']['role_id'], $allowedRoles, true)) {
        header('Location: access_denied.php');
        exit;
    }
}

function setFlash($message, $type = 'success') {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function recordLoginAttempt(PDO $pdo, $username, $email, $result, $reason = null) {
    $stmt = $pdo->prepare('INSERT INTO login_attempts (username, email, ip_address, user_agent, attempt_result, failure_reason) VALUES (:username, :email, :ip_address, :user_agent, :result, :reason)');
    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ':result' => $result,
        ':reason' => $reason,
    ]);
}

function createSessionRecord(PDO $pdo, $userId) {
    $sessionToken = bin2hex(random_bytes(32));
    $now          = date('Y-m-d H:i:s');
    $expiresAt    = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT_SECONDS);

    // Delete any existing session record first, then insert fresh
    $pdo->prepare('DELETE FROM sessions WHERE session_id = :session_id')
        ->execute([':session_id' => session_id()]);

    $stmt = $pdo->prepare('
        INSERT INTO sessions 
            (session_id, user_id, session_token, ip_address, user_agent, last_activity, expires_at)
        VALUES 
            (:session_id, :user_id, :session_token, :ip_address, :user_agent, :last_activity, :expires_at)
    ');

    $stmt->execute([
        ':session_id'    => session_id(),
        ':user_id'       => $userId,
        ':session_token' => $sessionToken,
        ':ip_address'    => $_SERVER['REMOTE_ADDR']     ?? 'unknown',
        ':user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ':last_activity' => $now,
        ':expires_at'    => $expiresAt,
    ]);

    $_SESSION['session_token'] = $sessionToken;
}

function getSetting(PDO $pdo, $key, $default = null) {
    try {
        $stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = :key LIMIT 1');
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

function generateReservationNumber(PDO $pdo) {
    $stmt = $pdo->query('SELECT COALESCE(MAX(reservation_id), 0) + 1 FROM reservations');
    $nextId = (int)$stmt->fetchColumn();
    return 'RES' . date('Ymd') . str_pad($nextId, 5, '0', STR_PAD_LEFT);
}

function createOtp($pdo, $user_id) {
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

    $pdo->prepare("DELETE FROM two_factor_auth WHERE user_id = ?")
        ->execute([$user_id]);

    // MySQL calculates expiry using its OWN NOW() — no PHP timezone involved
    $pdo->prepare("
        INSERT INTO two_factor_auth (user_id, otp_code, expires_at) 
        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))
    ")->execute([$user_id, $otp]);

    return $otp;
}

function sendOtpEmail($email, $fullName, $otp) {
    // ← NO require_once lines needed anymore, Composer autoloads it

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->SMTPDebug  = 0;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'jutanghwa09@gmail.com';
        $mail->Password   = 'xivn unyp geds hhdn'; // replace with fresh App Password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->Timeout    = 10;

        $mail->setFrom('jutanghwa09@gmail.com', 'Hotel Booking');
        $mail->addAddress($email, $fullName);
        $mail->isHTML(true);
        $mail->Subject = 'Your One-Time Verification Code';
        $mail->Body    = "
            <p>Hello <strong>" . htmlspecialchars($fullName) . "</strong>,</p>
            <p>Your verification code is:</p>
            <h2 style='letter-spacing:4px; color:#333;'>{$otp}</h2>
            <p>This code expires in <strong>5 minutes</strong>. Do not share it with anyone.</p>
        ";
        $mail->AltBody = "Hello {$fullName}, your OTP is: {$otp}. Expires in 5 minutes.";

        $mail->send();
        return true;

    } catch (\Exception $e) {
        error_log('[OTP MAIL FAILED] To: ' . $email . ' | Error: ' . $mail->ErrorInfo);
        return false;
    }
}


function validateOtp($pdo, $user_id, $otp) {
    $stmt = $pdo->prepare("
        SELECT * FROM two_factor_auth
        WHERE user_id   = ?
        AND   otp_code  = ?
        AND   expires_at > UTC_TIMESTAMP()
        LIMIT 1
    ");
    // ↑ UTC_TIMESTAMP() matches gmdate() used when saving

    $stmt->execute([$user_id, $otp]);

    if ($stmt->rowCount() > 0) {
        $pdo->prepare("DELETE FROM two_factor_auth WHERE user_id = ?")
            ->execute([$user_id]);
        return true;
    }

    return false;
}


function ensureAuthenticatedUser(PDO $pdo, $user) {
    if (!$user) {
        header('Location: login.php');
        exit;
    }
    if (!$user['email_verified']) {
        setFlash('Please verify your email before proceeding.', 'warning');
    }
}

function redirectByRole($roleId) {
    switch ((int)$roleId) {
        case 1:
            header('Location: admin.php');
            break;
        case 2:
            header('Location: receptionist.php');
            break;
        default:
            header('Location: dashboard.php');
            break;
    }
    exit;
}

// ── CSRF ─────────────────────────────────────────────────

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken() {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
    // Rotate token after each use
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Input Sanitization ───────────────────────────────────

function sanitizeString($value, $maxLength = 255) {
    $value = trim($value);
    $value = strip_tags($value);              // strip HTML/PHP tags
    $value = mb_substr($value, 0, $maxLength); // enforce max length
    return $value;
}

function sanitizeInt($value) {
    return filter_var($value, FILTER_VALIDATE_INT) !== false
        ? (int)$value
        : null;
}

function sanitizeEmail($value) {
    $value = trim($value);
    $value = filter_var($value, FILTER_SANITIZE_EMAIL);
    return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
}

// ── Room status helpers ───────────────────────────────────

/**
 * Dynamically checks if a room is occupied/reserved on a given date.
 * Uses the reservations table as the source of truth.
 */
function getRoomStatusOnDate(PDO $pdo, $roomId, $date = null) {
    $date = $date ?? date('Y-m-d');

    $stmt = $pdo->prepare("
        SELECT status FROM reservations
        WHERE room_id = :room_id
        AND   status NOT IN ('Cancelled', 'Rejected')
        AND   check_in_date  <= :date
        AND   check_out_date >  :date
        LIMIT 1
    ");
    $stmt->execute([':room_id' => $roomId, ':date' => $date]);
    $row = $stmt->fetch();

    if (!$row) return 'Available';
    return $row['status'] === 'Confirmed' ? 'Occupied' : 'Reserved';
}


function syncRoomStatuses(PDO $pdo) {
    $today = date('Y-m-d');

    // Mark rooms with an active confirmed booking as Occupied
    $pdo->prepare("
        UPDATE rooms r
        SET    r.status = 'Occupied'
        WHERE  r.status != 'Maintenance'
        AND    EXISTS (
            SELECT 1 FROM reservations res
            WHERE  res.room_id = r.room_id
            AND    res.status  = 'Confirmed'
            AND    res.check_in_date  <= :today
            AND    res.check_out_date >  :today
        )
    ")->execute([':today' => $today]);

    // Mark rooms with a pending/upcoming booking as Reserved
    $pdo->prepare("
        UPDATE rooms r
        SET    r.status = 'Reserved'
        WHERE  r.status != 'Maintenance'
        AND    r.status != 'Occupied'
        AND    EXISTS (
            SELECT 1 FROM reservations res
            WHERE  res.room_id = r.room_id
            AND    res.status  IN ('Pending', 'Confirmed')
            AND    res.check_out_date > :today
            AND    res.check_in_date  > :today
        )
    ")->execute([':today' => $today]);

    // Mark rooms with no active/upcoming bookings as Available
    $pdo->prepare("
        UPDATE rooms r
        SET    r.status = 'Available'
        WHERE  r.status NOT IN ('Maintenance', 'Occupied')
        AND    NOT EXISTS (
            SELECT 1 FROM reservations res
            WHERE  res.room_id = r.room_id
            AND    res.status  NOT IN ('Cancelled', 'Rejected')
            AND    res.check_out_date > :today
        )
    ")->execute([':today' => $today]);
}

// ── reCAPTCHA verification ────────────────────────────────
function verifyCaptcha() {
    $token = $_POST['g-recaptcha-response'] ?? '';

    if (empty($token)) {
        return false;
    }

    $response = file_get_contents(
        'https://www.google.com/recaptcha/api/siteverify?secret='
        . urlencode(RECAPTCHA_SECRET_KEY)
        . '&response=' . urlencode($token)
        . '&remoteip=' . urlencode($_SERVER['REMOTE_ADDR'] ?? '')
    );

    if (!$response) {
        return false;
    }

    $data = json_decode($response, true);
    return isset($data['success']) && $data['success'] === true;
}