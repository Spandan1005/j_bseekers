#!/usr/bin/php
<?php
// add reviews listener.  listens on <fix this later>
// Andrew Galella, 10.19.2026

// Andrew Galella updated 10.15.2026 so it should actually work
// uses recall and complaint tables for cached data reference.

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/validate.php';


function addReview(): array {
    $user = trim($request['username']);
    $session_key = $request['session_key'];
    $year = $request['year'];
    $make = $request['make'];
    $model = $request['model'];
    $rating = $request['rating'];
    $review = $request['review'];

    $validate = doValidate($user, $session_key);
    if ($validate['status'] !== 'success')
    {
        return $validate;
    }
    try {
        $db = getDB();
        $stmt = $db->prepare("insert into reviews (username, year, make, model, rating, review) values (?,?,?,?,?,?)");
        $stmt->bind_param('sissis', $user, $year, $make, $model, $rating, $review);
        $stmt->execute();
        //$fetched = $stmt->fetch();
        $stmt->close();


        return [
            'status' => 'success',
            'reviews' => 'Successful addition of review';
        ];
    }
    catch (Throwable $e) {
        // catchall failure
        error_log('addReviews: ' . $e->getMessage());
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
