<?php

// checks if cache needs to be refreshed, does so if necessary
// cache is based on known cars in the cars table.

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';


function refreshRecalls(mysqli $db, int $year, string $make, string $model): array {
// still making edits

}
