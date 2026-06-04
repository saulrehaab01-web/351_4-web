<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
$featured = $pdo->query('SELECT rt.type_name, rt.base_price, rt.amenities, rt.image_url, COUNT(r.room_id) AS available_rooms FROM room_types rt JOIN rooms r ON rt.room_type_id = r.room_type_id WHERE r.status = "Available" GROUP BY rt.room_type_id ORDER BY rt.base_price ASC LIMIT 4')->fetchAll();
include __DIR__ . '/header.php';
?>
<section class="hero section">
    <div class="hero-content">
        <span class="eyebrow">Luxury Stay in Every Detail</span>
        <h1>Welcome to Grand Palace Hotel</h1>
        <p>Experience unforgettable hospitality, modern luxury rooms, and premium services designed to make every stay special.</p>
        <div class="hero-actions">
            <a href="rooms.php" class="button button-primary">View Rooms</a>
            <a href="booking.php" class="button button-secondary">Book Now</a>
        </div>
    </div>
    <div class="highlight-box">
        <h2>Book your stay today</h2>
        <p>Find the perfect room, check availability, and complete your reservation with ease.</p>
        <div class="form-card">
            <form action="search.php" method="get" class="form-grid">
                <div class="form-group">
                    <label for="checkin">Check-in</label>
                    <input type="date" name="checkin" id="checkin" required>
                </div>
                <div class="form-group">
                    <label for="checkout">Check-out</label>
                    <input type="date" name="checkout" id="checkout" required>
                </div>
                <div class="form-group">
                    <label for="guests">Guests</label>
                    <input type="number" name="guests" id="guests" min="1" value="1" required>
                </div>
                <button class="button button-primary" type="submit">Search Rooms</button>
            </form>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-title"><h2>Featured Rooms</h2></div>
    <div class="grid banner-grid">
        <?php foreach ($featured as $room): ?>
            <div class="card">
                <img src="images/<?php echo escape(trim($room['image_url'] ?: 'Hotel Nikko San Francisco - Updated 2026 Prices & Reviews (CA).jpg')); ?>" alt="<?php echo escape($room['type_name']); ?>">
                <div class="card-body">
                    <h3><?php echo escape($room['type_name']); ?></h3>
                    <p><?php echo escape($room['amenities']); ?></p>
                    <p><strong>From $<?php echo number_format($room['base_price'], 2); ?></strong> per night</p>
                    <a href="rooms.php" class="button button-secondary">Explore</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <div class="section-title"><h2>Our Services</h2></div>
    <ul class="services-list">
        <li><strong>24/7 Concierge:</strong> Personalized support from arrival to departure.</li>
        <li><strong>Luxury Dining:</strong> Signature restaurant experiences and in-room breakfast.</li>
        <li><strong>Spa & Wellness:</strong> Relax with premium wellness packages and treatments.</li>
        <li><strong>Airport Transfers:</strong> Comfortable transfers with professional chauffeurs.</li>
    </ul>
</section>

<section class="section">
    <div class="section-title"><h2>Guest Testimonials</h2></div>
    <ul class="testimonial-list">
        <li>
            <strong>Exceptional service!</strong>
            <p>The staff made our stay feel effortless and luxurious from the moment we arrived.</p>
            <div class="testimonial-author">— Maria S.</div>
        </li>
        <li>
            <strong>Beautiful room and view.</strong>
            <p>The suite was spacious, clean, and the perfect retreat after a day of sightseeing.</p>
            <div class="testimonial-author">— Jason P.</div>
        </li>
    </ul>
</section>

<?php include __DIR__ . '/footer.php';
