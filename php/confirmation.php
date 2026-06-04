<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
$reservationNumber = trim($_GET['reservation'] ?? '');
$reservation = null;
if ($reservationNumber) {
    $stmt = $pdo->prepare('SELECT r.*, u.full_name, u.email, rm.room_number, rt.type_name FROM reservations r JOIN users u ON r.guest_id = u.user_id JOIN rooms rm ON r.room_id = rm.room_id JOIN room_types rt ON rm.room_type_id = rt.room_type_id WHERE r.reservation_number = :reservation_number LIMIT 1');
    $stmt->execute([':reservation_number' => $reservationNumber]);
    $reservation = $stmt->fetch();
}
include __DIR__ . '/header.php';
?>
<section class="section">
    <div class="section-title"><h2>Booking Confirmation</h2></div>
    <?php if ($reservation): ?>
        <div class="card card-body">
            <h3>Reservation <?php echo escape($reservation['reservation_number']); ?></h3>
            <p><strong>Guest:</strong> <?php echo escape($reservation['full_name']); ?></p>
            <p><strong>Room:</strong> <?php echo escape($reservation['type_name']); ?> — <?php echo escape($reservation['room_number']); ?></p>
            <p><strong>Stay:</strong> <?php echo escape($reservation['check_in_date']); ?> to <?php echo escape($reservation['check_out_date']); ?></p>
            <p><strong>Guests:</strong> <?php echo escape($reservation['number_of_guests']); ?></p>
            <p><strong>Total:</strong> $<?php echo number_format($reservation['total_amount'], 2); ?></p>
            <p><strong>Status:</strong> <?php echo escape($reservation['status']); ?></p>
            <a href="payment.php?reservation=<?php echo urlencode($reservation['reservation_number']); ?>" class="button button-primary">Proceed to Payment</a>
        </div>
    <?php else: ?>
        <div class="flash flash-error">Reservation not found. Please return to the booking page.</div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/footer.php';
