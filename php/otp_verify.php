<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
$error = '';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$user = $_SESSION['user'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
	
	//echo "Entered OTP: " . $otp . "<br>";
	//exit;

if ($otp && validateOtp($pdo, $user['user_id'], $otp)) {
    $_SESSION['otp_verified'] = true;
    setFlash('Authentication successful. Welcome back!', 'success');
    redirectByRole($user['role_id']);
    exit;
}
    $error = 'The verification code is not valid. Please try again.';
}

if (isset($_GET['resend'])) {
    $otp = createOtp($pdo, $user['user_id']);
    if (sendOtpEmail($user['email'], $user['full_name'], $otp)) {
        setFlash('A new verification code has been sent to your email.', 'success');
    } else {
        setFlash('Unable to resend the verification code. Please contact support or try again later.', 'error');
    }
    header('Location: otp_verify.php');
    exit;
}

include __DIR__ . '/header.php';
?>
<section class="section">
    <div class="section-title"><h2>OTP Verification</h2></div>
    <div class="form-card">
        <?php if ($error): ?><div class="flash flash-error"><?php echo escape($error); ?></div><?php endif; ?>
        <p>Enter the 6-digit code sent to your email address.</p>
        <form method="post" action="otp_verify.php" class="form-grid js-validate-form">
            <div class="form-group">
                <label for="otp">Verification Code</label>
                <input type="text" name="otp" id="otp" inputmode="numeric" pattern="\d{6}" required>
            </div>
            <button class="button button-primary" type="submit">Verify</button>
        </form>
        <div style="margin-top: 1rem;">
            <a href="otp_verify.php?resend=1">Resend code</a>
        </div>
    </div>
</section>
<?php include __DIR__ . '/footer.php';
