<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
requireLogin();
requireRole([1]);
$revenue = $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = "Completed"')->fetchColumn();
$totalReservations = $pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn();
$todayCheckins = $pdo->query('SELECT COUNT(*) FROM reservations WHERE check_in_date = CURDATE()')->fetchColumn();
$cancelled = $pdo->query('SELECT COUNT(*) FROM reservations WHERE status = "Cancelled"')->fetchColumn();
include __DIR__ . '/header.php';
?>
<section class="section">
    <div class="section-title"><h2>Reports & Audit Logs</h2></div>
    <div class="grid banner-grid">
        <div class="card card-body"><h3>Revenue</h3><p>$<?php echo number_format($revenue, 2); ?></p></div>
        <div class="card card-body"><h3>Total Reservations</h3><p><?php echo escape($totalReservations); ?></p></div>
        <div class="card card-body"><h3>Today Check-ins</h3><p><?php echo escape($todayCheckins); ?></p></div>
        <div class="card card-body"><h3>Cancelled</h3><p><?php echo escape($cancelled); ?></p></div>
    </div>
    <div class="card card-body">
        <p>Audit logs and detailed reports can be viewed in the database audit_logs and reservations tables.</p>
    </div>
</section>
<?php include __DIR__ . '/footer.php';
