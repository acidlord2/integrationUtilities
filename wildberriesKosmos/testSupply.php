<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Orders.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Supplies.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');

$logger = new \Classes\Common\Log('wildberriesKosmos - getNewOrders.log');

$startDate = date('Y-m-d', strtotime('-2 days')) . 'T00:00:00.000+03:00';
$endDate = NULL;
$status = 0;

$ordersWBClass = new \Classes\Wildberries\v1\Orders('Kosmos');
$suppliesWBClass = new \Classes\Wildberries\v1\Supplies('Kosmos');


// check if supply exists
$supplyOpen = null;
$supplies = $suppliesWBClass->getSupplies();
foreach ($supplies as $supply)
	if ($supply['closedAt'] == null)
	{
		$supplyOpen = $supply;
		break;
	}

echo 'supplyOpen - ' . json_encode ($supplyOpen, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
return;

if ($supplyOpen === null)
	$supplyOpen = $suppliesWBClass->createSupply('WB' . date('Y-m-d H:i:s'));
	
foreach ($newOrders as $newOrder)
{
	if (array_search('WB' . $newOrder['id'], $ordersMSIDs) !== false)
	{
		$logger->write ('Already loaded - ' . $newOrder['id']);
		continue;
	}
	
	$positions = array();

	$productMS = $productMSClass->findProductsByCode($newOrder['article']);
	$productMS = isset($productMS[0]) ? $productMS : $productMS0;

	$position = array();
	$position ['quantity'] = 1;
	$position ['reserve'] = 1;
	$position ['price'] = isset($newOrder['salePrice']) && $newOrder['salePrice'] != null ? $newOrder['salePrice'] : $newOrder['price'];
	$position ['assortment'] = array(
		'meta' => $productMS[0]['meta']
	);

	$positions[] = $position;
		
	$date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $newOrder['createdAt']);
	
	$newOrdersMS[] = array(
		'name' => 'WB' . (string)$newOrder['id'],
		'organization' => array (
			'meta' => array (
				'href' => MS_KOSMOS,
				'type' => 'organization',
				'mediaType' => 'application/json'
			)
		),
		'externalCode' => (string)$newOrder['id'],
		'moment' => $date->format('Y-m-d H:i:s'),
		'deliveryPlannedMoment' => $date->format('Y-m-d H:i:s'),
		'applicable' => true,
		'vatEnabled' => false,
		'vatIncluded' => false,
		'agent' => array(
			'meta' => array (
				'href' => MS_WB_AGENT,
				'type' => 'counterparty',
				'mediaType' => 'application/json'
			)
		),
		'state' => array(
			'meta' => array(
			    'href' => MS_MPNEW_STATE,
				'type' => 'state',
				'mediaType' => 'application/json'
			)
		),
		'store' => array(
			'meta' => array(
				'href' => 'https://api.moysklad.ru/api/remap/1.1/entity/store/dd7ce917-4f86-11e6-7a69-8f550000094d',
				'type' => 'store',
				'mediaType' => 'application/json'
			)
		),
		'group' => array(
			'meta' => array(
				'href' => 'https://api.moysklad.ru/api/remap/1.1/entity/group/dd4ce7fe-4f86-11e6-7a69-971100000043',
				'type' => 'group',
				'mediaType' => 'application/json'
			)
		),
		'project' => array(
			'meta' => array(
				'href' => MS_PROJECT_WB,
				'type' => 'project',
				'mediaType' => 'application/json'
			)
		),
		'positions' => $positions,
		'attributes' => array(
			// тип оплаты
			0 => array(
				'meta' => array (
					'href' => MS_ATTR . MS_PAYMENTTYPE_ATTR,
					'type' => 'attributemetadata',
					'mediaType' => 'application/json'
				),
				'value' => array(
					'meta' => array(
						'href' => MS_PAYMENTTYPE_SBERBANK_ONLINE,
						'type' => 'customentity',
						'mediaType' => 'application/json'
					)
				)
			),
			// время доставки
			1 => array(
				'meta' => array (
					'href' => MS_ATTR . MS_DELIVERYTIME_ATTR,
					'type' => 'attributemetadata',
					'mediaType' => 'application/json'
				),
				'value' => array(
					'meta' => array(
						'href' => MS_DELIVERYTIME_9_21,
						'type' => 'customentity',
						'mediaType' => 'application/json'
					)
				)
			),
			// способ доставки
			2 => array(
				'meta' => array (
					'href' => MS_ATTR . MS_DELIVERY_ATTR,
					'type' => 'attributemetadata',
					'mediaType' => 'application/json'
				),
				'value' => array(
					'meta' => array(
					    'href' => MS_DELIVERY_VALUE_WB,
						'type' => 'customentity',
						'mediaType' => 'application/json'
					)
				)
			),
			// штрихкод
			3 => array(
				'meta' => array (
					'href' => MS_ATTR . MS_BARCODE2_ATTR,
					'type' => 'attributemetadata',
					'mediaType' => 'application/json'
				),
				'value' => (string)$newOrder['id']
			)
		)
	);
	
	$suppliesWBClass->addOrderToSupply($supplyOpen['id'], (string)$newOrder['id']);
	
}
if (count($newOrdersMS) > 0)
	$ordersMSClass->createCustomerorder($newOrdersMS);
//if (count($changeStatus) > 0)
//    $ordersWBClass->changeOrdersStatus($changeStatus);

	echo 'Processed ' . count ($newOrders) . ', created ' . count ($newOrdersMS);
?>

