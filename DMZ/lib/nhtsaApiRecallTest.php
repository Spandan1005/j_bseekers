<?php
//This test is for API - Evans

function nhtsa_error($message) {
return ['status' => 'error', 'message' => $message];
}

function nhtsa_handle($request) {

$year = (int)($request['year'] ?? 0);
$make = trim($request['make'] ?? '');
$model = trim($request['model'] ?? '');

if ($year == 0 || $make == '' || $model == '') {
return nhtsa_error('Year, make and model are required');
}

$url = 'https://api.nhtsa.gov/recalls/recallsByVehicle?' . http_build_query([
        'modelYear' => $year,
	'make' => $make,
	'model' => $model,
]);

// making the actual requst (outputs as plain text)
$body =@file_get_contents($url);

//Error handling
if ($body ===false){
return nhtsa_error('Request failed');
}

//converts json to php array
$data = json_decode($body, true);
if (!is_array($data)) {
return nhtsa_error("Got a reply but it wasn't in JSON format."); //the api is supposed to output a JSON format only
}

$recalls = [];


foreach ($data['results'] ?? [] as $recall) {

$date = DateTime::createFromFormat('!d/m/Y', $recall['ReportReceivedDate'] ?? '');

$recalls[] = [
 'campaign' => $recall['NHTSACampaignNumber'] ?? '',
 'component' => $recall['Component'] ?? 'N/A',
 'summary' => $recall['Summary'] ?? 'N/A',
 'date' => $date ? $date->getTimestamp() : 0,
];
}
return ['status' => 'success', 'recalls' => $recalls];
}
