#!/usr/bin/php
<?php
// Years listener.  listens on vehicleYears
// Andrew Galella, 10.15.2026

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/request.php';

// sending the request blind from the frontend to dmz so there's no function here
// calling send_request from request.php instead.

// open and close listener

$server = new rabbitMQServer("vehicle.ini","vehicleYears");
echo "Years Listener | UP | Listening to 'vehicleYears'..." .PHP_EOL;
$server->process_requests('send_request');
echo "Years Listener | DN | Exiting...".PHP_EOL;
exit();
?>
