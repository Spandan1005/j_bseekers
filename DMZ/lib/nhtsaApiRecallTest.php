//This test is for API - Evans

<?php
require_once __DIR__ . '/../libs/fresh.php';
require_once __DIR__ . '/../listeners/apiListener.php';

$url = 'https://api.nhtsa.gov/recalls/recallsByVehicle?' . http_build_query([
        'type' => $refreshrecalls,
        'year' => $year
	'make' => $make,
	'model' => $model,
]);

// making the actual requst (outputs as plain text)
$body =file_get_contents($url);

//Error handling
if ($body ===false){
echo "Request failed. \n";
exit;
}

//converts json to php array
$data = json_decode($body, true);
if (!is_array($data)) {
echo "Got a reply but it wasn't in JSON format.\n"; //the api is supposed to output a JSON format only
exit;
}

//Display readable results
echo "count: " . ($data['Count'] ?? 'n/a') . "\n";


if ($first) {
echo "first recall:\n";
echo "campaign: " . $first['NHTSACampaignNumber'] . "\n";
echo "component: " . $first['Component'] . "\n";
echo "summary: " . $first['Summary'] . "\n"; }
echo "date: " . $first['date'] . "\n"; }


