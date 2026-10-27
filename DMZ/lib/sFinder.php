<?php
//Evans

function search_error($message){
return ['status' => "error", "message" =>$message];

function search_handle ($request) {

$url ='https://places.googleapis.com/v1/places:searchText';
$fields= 'places.id,places.displayName,places.formattedAddress,places.googleMapsUri';
$maxResults = 10; 

//recieves inuts from Kurt then query them to specific google search
services = [
'repair' => ['query'=>'auto repair', 'type' =>'car_repair'],
'tire' => ['query'=>'tire shop', 'type' =>'tire_shop'],
'body_shop' => ['query'=>'auto body shop', 'type' =>'car_repair'],
'dealer' => ['query'=>'car dealer', 'type' =>'car_dealer'],
'car_wash' => ['query'=>'car wash', 'type' =>'car_wash']
]

$service =trim((string)($request["service"] ??'')):
$location =trim((string)($request["location"] ?? ''));
$make =trim(string)($request['make] ?? ''));


















$env = @parse_ini_file(__DIR__ . '/../emails.env') ?: [];
$api = getenv('PLACES_API_KEY') ?: ($env['PLACES_API_KEY'] ?? '');


