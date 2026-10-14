#!/usr/bin/php
<?php
// Garage Remove Car listener.  listens on garageRemoveCar
// Andrew Galella, 10.13.2026

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';


function removeCar($request): {

}

// open and close listener

$server = new rabbitMQServer("garage.ini","garageRemoveCar");
echo "Garage 'Remove Car' Listener | UP | Listening to 'garageRemoveCar'..." .PHP_EOL;
$server->process_requests('removeCar');
echo "Garage 'Remove Car' Listener | DN | Exiting...".PHP_EOL;
exit();
?>
