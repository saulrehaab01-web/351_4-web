<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (session_id()) {
    $stmt = $pdo->prepare('DELETE FROM sessions WHERE session_id = :session_id');
    $stmt->execute([':session_id' => session_id()]);
}
session_unset();
session_destroy();
setFlash('You have been logged out.', 'success');
header('Location: login.php');
exit;
