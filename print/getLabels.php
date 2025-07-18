<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ordersOzon.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/reportsMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');

	$logger = new Log ('print - getLabels.log');
	$postingNumbers = file_get_contents('php://input');
	$logger->write (__LINE__ . ' postingNumbers - ' . $postingNumbers);
	$org = $_REQUEST["org"];
	$agent = $_REQUEST["agent"];
	$count = $_REQUEST["count"];
	
	//$postData = array ('posting_number' => json_decode ($postingNumbers, true));
	if ($org == 'aruba') {
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Yandex/ordersYandex.php');
	    $orderClass = new OrdersYandex(BERU_API_ARUBA_CAMPAIGN);
	    echo $orderClass->getOrdersLabels(json_decode ($postingNumbers, true), $count);
	}
	elseif ($agent == 'Ozon' and $org == 'Ullo') {
		echo OrdersOzon::getOrderLabel (json_decode ($postingNumbers, true), $count, false);
	}
	elseif ($agent == 'WB') {
		$report = ReportsMS::findReportByName ('customerorder', 'Стикер WB');
		$files = array();
		$arrContextOptions = array(
			"ssl"=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			),
		);
		$idsArray = json_decode ($postingNumbers, true);
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
	}
	elseif ($agent == 'SM')
	{
		$orderMSClass = new OrdersMS();
		$idsArray = json_decode ($postingNumbers, true);
		$files = array();
		foreach ($idsArray as $id)
		{
			$order = $orderMSClass->getOrderById($id);
			$attribute = $orderMSClass->getAttribute($order, MS_WB_FILE_ATTR);
			$fileContent = $orderMSClass->getAttributeFileContent($attribute);
			if ($fileContent === false)
			{
				$logger->write (__LINE__ . ' ' . __FUNCTION__ . ' error getting file content for order id - ' . $id);
				continue;
			}
			$file = 'files/' . $id . '.pdf';
			file_put_contents($file, $fileContent);
			$files[] = $file;
		}
		$cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=files/printData.pdf ";
		foreach($files as $file) {
			$cmd .= $file . ' ';
		}
		$result = shell_exec($cmd);
		foreach($files as $file) {
			unlink($file);
		}
		echo "files/printData.pdf";
	}
	else
	{
		echo OrdersOzon::getOrderLabel (json_decode ($postingNumbers, true), $count, true);
	}
	
?>

