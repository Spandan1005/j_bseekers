#!/usr/bin/php
<?php
// package submission listener.  listens on packageSubmit
// Andrew Galella, written 11.24.2026


// __DIR__ makes sure the file seeks the other files starting from
// its own starting directory.

require_once __DIR__ . '/../rabbit/path.inc';
require_once __DIR__ . '/../rabbit/get_host_info.inc';
require_once __DIR__ . '/../rabbit/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/database.php';

// ^ file ref
const STORAGE = '/home/ajg42/Downloads/packages';

function checkSubmissions($request): array {
    $version = $request['version'];
    $checksum = $request['checksum'];

    // ^ grab version and checksum from client request
    // make sure fields aren't empty below


    if ($version <= 0 || $checksum === '') {
        return [
            'status' => 'error',
            'message' => 'All field are required.'
        ];
    }
    $file_path = STORAGE . '/v' . $version . '.tar.gz';
    if (!is_file($file_path))
    {
        return [
            'status' => 'error',
            'message' => "package $file_path is not there yet."
        ];
    }
    if (!hash_equals($checksum, hash_file('sha256', $file_path)))
    {
        return [
            'status' => 'error',
            'message' => 'hashes did not match.'
        ];
    }
    // ^^^ check that the file is actually there and matches the checksum we were given


    //echo "made it to try block";
    try {
        // insert the new package as an untested entry in the database.
        $db = getDB();
        $stmt = $db->prepare("insert into packages (version, file_path, checksum) values (?, ?, ?)");
        $stmt = $db->bind_param('iss', $version, $file_path, $checksum);
        $stmt->execute();
        $stmt->close();
        $db->close();

        return [
            'status' => 'success',
            'message' => "new package v$version recorded in table."
        ];

    }
    catch (Throwable $e) {
        if ($e->getCode() === 1062)
        {
            return [
                'status' => 'error',
                'message' => 'version number must be unique.'
            ];
        }
        // catchall failure
        //echo "unexpected error";
        error_log('checkSubmissions: ' . $e->getMessage(), 3, '/home/ajg42/Documents/error.log');
        return [
            'status' => 'error',
            'message' => 'Unexpected error occurred.'
        ];
    }
}

// open and close listener

$server = new rabbitMQServer("deploy.ini","packageSubmit");
echo "package submission Listener | UP | Listening to 'packageSubmit'..." .PHP_EOL;
$server->process_requests('checkSubmissions');
echo "package submission Listener | DN | Exiting...".PHP_EOL;
exit();
?>

