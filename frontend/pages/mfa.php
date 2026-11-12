<?php
session_start();
require __DIR__ . '/../lib/backend.php';

// Kurt Castro | 11.12
// Copied from home.php + trimmed
// for MFA after successful login

if (empty($_SESSION['mfa_user'])) { // redirect if login unsuccessful
	header('Location: login.php');
	exit; // stops rest of the page from sending so home HTML is not visible on login
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verify Code</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>
<main>
  <h3>Enter your OTP</h3>
  <?php // links to main pages, only shown after login ?>
  <p>An email containing a 6-digit code has been sent to the email associated with your account.</p>
  <p>The code will expire after 5 minutes.</p>
  <form method="POST">
    <div>
    <label for="otp">Code</label>
    <input type="text" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required />
    </div>
    <input type="submit" value="Verify" />
  </form>
  <p><a href="login.php">Back to login</a></p>
  
  <?php if ($msg): // for XSS prevention
    <p><?= htmlspecialchars($msg) ?></p>
  <?php endif; ?>

</main>
</body>
</html>
