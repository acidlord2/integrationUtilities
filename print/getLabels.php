<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ordersOzon.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/reportsMS.php');
	$logger = new Log ('print - getLabels.log');
	$postingNumbers = file_get_contents('php://input');
	$logger -> write (__LINE__ . ' postingNumbers - ' . $postingNumbers);
	$org = $_REQUEST["org"];
	$agent = $_REQUEST["agent"];
	$count = $_REQUEST["count"];
	
	//$postData = array ('posting_number' => json_decode ($postingNumbers, true));
	if ($org == 'aruba') {
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Yandex/ordersYandex.php');
	    $orderClass = new OrdersYandex(BERU_API_ARUBA_CAMPAIGN);
	    echo $orderClass->getOrdersLabels(json_decode ($postingNumbers, true), $count);
	}
	elseif ($org == 'Ullo') {
		echo OrdersOzon::getOrderLabel (json_decode ($postingNumbers, true), $count, false);
	}
	else if ($agent == 'WB') {
		$report = ReportsMS::findReportByName ('customerorder', 'Товарный чек WB');
		$files = array();
		$arrContextOptions = array(
			"ssl"=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			),
		);
		
		foreach (json_decode ($postingNumbers, true) as $id)
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
	}
	else
	{
		echo OrdersOzon::getOrderLabel (json_decode ($postingNumbers, true), $count, true);
	}
	
?>

