#!/usr/bin/php
<?php
// __DIR__ makes sure the file seeks the other files starting from
// its own starting directory.

require_once __DIR__ . '/../../RabbitMQ/path.inc');
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';


function doLogin($request): array {

}

$server = new rabbitMQServer("testRabbitMQ.ini","loginServer");
echo "Login Listener | UP | Listening to 'loginServer'..." .PHP_EOL;
$server->process_requests('doLogin');
echo "Login Listener | DN | Exiting...".PHP_EOL;
exit();
?>

