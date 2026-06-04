<?php
require_once __DIR__ . '/functions.php';
include __DIR__ . '/header.php';
?>
<section class="section text-center">
    <div class="card card-body">
        <h2>Session Timed Out</h2>
        <p>Your session has expired due to inactivity. Please log in again to continue.</p>
        <a href="login.php" class="button button-primary">Login</a>
    </div>
</section>
<?php include __DIR__ . '/footer.php';
