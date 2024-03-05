<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php');

$token = Settings::getSettingsValues('ms_token');

$header = array (
    'Content-type: application/json',
    'Accept-Encoding: gzip',
    'Authorization: Bearer ' . $token
);

//$logger = new Log('test - test11.log');

$curl = curl_init('https://api.moysklad.ru/api/remap/1.2/entity/product?filter=code=000-0000');
curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$jsonOut = gzdecode(curl_exec($curl));
echo $jsonOut;
//$arrayOut = json_decode ($jsonOut, true);
$info = curl_getinfo($curl);
echo json_encode ($info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
curl_close($curl);


?>