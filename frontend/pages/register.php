<?php
require __DIR__ . '/../lib/backend.php';

// Kurt Castro | 9.28
// css styling not needed right now

// backend connection (also works now because of Spandan finishing backend.php)
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {  // only runs after form submission
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if ($email == '' || $username == '' || $password == '' || $confirm == '') { // server-side checks for empty fields
	    $msg = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { // checks if email format is valid
	    $msg = 'Invalid email address.';
    } elseif (strlen($password) < 10) {
	    $msg = 'Password must be at least 10 characters.';
    } elseif ($password !== $confirm) {
	    $msg = 'Passwords do not match.';
    } else {
	    $reply = send_request([ // sends fields to DB (DB hashes pw) 
		    'type' => 'register',
		    'email' => $email,
		    'username' => $username,
		    'password' => $password,
	    ]);
	    $msg = $reply['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Register</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<main>
<h3>Register</h3>
<form onsubmit="return validate(this)" method="POST">
    <div>
	<label for="email">Email</label>
	<input id="email" type="email" name="email" required />
    </div>
    <div>
	<label for="username">Username</label>
	<input type="text" name="username" required maxlength="20" />
    </div>
    <div>
	<label for="pw">Password</label>
	<input type="password" id="pw" name="password" required minlength="10" />
    </div>
    <div>
	<label for="confirm">Confirm</label>
	<input type="password" name="confirm" required minlength="10" />
    </div>
    <input type="submit" value="Register" />
</form>

<?php if ($msg): // prevent XSS by escaping <,> so HTML can't be ran in msg ?> 
  <p><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<script>
function validate (form) { // client checks before form submission
	let email = form.email.value;
	let username = form.username.value;
	let password = form.password.value;
	let confirm = form.confirm.value;
	let isValid = true;

	if (!form.email.checkValidity()) { // checks for invalid email format
		alert("Invalid email format");
		isValid = false;
	}
	if (!/^[a-zA-Z0-9_-]{5,20}$/.test(username)) { // only alphanumerical, uppercase, lowercase, _, - and min charlength 5, max 20
		alert("Invalid username format");
		isValid = false;
	}
	if (password.length < 10) {
		alert("Password must be at least 10 characters");
		isValid = false;
	}
	if (password !== confirm) { // password validation
		alert("Passwords do not match");
		isValid = false;
	}
	return isValid;
}
</script>
</main>
</body>
</html>
