<?php
//Evans

function search_error($message){
return ['status' => "error", "message" =>$message];
}
function search_handle ($request) {

$url ='https://places.googleapis.com/v1/places:searchText';
$fields= 'places.id,places.displayName,places.formattedAddress,places.googleMapsUri';
$maxResults = 10; 

//recieves inuts from Kurt then query them to specific google search
$services = [
'repair' => ['query'=>'auto repair', 'type' =>'car_repair'],
'tire' => ['query'=>'tire shop', 'type' =>'tire_shop'],
'body_shop' => ['query'=>'auto body shop', 'type' =>'car_repair'],
'dealer' => ['query'=>'car dealer', 'type' =>'car_dealer'],
'car_wash' => ['query'=>'car wash', 'type' =>'car_wash']
];

$service =trim((string)($request["service"] ??'')); //refrence to line 47
$location =trim((string)($request["location"] ?? ''));
$make =trim((string)($request['make] ?? ''));

//continuing from where i left off
if (!isset($services[$service])) {
return search_error('Unknown service type');
} 
//even tho its been validated but doesnt kurt to do it again.
if (!preg_match('/^\d{5}$/', $location) && !preg_match("/^[A-Za-z0-9 ,.'-]{2,60}$/", $location)){
return search_error("Location must be a 5 digit ZIP or a City and state");
}
if ($make !== '' && !preg_match('/^[A-Za-z0-9 -]{1,30}$/', $make)) {
return search_error('Invalid make');
}


$env = @parse_ini_file(__DIR__ . '/../emails.env') ?: [];
$api = getenv('PLACES_API_KEY') ?: ($env['PLACES_API_KEY'] ?? '');

if (!$api) {
return search_error ('Api is missing');
}

//kurts redifined function
$info = $services[$service];
$query = $info['query'];

if ($make !== '') {
        if ($service === 'dealer') {
                $query = "$make dealer";
        } elseif ($service !== 'car_wash') {
                $query = "$make " . $info['query'];
        }
}
$query .= " near $location";

$body = [
'textQuery' => $query,'includedType' => $info['type'],'pageSize' => $maxResults,
];

$context = stream_context_create(['http' => [
'method' => 'POST',
'header' => "Content-Type: application/json\r\n" . "X-Goog-Api-Key: $api\r\n" . "X-Goog-FieldMask: " . $fields . "\r\n",
'content' => json_encode($body),'ignore_errors' => true, ]
]);

//making the actual request
$req= @file_get_contents($url, false, $context); // @ignores warnings,

if ($req== false){
return search_error ('couldnt reach the shop');
}
$data= json_decode($req, true);

if (!is_array($data)) {
return search_error ("Got a reply but it wasn't in JSON format.\n"); 
}


//(formatting the outputting to frontend(Kurt)
$shops =[];

foreach ($data['places'] ?? [] as $place) {
$shops[
'name'=> (string)($places['displayName']['text'] ?? ''),
'address'=> (string)($place['formattedAddress'] ?? ''),
'maps_url'=> (string)($plase['googleMapsUri'] ?? ''),
];
}
return ['status' => 'success', 'shops' => $shops];
}
