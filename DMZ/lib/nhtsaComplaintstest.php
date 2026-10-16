
<?php
//This test is for API - Evans

function nhtsa_handle($request) {

$$refreshComplaints = $request['type'] ?? '';
$$year = $request['year'] ?? '';
$$make = $request['make'] ?? '';
$$model = $request['model'] ?? '';

$url = 'https://api.nhtsa.gov/complaints/complaintsByVehicle?' . http_build_query([
        'type' => $refreshComplaints,
        'year' => $year,
	'make' => $make,
	'model' => $model,
]);

// making the actual requst (outputs as plain text)
$body =@file_get_contents($url);

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

$filteredResults =[];

//Display expected results

foreach ($data['results'] as $complaint) {
$filteredResults[] = [
 "component: " =>$complaint['Component'] ?? 'N/A',
 "summary: "=>$complaint['summary'] ?? 'N/A', 
 "date: " =>$complaint['date'] ?? 'N/A',
];
}
return $filteredResults;
}

