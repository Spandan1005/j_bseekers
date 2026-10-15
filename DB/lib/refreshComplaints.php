<?php

// Andrew Galella 10.15.2026


// modified version of refreshRecalls

// checks if complaint cache needs to be refreshed, does so if necessary
// cache is based on known cars in the cars table.

require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/fresh.php';
require_once __DIR__ . '/../lib/request.php';


function refreshComplaints(mysqli $db, int $year, string $make, string $model): array {
    // still making edits
    if (isFresh($db, 'complaints', $year, $make, $model))
    {
        return [
            'status' => 'fresh',
            'message' => 'no need to refresh.'
        ];
    }
    $reply = send_request(
        [
            'type' => 'refreshComplaints',
            'year' => $year,
            'make' => $make,
            'model' => $model
        ]

    );

    if ($reply['status'] !== 'success')
    {
        return $reply;
    }

    try {
        $db->begin_transaction(); // need to add this so that the delete-insert is atomic
        // we know there's more data so we're deleting the old data
        $stmt = $db->prepare("delete from complaints where year = ? and make = ? and model = ?");
        $stmt->bind_param('iss', $year, $make, $model);
        $stmt->execute();
        $stmt->close();

        $comp = '';
        $summ = '';
        $date = 0;

        $stmt = $db->prepare("insert into complaints (component, _summary, year, make, model, comp_date) values (?,?,?,?,?,?)");
        $stmt->bind_param('ssissi', $comp, $summ, $year, $make, $model, $date);
        // need to loop thru every result for the reply.
        foreach ($reply['complaints'] as $complaint)
        {
            $comp = $complaint['component'];
            $summ = $complaint['summary'];
            $date = (int)$complaint['date'];
            $stmt->execute();
        }
        $stmt->close();
        $db->commit(); // close transaction
        return [
            'status' => 'refreshed',
            'message' => 'complaint cache refreshed.'
        ];
    }
    catch (Throwable $e) {
        $db->rollback(); // this ensures that the database reverts to normal if the procedure fails.
        // catchall failure
        error_log('refreshComplaints: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Unexpected error occurred.'
        ];
    }

}
