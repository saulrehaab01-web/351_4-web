<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if (empty(GOOGLE_CLIENT_ID) || empty(GOOGLE_CLIENT_SECRET)) {
    include __DIR__ . '/header.php';
    ?>
    <section class="section text-center">
        <div class="card card-body">
            <h2>Google OAuth Login</h2>
            <p>Google OAuth is enabled in the application, but OAuth credentials are not configured.</p>
            <p>Please update <code>config.php</code> with <code>GOOGLE_CLIENT_ID</code> and <code>GOOGLE_CLIENT_SECRET</code> to activate.</p>
            <a href="login.php" class="button button-primary">Back to Login</a>
        </div>
    </section>
    <?php
    include __DIR__ . '/footer.php';
    exit;
}

if (!isset($_GET['code'])) {
    $params = http_build_query([
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'offline',
        'prompt' => 'consent',
    ]);
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    exit;
}

$code = $_GET['code'];
$tokenUrl = 'https://oauth2.googleapis.com/token';
$response = file_get_contents($tokenUrl, false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query([
            'code' => $code,
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'grant_type' => 'authorization_code',
        ]),
    ],
]));
$data = json_decode($response, true);
if (empty($data['id_token'])) {
    setFlash('Google login failed. Please try again.', 'error');
    header('Location: login.php');
    exit;
}
$idToken = explode('.', $data['id_token'])[1];
$payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $idToken)), true);
$email = $payload['email'] ?? '';
$fullName = $payload['name'] ?? 'Google Guest';
$googleId = $payload['sub'] ?? null;
$picture = $payload['picture'] ?? null;

require_once __DIR__ . '/db.php';
$stmt = $pdo->prepare('SELECT * FROM users WHERE google_id = :google_id OR email = :email LIMIT 1');
$stmt->execute([':google_id' => $googleId, ':email' => $email]);
$user = $stmt->fetch();
if (!$user) {
    $stmt = $pdo->prepare('INSERT INTO users (username, email, full_name, role_id, google_id, google_profile_picture, email_verified) VALUES (:username, :email, :full_name, 3, :google_id, :picture, 1)');
    $stmt->execute([
        ':username' => strtolower(preg_replace('/[^a-z0-9]/i', '', strtok($fullName, ' '))) . rand(100, 999),
        ':email' => $email,
        ':full_name' => $fullName,
        ':google_id' => $googleId,
        ':picture' => $picture,
    ]);
    $user = $pdo->query('SELECT * FROM users WHERE google_id = ' . $pdo->quote($googleId) . ' LIMIT 1')->fetch();
}
$_SESSION['user'] = [
    'user_id' => $user['user_id'],
    'username' => $user['username'],
    'email' => $user['email'],
    'full_name' => $user['full_name'],
    'role_id' => $user['role_id'],
];
$_SESSION['otp_verified'] = true;
createSessionRecord($pdo, $user['user_id']);
setFlash('Logged in with Google successfully.', 'success');
header('Location: dashboard.php');
exit;
