#!/usr/bin/php
<?php
// Remove listing listener.  listens on <replace>
// Andrew Galella, 10.22.2026

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/validate.php';


function removeListing($request): array {
    $user = trim($request['username']);
    $session_key = $request['session_key'];
    $review_id = (int)$request['car_id'];

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
        if !($fetched && isset($year)) {
            return [
                'status' => 'error',
                'message' => 'Car not found in owner\'s garage.'
            ];
        }

        $stmt = $db->prepare("delete from listings where car_id = ? limit 1");
        $stmt->bind_param('i', $car_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        $db->close();
        if ($affected) {
            return [
                'status' => 'success',
                'message' => 'Listing removed.'
            ];
        }
        return [
            'status' => 'error',
            'message' => 'Listing not found.'
        ];
    }
    catch (Throwable $e) {
        // catchall failure
        error_log('removeListing: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error occurred.'
        ];
    }
}

// open and close listener

$server = new rabbitMQServer("listings.ini","<replace>");
echo "Delete listing Listener | UP | Listening to '<replace>'..." .PHP_EOL;
$server->process_requests('removeListing');
echo "Delete listing Listener | DN | Exiting...".PHP_EOL;
exit();
?>
