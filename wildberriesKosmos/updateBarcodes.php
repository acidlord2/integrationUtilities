<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Orders.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Supplies.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');

$logger = new \Classes\Common\Log('wildberriesKosmos - updateBarcode.log');

$ordersWBClass = new \Classes\Wildberries\v1\Orders('Kosmos');
$suppliesWBClass = new \Classes\Wildberries\v1\Supplies('Kosmos');

$filter = 'organization=' . MS_KOSMOS . ';agent=' . MS_WB_AGENT . ';' . MS_ATTR . MS_BARCODE_ATTR_ID . '=;';

$ordersMSClass = new OrdersMS();
$ordersMS = $ordersMSClass->findOrders($filter);
array_splice($ordersMS, 100);
// check if supply exists
$supplyOpen = null;
$supplies = $suppliesWBClass->getSupplies();
foreach ($supplies as $supply)
	if ($supply['closedAt'] == null)
	{
		$supplyOpen = $supply;
		break;
	}


if ($supplyOpen === null)
	$supplyOpen = $suppliesWBClass->createSupply('WB' . date('Y-m-d H:i:s'));

$ordersMSIDs = array_map('intval', array_column($ordersMS, 'externalCode'));
$logger->write(__LINE__ . ' ordersMSIDs ' . json_encode($ordersMSIDs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$stickers = array();
foreach (array_chunk($ordersMSIDs, 100) as $chunk){
	$stickersTmp = $ordersWBClass->getStickers($chunk);
	$stickers = array_merge($stickers, $stickersTmp['stickers']);
}
$stickerIds = array_column ($stickers, 'orderId');

$msOrdersPostData = array();
foreach ($ordersMS as &$orderMS)
{
	$index = array_search((int)$orderMS['externalCode'], $stickerIds);
	if($index !== false) {
		$orderMS["attributes"][] = array(
			'meta' => array (
				'href' => MS_ATTR . MS_BARCODE_ATTR_ID,
				'type' => 'attributemetadata',
				'mediaType' => 'application/json'
			),
			'value' => (string)$stickers[$index]['barcode']
		);
		$orderMS["attributes"][] = array(
			'meta' => array (
				'href' => MS_ATTR . MS_DELIVERYNUMBER_ATTR,
				'type' => 'attributemetadata',
				'mediaType' => 'application/json'
			),
			'value' => $stickers[$index]['partA'] . '-' . $stickers[$index]['partB']
		);
		$orderMS["attributes"][] = array(
			'meta' => array (
				'href' => MS_ATTR . MS_WB_FILE_ATTR,
				'type' => 'attributemetadata',
				'mediaType' => 'application/json'
			),
			'file' => array(
				'filename' => $stickers[$index]['orderId'] . '.png',
				'content' => $stickers[$index]['file']
			)
		);
		$msOrdersPostData[] = $orderMS;
	}
}
if (count ($msOrdersPostData))
	$ordersMSClass->createCustomerorder($msOrdersPostData);
echo 'Updated ' . count ($msOrdersPostData) . ' of ' . count ($ordersMS);
