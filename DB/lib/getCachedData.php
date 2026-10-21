<?php

// gets whatevers in cache related to y/m/m,
//
// I split the logic for grabbing cached data into a library
// so we can reuse the code. - Andrew Galella 10.21.2026

require_once __DIR__ . '/../lib/database.php';

function getCachedData(mysqli $db, int $year, string $make, string $model): array {
    try {
        $stmt = $db->prepare("select campaign, component, _summary as summary, camp_date as date from recalls where year = ? and make = ? and model = ? order by camp_date desc");
        $stmt->bind_param('iss', $year, $make, $model);
        $stmt->execute();
        $result = $stmt->get_result();
        $recalls = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($recalls as &$recall)
        {
            $recall['date'] = (int)$recall['date'];

        }


        // grab complaints

        $stmt = $db->prepare("select component, _summary as summary, comp_date as date from complaints where year = ? and make = ? and model = ? order by comp_date desc");
        $stmt->bind_param('iss', $year, $make, $model);
        $stmt->execute();
        $result = $stmt->get_result();
        $complaints = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($complaints as &$complaint)
        {
            $complaint['date'] = (int)$complaint['date'];

        }
        return [
            'status' => 'success',
            'recalls' => $recalls,
            'complaints' => $complaints
        ];
    }
    catch (Throwable $e) {
        // catchall failure
        error_log('getCachedData: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error.'
        ];
    }
}
