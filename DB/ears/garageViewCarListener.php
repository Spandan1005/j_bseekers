#!/usr/bin/php
<?php
// Garage View Car listener.  listens on garageCar
// Andrew Galella, 10.13.2026

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/validate.php';


function viewCar($request): array {
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
        $stmt = $db->prepare("select car_id, year, make, model from cars where username = ? and car_id = ?");
        $stmt->bind_param('si', $user, $car_id);
        $stmt->execute();
        $stmt->bind_result($);
        $car = $stmt->fetch();
        $stmt->close();
        //  ['status' => 'success',
        // 'car' => ['car_id' => 4, 'year' => 2021, 'make' => 'HONDA', 'model' => 'CR-V'],
        // 'recalls' => [
        //     ['campaign' => '21V123000', 'component' => 'BRAKES', 'summary' => '...', 'date' => 1700000000],
        // ],
        // 'complaints' => [q
        // ['component' => 'ENGINE', 'summary' => '...', 'date' => 1700000000],
        // ]]

        return [
            'status' => 'success',
            'cars' => $myCars
        ];
    }
    catch (Throwable $e) {
        // catchall failure
        error_log('viewCars: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error.'
        ];
    }
}

// open and close listener

$server = new rabbitMQServer("garage.ini","garageCar");
echo "Garage 'View Car' Listener | UP | Listening to 'garageCar'..." .PHP_EOL;
$server->process_requests('viewCar');
echo "Garage 'View Car' Listener | DN | Exiting...".PHP_EOL;
exit();
?>
