<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
$error = '';
$success = false;
$token = trim($_GET['token'] ?? '');
if (!$token) {
    header('Location: login.php');
    exit;
}
$stmt = $pdo->prepare('SELECT * FROM users WHERE password_reset_token = :token AND password_reset_expires >= NOW() LIMIT 1');
$stmt->execute([':token' => $token]);
$user = $stmt->fetch();
if (!$user) {
    $error = 'Invalid or expired reset token.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    if (!$password || !$confirmPassword) {
        $error = 'Please enter and confirm your new password.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash, password_reset_token = NULL, password_reset_expires = NULL WHERE user_id = :id');
        $stmt->execute([':password_hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => $user['user_id']]);
        $success = true;
    }
}
include __DIR__ . '/header.php';
?>
<section class="section">
    <div class="section-title"><h2>Reset Password</h2></div>
    <div class="form-card">
        <?php if ($error): ?><div class="flash flash-error"><?php echo escape($error); ?></div><?php endif; ?>
        <?php if ($success): ?>
            <div class="flash flash-success">Your password has been updated. <a href="login.php">Login now</a>.</div>
        <?php elseif ($user): ?>
            <form method="post" action="reset_password.php?token=<?php echo escape($token); ?>" class="form-grid">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" name="password" id="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>
                <button class="button button-primary" type="submit">Update Password</button>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/footer.php';
