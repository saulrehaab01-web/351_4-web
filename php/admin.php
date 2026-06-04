<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

syncRoomStatuses($pdo); // ← add this line

$usersCount        = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$roomsCount        = $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
$reservationsCount = $pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn();

// Add live room availability counts
$availableCount    = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'Available'")->fetchColumn();
$reservedCount     = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'Reserved'")->fetchColumn();
$occupiedCount     = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'Occupied'")->fetchColumn();

requireLogin();
requireRole([1]);
$usersCount = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$roomsCount = $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
$reservationsCount = $pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn();
$paymentsCount = $pdo->query('SELECT COUNT(*) FROM payments')->fetchColumn();
include __DIR__ . '/header.php';
?>
<section class="section">
    <div class="section-title"><h2>Admin Dashboard</h2></div>
    <div class="grid banner-grid">
        <div class="card card-body"><h3>Users</h3><p><?php echo escape($usersCount); ?> total accounts</p></div>
        <div class="card card-body"><h3>Rooms</h3><p><?php echo escape($roomsCount); ?> rooms managed</p></div>
        <div class="card card-body"><h3>Reservations</h3><p><?php echo escape($reservationsCount); ?> reservations</p></div>
        <div class="card card-body"><h3>Payments</h3><p><?php echo escape($paymentsCount); ?> payment records</p></div>
    </div>
    <div class="section-title"><h2>Management</h2></div>
    <div class="grid banner-grid">
        <div class="card card-body"><a href="user_management.php" class="button button-primary">User Management</a></div>
        <div class="card card-body"><a href="room_management.php" class="button button-primary">Room Management</a></div>
        <div class="card card-body"><a href="reports.php" class="button button-primary">Reports & Audit Logs</a></div>
    </div>
</section>
<?php include __DIR__ . '/footer.php';
