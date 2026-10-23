#!/usr/bin/php
<?php
// update listings listener.  listens on updateListing
// Andrew Galella, 10.22.2026

// Andrew Galella updated 10.15.2026 so it should actually work
// uses recall and complaint tables for cached data reference.

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/validate.php';


function updateListing($request): array {
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

        $stmt = $db->prepare("update listings set price = ?, mileage = ?, color = ?, location = ?, description = ?, updated = unix_timestamp() where car_id = ?");
        $stmt->bind_param('disssi', $price, $mileage, $color, $location, $desc, $car_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        //$fetched = $stmt->fetch();
        $stmt->close();
        $db->close();
        if ($affected) {
            return [
                'status' => 'success',
                'message' => 'Listing updated'
            ];
        }
        return [
            'status' => 'error',
            'message' => 'Listing not found.'
        ];

    }
    catch (Throwable $e) {
        // catchall failure
        error_log('updateListing: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error.'
        ];
    }
}


// open and close listener

$server = new rabbitMQServer("listing.ini","updateListing");
echo "Update Listing Listener | UP | Listening to 'updateListing'..." .PHP_EOL;
$server->process_requests('updateListing');
echo "Update Listing Listener | DN | Exiting...".PHP_EOL;
exit();
?>
