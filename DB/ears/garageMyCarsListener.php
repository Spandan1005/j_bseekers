#!/usr/bin/php
<?php
// Garage My Cars listener.  listens on garageMyCars
// Andrew Galella, 10.13.2026

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/validate.php';


function listMyCars($request): array {

}

// open and close listener

$server = new rabbitMQServer("garage.ini","garageMyCars");
echo "Garage 'My Cars' Listener | UP | Listening to 'garageMyCars'..." .PHP_EOL;
$server->process_requests('listMyCars');
echo "Garage 'My Cars' Listener | DN | Exiting...".PHP_EOL;
exit();
?>
