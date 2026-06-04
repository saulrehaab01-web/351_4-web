<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
requireLogin();
requireRole([1, 2]);
$reservations = $pdo->query('SELECT r.reservation_number, u.full_name, rm.room_number, rt.type_name, r.check_in_date, r.check_out_date, r.status FROM reservations r JOIN users u ON r.guest_id = u.user_id JOIN rooms rm ON r.room_id = rm.room_id JOIN room_types rt ON rm.room_type_id = rt.room_type_id ORDER BY r.check_in_date DESC LIMIT 10')->fetchAll();
include __DIR__ . '/header.php';
?>
<section class="section">
    <div class="section-title"><h2>Receptionist Dashboard</h2></div>
    <div class="card card-body">
        <p>Manage reservations, process check-ins/check-outs, and review booking status for guest arrivals.</p>
    </div>
    <div class="section-title"><h2>Recent Reservations</h2></div>
    <div class="grid banner-grid">
        <?php foreach ($reservations as $reservation): ?>
            <div class="card card-body">
                <h3><?php echo escape($reservation['reservation_number']); ?> — <?php echo escape($reservation['status']); ?></h3>
                <p><strong>Guest:</strong> <?php echo escape($reservation['full_name']); ?></p>
                <p><strong>Room:</strong> <?php echo escape($reservation['type_name']); ?> #<?php echo escape($reservation['room_number']); ?></p>
                <p><strong>Dates:</strong> <?php echo escape($reservation['check_in_date']); ?> to <?php echo escape($reservation['check_out_date']); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php include __DIR__ . '/footer.php';
