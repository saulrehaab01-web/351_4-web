<?php
require_once __DIR__ . '/functions.php';
$user = getCurrentUser();
$flash = getFlash();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape(SITE_NAME); ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/script.js" defer></script>
</head>
<body>
   
<header class="site-header">
    <div class="brand-bar">
        <div class="brand-logo"><a href="index.php"><?php echo escape(SITE_NAME); ?></a></div>
        <div class="brand-contact">
            <span><strong>Email:</strong> <?php echo escape(SITE_EMAIL); ?></span>
            <span><strong>Phone:</strong> <?php echo escape(SITE_PHONE); ?></span>
        </div>
    </div>
    <nav class="main-nav">
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="rooms.php">Rooms</a>
        <a href="contact.php">Contact</a>
        <?php if ($user): ?>
            <a href="dashboard.php">Dashboard</a>
            <?php if ($user['role_id'] === 2): ?>
                <a href="receptionist.php">Reception</a>
            <?php endif; ?>
            <?php if ($user['role_id'] === 1): ?>
                <a href="admin.php">Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="button button-ghost">Logout</a>
        <?php else: ?>
            <a href="login.php" class="button button-primary">Login</a>
            <a href="register.php" class="button button-secondary">Register</a>
        <?php endif; ?>
    </nav>
</header>
<?php if ($flash): ?>
    <div class="flash flash-<?php echo escape($flash['type']); ?>">
        <?php echo escape($flash['message']); ?>
    </div>
<?php endif; ?>
<main class="page-content">
