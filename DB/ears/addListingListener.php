#!/usr/bin/php
<?php
// add listings listener.  listens on createListing
// Andrew Galella, 10.22.2026

// Andrew Galella updated 10.15.2026 so it should actually work
// uses recall and complaint tables for cached data reference.

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/validate.php';


function addListing($request): array {
    $user = trim($request['username']);
    $session_key = $request['session_key'];
    $car_id = (int)$request['car_id'];
    $price = (float)$request['price'];
    $mileage = (int)$request['mileage'];
    $color = $request['color'];
    $location = $request['location'];
    $desc = $request['description'] ?? null;


    $validate = doValidate($user, $session_key);
    if ($validate['status'] !== 'success')
    {
        return $validate;
    }
    try {
        $db = getDB();
        $stmt = $db->prepare("select year from cars where username = ? and car_id = ?");
        $stmt->bind_param("si", $user, $car_id);
        $stmt->execute();
        $stmt->bind_result($year);
        $fetched = $stmt->fetch();
        $stmt->close();
        if (!($fetched && isset($year))) {
            return [
                'status' => 'error',
                'message' => 'Car not found in owner\'s garage.'
            ];
        }

        $stmt = $db->prepare("insert into listings (car_id, price, mileage, color, location, description) values (?,?,?,?,?,?)");
        $stmt->bind_param('idisss', $car_id, $price, $mileage, $color, $location, $desc);
        $stmt->execute();
        //$fetched = $stmt->fetch();
        $stmt->close();
        $db->close();

        return [
            'status' => 'success',
            'message' => 'Listing created'
        ];
    }
    catch (Throwable $e) {
        // catchall failure
        error_log('addListing: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error.'
        ];
    }
}


// open and close listener

$server = new rabbitMQServer("listing.ini","createListing");
echo "Add Listing Listener | UP | Listening to 'createListing'..." .PHP_EOL;
$server->process_requests('addListing');
echo "Add Listing Listener | DN | Exiting...".PHP_EOL;
exit();
?>
