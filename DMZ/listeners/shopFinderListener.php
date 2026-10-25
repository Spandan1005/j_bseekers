#!/usr/bin/php
<?php
//Evans

require_once __DIR__ . '/../../RabbitMQ/path.inc';
require_once __DIR__ . '/../../RabbitMQ/get_host_info.inc';
require_once __DIR__ . '/../../RabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/../lib/shopFinder.php';

function doShopSearch($request): array {
        try{
        if (!is_array($request)){
        $result = search_error('Bad Request'); //doing results instead of the usual return so we dont rely on MQ for debugging only
        }else {
        $result = search_handle($request);
        }
	}
        catch (Throwable $error) {
        error_log('doShopSearch: ' . $error->getmessage());
        $result = search_error('Unexpected error occured ');
        }
}

chdir(__DIR__ . '/../../RabbitMQ');
$server = new rabbitMQServer("dmz.ini", "dmzShopFinder");
echo "SHOP FINDER Listener | UP | Listening to 'Shop Searches'..." . PHP_EOL;
$server->process_requests('doShopSearch');
echo "SHOP FINDER Listener | DN | Exiting..." . PHP_EOL;
exit();
?>

