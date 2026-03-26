<?php

require_once 'config.php';

// Destroy session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

// Clear remember-me cookie
if (isset($_COOKIE['ems_user'])) {
    setcookie('ems_user', '', time() - 3600, '/', '', false, true);
}

header('Location: ' . APP_URL . '/pages/login.php');
exit;
