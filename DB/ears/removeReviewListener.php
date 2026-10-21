#!/usr/bin/php
<?php
// Remove review listener.  listens on reviewDelete
// Andrew Galella, 10.19.2026

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/validate.php';


function removeReview($request): array {
    $user = trim($request['username']);
    $session_key = $request['session_key'];
    $review_id = $request['review_id'];

    $validate = doValidate($user, $session_key);
    if ($validate['status'] !== 'success')
    {
        return $validate;
    }
    try {
        $db = getDB();
        $stmt = $db->prepare("delete from reviews where username = ? and review_id = ? limit 1");
        $stmt->bind_param('si', $user, $review_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        $db->close();
        if ($affected) {
            return [
                'status' => 'success',
                'message' => 'Review removed.'
            ];
        }
        return [
            'status' => 'error',
            'message' => 'Review not found.'
        ];
    }
    catch (Throwable $e) {
        // catchall failure
        error_log('removeReview: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error occurred.'
        ];
    }
}

// open and close listener

$server = new rabbitMQServer("review.ini","reviewDelete");
echo "Delete review Listener | UP | Listening to 'reviewDelete'..." .PHP_EOL;
$server->process_requests('removeReview');
echo "Delete review Listener | DN | Exiting...".PHP_EOL;
exit();
?>
