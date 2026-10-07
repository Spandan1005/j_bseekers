//This test is for API - Evans

<?php
require __DIR__ . '/../pages/home.php';

//sample vehicle for testing (hardcoded)
$make = 'acura';
$model = 'rdx';
$year = 2012;

$url = 'https://api.nhtsa.gov/recalls/recallsByVehicle?' . http_build_query([
	'make' => $make,
	'model' => $model,
	'modelYear' => $year,
]);

// making the actual requst (outputs as plain text)
$body =@file_get_contents($url);
header ('Content-Type: text/plain');

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
echo "messages: " . ($data['Message'] ??''). "\n\n";

$first = $data['results'][0] ?? null;
if ($first) {
echo "first recall:\n";
echo "campaign: " . $first['NHTSACampaigneNumber'] . "\n";
echo "component: " . $first['Component'] . "\n";
echo "summary: " . $first['Summary'] . "\n"; }
