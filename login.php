<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// If already fully logged in, redirect to correct dashboard
if (isLoggedIn()) {
    redirectByRole($_SESSION['user']['role_id']);
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfToken(); // ← CSRF check
}

    $usernameOrEmail = sanitizeString($_POST['username'] ?? '', 100);
    $password        =                $_POST['password'] ?? '';
    $usernameOrEmail = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($usernameOrEmail && $password) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :login OR email = :login LIMIT 1');
        $stmt->execute([':login' => $usernameOrEmail]);
        $user = $stmt->fetch();
        if ($user) {
            $lockedUntil = $user['account_locked_until'] ? strtotime($user['account_locked_until']) : 0;
            if ($lockedUntil > time()) {
                $error = 'Your account is temporarily locked. Please try again later.';
                recordLoginAttempt($pdo, $usernameOrEmail, $user['email'], 'Blocked', 'Account locked');
            } elseif (password_verify($password, $user['password_hash'])) {
                if (!$user['is_active']) {
                    $error = 'Your account is inactive. Please contact support.';
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL, last_login = NOW() WHERE user_id = :id');
                    $stmt->execute([':id' => $user['user_id']]);
                    $_SESSION['user'] = [
                        'user_id' => (int)$user['user_id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'full_name' => $user['full_name'],
                        'role_id'   => (int)$user['role_id'],
                    ];
                    $_SESSION['otp_verified'] = false;
                    session_regenerate_id(true); 
                    createSessionRecord($pdo, $user['user_id']);
                    recordLoginAttempt($pdo, $usernameOrEmail, $user['email'], 'Success');
                    $otp = createOtp($pdo, $user['user_id']);
                    if (sendOtpEmail($user['email'], $user['full_name'], $otp)) {
                        setFlash('A one-time verification code has been sent to your email.', 'success');
                    } 
                    else {
                    setFlash('Unable to send the verification code. Please contact support.', 'error');
                    }
                    header('Location: otp_verify.php');
                    exit;
                }
            } else {
                $failed = $user['failed_login_attempts'] + 1;
                $lockout = getSetting($pdo, 'max_login_attempts', 5);
                $lockedUntilDate = null;
                if ($failed >= $lockout) {
                    $lockedUntilDate = date('Y-m-d H:i:s', time() + (int)getSetting($pdo, 'lockout_duration', 900));
                }
                $stmt = $pdo->prepare('UPDATE users SET failed_login_attempts = :failed, account_locked_until = :locked WHERE user_id = :id');
                $stmt->execute([':failed' => $failed, ':locked' => $lockedUntilDate, ':id' => $user['user_id']]);
                $error = 'Invalid credentials. Please try again.';
                recordLoginAttempt($pdo, $usernameOrEmail, $user['email'], 'Failed', 'Wrong password');
            }
        } else {
            $error = 'Invalid credentials. Please try again.';
            recordLoginAttempt($pdo, $usernameOrEmail, $usernameOrEmail, 'Failed', 'User not found');
        }
    } else {
        $error = 'Please enter your username/email and password.';
    }

include __DIR__ . '/header.php';
?>

<!-- reCAPTCHA script — load once in the page head area -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<section class="section">
    <div class="section-title"><h2>Login</h2></div>
    <div class="grid banner-grid">
        <div class="form-card">
            <?php if ($error): ?><div class="flash flash-error"><?php echo escape($error); ?></div><?php endif; ?>
            <form method="post" action="login.php" class="form-grid js-validate-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" name="username" id="username" value="<?php echo escape($_POST['username'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>
                </div>
                <button class="button button-primary" type="submit">Login</button>
            </form>
            <div style="margin-top: 1rem;">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>
            <div style="margin-top: 1rem;">
                <a href="google_oauth.php" class="button button-secondary">Login with Google</a>
            </div>
        </div>
        <div class="card card-body">
            <h3>Need an account?</h3>
            <p>Register now to book premium rooms, manage reservations, and access your personal dashboard.</p>
            <a href="register.php" class="button button-primary">Create Account</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/footer.php'; 