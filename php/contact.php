<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
$messageSent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $message) {
        $messageSent = true;
    }
}
include __DIR__ . '/header.php';
?>
<section class="section">
    <div class="section-title"><h2>Contact Us</h2></div>
    <div class="grid banner-grid">
        <div class="card card-body">
            <p>Need help with a reservation or want to learn more about our hotel services? Send us a message and our team will get back to you soon.</p>
            <?php if ($messageSent): ?>
                <div class="flash flash-success">Thank you! Your message was sent successfully.</div>
            <?php endif; ?>
            <form action="contact.php" method="post" class="form-grid js-validate-form">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" value="<?php echo escape($_POST['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" value="<?php echo escape($_POST['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea name="message" id="message" rows="6" required><?php echo escape($_POST['message'] ?? ''); ?></textarea>
                </div>
                <button class="button button-primary" type="submit">Send Message</button>
            </form>
        </div>
        <div class="card card-body">
            <h3>Hotel Address</h3>
            <p><?php echo escape(SITE_ADDRESS); ?></p>
            <p><strong>Email:</strong> <a href="mailto:<?php echo escape(SITE_EMAIL); ?>"><?php echo escape(SITE_EMAIL); ?></a></p>
            <p><strong>Phone:</strong> <?php echo escape(SITE_PHONE); ?></p>
            <div style="margin-top:1rem;">
                <iframe src="https://maps.google.com/maps?q=<?php echo urlencode(SITE_ADDRESS); ?>&output=embed" width="100%" height="250" style="border:0;border-radius:1rem;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/footer.php';
