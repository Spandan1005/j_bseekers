<?php
session_start();
require __DIR__ . '/../lib/backend.php';

// Kurt Castro | 9.28
// Copied from login.php + trimmed
// In progress

if (empty($_SESSION['session_key'])) {
	header('Location: login.php');
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
  <h3>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h3>
  <p><a href="logout.php">Log out</a></p>

</main>
</body>
</html>
