<?php
//Evans.  copied and modified from recallEmail.php as it uses the same format

use PHPMailer\PHPMailer\PHPMailer;
function mfa_error($message) {
return ['status' => 'error', 'message' => $message];
}

function mfa_body($username,$otp) {
$subject = "Here's your login code from Jbseekers";
$body= "Hi $username, \n\n";
$body.= "Your jbseeker code is $otp\n\n";
$body.= "Your code is only valid for 5 mins.\n\n";

return [$subject,$body];
}

function  mfa_handle($request){
$username = trim ((string)($request['username'] ??''));
$email =trim((string)($request['email']?? ''));
$otp = trim ((string)($request['otp']??'')); //kept it as string as u asked for Andrew

if ($username == '' ||$email == '' || $otp==''){
return mfa_error('username, email and otp are required man');
}

$env = @parse_ini_file(__DIR__ . '/../emails.env') ?: [];
$user = getenv('MAIL_USER') ?: ($env['MAIL_USER'] ?? '');
$pass = getenv('MAIL_PASS') ?: ($env['MAIL_PASS'] ?? '');
if (!$user || !$pass) {
return mfa_error('Mail login is missing on the DMZ server');
}

[$subject, $body] = mfa_body($username, $otp);


// PHPMailer debuggin purpose only.
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
return mfa_error('PHPMailer is not installed on the DMZ server');
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
$mail->setFrom($user, 'jbseekers'); //check this if theres any issue 
$mail->addAddress($email);
// plain text email, subject and body were built by email_body()
$mail->isHTML(false);
$mail->Subject = $subject;
$mail->Body = $body;
$mail->send();
$sent = 1;

if ($sent ==1) {
return mfa_handle("Email was sent");
}
} catch (Throwable $error) {
// log the real reason on the DMZ server and keep going with the next person
error_log('email_handle: one email failed: ' . $error->getMessage());
return mfa_error("OTP email failed");
}

catch (Throwable $error) {
error_log('email_handle: setup failed: ' . $error->getMessage());
return mfa_error('Email could not be set up');
}

if ($sent == 0) {
return mfa_error("No email was sent");
}

//reply goes back to whoever sent the recall email request
return ['status' => 'success', 'message' => "Sent $sent email(s), $failed failed"];
}

