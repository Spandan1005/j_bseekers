#!/usr/bin/php
<?php

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/recallEmail.php';

function doRecallEmails($request): array {
	try{
	if (!is_array($request)){
	return email_error('Bad Request');
	}
	return email_handle($request);
	}
	catch (Throwable $error) {
	error_log('doRecallEmails: ' . $error->getmessage());
	return email_error('Unexpected error occured ');
	}
}

chdir(__DIR__ . '/../../RabbitMQ');
$server = new rabbitMQServer("dmz.ini", "dmzRecallEmail");
echo "NHTSA Email Recalls Listener | UP | Listening to 'Email Recalls'..." . PHP_EOL;
$server->process_requests('doRecallEmails');
echo "NHTSA Listener | DN | Exiting..." . PHP_EOL;
exit();
?>
