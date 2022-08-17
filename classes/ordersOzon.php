<?php
/**
 *
 * @class OrdersOzon
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class OrdersOzon
{
	private static $logFilename = 'ordersOzon.log';
	
	public static function getOrder ($parameters, $kaori = false)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiOzon.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		$logger = new Log(self::$logFilename);
		$logger->write("getOrder.parameters - " . json_encode ($parameters, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

		$service_url = OZON_MAINURL . 'v3/posting/fbs/get';
		$logger->write("getOrder.service_url - " . json_encode ($service_url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$order = ApiOzon::postOzonData ($service_url, $parameters, $kaori);
		$logger->write("getOrder.order - " . json_encode ($order, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $order;
	}

	public static function orderList ($since, $to, $status, $kaori = false)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiOzon.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log(self::$logFilename);

		$postData = array ('dir' => 'ASC',
							'filter' => array ('since' => $since, 'to' => $to, 'status' => $status),
							'limit' => 50,
							'offset' => 0,
							'with' => array ('barcodes' => true));
		$orders = array();
		while (true)
		{	
			$service_url = OZON_MAINURL . 'v3/posting/fbs/list';
			$logger->write("orderList.service_url - " . json_encode ($service_url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$logger->write("orderList.postData - " . json_encode ($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$order_list = ApiOzon::postOzonData ($service_url, $postData, $kaori);
			$logger->write("orderList.order_list - " . json_encode ($order_list, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			if (count ($order_list['result']['postings']) == 0){
			    break;
			}
		  
			$orders = array_merge($orders, $order_list['result']['postings']);
			$postData['offset'] += 50;
		}

		return $orders;
	}

	public static function getOrderLabel ($postingNumbers, $count, $kaori = false)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiOzon.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log(self::$logFilename);

		$logger->write("orderLabel.postingNumbers - " . json_encode ($postingNumbers), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$postingNumberTmp = array();
		$files = array();
		foreach ($postingNumbers as $key => $postingNumber)
		{
			array_push ($postingNumberTmp, $postingNumber);
			$logger->write("orderLabel.key - " . $key);
			$logger->write("orderLabel.key + 1 / 20 - " . (($key + 1) % 20));
			
			if (($key == count($postingNumbers) - 1) || (($key + 1) % 20 === 0))
			{
				$postData = array ('posting_number' => $postingNumberTmp);
				$service_url = OZON_MAINURL . OZON_API_V2 . OZON_API_PACKAGE_LABEL;
				$logger->write("orderLabel.service_url - " . $service_url);
				$logger->write("orderLabel.postData - " . json_encode ($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$pdf = ApiOzon::postOzonDataBlob ($service_url, $postData, $kaori);
				$logger->write("orderLabel.pdf - " . $pdf);
				file_put_contents("files/labelsData" . $key . ".pdf", $pdf);
				array_push ($files, "files/labelsData" . $key . ".pdf");
				$postingNumberTmp = array();
			}
		}
			
		$cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=files/labelsData.pdf ";
		//Add each pdf file to the end of the command
		foreach($files as $file) {
			$cmd .= $file . " ";
		}
		$result = shell_exec($cmd);

		foreach ($files as $file)
			unlink($file);
		
		return "files/labelsData.pdf";
	}

}

?>