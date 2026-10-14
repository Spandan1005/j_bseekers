#!/usr/bin/php
<?php
// logout listener.  listens on logoutServer
// Andrew Galella, 10.13.2026

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/validate.php';

function doLogout($request): array {
    $user = trim($request['username']);
    $session_key = $request['session_key'];

    if ($user === '' || $pass === '') {
        return [
            'status' => 'error',
            'message' => 'missing fields.'
        ];
    }
    try {
        $db = getDB();
        $stmt = $db->prepare("delete from sessions where username = ? and session_key = SHA2(?,256) limit 1");
        $stmt->bind_param('ss', $user, $session_key);
        $stmt->execute();
        $stmt->close();
        $db->close();
        return [
            'status' => 'success',
            'message' => 'logout success.'
        ];
    }
    catch (Throwable $e) {
        // catchall failure
        error_log('doLogout: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error occurred.'
        ];
    }
}

// open and close listener

$server = new rabbitMQServer("testRabbitMQ.ini","logoutListener");
echo "Logout Listener | UP | Listening to 'logoutServer'..." .PHP_EOL;
$server->process_requests('doLogout');
echo "Logout Listener | DN | Exiting...".PHP_EOL;
exit();
?>
