#!/usr/bin/php
<?php
// Garage get vehicle info listener.  listens on reviewVehicleInfo
// Andrew Galella, 10.21.2026

// Andrew Galella updated 10.15.2026 so it should actually work
// uses recall and complaint tables for cached data reference.

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/validate.php';
require_once __DIR__ . '/../lib/getCachedData.php';


function getVehicleInfo($request): array {
    $user = trim($request['username']);
    $session_key = $request['session_key'];
    $year = $request['year'];
    $make = $request['make'];
    $model = $request['model'];

    $validate = doValidate($user, $session_key);
    if ($validate['status'] !== 'success')
    {
        return $validate;
    }
    try {
        $db = getDB();
        $data = getCachedData($db, $year, $make, $model);
        $db->close();

        return $data;
    }
    catch (Throwable $e) {
        // catchall failure
        error_log('getVehicleInfo: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error.'
        ];
    }
}

// open and close listener

$server = new rabbitMQServer("review.ini","reviewVehicleInfo");
echo "Get Vehicle Info Listener | UP | Listening to 'reviewVehicleInfo'..." .PHP_EOL;
$server->process_requests('getVehicleInfo');
echo "Get Vehicle Info Listener | DN | Exiting...".PHP_EOL;
exit();
?>
