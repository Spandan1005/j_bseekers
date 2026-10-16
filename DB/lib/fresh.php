<?php

// checks if cache needs to be refreshed,
//
// I split the helper from the cache refresh functions for
// readability sake - Andrew Galella 10.15.2026

require_once __DIR__ . '/../lib/database.php';


// helper to skip cache that's still ok

function isFresh(mysqli $db, string $table, int $year, string $make, string $model): bool {
    $stmt = $db->prepare("select max(cache_date) from $table where year = ? and make = ? and model = ?");
    $stmt->bind_param('iss', $year, $make, $model);
    $stmt->execute();
    $stmt->bind_result($date);
    $fetched = $stmt->fetch();
    $stmt->close();
    if ($fetched && $date === null) {
        return false;
    }
    return (time() - (int)$date) <= 7200;
}
