<?php
session_start();
require __DIR__ . '/../lib/backend.php';

// Kurt Castro | 9.28
// Copied from login.php + trimmed
// Functioning with log out link

if (empty($_SESSION['session_key'])) { // checks for session key, if none then redirects to login
	header('Location: login.php');
	exit; // stops rest of the page from sending so home HTML is not visible on login
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Home</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<main>
  <?php // escapes username to prevent SQL injection or XSS ?>
  <h3>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h3>
  <p><a href="nhtsaApiTest.php">Recall Test</a></p>

  <p><a href="logout.php">Log out</a></p>

</main>
</body>
</html>
