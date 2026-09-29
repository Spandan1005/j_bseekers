<?php
session_start();
require __DIR__ . '/../lib/backend.php';

// Kurt Castro | 9.28
// css styling not needed right now

// backend connection
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username == '' || $password == '') {
	    $msg = 'All fields are required.';
    } else {
	    $reply = send_request([
		    'type' => 'login',
		    'username' => $username,
		    'password' => $password,
	    ]);
	    if ($reply['status'] === 'success') {
		    session_regenerate_id(true);
		    $_SESSION['username'] = $username;
		    $_SESSION['session_key'] = $reply['session_key'];
		    header('Location: home.php');
		    exit;
	    }
	    $msg = $reply['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<main>
<h3>Login</h3>
<form onsubmit="return validate(this)" method="POST">
    <div>
	<label for="username">Username</label>
	<input id="username" type="text" name="username" required />
    </div>
    <div>
	<label for="pw">Password</label>
	<input type="password" id="pw" name="password" required minlength="10" />
    </div>
    <input type="submit" value="Login" />
</form>

<?php if ($msg): ?>
  <p><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<script>
function validate (form) {
	let username = form.username.value;
	let password = form.password.value;
	let isValid = true;

	if (!/^[a-zA-Z0-9_-]{5,20}$/.test(username)) {
		alert("Invalid username format");
		isValid = false;
	}
	if (password.length < 10) {
		alert("Password must be at least 10 characters");
		isValid = false;
	}
	return isValid;
}
</script>
</main>
</body>
</html>
