<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600);
            $stmt = $pdo->prepare('UPDATE users SET password_reset_token = :token, password_reset_expires = :expires WHERE user_id = :id');
            $stmt->execute([':token' => $token, ':expires' => $expires, ':id' => $user['user_id']]);
            $resetLink = sprintf('http://%s%sreset_password.php?token=%s', $_SERVER['HTTP_HOST'], dirname($_SERVER['REQUEST_URI']) . '/', $token);
            $message = 'If an account exists with that email, a reset link was issued. Check your inbox.';
        } else {
            $message = 'If an account exists with that email, a reset link was issued. Check your inbox.';
        }
    } else {
        $message = 'Enter a valid email address.';
    }
}
include __DIR__ . '/header.php';
?>
<section class="section">
    <div class="section-title"><h2>Forgot Password</h2></div>
    <div class="form-card">
        <?php if ($message): ?><div class="flash flash-success"><?php echo escape($message); ?></div><?php endif; ?>
        <form method="post" action="forgot_password.php" class="form-grid js-validate-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" value="<?php echo escape($_POST['email'] ?? ''); ?>" required>
            </div>
            <button class="button button-primary" type="submit">Send Reset Link</button>
        </form>
        <div style="margin-top:1rem;"><a href="login.php">Back to login</a></div>
    </div>
</section>
<?php include __DIR__ . '/footer.php';
