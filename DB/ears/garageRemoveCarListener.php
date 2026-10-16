#!/usr/bin/php
<?php
// Garage Remove Car listener.  listens on garageRemoveCar
// Andrew Galella, 10.13.2026

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/validate.php';


function removeCar($request): array {
    $user = trim($request['username']);
    $session_key = $request['session_key'];
    $car_id = $request['car_id'];

    $validate = doValidate($user, $session_key);
    if ($validate['status'] !== 'success')
    {
        return $validate;
    }
    try {
        $db = getDB();
        $stmt = $db->prepare("delete from cars where username = ? and car_id = ? limit 1");
        $stmt->bind_param('si', $user, $car_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        $db->close();
        if ($affected) {
            return [
                'status' => 'success',
                'message' => 'Car removed.'
            ];
        }
        return [
            'status' => 'error',
            'message' => 'Car not found.'
        ];
    }
    catch (Throwable $e) {
        // catchall failure
        error_log('removeCar: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error occurred.'
        ];
    }
}

// open and close listener

$server = new rabbitMQServer("garage.ini","garageRemoveCar");
echo "Garage 'Remove Car' Listener | UP | Listening to 'garageRemoveCar'..." .PHP_EOL;
$server->process_requests('removeCar');
echo "Garage 'Remove Car' Listener | DN | Exiting...".PHP_EOL;
exit();
?>
