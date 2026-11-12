#!/usr/bin/php
<?php
// mfa listener.  listens on mfaServer
// Andrew Galella, written 11.11.2026

// __DIR__ makes sure the file seeks the other files starting from
// its own starting directory.

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';

// ^ file ref

function doMFA($request): array {
    $user = trim($request['username']);
    $otp = $request['otp'];

    // ^ grab user and otp from client request
    // make sure fields aren't empty below


    if ($user === '' || $otp === '') {
        return [
            'status' => 'error',
            'message' => 'All field are required.'
        ];
    }
    //echo "made it to try block";
    try {
        // pull an otp from db assoc with user provided
        // if id is null, there was no record, return fail
        // generate 256 bit session key if success, send as hex
        $db = getDB();
        $stmt = $db->prepare("select id from otps where username = ? and otp = ? and expiration > unix_timestamp() limit 1");
        $stmt->bind_param('ss', $user, $otp);
        $stmt->execute();
        $stmt->bind_result($id);
        $fetched = $stmt->fetch();
        $stmt->close();
        $db->close();
        //echo "$email";
        if ($fetched && isset($id)) {
            $db2 = getDB();
            $db2->begin_transaction();
            $stmt = $db2->prepare("delete from otps where username = ?");
            $stmt->bind_param('s', $user);
            $stmt->execute();
            $stmt->close();
            $stmt = $db2->prepare("update users set last_login = UNIX_TIMESTAMP() where username = ?");
            $stmt->bind_param('s', $user);
            $stmt->execute();
            $stmt->close();
            // create record of session key in the sessions table with a ttl.  this will be used
            // in validation.php library.  Andrew Galella 10/14/2026
            $session_key = bin2hex(random_bytes(32));
            //echo "$session_key";

            $stmt = $db2->prepare("insert into sessions (session_key, username, expiration) values (SHA2(?,256), ?, UNIX_TIMESTAMP()+7200)");
            $stmt->bind_param('ss', $session_key, $user);
            $stmt->execute();
            $stmt->close();
            $db2->commit(); // added code to make delete + update + insert atomic.  otherwise a failed update or insert makes the 2fa code get consumed for nothing
            $db2->close();
            return [
                'status' => 'success',
                'session_key' => $session_key,
                'message' => 'login success.'
            ];
        }
        $db2 = getDB();
        $stmt = $db2->prepare("update otps set attempts = attempts+1 where username = ?");
        $stmt->bind_param('s', $user);
        $stmt->execute();
        $stmt->close();
        $stmt = $db2->prepare("delete from otps where attempts >= 5 and username = ?");
        $stmt->bind_param('s', $user);
        $stmt->execute();
        $stmt->close();
        $db2->close();

        //echo "there was no username like that";
        return [
            'status' => 'error',
            'message' => 'Invalid code.'
        ];
    }
    catch (Throwable $e) {
        // catchall failure
        //echo "unexpected error";
        error_log('doMFA: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error occurred.'
        ];
    }
}

// open and close listener

$server = new rabbitMQServer("testRabbitMQ.ini","mfaServer");
echo "MFA Listener | UP | Listening to 'mfaServer'..." .PHP_EOL;
$server->process_requests('doMFA');
echo "MFA Listener | DN | Exiting...".PHP_EOL;
exit();
?>

