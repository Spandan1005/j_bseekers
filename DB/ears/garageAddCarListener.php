#!/usr/bin/php
<?php
// Garage Add Car listener.  listens on garageAddCar
// Andrew Galella, 10.13.2026

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/validate.php';
require_once __DIR__ . '/../lib/refreshComplaints.php';
require_once __DIR__ . '/../lib/refreshRecalls.php';


function addCar($request): array {
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

    if (empty($year) || empty($make) || empty($model)) {
        return [
            'status' => 'error',
            'message' => 'All field are required.'
        ];
    }
    try {
        $db = getDB();
        $stmt = $db->prepare("select car_id from cars where year = ? and make = ? and model = ? limit 1");
        $stmt->bind_param('iss', $year, $make, $model);
        $stmt->execute();
        $stmt->bind_result($id);
        $fetched = $stmt->fetch();
        $stmt->close();

        $stmt = $db->prepare("insert into cars (username, year, make, model) values (?,?,?,?)");
        $stmt->bind_param('siss', $user, $year, $make, $model);
        $stmt->execute();
        $stmt->close();

        if ($fetched && isset($id))
        {
            refreshRecalls($db, $year, $make, $model);
            refreshComplaints($db, $year, $make, $model);
        }

        // ^ checks if the earlier select statement found this car to exist already
        // if it does, leave it for the cron.  Andrew Galella 10.15.2026


        $db->close();
        return [
            'status' => 'success',
            'message' => 'Car added.'
        ];
    }
    catch (Throwable $e) {
        // catchall failure
        error_log('addCar: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error.'
        ];
    }
}

// open and close listener

$server = new rabbitMQServer("garage.ini","garageAddCar");
echo "Garage 'Add Car' Listener | UP | Listening to 'garageAddCar'..." .PHP_EOL;
$server->process_requests('addCar');
echo "Garage 'Add Car' Listener | DN | Exiting...".PHP_EOL;
exit();
?>
