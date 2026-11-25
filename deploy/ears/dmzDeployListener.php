#!/usr/bin/php
<?php
// package deploymen listener.  listens on dmzDeploy
// Andrew Galella, written 11.24.2026


// __DIR__ makes sure the file seeks the other files starting from
// its own starting directory.

require_once __DIR__ . '/../rabbit/path.inc';
require_once __DIR__ . '/../rabbit/get_host_info.inc';
require_once __DIR__ . '/../rabbit/rabbitMQLib.inc';

// ^ file ref
const REPO = '/home/evans-mensah/J_bseekers/j_bseekers';
const V_FILE = REPO . '/VERSION';
const STORAGE = '/home/evans-mensah/Downloads';
const INSTALL = __DIR__ . '/../bin/install.sh';

// ^^ EVANS you need to change these for the dmz.


function getVersion(): int {
    if (!is_file(V_FILE))
    {
        return 0;
    }
    return (int)trim(file_get_contents(V_FILE));
}

function deploy($request): array {
    $version = (int)($request['version'] ?? 0);
    $checksum = $request['checksum'] ?? '';
    if ($version <= 0 || $checksum === '') {
        return [
            'status' => 'success',
            'version' => getVersion()
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

    $cmd = escapeshellarg(INSTALL) . ' ' . escapeshellarg($file_path) . ' ' . escapeshellarg(REPO) . ' 2>&1';
    exec($cmd, $out, $exitcode);
    if ($exitcode !== 0)
    {
        error_log('deploy error for v' . $version . ': ' . implode("\n", $out));
        return [
            'status' => 'error',
            'message' => "application of v$version failed, exit=$exitcode"
        ];
    }
    file_put_contents(V_FILE, (string)$version);
    return [
        'status' => 'success',
        'version' => $version,
        'message' => "application of v$version successful"
    ];
}



// open and close listener

$server = new rabbitMQServer("deploy.ini","dmzDeploy");
echo "package deployment Listener | UP | Listening to 'dmzDeploy'..." .PHP_EOL;
$server->process_requests('deploy');
echo "package deployment Listener | DN | Exiting...".PHP_EOL;
exit();
?>

