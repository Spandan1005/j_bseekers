#!/usr/bin/php
<?php
// autorefresher for cache.  used for cron.
// Andrew Galella, 10.15.2026

require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/refreshComplaints.php';
require_once __DIR__ . '/../lib/refreshRecalls.php';

const CACHE_LOG = __DIR__ . '/../logs/autoRefresh.log';
const ZEES = 2;

function cacheLog(string $message): void {
    file_put_contents(CACHE_LOG, date(DATE_RSS) . ' ' . $message .PHP_EOL, FILE_APPEND | LOCK_EX);
}

file_put_contents(CACHE_LOG, '' . PHP_EOL, FILE_APPEND | LOCK_EX);
cacheLog('##################################### Cache Refresh | START #####################################');

try {
    $db = getDB();

    $result = $db->query("select distinct year, make, model from cars");
    $knownCars = $result->fetch_all(MYSQLI_ASSOC);

    //cacheLog('Distinct Vehicle(s) found: ' . count($knownCars));
    cacheLog(sprintf(
    	"%-4s | %-23s | %-27s | refreshRecall   | refreshComplaint",
	"YEAR", "MAKE", "MODEL"));
    cacheLog('------------------------------------------------------------------------------------------------- (' . count($knownCars) . " distinct vehicle(s) found)");

    $nabbed = 0;

    foreach ($knownCars as $knownCar)
    {
        $year = (int)$knownCar['year'];
        $make = $knownCar['make'];
        $model = $knownCar['model'];

        $recall = refreshRecalls($db, $year, $make, $model);
        $complaint = refreshComplaints($db, $year, $make, $model);

        //cacheLog("$year $make $model | refreshRecall: {$recall['status']} | refreshComplaint: {$complaint['status']}");
	cacheLog(sprintf(
		"%-4s | %-23s | %-27s | %-15s | %-16s",
		$year, $make, $model, $recall['status'], $complaint['status']
	));

        if ($recall['status'] !== 'fresh' || $complaint['status'] !== 'fresh')
        {
            $nabbed++;
            sleep(ZEES);
        }
    }
    $db->close();
    cacheLog('###################################### Cache Refresh | END ######################################');
}
catch (Throwable $e) {
    // catchall failure
    cacheLog('ERROR: ' . $e->getMessage());
    exit(1); //exit with error code.
}
?>
