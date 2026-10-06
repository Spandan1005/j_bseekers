#!/usr/bin/php
<?php
// login listener.  listens on loginServer
// Andrew Galella, written 09.30.2026

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
            'session_key' => 'NULL',
            'message' => 'All field are required.'
        ];
    }
    try {
        // pull an email address from db assoc with user and pass provided
        // if email is null, there was no record, return fail
        // generate 256 bit session key if success, send as hex
        $db = getDB();
        $stmt = $db->prepare("select email from users where username = ? and password = SHA2(?,256) limit 1");
        $stmt->bind_param('ss', $user, $pass);
        $stmt->execute();
        $stmt->bind_result($email);
        $fetched = $stmt->fetch();
        $stmt->close();
        $db->close();
        if ($fetched && isset($email)) {
            $db2 = getDB();
            $stmt = $db2->prepare("update users set last_login = CURRENT_TIMESTAMP where username = ?");
            $stmt->bind_param('s', $user);
            $stmt->execute();
            $stmt->close();
            $db2->close();
            return [
                'status' => 'success',
                'session_key' => bin2hex(random_bytes(32)),
                'message' => 'login success.'
            ];
        }
        return [
            'status' => 'error',
            'session_key' => 'NULL',
            'message' => 'Invalid username or password.'
        ];
    }
    catch (Throwable $e) {
        // catchall failure
        error_log('doLogin: ' . $e->getMessage());
        return [
            'status' => 'error',
            'session_key' => 'NULL',
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

