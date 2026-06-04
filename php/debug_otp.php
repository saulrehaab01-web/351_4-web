<?php

require_once __DIR__ . '/db.php';

$rows = $pdo->query("
    SELECT user_id, otp_code, expires_at, NOW() as mysql_now,
           (expires_at > NOW()) as is_valid,
           TIMEDIFF(expires_at, NOW()) as time_remaining
    FROM two_factor_auth
")->fetchAll();

echo '<pre>';
print_r($rows);
echo '</pre>';

echo '<p>PHP time: ' . date('Y-m-d H:i:s') . '</p>';
echo '<p>PHP UTC:  ' . gmdate('Y-m-d H:i:s') . '</p>';
