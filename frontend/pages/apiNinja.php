// Ninja API test

<?php
header ('Content-Type: text/plain');
//sample vehicle for testing (hardcoded)
$make = 'bmw';
$model = '3 series';

$url = 'https://api.api-ninjas.com/v1/cars?' . http_build_query([
        'make' => $make,
        'model' => $model,
]);

$options = [
'http'=> [
'method' => 'GET', 
'header'=> "X-Api-Key: uqpfsxWpCMKJsXZO8yEPh4kOIxwhBrzOzGgHGXtN\r\n"
]
];

$context = stream_context_create($options);
$body = file_get_contents($url,false, $context);

//Error handling
if ($body ===false){
echo "Request failed. \n";
exit;
}
echo $http_response_header[0] . "\n\n";

//converts json to php array
$data = json_decode($body, true);
if (!is_array($data)) {
echo "Got a reply but it wasn't in JSON format.\n"; //the api is supposed>
exit;
}

print_r ($data);



?>
