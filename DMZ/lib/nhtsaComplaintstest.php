<?php

//This test is for API - Evans
function nhtsa_error($message) {
return ['status' => 'error', 'message' => $message];
}

function nhtsa_handle($request) {


$year = (int)($request['year'] ?? 0);
$make = trim($request['make'] ?? '');
$model = trim($request['model'] ?? '');

if ($year == 0 || $make == '' || $model ==''){
return nhtsa_error('year, make and model are required');
}

$url = 'https://api.nhtsa.gov/complaints/complaintsByVehicle?' . http_build_query([
        'modelYear' => $year,
	'make' => $make,
	'model' => $model,
]);

// making the actual requst (outputs as plain text)
$body =@file_get_contents($url);

//Error handling
if ($body ===false){
return nhtsa_error("Request failed.");
}

//converts json to php array
$data = json_decode($body, true);
if (!is_array($data)) {
return nhtsa_error("Got a reply but it wasn't in JSON format."); //the api is supposed to output a JSON format only
}

$filteredResults =[];

//Display expected results

foreach ($data['results'] ?? [] as $complaint) {

$date = DateTime::createFromFormat('!m/d/Y', $complaint['dateComplaintFiled'] ?? '');

$filteredResults[] = [
 "component" =>$complaint['Component'] ?? 'N/A',
 "summary"=>$complaint['summary'] ?? 'N/A', 
 'date' => $date ? $date->getTimestamp() : 0,
];
}
return ['status' => 'success', 'complaints' => $filteredResults];
}

