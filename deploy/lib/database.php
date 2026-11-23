<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Common function to establish connection to mysql db
// Andrew Galella, written 09.30.2026

function getDB(): mysqli {
	$creds = require __DIR__ . '/../config/credentials.php';
	$db = new mysqli($creds['host'],$creds['user'],$creds['pass'],$creds['db']);
	return $db;
}
