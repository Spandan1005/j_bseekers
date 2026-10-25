#!/usr/bin/php
<?php
// add reviews listener.  listens on reviewWrite
// Andrew Galella, 10.19.2026

// Andrew Galella updated 10.15.2026 so it should actually work
// uses recall and complaint tables for cached data reference.

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/validate.php';


function addReview($request): array {
    $user = trim($request['username']);
    $session_key = $request['session_key'];
    $year = $request['year'];
    $make = $request['make'];
    $model = $request['model'];
    $rating = $request['rating'];
    $review = $request['review'] ?? null;

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
        $db->close();

        return [
            'status' => 'success',
            'message' => 'Successful addition of review'
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

$server = new rabbitMQServer("review.ini","reviewWrite");
echo "Add Review Listener | UP | Listening to 'reviewWrite'..." .PHP_EOL;
$server->process_requests('addReview');
echo "Add Review Listener | DN | Exiting...".PHP_EOL;
exit();
?>
