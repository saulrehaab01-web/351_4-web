<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
$reservationNumber = trim($_GET['reservation'] ?? '');
$reservation = null;
$error = '';
if ($reservationNumber) {
    $stmt = $pdo->prepare('SELECT r.*, u.full_name, u.email, rm.room_number, rt.type_name FROM reservations r JOIN users u ON r.guest_id = u.user_id JOIN rooms rm ON r.room_id = rm.room_id JOIN room_types rt ON rm.room_type_id = rt.room_type_id WHERE r.reservation_number = :reservation_number LIMIT 1');
    $stmt->execute([':reservation_number' => $reservationNumber]);
    $reservation = $stmt->fetch();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reservation) {
    $paymentMethod = $_POST['payment_method'] ?? 'OnlinePayment';
    $transactionId = bin2hex(random_bytes(8));
    $stmt = $pdo->prepare('INSERT INTO payments (reservation_id, payment_reference, amount, payment_method, payment_status, transaction_id, payment_date, processed_by) VALUES (:reservation_id, :reference, :amount, :method, "Completed", :transaction_id, NOW(), :processed_by)');
    $stmt->execute([
        ':reservation_id' => $reservation['reservation_id'],
        ':reference' => 'PAY' . strtoupper(bin2hex(random_bytes(4))),
        ':amount' => $reservation['total_amount'],
        ':method' => $paymentMethod,
        ':transaction_id' => $transactionId,
        ':processed_by' => $reservation['guest_id'],
    ]);
    $stmt = $pdo->prepare('UPDATE reservations SET status = "Confirmed" WHERE reservation_id = :id');
    $stmt->execute([':id' => $reservation['reservation_id']]);
    setFlash('Payment completed successfully. Your booking is confirmed.', 'success');
    header('Location: dashboard.php');
    exit;
}
include __DIR__ . '/header.php';
?>
<section class="section">
    <div class="section-title"><h2>Payment</h2></div>
    <?php if ($reservation): ?>
        <div class="grid banner-grid">
            <div class="card card-body">
                <h3>Reservation Summary</h3>
                <p><strong>Reservation:</strong> <?php echo escape($reservation['reservation_number']); ?></p>
                <p><strong>Room:</strong> <?php echo escape($reservation['type_name']); ?></p>
                <p><strong>Dates:</strong> <?php echo escape($reservation['check_in_date']); ?> to <?php echo escape($reservation['check_out_date']); ?></p>
                <p><strong>Total Amount:</strong> $<?php echo number_format($reservation['total_amount'], 2); ?></p>
            </div>
            <div class="card card-body">
                <?php if ($error): ?><div class="flash flash-error"><?php echo escape($error); ?></div><?php endif; ?>
                <form method="post" action="payment.php?reservation=<?php echo urlencode($reservation['reservation_number']); ?>" class="form-grid">
                    <div class="form-group">
                        <label for="payment_method">Payment Method</label>
                        <select name="payment_method" id="payment_method" required>
                            <option value="CreditCard">Credit Card</option>
                            <option value="DebitCard">Debit Card</option>
                            <option value="BankTransfer">Bank Transfer</option>
                            <option value="OnlinePayment" selected>Online Payment</option>
                        </select>
                    </div>
                    <button class="button button-primary" type="submit">Submit Payment</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="flash flash-error">Reservation not found. Please begin a new booking.</div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/footer.php';
