#!/usr/bin/php
<?php
// Makes listener.  listens on vehicleMakes
// Andrew Galella, 10.15.2026

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/request.php';

// sending the request blind from the frontend to dmz so there's no function here
// calling send_request from request.php instead.

// open and close listener

$server = new rabbitMQServer("vehicle.ini","vehicleMakes");
echo "Makes Listener | UP | Listening to 'vehicleMakes'..." .PHP_EOL;
$server->process_requests('send_request');
echo "Makes Listener | DN | Exiting...".PHP_EOL;
exit();
?>
