#!/usr/bin/php
<?php

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../../lib/complaintsApi.php';

function doComplaints($request): array {
	try{
	if (!is_array($request));
	return nhtsa_error('Bad Request');
	}
	return nhtsa_handle($request);
	}
	catch (Throwable $error) {
	error_log('doComplaints: ' . $error->getmessage());
	return nhtsa_error('Unexpected error occured ');
	}
}

$server = new rabbitMQServer("dmz.ini", "dmzCompliants");
echo "NHTSA Listener | UP | Listening to 'NHTSA Complaints'..." . PHP_EOL;
$server->process_requests(doComplaints');
echo "NHTSA Listener | DN | Exiting..." . PHP_EOL;
exit();
?>
