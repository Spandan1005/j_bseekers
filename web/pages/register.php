<?php
// Kurt Castro | 9.28
// still need to connect to backend.php
// css styling not needed right now
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

<script>
function validate (form) {
	let email = form.email.value;
	let username = form.username.value;
	let password = form.password.value;
	let confirm = form.confirm.value;
	let isValid = true;

	if (!form.email.checkValidity()) {
		alert("Invalid email format");
		isValid = false;
	}
	if (username.length < 5 || username.length > 20 {
		alert("Username must be 5-20 characters");
		isValid = false;
	}
	if (username.includes(" ")) {
		alert("Username cannot contain spacing");
		isValid = false;
	if (password.length < 10) {
		alert("Password must be at least 10 characters");
		isValid = false;
	}
	if (password !== confirm) {
		alert("Passwords do not match");
		isValid = false;
	}
	return isValid;
}
</script>
</main>
</body>
</html>
