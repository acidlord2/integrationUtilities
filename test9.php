<?php
//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Db.php');

//$dbClass = new \classes\Common\Db();
//$a = $dbClass->truncate('report_sales');

$array = array(array('a' => 'a', 'b' => 'b', 'c' => 'c'), array ('a' => 'aa', 'b' => 'vb', 'c' => 'cc'));

$new = array_filter($array, function($v,$k){
    $k[
},ARRAY_FILTER_USE_BOTH);
var_dump($new);

//echo json_encode(array_slice($files, 1), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>