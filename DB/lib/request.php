<?php
// 10/15/2026 Andrew Galella - building db side request.php off of Kurt and Spandan's
// backend.php from the frontend.


//All te file paths to directory and the ini file - Spandan Patel 9/29/26
const MQ_DIR = __DIR__ . '/../../RabbitMQ';
//const MQ_INI = 'testRabbitMQ.ini'; //no longer needed as each route is in MQ_ROUTES and it names its own ini file

//defining requests - Spandan Ptel 9/29/26
const MQ_ROUTES = [
'get_years' => ['dmz.ini', 'dmzYears'],
'get_makes' => ['dmz.ini', 'dmzMakes'],
'get_models' => ['dmz.ini', 'dmzModels'],
'refreshRecall' => ['dmz.ini', 'dmzRecalls'],
'refreshComplaint' => ['dmz.ini', 'dmzComplaints']
];

function mq_error($message) {
return ['status' => 'error', 'message' => $message]; } //follows the naming convention from Kurt's stuff

function send_request(array $payload){
	$type = $payload['type'] ?? '';
	if (!isset(MQ_ROUTES[$type])) {
		return mq_error('Unknown request type.');
	}
	[$iniFile, $section] = MQ_ROUTES[$type];

	if (!extension_loaded('amqp')) {
	return mq_error('Server setup problem: php amqp not installed, please install');
	}
	
	$oldDir = getcwd();
	chdir(MQ_DIR);
	try {
		$ini = parse_ini_file($iniFile, true); //referencing the ini file 
		$host = $ini[$section]['BROKER_HOST'] ?? null;
		$port = (int)($ini[$section]['BROKER_PORT'] ?? 5672); //broker IP's default port just an FYI
		if ($host === null) {
		return mq_error("Section [$section] is missing from " . $iniFile); //talks abt a host or a section that could be missing from ini 
		}
		$sock = @fsockopen($host, $port, $errno, $errstr, 3); //refering to trying tcp connection
		if (!$sock) {
		return mq_error('Cant reach the message broker right now'); //errpr message for if broker is unreachable
		}
		fclose($sock); //clsoing the tcp connection

		require_once MQ_DIR . '/rabbitMQLib.inc';
		$client = new rabbitMQClient($iniFile, $section); //the client for this section
		$reply = $client->send_request($payload); 	//request gets sent

		if (!is_array($reply)) {
		return mq_error('Unexpected reply from the server.');
		}
		return $reply;
	} finally {
		chdir($oldDir); //going back to the og folder
	}

}
