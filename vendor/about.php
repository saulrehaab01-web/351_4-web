<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
include __DIR__ . '/header.php';
?>
<section class="section">
    <div class="section-title"><h2>About Grand Palace Hotel</h2></div>
    <div class="grid banner-grid">
        <div>
            <p>Grand Palace Hotel is a premier destination for guests seeking luxury, comfort, and exceptional service. Nestled in the heart of the city, our hotel blends contemporary design with world-class hospitality.</p>
            <p>From elegant guest rooms to premium dining and modern meeting facilities, Grand Palace Hotel delivers the finest experience for business travelers, families, and couples.</p>
        </div>
        <img src="images/Hotel Nikko San Francisco — Hotel Review _ Condé Nast Traveler.jpg" alt="Hotel lobby" />
    </div>
</section>

<section class="section">
    <div class="section-title"><h2>Our Amenities</h2></div>
    <ul class="feature-list">
        <li>Luxury Suites and Signature Rooms</li>
        <li>Indoor pool, fitness center, and spa services</li>
        <li>Cuisine from gourmet restaurants and bars</li>
        <li>High-speed Wi-Fi and workspace services</li>
    </ul>
</section>

<section class="section">
    <div class="section-title"><h2>Support & Contact</h2></div>
    <div class="card card-body">
        <p>Need help planning your stay? Our guest services team is available 24/7 to assist with reservations, special requests, and travel arrangements.</p>
        <p>Email: <a href="mailto:<?php echo escape(SITE_EMAIL); ?>"><?php echo escape(SITE_EMAIL); ?></a></p>
        <p>Phone: <?php echo escape(SITE_PHONE); ?></p>
    </div>
</section>

<?php include __DIR__ . '/footer.php';
