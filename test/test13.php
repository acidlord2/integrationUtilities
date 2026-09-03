<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/docker-config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php');
$log = new Log('test13.log');

// was a hardcoded Basic credential - read the token from settings like every other client
$curl_post_headerms = array (
    'Content-type: application/json',
    'Accept-Encoding: gzip',
    'Authorization: Bearer ' . Settings::getSettingsValues('ms_token')
);


$curl = curl_init('https://api.moysklad.ru/api/remap/1.2/entity/customerorder/000047d4-bb1b-11ea-0a80-06780000620b');
curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerms);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$jsonOut = curl_exec($curl);

$log->write('zipped content: ' . $jsonOut);

if(gzdecode($jsonOut)) {
    $jsonOut = gzdecode($jsonOut);
}
$log->write('unzipped content: ' . $jsonOut);

$arrayOut = json_decode ($jsonOut, true);
$info = curl_getinfo($curl);
curl_close($curl);
    
?>