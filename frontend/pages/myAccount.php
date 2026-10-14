<?php
session_start();
require __DIR__ . '/../lib/backend.php';

// Kurt Castro | 10.14
// Account: show user + email, toggle for email notifications

// session check
if (empty($_SESSION['session_key'])) {
	header('Location: login.php'); // redirect to login if no session key
	exit;
}

$msg = '';

// load user info every time page opens
// DB not yet routed so testing locally | 10.14
$username ='';
$email='';
$recall_emails = 0;
$reply = send_request([
	'type' => 'get_account',
	'session_key' => $_SESSION['session_key'],
	'username' => $_SESSION['username'],
]);
if (isset($reply['username'])) {
	$username = $reply['username'];
}
if (isset($reply['recall_emails'])) {
	$recall_emails = $reply['recall_emails'];
}

// checks box if recall emails are already on
$checked = '';
if ($recall_emails === '1') {
	$checked = 'checked';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My Account</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<main>
<h3>My Account</h3>
<p><a href="home.php">Home</a></p>

<?php if ($msg): // prevents XSS so HTML doesn't run ?>
  <p><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<?php // XSS prevention cause user types when registering ?>
	<p>Username: <?= htmlspecialchars($username) ?></p>
	<p>Email: <?= htmlspecialchars($email) ?></p>

<?php // toggle for recall alert emails ?>
<form method="POST" action="myAccount.php">
    <div>
    <input type="checkbox" id="recall_emails" name="recall_emails" value="1" />
    <label for="recall_emails">Email me when a car in my garage gets a new recall</label>
    </div>
    <button type="submit" name="action" value="save">Save</button>
</form>

<p><a href="logout.php">Log out</a></p>
</main>
</body>
</html>
