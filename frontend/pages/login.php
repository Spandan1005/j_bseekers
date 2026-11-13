<?php
session_start();
require __DIR__ . '/../lib/backend.php';

// Kurt Castro | 9.28
// css styling not needed right now

// backend connection (functional now because of Spandan finishing backend.php)
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // only runs when form is submitted
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username == '' || $password == '') { // empty fields not allowed
	    $msg = 'All fields are required.';
    } else {
	    $reply = send_request([ // sends login request to DB
		    'type' => 'login',
		    'username' => $username,
		    'password' => $password,
	    ]);
	    if ($reply['status'] === 'success') { // 11.12: DB should email code, no login yet
		    // session_regenerate_id(true); // creates new session ID after login 11.12
		    // $_SESSION['username'] = $username; 11.12 
		    // $_SESSION['session_key'] = $reply['session_key']; 11.12
		    $_SESSION['mfa_user'] = $username; // user who's trying to login, not logged in yet
		    header('Location: mfa.php');
		    exit;
	    }
	    $msg = 'Invalid username or password.'; // generic error message for user
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark"> 
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
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
<p>Don't have an account? <a href="register.php">Register</a></p>

<?php if ($msg): // prevent XSS by escaping <,> so HTML can't be ran in msg ?>
  <p><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<script>
function validate (form) { // client checks prior to form submission
	let username = form.username.value;
	let password = form.password.value;
	let isValid = true;

	if (!/^[a-zA-Z0-9_-]{5,20}$/.test(username)) { // lowercase, uppercase, numbers, _, - only with min charlength of 5 and max 20
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
