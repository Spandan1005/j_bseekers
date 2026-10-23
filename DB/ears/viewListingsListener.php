#!/usr/bin/php
<?php
// view listing listener.  listens on readListing
// Andrew Galella, 10.22.2026

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/validate.php';


function viewListings($request): array {
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
        $stmt = $db->prepare("select cars.car_id, cars.username, email,
                             year, make, model,
                             price, mileage, color, location, description
                             from cars join listings on cars.car_id = listings.car_id join users on cars.username = users.username
                             where year = ? and make = ? and model = ?");
        $stmt->bind_param('iss', $year, $make, $model);
        $stmt->execute();
        $result = $stmt->get_result();
        $listings = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->close();
        return [
            'status' => 'success',
            'listings' => $listings
        ];
    }
    catch (Throwable $e) {
        // catchall failure
        error_log('viewListings: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error.'
        ];
    }
}

// open and close listener

$server = new rabbitMQServer("listing.ini","readListing");
echo "Listing Listener | UP | Listening to 'readListing'..." .PHP_EOL;
$server->process_requests('viewListings');
echo "Listing Listener | DN | Exiting...".PHP_EOL;
exit();
?>
