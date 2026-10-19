<?php
// year/make/model handler for yearsApiListener, makeApiListener and modelsApiListener - Evans

function nhtsa_error($message) {
return ['status' => 'error', 'message' => $message];
}

function nhtsa_handle($request) {

$type = $request['type'] ?? '';
$year = (int)($request['year'] ?? 0);
$make = trim($request['make'] ?? '');

// This is based on what the frontend ask for
if ($type == 'get_years') {
	$list = 'modelYears';
	$field = 'modelYear';
	$query = ['issueType' => 'c'];
} elseif ($type == 'get_makes' && $year != 0) {
	$list = 'makes';
	$field = 'make';
	$query = ['modelYear' => $year, 'issueType' => 'c'];
} elseif ($type == 'get_models' && $year != 0 && $make != '') {
	$list = 'models';
	$field = 'model';
	$query = ['modelYear' => $year, 'make' => $make, 'issueType' => 'c'];
} else {
	return nhtsa_error('Bad Request');
}

$url = 'https://api.nhtsa.gov/products/vehicle/' . $list . '?' . http_build_query($query);

// making the actual requst
$body =@file_get_contents($url);

//Error handling 
if ($body ===false){
return nhtsa_error('Request to NHTSA failed');
}

//converts json to php array
$data = json_decode($body, true);
if (!is_array($data)) {
return nhtsa_error("Got a reply but it wasn't in JSON format");
}

$results = [];

foreach ($data['results'] ?? [] as $row) {
	if (isset($row[$field])) {
		if ($field == 'modelYear' && $row[$field] == '9999') { // fix for year 9999
			continue;
		}
		$results[] = $row[$field];
	}
}

$results = array_values(array_unique($results));

if ($type == 'get_years') {
	rsort($results); // newest year first in the dropdown
	return ['status' => 'success', 'years' => $results];
}
sort($results); // A-Z for makes and models
return ['status' => 'success', $list => $results]; // 'makes' or 'models', what myGarage.php reads
}
