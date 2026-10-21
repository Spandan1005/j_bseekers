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

// save button 
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // only runs after form submission
	$recall_emails = 0; // default off
	if (isset($_POST['recall_emails'])) {
		$recall_emails = 1; // 1 = on 
	}

	$reply = send_request([ // tells DB to turn recall notification on/off
		'type' => 'set_recall_emails',
		'session_key' => $_SESSION['session_key'],
		'username' => $_SESSION['username'],
		'recall_emails' => $recall_emails,
	]);
	$msg = $reply['message'];
}

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
if (isset($reply['account'])) { // 10.21 changed because data is sent in array
	$username = $reply['account']['username'];
	$email = $reply['account']['email'];
	$recall_emails = $reply['account']['recall_emails'];
}

// checks box if recall emails are already on
$checked = '';
if ($recall_emails == 1) { 
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
    <input type="checkbox" id="recall_emails" name="recall_emails" value="1" <?= $checked ?> />
    <label for="recall_emails">Email me when a car in my garage gets a new recall</label>
    </div>
    <button type="submit" name="action" value="save">Save</button>
</form>

<p><a href="logout.php">Log out</a></p>
</main>
</body>
</html>
