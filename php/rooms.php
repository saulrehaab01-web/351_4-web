<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

syncRoomStatuses($pdo);

// ── Status badge helper ───────────────────────────────────
function roomStatusBadge($status) {
    $map = [
        'Available'   => ['label' => 'Available',    'class' => 'badge-available'],
        'Reserved'    => ['label' => 'Reserved',     'class' => 'badge-reserved'],
        'Occupied'    => ['label' => 'Occupied',     'class' => 'badge-occupied'],
        'Maintenance' => ['label' => 'Maintenance',  'class' => 'badge-maintenance'],
    ];
    $s = $map[$status] ?? ['label' => escape($status), 'class' => 'badge-available'];
    return '<span class="room-status-badge ' . $s['class'] . '">' . $s['label'] . '</span>';
}

$rooms = $pdo->query('
    SELECT r.room_id, r.room_number, r.status,
           rt.type_name, rt.base_price, rt.max_occupancy, rt.amenities, rt.description
    FROM rooms r
    JOIN room_types rt ON r.room_type_id = rt.room_type_id
    ORDER BY rt.base_price ASC
')->fetchAll();

include __DIR__ . '/header.php';
?>

<section class="section">
    <div class="section-title"><h2>Our Rooms</h2></div>
    <div class="grid banner-grid">
        <?php foreach ($rooms as $room): ?>
            <div class="card">
                <img src="images/<?php echo escape('Hotel Nikko San Francisco — Hotel Review _ Condé Nast Traveler (1).jpg'); ?>"
                     alt="<?php echo escape($room['type_name']); ?>">
                <div class="card-body">

                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px; margin-bottom:.5rem;">
                        <h3 style="margin:0;"><?php echo escape($room['type_name']); ?> — #<?php echo escape($room['room_number']); ?></h3>
                        <?php echo roomStatusBadge($room['status']); ?>
                    </div>

                    <p><?php echo escape($room['description']); ?></p>
                    <p><strong>Price:</strong> $<?php echo number_format($room['base_price'], 2); ?> / night</p>
                    <p><strong>Occupancy:</strong> <?php echo escape($room['max_occupancy']); ?> guests</p>

                    <div style="display:flex; gap:8px; margin-top:1rem; flex-wrap:wrap;">
                        <a href="room_details.php?room_id=<?php echo urlencode($room['room_id']); ?>"
                           class="button button-secondary">View Details</a>

                        <?php if ($room['status'] === 'Available'): ?>
                            <a href="booking.php?room_id=<?php echo urlencode($room['room_id']); ?>"
                               class="button button-primary">Book Now</a>
                        <?php else: ?>
                            <button class="button button-primary" disabled
                                    style="opacity:.5; cursor:not-allowed;">
                                Not Available
                            </button>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php include __DIR__ . '/footer.php'; ?>
