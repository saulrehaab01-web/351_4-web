<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';
$guests = filter_input(INPUT_GET, 'guests', FILTER_VALIDATE_INT) ?: 1;
$type = filter_input(INPUT_GET, 'type', FILTER_VALIDATE_INT);
$errors = [];
$results = [];
if ($checkin && $checkout && strtotime($checkout) > strtotime($checkin)) {
    $query = 'SELECT rm.room_id, rm.room_number, rm.status, rt.type_name, rt.base_price, rt.max_occupancy, rt.amenities FROM rooms rm JOIN room_types rt ON rm.room_type_id = rt.room_type_id WHERE rm.status = "Available"';
    $params = [];
    if ($type) {
        $query .= ' AND rt.room_type_id = :type';
        $params[':type'] = $type;
    }
    if ($guests) {
        $query .= ' AND rt.max_occupancy >= :guests';
        $params[':guests'] = $guests;
    }
    $query .= ' AND rm.room_id NOT IN (SELECT room_id FROM reservations WHERE status IN ("Confirmed","CheckedIn") AND ((check_in_date <= :checkin AND check_out_date > :checkin) OR (check_in_date < :checkout AND check_out_date >= :checkout) OR (check_in_date >= :checkin AND check_out_date <= :checkout)))';
    $params[':checkin'] = $checkin;
    $params[':checkout'] = $checkout;
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
} else {
    $errors[] = 'Enter valid check-in and check-out dates.';
}
$roomTypes = $pdo->query('SELECT room_type_id, type_name FROM room_types WHERE is_active = 1')->fetchAll();
include __DIR__ . '/header.php';
?>
<section class="section">
    <div class="section-title"><h2>Search Rooms</h2></div>
    <div class="form-card">
        <form method="get" action="search.php" class="form-grid">
            <div class="form-group">
                <label for="checkin">Check-in</label>
                <input type="date" name="checkin" id="checkin" value="<?php echo escape($checkin); ?>" required>
            </div>
            <div class="form-group">
                <label for="checkout">Check-out</label>
                <input type="date" name="checkout" id="checkout" value="<?php echo escape($checkout); ?>" required>
            </div>
            <div class="form-group">
                <label for="guests">Guests</label>
                <input type="number" name="guests" id="guests" min="1" value="<?php echo escape($guests); ?>" required>
            </div>
            <div class="form-group">
                <label for="type">Room Type</label>
                <select name="type" id="type">
                    <option value="">Any Type</option>
                    <?php foreach ($roomTypes as $roomType): ?>
                        <option value="<?php echo escape($roomType['room_type_id']); ?>" <?php echo $roomType['room_type_id'] == $type ? 'selected' : ''; ?>><?php echo escape($roomType['type_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="button button-primary" type="submit">Search</button>
        </form>
    </div>
    <?php if ($errors): ?>
        <?php foreach ($errors as $message): ?>
            <div class="flash flash-error"><?php echo escape($message); ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if ($results): ?>
        <div class="grid banner-grid" style="margin-top:1.5rem;">
            <?php foreach ($results as $room): ?>
                <div class="card">
                    <img src="images/<?php echo escape('Seven Seas Explorer - Penthouse  #visioncruise #cruise #travel.jpg'); ?>" alt="<?php echo escape($room['type_name']); ?>">
                    <div class="card-body">
                        <h3><?php echo escape($room['type_name']); ?> — #<?php echo escape($room['room_number']); ?></h3>
                        <p><strong>Price:</strong> $<?php echo number_format($room['base_price'], 2); ?> / night</p>
                        <p><strong>Occupancy:</strong> <?php echo escape($room['max_occupancy']); ?></p>
                        <a href="booking.php?room_id=<?php echo urlencode($room['room_id']); ?>&checkin=<?php echo urlencode($checkin); ?>&checkout=<?php echo urlencode($checkout); ?>&guests=<?php echo urlencode($guests); ?>" class="button button-primary">Book Now</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="margin-top:1rem;">No rooms matched your search. Try changing the dates or room type.</p>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/footer.php';
