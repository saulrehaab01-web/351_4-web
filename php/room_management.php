<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
requireLogin();
requireRole([1, 2]);
$rooms = $pdo->query('SELECT r.room_id, r.room_number, r.status, rt.type_name, rt.base_price FROM rooms r JOIN room_types rt ON r.room_type_id = rt.room_type_id ORDER BY r.room_number ASC')->fetchAll();
include __DIR__ . '/header.php';
?>
<section class="section">
    <div class="section-title"><h2>Room Management</h2></div>
    <div class="grid banner-grid">
        <?php foreach ($rooms as $room): ?>
            <div class="card card-body">
                <h3>Room <?php echo escape($room['room_number']); ?></h3>
                <p><strong>Type:</strong> <?php echo escape($room['type_name']); ?></p>
                <p><strong>Price:</strong> $<?php echo number_format($room['base_price'], 2); ?></p>
                <p><strong>Status:</strong> <?php echo escape($room['status']); ?></p>
                <button class="button button-secondary" disabled>Edit</button>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="form-card">
        <h3>Add New Room</h3>
        <p class="text-center">Room creation is available through the admin backend. This page displays room status and availability.</p>
    </div>
</section>
<?php include __DIR__ . '/footer.php';
