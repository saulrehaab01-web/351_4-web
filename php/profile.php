<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
requireLogin();
$user = getCurrentUser();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($fullName === '') {
        $error = 'Full name cannot be empty.';
    } elseif ($newPassword && $newPassword !== $confirmPassword) {
        $error = 'New password and confirmation do not match.';
    } elseif ($newPassword && !$currentPassword) {
        $error = 'Enter your current password to change the password.';
    } else {
        if ($newPassword) {
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = :id');
            $stmt->execute([':id' => $user['user_id']]);
            $dbUser = $stmt->fetch();
            if (!$dbUser || !password_verify($currentPassword, $dbUser['password_hash'])) {
                $error = 'Current password is incorrect.';
            }
        }
        if (!$error) {
            $sql = 'UPDATE users SET full_name = :full_name, phone = :phone, address = :address';
            $params = [':full_name' => $fullName, ':phone' => $phone, ':address' => $address, ':id' => $user['user_id']];
            if ($newPassword) {
                $sql .= ', password_hash = :password_hash';
                $params[':password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE user_id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $_SESSION['user']['full_name'] = $fullName;
            setFlash('Profile updated successfully.', 'success');
            header('Location: profile.php');
            exit;
        }
    }
}
include __DIR__ . '/header.php';
?>
<section class="section">
    <div class="section-title"><h2>Profile</h2></div>
    <div class="form-card">
        <?php if ($error): ?><div class="flash flash-error"><?php echo escape($error); ?></div><?php endif; ?>
        <form method="post" action="profile.php" class="form-grid">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" name="full_name" id="full_name" value="<?php echo escape($_POST['full_name'] ?? $user['full_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" name="phone" id="phone" value="<?php echo escape($_POST['phone'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" name="address" id="address" value="<?php echo escape($_POST['address'] ?? ''); ?>">
            </div>
            <div class="section-title"><h3>Change Password</h3></div>
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" name="current_password" id="current_password">
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" name="new_password" id="new_password">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" name="confirm_password" id="confirm_password">
            </div>
            <button class="button button-primary" type="submit">Save Changes</button>
        </form>
    </div>
</section>
<?php include __DIR__ . '/footer.php';
