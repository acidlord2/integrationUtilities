<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
	$result = Db::exec_query_array ("SELECT * FROM prices_list_priceList ORDER BY sort_order ASC");
	
	//echo json_encode ($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	$sortOrder = 10;
	foreach ($result as $row)
	{
		Db::exec_query ('update prices_list_priceList set sort_order = ' . $sortOrder . ' where price_id = ' . $row['price_id']);
		$sortOrder += 10;
	}
?>