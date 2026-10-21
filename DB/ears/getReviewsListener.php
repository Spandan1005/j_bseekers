#!/usr/bin/php
<?php
// view reviews listener.  listens on reviewGet
// Andrew Galella, 10.19.2026

// Andrew Galella updated 10.15.2026 so it should actually work
// uses recall and complaint tables for cached data reference.

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/validate.php';


function getReviews(): array {
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
        $stmt = $db->prepare("select review_id, username, rating, avg(rating) as avg_rating, review, creation as date from reviews where year = ? and make = ? and model = ?");
        $stmt->bind_param('iss', $year, $make, $model);
        $stmt->execute();
        $result = $stmt->get_result();
        $reviews = $result->fetch_all(MYSQLI_ASSOC);
        //$fetched = $stmt->fetch();
        $stmt->close();
        foreach ($reviews as &$review)
        {
            $review['date'] = (int)$review['date'];

        }


        return [
            'status' => 'success',
            'reviews' => $reviews
        ];
    }
    catch (Throwable $e) {
        // catchall failure
        error_log('getReviews: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error.'
        ];
    }
}


// open and close listener

$server = new rabbitMQServer("review.ini","reviewGet");
echo "Get review Listener | UP | Listening to 'reviewGet'..." .PHP_EOL;
$server->process_requests('getReviews');
echo "Get review Listener | DN | Exiting...".PHP_EOL;
exit();
?>
