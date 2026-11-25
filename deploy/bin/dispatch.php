#!/usr/bin/php
<?php

require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/decisions.php';
$clusters = require __DIR__ . '/../config/clusters.php';

const RABBIT = __DIR__ . '/../rabbit';


function lookup(mysqli $db, int $version): ?array {
    $stmt = $db->prepare("select file_path, checksum from packages where version = ? limit 1");
    $stmt->bind_param('i', $version);
    $stmt->execute();
    $stmt->bind_result($file_path, $checksum);
    $fetched = $stmt->fetch();
    $stmt->close();
    if (fetched && isset($file_path) && isset($checksum))
    {
        return [
            'file_path' => $file_path,
            'checksum' => $checksum
        ];
    }
    return null;
}
