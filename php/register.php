<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
 
$error = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
 
    // ── CAPTCHA check — must pass before anything else ────
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    if (!verifyRecaptcha($recaptchaResponse)) {
        $error = 'Please complete the CAPTCHA verification.';
    } else {
        $username        = sanitizeString($_POST['username']         ?? '', 50);
        $email           = sanitizeEmail( $_POST['email']            ?? '');
        $password        =                $_POST['password']         ?? '';
        $confirmPassword =                $_POST['confirm_password'] ?? '';
        $fullName        = sanitizeString($_POST['full_name']        ?? '', 100);
        $phone           = sanitizeString($_POST['phone']            ?? '', 20);
        $address         = sanitizeString($_POST['address']          ?? '', 255);
        $roleId          = 3; // always Guest — never from POST
 
        if (!$username || !$email || !$fullName || !$password || !$confirmPassword) {
            $error = 'Please complete all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $error = 'Password must contain at least one uppercase letter and one number.';
        } elseif ($password !== $confirmPassword) {
            $error = 'The passwords do not match.';
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username OR email = :email');
            $stmt->execute([':username' => $username, ':email' => $email]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Username or email already exists.';
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO users (username, email, password_hash, full_name, phone, address, role_id, email_verified)
                    VALUES (:username, :email, :password_hash, :full_name, :phone, :address, :role_id, 1)
                ');
                $stmt->execute([
                    ':username'      => $username,
                    ':email'         => $email,
                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    ':full_name'     => $fullName,
                    ':phone'         => $phone,
                    ':address'       => $address,
                    ':role_id'       => $roleId,
                ]);
                setFlash('Registration successful. Please login to continue.', 'success');
                header('Location: login.php');
                exit;
            }
        }
    }
}
 
include __DIR__ . '/header.php';
?>
 
<!-- reCAPTCHA script -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
 
<section class="section">
    <div class="section-title"><h2>Create Account</h2></div>
    <div class="form-card">
        <?php if ($error): ?>
            <div class="flash flash-error"><?php echo escape($error); ?></div>
        <?php endif; ?>
 
        <form method="post" action="register.php" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
 
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" name="full_name" id="full_name"
                       value="<?php echo escape($_POST['full_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username"
                       value="<?php echo escape($_POST['username'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email"
                       value="<?php echo escape($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" name="phone" id="phone"
                       value="<?php echo escape($_POST['phone'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" name="address" id="address"
                       value="<?php echo escape($_POST['address'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
 
            <!-- reCAPTCHA widget -->
            <div class="form-group">
                <div class="g-recaptcha" data-sitekey="6LcNigwtAAAAACLRL6xYR4ae89-r7EHuGs85k8I5"></div>
            </div>
 
            <button class="button button-primary" type="submit">Register</button>
        </form>
 
        <div style="margin-top: 1rem;">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</section>
 
<?php include __DIR__ . '/footer.php'; ?>