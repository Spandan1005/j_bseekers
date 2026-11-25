<?php
// Andrew Galella 11/24/2026
// this is how the deployment machine decides where everything goes.

function maxVersion(mysqli $db, string $status, ?int $below = null): ?int {
    if ($below === null)
    {
        $stmt = $db->prepare("select max(version) from packages where status = ?");
        $stmt->bind_param('s', $status);
    }
    else {
        $stmt = $db->prepare("select max(version) from packages where status = ? and version < ?");
        $stmt->bind_param('si', $status, $below);
    }
    $stmt->execute();
    $stmt->bind_result($version);
    $fetched = $stmt->fetch();
    $stmt->close();
    if ($fetched && isset($version))
    {
        return $version;
    }
    return null;
}



function getStatus(mysqli $db, int $version): ?string {
    $stmt = $db->prepare("select status from packages where version = ? limit 1");
    $stmt->bind_param('i', $version);
    $stmt->execute();
    $stmt->bind_result($status);
    $fetched = $stmt->fetch();
    $stmt->close();

    if ($fetched && isset($status))
    {
        return $status;
    }
    return null;
}


function decide(mysqli $db, string $env, int $version): array {
    if ($version > 0)
    {
        $current = getStatus($db, $version);
    }
    else {
        $current = null;
    }

    if ($current === 'bad')
    {
        $target = maxVersion($db, 'safe', $version);
        if ($target === null)
        {
            return [
                'action' => 'hold',
                'message' => "v$version is bad and there's no earlier safe version."
            ];
        }
        return [
            'action' => 'rollback',
            'version' => $target,
            'message' => "v$version is bad, rolling back to v$target"
        ];
    }


    if ($env === 'prod')
    {
        $target = maxVersion($db, 'safe');
        if ($target !== null && $target > $version)
        {
            return [
                'action' => 'update',
                'version' => $target,
                'message' => "deploying v$target to prod"
            ];
        }
        return [
            'action' => 'none',
            'message' => "prod is already on the newest safe version v$version"
        ];
    }


    if ($env === 'qa')
    {
        if ($current === 'new')
        {
            return [
                'action' => 'none',
                'message' => "qa is already on an untested version v$version"
            ];
        }
        $target = maxVersion($db, 'new');
        if ($target !== null && $target > $version)
        {
            return [
                'action' => 'update',
                'version' => $target,
                'message' => "deploying v$target to qa"
            ];
        }
        return [
            'action' => 'none',
            'message' => "there are no newer untested versions to push to qa"
        ];
    }
    return [
        'action' => 'none',
        'message' => "no change needed"
    ];
}
