#!/usr/bin/php
<?php
// get acct listener.  listens on accountGet
// Andrew Galella, written 10.19.2026

// __DIR__ makes sure the file seeks the other files starting from
// its own starting directory.

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/validate.php';

function accountGet($request): {
    $user = trim($request['username']);
    $session_key = $request['session_key'];

    $validate = doValidate($user, $session_key);
    if ($validate['status'] !== 'success')
    {
        return $validate;
    }
    try {
        $db = getDB();
        $stmt = $db->prepare("select username, email, opt_in as recall_emails from users where username = ? limit 1");
        $stmt->bind_param('s', $user);
        $stmt->execute();
        $acct = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $db->close();
        return [
            'status' => 'success',
            'account' => $acct
        ];
    }
    catch (Throwable $e) {
        // catchall failure
        error_log('accountGet: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error occurred.'
        ];
    }
}

// open and close listener

$server = new rabbitMQServer("account.ini","accountGet");
echo "Get Account Listener | UP | Listening to 'accountGet'..." .PHP_EOL;
$server->process_requests('accountGet');
echo "Get Account Listener | DN | Exiting...".PHP_EOL;
exit();
?>
