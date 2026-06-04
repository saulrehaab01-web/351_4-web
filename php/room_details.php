<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
$roomId = filter_input(INPUT_GET, 'room_id', FILTER_VALIDATE_INT);
if (!$roomId) {
    header('Location: rooms.php');
    exit;
}
$stmt = $pdo->prepare('SELECT r.room_id, r.room_number, r.status, r.notes, rt.type_name, rt.base_price, rt.max_occupancy, rt.amenities, rt.description FROM rooms r JOIN room_types rt ON r.room_type_id = rt.room_type_id WHERE r.room_id = :room_id');
$stmt->execute([':room_id' => $roomId]);
$room = $stmt->fetch();
if (!$room) {
    header('Location: 404.php');
    exit;
}
include __DIR__ . '/header.php';
?>
<section class="section">
    <div class="section-title"><h2><?php echo escape($room['type_name']); ?> - Room <?php echo escape($room['room_number']); ?></h2></div>
    <div class="grid banner-grid">
        <div class="card">
            <img src="images/<?php echo escape('This is the most expensive hotel suite in Dubai,___.jpg'); ?>" alt="Room gallery">
        </div>
        <div class="card card-body">
            <p><?php echo escape($room['description']); ?></p>
            <ul>
                <li><strong>Room number:</strong> <?php echo escape($room['room_number']); ?></li>
                <li><strong>Occupancy:</strong> <?php echo escape($room['max_occupancy']); ?> guests</li>
                <li><strong>Amenities:</strong> <?php echo escape($room['amenities']); ?></li>
                <li><strong>Status:</strong> <?php echo escape($room['status']); ?></li>
                <li><strong>Price:</strong> $<?php echo number_format($room['base_price'], 2); ?> per night</li>
            </ul>
            <p><?php echo escape($room['notes']); ?></p>
            <a href="booking.php?room_id=<?php echo urlencode($room['room_id']); ?>" class="button button-primary">Book This Room</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/footer.php';
