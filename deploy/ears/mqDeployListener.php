#!/usr/bin/php
<?php
// package deploymen listener.  listens on packageDeploy
// Andrew Galella, written 11.24.2026


// __DIR__ makes sure the file seeks the other files starting from
// its own starting directory.

require_once __DIR__ . '/../rabbit/path.inc';
require_once __DIR__ . '/../rabbit/get_host_info.inc';
require_once __DIR__ . '/../rabbit/rabbitMQLib.inc';

// ^ file ref
const REPO = '/home/snp85/j_bseekers';
const V_FILE = REPO . '/VERSION';
const STORAGE = '/home/snp85/Downloads';
const INSTALL = __DIR__ . '/../bin/install.sh';
const CLUSTER = __DIR__ . '/../config/cluster.php';

// ^^ SPANDAN check to make sure these are right for rabbit.


function getVersion(): int {
    if (!is_file(V_FILE))
    {
        return 0;
    }
    return (int)trim(file_get_contents(V_FILE));
}


function applyToSelf(int $version, string $file_path): array {
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
        'status' => 'success'
    ];
}

function applyToSiblings(int $version, string $checksum, string $file_path): array {
    if (!is_file(CLUSTER))
    {
        'status' => 'error',
        'message' => "cluster file not present"
    }
    $config = require CLUSTER;
    $broker = $config['broker'];
    $failures = array();

    foreach ($config['siblings'] as $sibling)
    {
        $dest = $sibling['scp_user'] . '@' . $sibling['scp_host'] . ':' . $sibling['storage'] . '/';
        $cmd = 'scp ' . escapeshellarg($file_path) . ' ' . escapeshellarg($dest);
        exec($cmd . ' 2>&1', $out, $exit);
        if ($exit !== 0)
        {
            $failures[] = $sibling['section'] . ' (scp fail)';
            continue;
        }
        try {
            $client = new rabbitMQClient('deploy.ini', $sibling['section'], $broker);
            $reply = $client->send_request([
                'version' => $version,
                'checksum' => $checksum
            ]);
            if (!is_array($reply) || ($reply['status'] ?? '') !== 'success')
            {
                $failures[] = $sibling['section'] . ' ('. ($reply['message'] ?? 'bad reply') . ')';
            }

        }
        catch (Throwable $e){
            $failures[] = $sibling['section'] . ' (' . $e->getMessage() . ')'
        }

    }
    if (empty($failures))
    {
        return [
            'status' => 'success',
            'message' => 'update distribiuted'
        ];
    }
    return [
        'status' => 'error',
        'message' => 'distribution failures: ' . implode(', ', $failures)
    ];
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

    $local = applyToSelf($version, $file_path);
    if ($local['status'] !== 'success')
    {
        return $local;
    }


    $distrib = applyToSiblings($version, $checksum, $file_path);
    if ($distrib['status'] !== 'success')
    {
        return [
            'status' => 'error',
            'version' => '$version',
            'message' => "hub updated to v$version, but " . $distrib['message']
        ];
    }

    return [
        'status' => 'success',
        'version' => $version,
        'message' => "application of v$version successful"
    ];
}



// open and close listener

$server = new rabbitMQServer("deploy.ini","packageDeploy");
echo "package deployment Listener | UP | Listening to 'packageDeploy'..." .PHP_EOL;
$server->process_requests('deploy');
echo "package deployment Listener | DN | Exiting...".PHP_EOL;
exit();
?>

