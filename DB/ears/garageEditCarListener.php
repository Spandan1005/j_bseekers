#!/usr/bin/php
<?php
// Garage Edit Car listener.  listens on garageEditCar
// Andrew Galella, 10.13.2026

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';


function editCar($request): {

}

// open and close listener

$server = new rabbitMQServer("garage.ini","garageEditCar");
echo "Garage 'Edit Car' Listener | UP | Listening to 'garageEditCar'..." .PHP_EOL;
$server->process_requests('editCar');
echo "Garage 'Edit Car' Listener | DN | Exiting...".PHP_EOL;
exit();
?>
