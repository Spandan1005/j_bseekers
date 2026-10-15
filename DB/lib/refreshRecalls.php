<?php

// Andrew Galella 10.15.2026

// checks if cache needs to be refreshed, does so if necessary
// cache is based on known cars in the cars table.

require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/fresh.php';
require_once __DIR__ . '/../lib/request.php';


function refreshRecalls(mysqli $db, int $year, string $make, string $model): array {
    // still making edits
    if (isFresh($db, 'recalls', $year, $make, $model))
    {
        return [
            'status' => 'fresh',
            'message' => 'no need to refresh.'
        ];
    }
    $reply = send_request(
        [
            'type' => 'refreshRecalls',
            'year' => $year,
            'make' => $make,
            'model' => $model
        ]

    );

    if ($reply['status'] !=== 'success')
    {
        return $reply;
    }

    try {
        // we know there's more data so we're deleting the old data
        $stmt = $db->prepare("delete from recalls where year = ? and make = ? and model = ?");
        $stmt->bind_param('iss', $year, $make, $model);
        $stmt->execute();
        $stmt->close();

        $camp = '';
        $comp = '';
        $summ = '';
        $date = 0;

        $stmt = $db->prepare("insert into recalls (campaign, component, _summary, year, make, model, camp_date) values (?,?,?,?,?,?,?)");
        $stmt->bind_param('sssissi', $camp, $comp, $summ, $year, $make, $model, $date);
        // need to loop thru every result for the reply.
        foreach ($reply['recalls'] as $recall)
        {
            $camp = $recall['campaign'];
            $comp = $recall['component'];
            $summary = $recall['summary'];
            $date = (int)$recall['date'];
            $stmt->execute();
        }
        $stmt->close();
        return [
            'status' => 'refreshed',
            'message' => 'recall cache refreshed.'
        ];
    }
    catch (Throwable $e) {
        // catchall failure
        error_log('refreshRecalls: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error occurred.'
        ];
    }

}
