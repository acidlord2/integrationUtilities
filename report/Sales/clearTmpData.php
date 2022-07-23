<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
$table = 'report_sales';
$log = new \Classes\Common\Log('reports - Sales - clearTmpData.log');

$dbClass = new \Classes\Common\Db();
$dbClass->truncate($table);
?>

