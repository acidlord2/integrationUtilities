<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/docker-config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Orders.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Supplies.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/wildberries/order.php');

$shop = 'Kosmos';

// the sticker pass talks to WB and MS a lot - use the maximum the server allows
@set_time_limit(300);

$logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
$logName .= '.log';
$log = new \Classes\Common\Log($logName);

/**
 * Gets stickers for the passed WB orders and returns MS orders ready to be updated with them.
 *
 * @param string $shop
 * @param object $ordersWBClass
 * @param array $orderIds - WB order ids
 * @param array $ordersMSByName - map 'WB<id>' => MS order
 * @param array $supplyOpen - open WB supply
 * @param object $log
 * @param int $maxAttempts - passes over the orders WB did not return a sticker for
 * @return array - MS orders to post
 */
function buildStickerUpdates($shop, $ordersWBClass, $orderIds, $ordersMSByName, $supplyOpen, $log, $maxAttempts = 4)
{
	$updates = array();
	if (!count($orderIds))
		return $updates;

	$stickers = $ordersWBClass->getStickersMap($orderIds, $maxAttempts);

	foreach ($orderIds as $orderId)
	{
		if (!isset($ordersMSByName['WB' . $orderId]))
			continue;

		if (!isset($stickers[(int)$orderId]))
		{
			$log->write(__LINE__ . ' sticker.missing orderId=' . $orderId);
			continue;
		}

		$orderTransformer = new \Wildberries\Order\OrderTransformation($shop, $stickers[(int)$orderId]);
		$updates[] = $orderTransformer->transformWildberriesStickerToMS($ordersMSByName['WB' . $orderId], $supplyOpen);
	}

	return $updates;
}

/**
 * Writes sticker updates to MS in small batches instead of one big request.
 * A sticker is a png, so a single request with a whole supply in it is megabytes and minutes -
 * if the host kills the script half way through, nothing at all gets saved. In batches
 * whatever was written stays written and the next run continues from there.
 *
 * @return int - number of orders written
 */
