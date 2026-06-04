<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
requireLogin();
$user = getCurrentUser();
$reservations = $pdo->prepare('SELECT r.reservation_number, r.check_in_date, r.check_out_date, r.status, rt.type_name, rm.room_number, r.total_amount FROM reservations r JOIN rooms rm ON r.room_id = rm.room_id JOIN room_types rt ON rm.room_type_id = rt.room_type_id WHERE r.guest_id = :guest_id ORDER BY r.check_in_date DESC');
$reservations->execute([':guest_id' => $user['user_id']]);
$reservations = $reservations->fetchAll();
$payments = $pdo->prepare('SELECT p.payment_reference, p.amount, p.payment_method, p.payment_status, p.payment_date FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id WHERE r.guest_id = :guest_id ORDER BY p.payment_date DESC');
$payments->execute([':guest_id' => $user['user_id']]);
$payments = $payments->fetchAll();
include __DIR__ . '/header.php';
?>
<section class="section">
    <div class="section-title"><h2>Guest Dashboard</h2></div>
    <div class="card card-body">
        <h3>Welcome, <?php echo escape($user['full_name']); ?></h3>
        <p>Your role: <?php echo escape(getRoleName($user['role_id'])); ?></p>
    </div>
    <div class="section-title"><h2>My Bookings</h2></div>
    <?php if ($reservations): ?>
        <div class="grid banner-grid">
            <?php foreach ($reservations as $reservation): ?>
                <div class="card card-body">
                    <h3><?php echo escape($reservation['reservation_number']); ?></h3>
                    <p><strong>Room:</strong> <?php echo escape($reservation['type_name']); ?> — <?php echo escape($reservation['room_number']); ?></p>
                    <p><strong>Dates:</strong> <?php echo escape($reservation['check_in_date']); ?> to <?php echo escape($reservation['check_out_date']); ?></p>
                    <p><strong>Total:</strong> $<?php echo number_format($reservation['total_amount'], 2); ?></p>
                    <p><strong>Status:</strong> <?php echo escape($reservation['status']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No bookings found yet. <a href="rooms.php">Browse rooms</a> to get started.</p>
    <?php endif; ?>
    <div class="section-title"><h2>Payment History</h2></div>
    <?php if ($payments): ?>
        <div class="grid banner-grid">
            <?php foreach ($payments as $payment): ?>
                <div class="card card-body">
                    <h3><?php echo escape($payment['payment_reference']); ?></h3>
                    <p><strong>Amount:</strong> $<?php echo number_format($payment['amount'], 2); ?></p>
                    <p><strong>Method:</strong> <?php echo escape($payment['payment_method']); ?></p>
                    <p><strong>Status:</strong> <?php echo escape($payment['payment_status']); ?></p>
                    <p><strong>Date:</strong> <?php echo escape($payment['payment_date']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>You have no recorded payments yet.</p>
    <?php endif; ?>
</section>
<section class="section">
    <a href="profile.php" class="section-title">Manage Profile</a>
</section>
<?php include __DIR__ . '/footer.php';
