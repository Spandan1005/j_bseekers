#!/usr/bin/php
<?php
// __DIR__ makes sure the file seeks the other files starting from
// its own starting directory.

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';


function doRegister($request): array {
    $user = trim($request['username']);
    $email = trim($request['email']);
    $pass = trim($request['password']);

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
    if (strlen($pass) < 10) {
        return [
            'status' => 'error',
            'message' => 'Passwords must be at least 10 characters.'
        ];
    }

    $passhash = hash('sha256', $pass);

    try {
        $db = getDB();
        $stmt = $db->prepare("insert into users (username, email, password) values (?,?,?)");
        $stmt->bind_param('sss', $user, $email, $passhash);
        $stmt->execute();
        $stmt->close();
        $db->close();

        return [
            'status' => 'success',
            'message' => 'registration success.'
        ];
    }
    catch (mysqli_sql_exception $e) {
        if ($e->getCode === 1062) {
            return [
                'status' => 'error',
                'message' => 'Username or email must be unique.'
            ];
        }
        error_log('doLogin: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error occurred.'
        ];
    }
}

$server = new rabbitMQServer("testRabbitMQ.ini","registerServer");
echo "Register Listener | UP | Listening to 'registerServer'..." .PHP_EOL;
$server->process_requests('doRegister');
echo "Register Listener | DN | Exiting...".PHP_EOL;
exit();
?>

