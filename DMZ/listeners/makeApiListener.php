#!/usr/bin/php
<?php

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';

function doMakes($request): array {
	try{
	if (!is_array($request));{
	return nhtsa_error('Bad Request');
	}
	return nhtsa_handle($request);
	}
	catch (Throwable $error) {
	error_log('doMakes: ' . $error->getmessage());
	return nhtsa_error('Unexpected error occured ');
	}
}

$server = new rabbitMQServer("dmz.ini", "dmzMakes");
echo "NHTSA Makes Listener | UP | Listening to 'Makes'..." . PHP_EOL;
$server->process_requests('doMakes');
echo "NHTSA Listener | DN | Exiting..." . PHP_EOL;
exit();
?>
