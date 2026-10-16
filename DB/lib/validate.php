<?php
require_once __DIR__ . '/../lib/database.php';
// library for session token validation
// Andrew Galella, 10/14/2026

function doValidate($user, $session_key): array {
    if (empty($user) || empty($session_key)) {
        return [
            'status' => 'error',
            'message' => 'All fields are required.'
        ];
    }
    try {
        // pull session_key from table if possible. if it exists, then
        // we have a successful validation. failure otherwise.
        $db = getDB();
        $stmt = $db->prepare("select creation from sessions where username = ? and session_key = SHA2(?,256) and expiration > UNIX_TIMESTAMP() limit 1");
        $stmt->bind_param('ss', $user, $session_key);
        $stmt->execute();
        $stmt->bind_result($creation);
        $fetched = $stmt->fetch();
        $stmt->close();
        $db->close();
        if ($fetched && isset($creation)) {
            return [
                'status' => 'success',
                'message' => 'validation successful.'
            ];
        }
        return [
            'status' => 'invalid',
            'message' => 'Invalid session key.'
        ];
    }
    catch (Throwable $e) {
        // catchall failure
        error_log('doValidate: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error.'
        ];
    }
}
