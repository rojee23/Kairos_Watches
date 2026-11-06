<?php
session_start();
session_unset();   // remove all session variables
session_destroy(); // destroy the session
setcookie('user_email', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);
header("Location: ../LogIn/login.php"); // redirect to login page
exit();
?>