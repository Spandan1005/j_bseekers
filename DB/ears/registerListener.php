#!/usr/bin/php
<?php
// __DIR__ makes sure the file seeks the other files starting from
// its own starting directory.

require_once __DIR__ . '/../../RabbitMQ/path.inc');
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';


function doRegister($request): array {
    $user = trim($request['user']);
    $email = trim($request['email']);
    $pass = trim($request['pass']);

    if ($email === '' || $user === '' || $pass === '') {
        return [
            'status' => 'error',
            'message' => 'All field are required.'
        ];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'status' => 'error',
            'message' => 'Invalid email address.'
        ];
    }
    if (strlen($password) < 10) {
        return [
            'status' => 'error',
            'message' => 'Passwords must be at least 10 characters.'
        ];
    }

    $passhash = hash('sha256', $pass);

    echo "received register request:" . PHP_EOL;
    echo "email: {$email}" . PHP_EOL;
    echo "user: {$user}" . PHP_EOL;
    echo "hash: {$passhash}" . PHP_EOL;

    return [
        'status' => 'success',
        'message' => 'registration success.'
    ];
}

$server = new rabbitMQServer("testRabbitMQ.ini","registerServer");
echo "Register Listener | UP | Listening to 'registerServer'..." .PHP_EOL;
$server->process_requests('doRegister');
echo "Register Listener | DN | Exiting...".PHP_EOL;
exit();
?>

