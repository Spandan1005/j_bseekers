//This test is for API - Evans

<?php

//sample vehicle for testing (hardcoded)
$make = 'bmw';
$model = '3';
$badge = '320d';

$url = 'https://api.api-ninjas.com/v2/cars? . http_build_query(

$options = [
'http'=> [
'method' => 'GET' 
'header' "X-Api-Key: uqpfsxWpCMKJsXZO8yEPh4kOIxwhBrzOzGgHGXtN"']
["make" => $make,
"model" => $model,
"badge" => $badge

]);
];

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
print_r ($data);

?>

/*
//Display readable results
echo "count: " . ($data['Count'] ?? 'n/a') . "\n";
echo "messages: " . ($data['Message'] ??''). "\n\n";

$first = $data['results'][0] ?? null;
if ($first) {

echo "first recall:\n";
echo "campaign: " . $first['NHTSACampaigneNumber'] . "\n";
echo "component: " . $first['Component'] . "\n";
echo "summary: " . $first['Summary'] . "\n"; }
