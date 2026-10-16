#!/usr/bin/php
<?php

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/nhtsaApiRecallTest.php';


function doRecalls($request): array {
	try{
	if (!is_array($request)){
	return nhtsa_error('Bad Request');
	}
	return nhtsa_handle($request);
	}
	catch (Throwable $error) {
	error_log('doRecalls: ' . $error->getmessage());
	return nhtsa_error('Unexpected error occured ');
	}
}

chdir(__DIR__ . '/../../RabbitMQ');
$server = new rabbitMQServer("dmz.ini", "dmzRecalls");
echo "NHTSA Recall Listener | UP | Listening to 'Recalls'..." . PHP_EOL;
$server->process_requests('doRecalls');
echo "NHTSA Listener | DN | Exiting..." . PHP_EOL;
exit();
?>
