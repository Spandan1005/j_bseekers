#!/usr/bin/php
<?php
// set acct opt in listener.  listens on accountSetRecallEmails
// Andrew Galella, written 10.19.2026

// __DIR__ makes sure the file seeks the other files starting from
// its own starting directory.

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/validate.php';

function setAcctOpt($request): array {
    $user = trim($request['username']);
    $session_key = $request['session_key'];
    $recall_email = $request['recall_emails'];

    $validate = doValidate($user, $session_key);
    if ($validate['status'] !== 'success')
    {
        return $validate;
    }
    try {
        $db = getDB();
        $stmt = $db->prepare("update users set opt_in = ? where username = ? limit 1");
        $stmt->bind_param('is', $recall_email, $user);
        $stmt->execute();
        $stmt->close();
        $db->close();
        return [
            'status' => 'success',
            'message' => 'Settings saved.'
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

$server = new rabbitMQServer("account.ini","accountSetRecallEmails");
echo "Set Account Recall Email Listener | UP | Listening to 'accountSetRecallEmails'..." .PHP_EOL;
$server->process_requests('setAcctOpt');
echo "Set Account Recall Email Listener | DN | Exiting...".PHP_EOL;
exit();
?>
