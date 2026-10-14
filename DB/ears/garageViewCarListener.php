#!/usr/bin/php
<?php
// Garage View Car listener.  listens on garageCar
// Andrew Galella, 10.13.2026

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';


function viewCar($request): {

}

// open and close listener

$server = new rabbitMQServer("garage.ini","garageCar");
echo "Garage 'View Car' Listener | UP | Listening to 'garageCar'..." .PHP_EOL;
$server->process_requests('viewCar');
echo "Garage 'View Car' Listener | DN | Exiting...".PHP_EOL;
exit();
?>