function flushStickerUpdates($ordersMSClass, $updates, $log, $chunkSize = 20)
{
	$written = 0;
	foreach (array_chunk($updates, $chunkSize) as $chunk)
	{
		$result = $ordersMSClass->createCustomerorder($chunk);
		if (is_array($result) && isset($result[0]['id']))
		{
			$written += count($chunk);
			continue;
		}

		$log->write(__LINE__ . ' sticker.writeFailed orders=' . count($chunk) . ' response - ' . json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}

	return $written;
}

$startDate = date('Y-m-d', strtotime('-2 days')) . 'T00:00:00.000+03:00';
$endDate = NULL;
$status = 0;

$ordersWBClass = new \Classes\Wildberries\v1\Orders($shop);
$suppliesWBClass = new \Classes\Wildberries\v1\Supplies($shop);

$newOrders = $ordersWBClass->getNewOrders();

$ordersMSClass = new OrdersMS();
$productMSClass = new ProductsMS();

$ordersMS = array();
if (count($newOrders))
{
	$ordersIDs = array_column ($newOrders, 'id');
	$filter = '';
	foreach ($ordersIDs as $ordersID){
		$filter .= 'name=WB' . $ordersID . ';';
	}

	$ordersMS = $ordersMSClass->findOrders($filter);

	// A failed lookup must never be read as "they are all already loaded". findOrders returns
	// null when MoySklad answered something unusable - a 504, a concurrency limit - and
	// array_search($name, null) returns null, which !== false, so every order below would be
	// logged "Already loaded" and quietly skipped. That is how 19 waiting orders were passed over
	// in silence on 2026-09-03. Abort instead: the orders stay in /orders/new and the next run
	// picks them up.
	if (!is_array($ordersMS))
	{
		$log->write (__LINE__ . ' MoySklad order lookup failed - aborting so the orders stay in /orders/new');
		echo 'MoySklad lookup failed, 0 processed';
		return;
	}
}
$ordersMSIDs = array_column ($ordersMS, 'name');

$newOrdersMS = array();

$openSupplies = $suppliesWBClass->getOpenSupplies();

// orders go into an open non-B2B supply - B2B supplies are created and filled by WB itself
$supplyOpen = null;
foreach ($openSupplies as $supply)
	if (empty($supply['isB2b']))
	{
		$supplyOpen = $supply;
		break;
	}

// nothing to place and no open supply to check for missing stickers - nothing to do
if (!count($newOrders) && !count($openSupplies))
{
	echo 'Processed 0 orders';
	return;
}

if ($supplyOpen === null && count($newOrders))
{
	$supplyOpen = $suppliesWBClass->createSupply('WB' . date('Y-m-d H:i:s'));
	if (isset($supplyOpen['id']))
		$openSupplies[] = $supplyOpen;
	else
	{
		$log->write (__LINE__ . ' Could not create a supply - ' . json_encode ($supplyOpen, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$supplyOpen = null;
	}
}

$productMS0 = count($newOrders) ? $productMSClass->findProductsByCode('000-0000') : array();

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

	$orderTransformer = new \Wildberries\Order\OrderTransformation($shop, $newOrder);
	$newOrdersMS[] = $orderTransformer->transformWildberriesToMS();
}
unset($newOrder);

$result = array();
if (count($newOrdersMS) > 0){
	$result = $ordersMSClass->createCustomerorder($newOrdersMS);
}
if (!is_array($result))
{
	$log->write (__LINE__ . ' createCustomerorder failed, response - ' . json_encode ($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	$result = array();
}
$ordersMS = array_merge($ordersMS, $result);
$ordersMSIDs = array_column ($ordersMS, 'name');
$log->write (__LINE__ . ' ordersMSIDs - ' . json_encode ($ordersMSIDs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

$ordersMSByName = array();
foreach ($ordersMS as $orderMS)
	if (isset($orderMS['name']))
		$ordersMSByName[$orderMS['name']] = $orderMS;

$stickersWritten = 0;
$handledIDs = array();

if (count($newOrders) && $supplyOpen !== null)
{
	// B2B orders belong to a B2B supply, which only WB can create and fill - trying to add
	// them to our supply fails and would be retried on every run
	$addIDs = array();
	$b2bIDs = array();
	$notInMSIDs = array();
	foreach ($newOrders as $newOrder)
	{
		// Adding an order to a supply removes it from /orders/new permanently, so an order
		// whose MoySklad counterpart is missing has to be left where it is. Otherwise a
		// MoySklad outage drops it silently and no later run can ever pick it up again.
		if (!isset($ordersMSByName['WB' . $newOrder['id']]))
		{
			$notInMSIDs[] = $newOrder['id'];
			continue;
		}

		if (!empty($newOrder['options']['isB2B']))
			$b2bIDs[] = $newOrder['id'];
		else
			$addIDs[] = $newOrder['id'];
	}

	if (count($notInMSIDs))
		$log->write (__LINE__ . ' Not in MS, left in /orders/new for the next run - ' . json_encode ($notInMSIDs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

	if (count($b2bIDs))
		$log->write (__LINE__ . ' B2B orders left for WB to place into a B2B supply - ' . json_encode ($b2bIDs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

	if (count($addIDs))
	{
		$log->write (__LINE__ . ' Adding orders to supply - ');
		// only orders WB actually accepted can have a sticker - adding to a supply is what
		// moves them from status new to confirm
		$addedIDs = $suppliesWBClass->addOrdersToSupply($supplyOpen['id'], $addIDs);
		// WB needs a moment to generate the sticker after the order is put into a supply
		usleep(1000000);

		foreach ($addedIDs as $addedID)
			if (isset($ordersMSByName['WB' . $addedID]))
				$handledIDs[(int)$addedID] = (int)$addedID;

		// written before the backfill: new orders must never be starved by mop-up work
		$stickersWritten += flushStickerUpdates(
			$ordersMSClass,
			buildStickerUpdates($shop, $ordersWBClass, array_values($handledIDs), $ordersMSByName, $supplyOpen, $log),
			$log
		);
	}
}

// an order in a supply is not returned by /orders/new anymore, so this is the only chance
// to pick up the ones left without a sticker earlier. B2B supplies are included: their
// orders are in status confirm and do have stickers, we just never put them there ourselves.
// Bounded per run so the runtime stays predictable as a supply grows towards a few hundred orders
$backfillScanLimit = 200;
$backfillWriteLimit = 100;

foreach ($openSupplies as $supply)
{
	if ($backfillWriteLimit <= 0)
		break;

	// newest first: an order the run above failed on is at the end of the supply
	$backfillIDs = array();
	foreach (array_reverse($suppliesWBClass->getSupplyOrderIds($supply['id'])) as $supplyOrderID)
	{
		if (isset($handledIDs[(int)$supplyOrderID]))
			continue;
		$backfillIDs[] = (int)$supplyOrderID;
		if (count($backfillIDs) >= $backfillScanLimit)
			break;
	}

	if (!count($backfillIDs))
		continue;

	$backfillNames = array();
	foreach ($backfillIDs as $backfillID)
		$backfillNames[] = 'WB' . $backfillID;

	$backfillIDs = array();
	$backfillOrdersMSByName = array();
	foreach ($ordersMSClass->findOrdersByNames($backfillNames) as $orderMS)
	{
		if (count($backfillIDs) >= $backfillWriteLimit)
			break;
		if ($ordersMSClass->getAttribute($orderMS, MS_WB_FILE_ATTR) !== false)
			continue;
		$backfillOrdersMSByName[$orderMS['name']] = $orderMS;
		$backfillIDs[] = (int)substr($orderMS['name'], 2);
	}

	if (count($backfillIDs))
	{
		$log->write (__LINE__ . ' sticker.backfill supply=' . $supply['id'] . ' orders - ' . json_encode ($backfillIDs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$written = flushStickerUpdates(
			$ordersMSClass,
			buildStickerUpdates($shop, $ordersWBClass, $backfillIDs, $backfillOrdersMSByName, $supply, $log, 2),
			$log
		);
		$stickersWritten += $written;
		$backfillWriteLimit -= count($backfillIDs);
	}
}

echo 'Processed ' . count ($newOrders) . ', created ' . count ($newOrdersMS), ', stickers updated ' . $stickersWritten;
?>
