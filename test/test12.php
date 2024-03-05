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

$postData = array (
    'template' => array (
        'meta' => array(
            'href' => 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/customtemplate/d9c5a463-b8e6-4fb9-a565-e0e9de92b1f8',
            'type' => 'customtemplate',
            'mediaType' => 'application/json'
        )
    ),
    'extension' => 'pdf'
);


$curl = curl_init('https://api.moysklad.ru/api/remap/1.2/entity/customerorder/903e7cf4-673b-11ee-0a80-07600016c5ba/export');
curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, gzencode(json_encode($postData)));
$jsonOut = curl_exec($curl);
echo $jsonOut;
//$arrayOut = json_decode ($jsonOut, true);
$info = curl_getinfo($curl);
echo json_encode ($info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
curl_close($curl);


?>