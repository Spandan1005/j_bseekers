#!/usr/bin/php
<?php
// __DIR__ makes sure the file seeks the other files starting from
// its own starting directory.

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';


function doLogin($request): array {
    $user = trim($request['username']);
    $pass = $request['password'];

    if ($user === '' || $pass === '') {
        return [
            'status' => 'error',
            'session_key' => 'NULL',
            'message' => 'All field are required.'
        ];
    }
    try {

        $db = getDB();
        $query = $db->prepare("select email from users where username = ? and password = SHA2(?,256) limit 1");
        $stmt->bind_param('ss', $user, $pass);
        $stmt->execute();
        $stmt->bind_result($email);
        $fetched = $stmt->fetch();
        $stmt->close();
        $db->close();
        if ($fetched && isset($email)) {
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
        error_log('doLogin: ' . $e->getMessage());
        return [
            'status' => 'error',
            'session_key' => 'NULL',
            'message' => 'Unexpected error occurred.'
        ];
    }
}

$server = new rabbitMQServer("testRabbitMQ.ini","loginServer");
echo "Login Listener | UP | Listening to 'loginServer'..." .PHP_EOL;
$server->process_requests('doLogin');
echo "Login Listener | DN | Exiting...".PHP_EOL;
exit();
?>

