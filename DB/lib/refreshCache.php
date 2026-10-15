<?php

// checks if cache needs to be refreshed, does so if necessary
// cache is based on known cars in the cars table.

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../../RabbitMQ/testRabbitMQ.ini';


// helper to skip cache that's still ok

function isFresh(mysqli $db, string $table, int $year, string $make, string $model): bool {
    $stmt = $db->prepare("select max(cache_date) from ? where year = ? amd make = ? and model = ?");
    $stmt->bind_param('siss', $year, $make, $model);
    $stmt->execute();
    $stmt->bind_result((int)$date);
    $fetched = $stmt->fetch();
    $stmt->close();
    if (empty($date)) {
        return false;
    }
    return (time() - $date) <= 7200;
}


function refreshRecalls():
