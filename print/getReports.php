<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/reportsMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('print - getReports.log');
	$ids = file_get_contents('php://input');
	$logger -> write ('ids - ' . $ids);
	$org = $_REQUEST["org"];
	$count = $_REQUEST["count"];
	$agent = $_REQUEST["agent"];
	
	//$postData = array ('posting_number' => json_decode ($postingNumbers, true));
	$idsArray = json_decode ($ids, true);
	if (count ($idsArray) == 0) {
		return;
	}
	if ($agent == 'Ozon') {
		$report = ReportsMS::findReportByName ('customerorder', 'Товарный чек OZON');
	}
	elseif ($agent == 'Beru' && $org == '4cleaning') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Товарный чек BERU ROMASHKA');
	}
	elseif ($agent == 'Beru' && $org == 'Ullo') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Товарный чек BERU ULLO');
	}
	elseif ($agent == 'Beru' && $org == 'aruba') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Экспресс (ТАКСИ)');
	}
	elseif ($agent == 'Beru' && $org == 'alians') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Товарный чек BERU ALLIANS');
	}
	elseif ($agent == 'Beru' && $org == 'vysota') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Товарный чек BERU VISOTA');
	}
	elseif ($agent == 'Beru' && $org == 'Kosmos') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Товарный чек BERU KOSMOS');
	}
	elseif ($agent == 'Beru' && $org == 'Summit') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Товарный чек BERU SUMMIT');
	}
	elseif ($agent == 'Goods' && $org == 'Kaori') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Товарный чек SBERMEGAMARKET');
	}
	elseif ($agent == 'Goods' && $org == 'Ullo') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Товарный чек ULLOSBERMM');
	}
	elseif ($agent == 'Goods' && $org == 'AST1') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Товарный чек ARUBASBERMM');
	}
	elseif ($agent == 'Goods' && $org == 'AST2') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Товарный чек LOTUSSBERMM');
	}
	elseif ($agent == 'Goods' && $org == 'AST3') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Товарный чек COMETASBERMM');
	}
	elseif ($agent == 'Goods' && $org == 'AST4') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Товарный чек APPOLONSBERMM');
	}
	elseif ($agent == 'Goods' && $org == 'AST5') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Товарный чек PLUTONSBERMM');
	}
	elseif ($agent == 'Goods' && $org == 'AST6') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Товарный чек VISOTASBERMM');
	}
	elseif ($agent == 'WB' && $org == 'Kosmos') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Товарный чек WB');
	}
	elseif ($agent == 'WB' && $org == 'Ullo') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Товарный чек WB ULLO');
	}
	elseif ($agent == 'SM' && $org == 'Ullo') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Товарный чек WB ULLO');
	}
	elseif ($agent == 'Ali') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Товарный чек ALI-AVITO-WB');
	}
	elseif ($agent == 'Curiers') {
	    $report = ReportsMS::findReportByName ('customerorder', 'Товарный чек MP DOSTAVKA');
	}
	$files = array();
	$arrContextOptions = array(
	    "ssl"=>array(
	        "verify_peer"=>false,
	        "verify_peer_name"=>false,
	    ),
	);
	
	foreach ($idsArray as $id)
	{
		$url = ReportsMS::printReport ($id, 'customerorder', $report['meta']);
		$pdf = file_get_contents ($url, false, stream_context_create($arrContextOptions));
		file_put_contents('files/' . $id . ".pdf", $pdf);
	}	
	
	$cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=files/printData.pdf ";
	//Add each pdf file to the end of the command
	foreach($idsArray as $id) {
		$cmd .= 'files/' . $id . '.pdf' . ' ';
	}
	$result = shell_exec($cmd);

	foreach($idsArray as $id)
		unlink('files/' . $id . '.pdf');
	
	echo "files/printData.pdf";
	

?>

