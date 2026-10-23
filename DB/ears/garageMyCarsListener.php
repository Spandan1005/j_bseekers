#!/usr/bin/php
<?php
// Garage My Cars listener.  listens on garageMyCars
// Andrew Galella, 10.13.2026

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/validate.php';


function listMyCars($request): array {
    $user = trim($request['username']);
    $session_key = $request['session_key'];

    $validate = doValidate($user, $session_key);
    if ($validate['status'] !== 'success')
    {
        return $validate;
    }
    try {
        $db = getDB();
        $stmt = $db->prepare("select cars.car_id, year, make, model, price, mileage, color, location, description from cars left join listings on cars.car_id = listings.car_id where username = ?");
        $stmt->bind_param('s', $user);
        $stmt->execute();
        $result = $stmt->get_result();
        $myCars = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->close();
        return [
            'status' => 'success',
            'cars' => $myCars
        ];
    }
    catch (Throwable $e) {
        // catchall failure
        error_log('listMyCars: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error.'
        ];
    }
}

// open and close listener

$server = new rabbitMQServer("garage.ini","garageMyCars");
echo "Garage 'My Cars' Listener | UP | Listening to 'garageMyCars'..." .PHP_EOL;
$server->process_requests('listMyCars');
echo "Garage 'My Cars' Listener | DN | Exiting...".PHP_EOL;
exit();
?>
