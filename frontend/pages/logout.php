<?php
// Kurt Castro | 9.30
// Ends session + redirects back to login
// 10.14: needs to tell DB to destroy session key to prevent reuse

session_start(); // must load session first, can't destroy uninitialized session
require __DIR__ . '/../lib/backend.php';

if (!empty($_SESSION['session_key'])) { // checks if there's a session to end
	send_request([ // DB deletes stored key
		'type' => 'logout',
		'session_key' => $_SESSION['session_key'],
		'username' => $_SESSION['username'],
	]);
	// should logout even if DB isn't up
}

session_destroy();
header('Location: login.php');
exit;
?>
