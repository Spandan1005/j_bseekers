#!/usr/bin/php
<?php
// login listener.  listens on loginServer
// Andrew Galella, written 09.30.2026

// updated 11/11 Andrew Galella - MFA.  Now THIS initiates login

// __DIR__ makes sure the file seeks the other files starting from
// its own starting directory.

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';

// ^ file ref

function doLogin($request): array {
    $user = trim($request['username']);
    $pass = $request['password'];

    // ^ grab user and pass from client request
    // make sure fields aren't empty below


    if ($user === '' || $pass === '') {
        return [
            'status' => 'error',
            'message' => 'All field are required.'
        ];
    }
    //echo "made it to try block";
    try {
        // pull an email address from db assoc with user and pass provided
        // if email is null, there was no record, return fail
        // generate otp and save in table if success, send to Evans to email out
        $db = getDB();
        $stmt = $db->prepare("select email from users where username = ? and password = SHA2(?,256) limit 1");
        $stmt->bind_param('ss', $user, $pass);
        $stmt->execute();
        $stmt->bind_result($email);
        $fetched = $stmt->fetch();
        $stmt->close();
        $db->close();
        //echo "$email";
        if ($fetched && isset($email)) {
            $db2 = getDB();
            $otp = sprintf('%06d',random_int(0,999999));
            $stmt = $db2->prepare("insert into otps (username, otp, expiration) values (?,?,unix_timestamp()+300)");
            $stmt->bind_param('ss', $user, $otp);
            $stmt->execute();
            $stmt->close();
            //send otp to Evans
            send_req_oneway(
                [
                    'type' => 'dmzMFA',
                    'username' => $user,
                    'email' => $email,
                    'otp' => $otp
                ]);
            return [
                'status' => 'success',
                'message' => 'OTP successfully sent.'
            ];
        }
        //echo "there was no username like that";
        return [
            'status' => 'error',
            'message' => 'Invalid username or password.'
        ];
    }
    catch (Throwable $e) {
        // catchall failure
        //echo "unexpected error";
        error_log('doLogin: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error occurred.'
        ];
    }
}

// open and close listener

$server = new rabbitMQServer("testRabbitMQ.ini","loginServer");
echo "Login Listener | UP | Listening to 'loginServer'..." .PHP_EOL;
$server->process_requests('doLogin');
echo "Login Listener | DN | Exiting...".PHP_EOL;
exit();
?>

