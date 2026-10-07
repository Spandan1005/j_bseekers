<?php
require __DIR__ . '/../lib/backend.php';
$reply = send_request([
	'type' => 'recalls',
	'make' => 'acura',
	'model'=> 'rdx',
	'modelYear'=> 2012,
]);

header('Content-Type: text/plain');
echo $reply['status'] . ' | ' . $reply['message'] . PHP_EOL;
echo 'count: ' . ($reply['count'] ?? 'n/a') . PHP_EOL;
