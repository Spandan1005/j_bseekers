#!/usr/bin/php
<?php
// Garage Add Car listener.  listens on garageAddCar
// Andrew Galella, 10.13.2026

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';


function addCar($request): {

}

// open and close listener

$server = new rabbitMQServer("garage.ini","garageAddCar");
echo "Garage 'Add Car' Listener | UP | Listening to 'garageAddCar'..." .PHP_EOL;
$server->process_requests('addCar');
echo "Garage 'Add Car' Listener | DN | Exiting...".PHP_EOL;
exit();
?>
