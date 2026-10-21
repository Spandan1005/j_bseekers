#!/usr/bin/php
<?php

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/nhtsaVehicle.php';

function doModels($request): array {
	try{
	if (!is_array($request)){
	return nhtsa_error('Bad Request');
	}
	return nhtsa_handle($request);
	}
	catch (Throwable $error) {
	error_log('doModels: ' . $error->getmessage());
	return nhtsa_error('Unexpected error occured ');
	}
}

chdir(__DIR__ . '/../../RabbitMQ');
$server = new rabbitMQServer("vehicle.ini", "vehicleModels");
echo "NHTSA Model Listener | UP | Listening to 'Models'..." . PHP_EOL;
$server->process_requests('doModels');
echo "NHTSA Listener | DN | Exiting..." . PHP_EOL;
exit();
?>
