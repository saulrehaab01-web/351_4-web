<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
requireLogin();
requireRole([1]);
$users = $pdo->query('SELECT u.user_id, u.username, u.email, u.full_name, u.phone, u.is_active, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id ORDER BY u.created_at DESC')->fetchAll();
include __DIR__ . '/header.php';
?>
<section class="section">
    <div class="section-title"><h2>User Management</h2></div>
    <div class="grid banner-grid">
        <?php foreach ($users as $user): ?>
            <div class="card card-body">
                <h3><?php echo escape($user['full_name']); ?></h3>
                <p><strong>Username:</strong> <?php echo escape($user['username']); ?></p>
                <p><strong>Email:</strong> <?php echo escape($user['email']); ?></p>
                <p><strong>Role:</strong> <?php echo escape($user['role_name']); ?></p>
                <p><strong>Status:</strong> <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></p>
                <button class="button button-secondary" disabled>Manage</button>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php include __DIR__ . '/footer.php';
