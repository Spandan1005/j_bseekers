#!/usr/bin/php
<?php
// Evans 11/12/26
require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/mfa.php';

function doMfa($request): array {
	try{
	if (!is_array($request)){
	return mfa_error('Bad Request');
	}
	return mfa_handle($request);
	}
	catch (Throwable $error) {
	error_log('doMfa: ' . $error->getmessage());
	return mfa_error('Unexpected error occured ');
	}
}

chdir(__DIR__ . '/../../RabbitMQ');
$server = new rabbitMQServer("dmz.ini", "dmzMFA");
echo "MFA Listener | UP | Listening to ''..." . PHP_EOL;
$server->process_requests('doMfa');
echo "MFA | DN | Exiting..." . PHP_EOL;
exit();
?>
