<?php
// Recall email function which is called by the email listener on DMZ
// Spandan Patel - 10/19/2026 

use PHPMailer\PHPMailer\PHPMailer;

function email_error($message) {
return ['status' => 'error', 'message' => $message];
}

//over here is what builds the initial email subject and plain text body
function email_body($year, $make, $model, $campaign, $component, $summary, $date) {

$subject = "New recall has been issued for your $year $make $model";

$dateText = 'N/A';
if ($date > 0) {
$dateText = date('m/d/Y', $date);
}

$body = "Hi,\n\n";
$body .= "A new recall has been issued for your $year $make $model:\n\n";
$body .= "Campaign: $campaign\n";
$body .= "Component: $component\n";
$body .= "Date: $dateText\n";
$body .= "Summary: $summary\n\n";

$body .= "You can turn these emails off any time from your account page on the website.\n";

return [$subject, $body];
}

function email_handle($request) {

$recipients = $request['recipients'] ?? [];
$year = (int)($request['year'] ?? 0); 
$make = trim($request['make'] ?? '');
$model = trim($request['model'] ?? '');
$campaign = trim($request['campaign'] ?? '');
$component = trim($request['component'] ?? 'N/A');
$summary = trim($request['summary'] ?? 'N/A');
$date = (int)($request['date'] ?? 0);

if (!is_array($recipients) || count($recipients) == 0) {
	return email_error('No recipients');
}

if ($year == 0 || $make == '' || $model == '' || $campaign == ''){
	return email_error('year, make, model and campaign are required');
}
//no line breaks 
$make = preg_replace('/[\r\n]+/', ' ', $make);
$model = preg_replace('/[\r\n]+/', ' ', $model);

[$subject, $body] = email_body($year, $make, $model, $campaign, $component, $summary, $date);

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

$sent = 0;
$failed = 0;

try {
// true makes PHPMailer throw an error on failure so the catch blocks can handle it
$mail = new PHPMailer(true);

// send through gmail's smtp server on port 587 with TLS
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = $user;
$mail->Password = $pass;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

// keeps one connection open while we send to everyone
$mail->SMTPKeepAlive = true;

$mail->setFrom($user, 'j_bseekers');

// plain text email, subject and body were built by email_body()
$mail->isHTML(false);
$mail->Subject = $subject;
$mail->Body = $body;

// one separate email per person so nobody sees anybody else's address
foreach ($recipients as $to) {
$to = trim((string)$to);
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
$failed++;
continue;
}
try {
$mail->clearAddresses();
$mail->addAddress($to);
$mail->send();
$sent++;
} catch (Throwable $error) {
// log the real reason on the DMZ server and keep going with the next person
error_log('email_handle: one email failed: ' . $error->getMessage());
$failed++;
}
}

$mail->smtpClose();
} catch (Throwable $error) {
error_log('email_handle: setup failed: ' . $error->getMessage());
return email_error('Email could not be set up');
}

if ($sent == 0) {
return email_error("No emails were sent ($failed failed)");
}

//reply goes back to whoever sent the recall email request
return ['status' => 'success', 'message' => "Sent $sent email(s), $failed failed"];
}
