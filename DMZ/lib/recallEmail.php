<?php
// Recall email function which is called by the email listener on DMZ
// Spandan Patel - 10/19/2026 

use PHPMailer\PHPMailer\PHPMailer;

function email_error ($messages) {
return ['status' => 'error', 'message' => $message];
}

//over here is what builds the initial email subject and plain text body
function email_body($username, $year, $make, $model, $campaigns) {

$subject = "New recall has been issued for your $year $make $model";

$body = "Hello $username, \n\n";
$body = "A new recall has been issued for your $year $make $model:\n\n";

foreach ($campaigns as $c) {
$date = 'N/A';
if (!empty($c['date'])) {
$date = date('m/d/Y', (int)$c['date']);
} 

$body .= "Campaign: " . ($c['campaign'] ?? 'N/A') . "\n";
$body .= "Component: " . ($c['component'] ?? 'N/A') . "\n";
$body .= "Date: " . $date . "\n";
$body .= "Summary: " . ($c['summary'] ?? 'N/A') . "\n\n";
}

$body .= "You can turn these emails off any time from your account page on the website.\n";

return [$subject, $body];
}

function email_handle($request) {

$to = trim($request['to'] ?? '');
$username = trim($request['username'] ?? '');
$year = (int)($request['year'] ?? 0); 
$make = trim($request['make'] ?? '');
$model = trim($request['model'] ?? '');
$campaigns = $request['new_campaigns'] ?? [];

if ($to == '' || $username == '' || $year == 0 || $make == '' || $model == '') {
return email_error('to, username, year, make and model are required');
}

if (!filter_var($t0, FILTER_VALIDATE_EMAIL)){
return email_error('Invalid email address');
}
if (!is_array($campaigns) || count($campaigns) == 0) {
return email_error('No new campaigns to send');
}
//no line breaks 
$make = preg_replace('/[\r\n]+/', ' ', $make);
$model = preg_replace('/[\r\n]+/', ' ', $model);

[$subject, $body] = email_body($username, $year, $make, $model, $campaigns);

//adding in mailing stuff here - SP 

// gmail login comes from the DMZ server's environment (same way apiNinja gets its key), never from the code
$user = getenv('MAIL_USER');
$pass = getenv('MAIL_PASS');
if (!$user || !$pass) {
return email_error('Mail login is missing on the DMZ server');
}

// PHPMailer is installed with composer in DMZ/vendor, so check it is there before using it
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
return email_error('PHPMailer is not installed on the DMZ server');
}
require_once $autoload;

try {
// true makes PHPMailer throw an error on failure so the catch below can handle it
$mail = new PHPMailer(true);

// send through gmail's smtp server on port 587 with TLS
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = $user;
$mail->Password = $pass;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

// who it is from and who it goes to
$mail->setFrom($user, 'j_bseekers');
$mail->addAddress($to, $username);

// plain text email, subject and body were built by email_body()
$mail->isHTML(false);
$mail->Subject = $subject;
$mail->Body = $body;

$mail->send();
} catch (Throwable $error) {
// log the real reason on the DMZ server, but only tell the caller a short message
error_log('email_handle: ' . $error->getMessage());
return email_error('Email could not be sent');
}

// reply goes back to whoever sent the recall_email request
return ['status' => 'success', 'message' => 'Email sent'];
}
