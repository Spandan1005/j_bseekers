#!/usr/bin/php
<?php
// Garage View Car listener.  listens on garageCar
// Andrew Galella, 10.13.2026

// Andrew Galella updated 10.15.2026 so it should actually work
// uses recall and complaint tables for cached data reference.

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
        $stmt = $db->prepare("select car_id, year, make, model from cars where username = ? and car_id = ? limit 1");
        $stmt->bind_param('si', $user, $car_id);
        $stmt->execute();
        $car = get_result()->fetch_assoc(); // need this cause otherwise you can't grab the whole row
        //$fetched = $stmt->fetch();
        $stmt->close();
        //  ['status' => 'success',
        // 'car' => ['car_id' => 4, 'year' => 2021, 'make' => 'HONDA', 'model' => 'CR-V'],
        // 'recalls' => [
        //     ['campaign' => '21V123000', 'component' => 'BRAKES', 'summary' => '...', 'date' => 1700000000],
        // ],
        // 'complaints' => [q
        // ['component' => 'ENGINE', 'summary' => '...', 'date' => 1700000000],
        // ]]


        // ^ this is the expected frontend response structure for this op according to Kurt
        if ($car === null)) {
            return [
                'status' => 'error',
                'message' => 'car not found.'
            ];
        }

        $car['car_id'] = (int)$car['car_id'];
        $car['year'] = (int)$car['year'];
        $year = $car['year'];
        $make = $car['make'];
        $model = $car['model'];

        // was going to have the cache refresh check happen here, but due to performance concerns
        // we're just going to rely on the cron.


        // grab recalls

        $stmt = $db->prepare("select campaign, component, _summary as summary, camp_date as date from recalls where year = ? and make = ? and model = ? order by camp_date desc");
        $stmt->bind_param('iss', $year, $make, $model);
        $stmt->execute();
        $result = $stmt->get_result();
        $recalls = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($recalls as &$recall)
        {
            $recall['date'] = (int)$recall['date'];

        }


        // grab complaints

        $stmt = $db->prepare("select component, _summary as summary, comp_date as date from complaints where year = ? and make = ? and model = ? order by comp_date desc");
        $stmt->bind_param('iss', $year, $make, $model);
        $stmt->execute();
        $result = $stmt->get_result();
        $complaints = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->close();
        foreach ($complaints as &$complaint)
        {
            $complaint['date'] = (int)$complaint['date'];

        }


        return [
            'status' => 'success',
            'car' => $car,
            'recalls' => $recalls,
            'complaints' => $complaints
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
