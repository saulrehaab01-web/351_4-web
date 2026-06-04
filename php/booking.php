<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
requireLogin();
$user = getCurrentUser();

$roomId   = filter_input(INPUT_GET, 'room_id', FILTER_VALIDATE_INT);
$checkin  = $_POST['checkin']  ?? ($_GET['checkin']  ?? '');
$checkout = $_POST['checkout'] ?? ($_GET['checkout'] ?? '');
$guests   = filter_input(INPUT_POST, 'guests', FILTER_VALIDATE_INT)
          ?: (filter_input(INPUT_GET,  'guests', FILTER_VALIDATE_INT) ?: 1);

$error = '';
$room  = null;
$today = date('Y-m-d');

if ($roomId) {
    $stmt = $pdo->prepare('
        SELECT r.room_id, r.room_number, r.status,
               rt.type_name, rt.base_price, rt.max_occupancy, rt.amenities
        FROM rooms r
        JOIN room_types rt ON r.room_type_id = rt.room_type_id
        WHERE r.room_id = :room_id LIMIT 1
    ');
    $stmt->execute([':room_id' => $roomId]);
    $room = $stmt->fetch();
}

if (!$room) {
    header('Location: rooms.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrfToken();

    $specialRequests = sanitizeString($_POST['special_requests'] ?? '', 1000);
    $checkin         = sanitizeString($_POST['checkin']          ?? '', 10);
    $checkout        = sanitizeString($_POST['checkout']         ?? '', 10);
    $guests          = sanitizeInt(  $_POST['guests']            ?? 1) ?? 1;

    $dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($dateRegex, $checkin) || !preg_match($dateRegex, $checkout)) {
        $error = 'Invalid date format.';
    } elseif (!$checkin || !$checkout) {
        $error = 'Please select both check-in and check-out dates.';
    } elseif ($checkin < $today) {
        $error = 'Check-in date cannot be in the past.';
    } elseif ($checkout <= $checkin) {
        $error = 'Check-out date must be after the check-in date.';
    } elseif ($guests < 1 || $guests > $room['max_occupancy']) {
        $error = 'Number of guests must be between 1 and ' . $room['max_occupancy'] . '.';
    } else {
        // ── Check room availability ───────────────────────────
        $stmtCheck = $pdo->prepare('
            SELECT COUNT(*) FROM reservations
            WHERE room_id = :room_id
            AND   status NOT IN ("Cancelled", "Rejected")
            AND   check_in_date  < :checkout
            AND   check_out_date > :checkin
        ');
        $stmtCheck->execute([
            ':room_id'  => $room['room_id'],
            ':checkin'  => $checkin,
            ':checkout' => $checkout,
        ]);

        if ($stmtCheck->fetchColumn() > 0) {
            $error = 'This room is not available for the selected dates. Please choose different dates.';
        } else {
            $nights            = (strtotime($checkout) - strtotime($checkin)) / 86400;
            $amount            = $nights * $room['base_price'];
            $reservationNumber = generateReservationNumber($pdo);

            $stmt = $pdo->prepare('
                INSERT INTO reservations
                    (reservation_number, guest_id, room_id, check_in_date, check_out_date,
                     number_of_guests, special_requests, total_amount, status, created_by)
                VALUES
                    (:reservation_number, :guest_id, :room_id, :checkin, :checkout,
                     :guests, :special_requests, :amount, "Pending", :created_by)
            ');
            $stmt->execute([
                ':reservation_number' => $reservationNumber,
                ':guest_id'           => $user['user_id'],
                ':room_id'            => $room['room_id'],
                ':checkin'            => $checkin,
                ':checkout'           => $checkout,
                ':guests'             => $guests,
                ':special_requests'   => $specialRequests,
                ':amount'             => $amount,
                ':created_by'         => $user['user_id'],
            ]);

            // ── Update room status after booking ──────────────
            if ($checkin <= $today && $checkout > $today) {
                $newStatus = 'Occupied';  // check-in is today or ongoing
            } else {
                $newStatus = 'Reserved';  // future booking
            }
            $pdo->prepare("UPDATE rooms SET status = :status WHERE room_id = :room_id")
                ->execute([':status' => $newStatus, ':room_id' => $room['room_id']]);
            // ─────────────────────────────────────────────────

            setFlash('Reservation created. Proceed to payment to confirm your stay.', 'success');
            header('Location: confirmation.php?reservation=' . urlencode($reservationNumber));
            exit;
        }
    }
}

include __DIR__ . '/header.php';
?>

<section class="section">
    <div class="section-title"><h2>Booking Form</h2></div>
    <div class="grid banner-grid">
        <div class="card card-body">
            <h3><?php echo escape($room['type_name']); ?> — Room <?php echo escape($room['room_number']); ?></h3>
            <p><strong>Room rate:</strong> $<?php echo number_format($room['base_price'], 2); ?> / night</p>
            <p><strong>Max occupancy:</strong> <?php echo escape($room['max_occupancy']); ?> guest(s)</p>

            <?php if ($error): ?>
                <div class="flash flash-error"><?php echo escape($error); ?></div>
            <?php endif; ?>

            <form method="post"
                  action="booking.php?room_id=<?php echo urlencode($room['room_id']); ?>"
                  class="form-grid">

                <input type="hidden" name="csrf_token"
                       value="<?php echo generateCsrfToken(); ?>">

                <div class="form-group">
                    <label for="checkin">Check-in Date</label>
                    <input type="date"
                           name="checkin"
                           id="checkin"
                           min="<?php echo $today; ?>"
                           value="<?php echo escape($checkin); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="checkout">Check-out Date</label>
                    <input type="date"
                           name="checkout"
                           id="checkout"
                           min="<?php echo date('Y-m-d', strtotime($today . ' +1 day')); ?>"
                           value="<?php echo escape($checkout); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="guests">Number of Guests</label>
                    <input type="number"
                           name="guests"
                           id="guests"
                           min="1"
                           max="<?php echo escape($room['max_occupancy']); ?>"
                           value="<?php echo escape($guests); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="special_requests">Special Requests</label>
                    <textarea name="special_requests"
                              id="special_requests"
                              rows="4"><?php echo escape($_POST['special_requests'] ?? ''); ?></textarea>
                </div>

                <div id="price-preview" style="padding:.75rem;background:#f9f9f9;border-radius:6px;display:none;">
                    <strong>Estimated Total:</strong>
                    <span id="total-price">—</span>
                </div>

                <button class="button button-primary" type="submit">Confirm Booking</button>
            </form>
        </div>

        <div class="card">
            <img src="images/Making Luxury Permanent_ The Growing Trend of___.jpg"
                 alt="<?php echo escape($room['type_name']); ?>">
        </div>
    </div>
</section>

<script>
const rate     = <?php echo (float)$room['base_price']; ?>;
const checkin  = document.getElementById('checkin');
const checkout = document.getElementById('checkout');
const preview  = document.getElementById('price-preview');
const total    = document.getElementById('total-price');

function updatePrice() {
    if (!checkin.value || !checkout.value) return;
    const nights = (new Date(checkout.value) - new Date(checkin.value)) / 86400000;
    if (nights < 1) {
        const next = new Date(checkin.value);
        next.setDate(next.getDate() + 1);
        checkout.min = next.toISOString().split('T')[0];
        if (checkout.value <= checkin.value) checkout.value = next.toISOString().split('T')[0];
        return;
    }
    preview.style.display = 'block';
    total.textContent = `$${(nights * rate).toFixed(2)} (${nights} night${nights > 1 ? 's' : ''})`;
}

checkin.addEventListener('change', updatePrice);
checkout.addEventListener('change', updatePrice);
updatePrice();
</script>

<?php include __DIR__ . '/footer.php'; ?>