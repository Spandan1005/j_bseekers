<?php
require __DIR__ . '/../lib/backend.php';

// Kurt Castro | 10.13
// Copied from register.php
// My Garage: list of user's cars, add/edit/remove buttons, popup with car details
// To do: list, buttons, popup

// session checker
if (empty($_SESSION['session_key'])) {
	header('Locaation: login.php'); // redirects to login if no session key
	exit;
}

$msg = '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My Garage</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<main>
<h3>My Garage</h3>
<p><a href="home.php">Home</a></p>

<?php if ($msg): // prevent XSS by escaping <,> so HTML can't be ran in msg ?> 
  <p><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<form method="GET" action="myGarage.php">
    <p>
    <button type="submit" name="action" value="add">Add</button>
    <button type="submit" name="action" value="edit">Edit</button>
    <button type="submit" name="action" value="remove" formmethod="POST" onclick="return confirm('Are you sure you want to remove this car?')">Remove</button
    </p>

    <p>You do not have any cars in your Garage</p>
    <p>Click Add to add a car</p>
</form>
</main>
</html>
