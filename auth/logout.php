<?php
/**
 * PRISMA-SLR — auth/logout.php
 * Ends the user's session.
 */
require_once __DIR__ . '/../config/auth.php';

startSecureSession();

// Clears all session data
$_SESSION = [];

// Deletes the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'],   $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

header('Location: /login.php');
exit;
