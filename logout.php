<?php

// Properly clear session and cookies then redirect to auth page
if (session_status() === PHP_SESSION_NONE) session_start();
// Unset all session variables
$_SESSION = [];
// Delete session cookie if used
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000,
		$params['path'], $params['domain'], $params['secure'], $params['httponly']
	);
}
// Finally destroy the session
session_destroy();

// Redirect to the auth page (login/register)
header('Location: auth.php');
exit;
