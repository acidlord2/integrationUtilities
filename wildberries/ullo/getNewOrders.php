<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/docker-config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Orders.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Supplies.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/wildberries/order.php');

$logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
$logName .= '.log';
$log = new \Classes\Common\Log($logName);

$startDate = date('Y-m-d', strtotime('-2 days')) . 'T00:00:00.000+03:00';
$endDate = NULL;
$status = 0;

$ordersWBClass = new \Classes\Wildberries\v1\Orders('Ullo');
$suppliesWBClass = new \Classes\Wildberries\v1\Supplies('Ullo');

$newOrders = $ordersWBClass->getNewOrders();

if (!count($newOrders))
{
	echo 'Processed 0 orders';
	return;
}

$ordersIDs = array_column ($newOrders, 'id');
$filter = '';
foreach ($ordersIDs as $ordersID){
	$filter .= 'name=WB' . $ordersID . ';';
}

$ordersMSClass = new OrdersMS();
$ordersMS = $ordersMSClass->findOrders($filter);
$ordersMSIDs = array_column ($ordersMS, 'name');

$productMSClass = new ProductsMS();
$productMS0 = $productMSClass->findProductsByCode('000-0000');

$newOrdersMS = array();
$changeStatus = array();

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

foreach ($newOrders as &$newOrder)
{
	if (array_search('WB' . $newOrder['id'], $ordersMSIDs) !== false)
	{
		$log->write ('Already loaded - ' . $newOrder['id']);
		continue;
	}
		
	$positions = array();

	$productMS = $productMSClass->findProductsByCode($newOrder['article']);
	$productMS = isset($productMS[0]) ? $productMS : $productMS0;
	$newOrder['productMS'] = $productMS;

	$orderTransformer = new \Wildberries\Order\OrderTransformation('Ullo', $newOrder);
	$newOrdersMS[] = $orderTransformer->transformWildberriesToMS();
}
$result = array();
if (count($newOrdersMS) > 0){
	$result = $ordersMSClass->createCustomerorder($newOrdersMS);
}
$ordersMS = array_merge($ordersMS, $result);
$ordersMSIDs = array_column ($ordersMS, 'name');
$log->write (__LINE__ . ' ordersMSIDs - ' . json_encode ($ordersMSIDs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
$suppliesWBClass->addOrdersToSupply($supplyOpen['id'], array_column($newOrders, 'id'));

$updateOrdersMS = array();
foreach ($newOrders as $newOrder){
	if (!in_array('WB' . $newOrder['id'], $ordersMSIDs))
	{
		continue;
	}

	#find order in result array and return item
	$order = array_filter($ordersMS, function($item) use ($newOrder){
		if (!isset($item['externalCode']))
			return false;
		return $item['externalCode'] == $newOrder['id'];
		usleep(200000);
	});

	$order = reset($order);
	// get sticker
	$stickers = $ordersWBClass->getStickers(array($newOrder['id']));
	if (isset($stickers['stickers'][0]))
	{
		$orderTransformer = new \Wildberries\Order\OrderTransformation('Ullo', $stickers['stickers'][0]);
		$updateOrdersMS[] = $orderTransformer->transformWildberriesStickerToMS($order, $supplyOpen);
	}
}
if (count($updateOrdersMS) > 0){
	$result = $ordersMSClass->createCustomerorder($updateOrdersMS);
}

echo 'Processed ' . count ($newOrders) . ', created ' . count ($newOrdersMS), ', stickers updated ' . count ($updateOrdersMS);
?>
