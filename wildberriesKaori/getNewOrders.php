<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Orders.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Products.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');

$logger = new \Classes\Common\Log('wildberriesKaori - getNewOrders.log');

$startDate = date('Y-m-d', strtotime('-2 days')) . 'T00:00:00.000+03:00';
$endDate = NULL;
$status = 0;

$ordersWBClass = new \Classes\Wildberries\v1\Orders('Kaori');

$newOrders = $ordersWBClass->orderList($startDate, $endDate, $status);

if (!count($newOrders))
{
	echo 'Processed 0 orders';
	return;
}

$ordersIDs = array_column ($newOrders, 'orderId');
$filter = '';
foreach ($ordersIDs as $ordersID){
	$filter .= 'name=WB' . $ordersID . ';';
}

$ordersMSClass = new OrdersMS();
$ordersMS = $ordersMSClass->findOrders($filter);
$ordersMSIDs = array_column ($ordersMS, 'name');

$productsWBClass = new \Classes\Wildberries\v1\Products('Kaori');
$productsWB = $productsWBClass->cardList();
$productCodes = array();
foreach ($productsWB as $product)
{
    if (isset($product['nomenclatures'][0]['variations'][0]['chrtId'])){
        $productCodes[] = $product['nomenclatures'][0]['variations'][0]['chrtId'];
    }
    else{
        $productCodes[] = null;
    }
}

$productMSClass = new ProductsMS();
$productMS0 = $productMSClass->findProductsByCode('000-0000');

$newOrdersMS = array();
$changeStatus = array();
	
foreach ($newOrders as $order)
{
	if (array_search('WB' . $order['orderId'], $ordersMSIDs) !== false)
	{
		$logger->write ('Already loaded - ' . $order['orderId']);
		continue;
	}
	
	$positions = array();
	$prKey = array_search($order['chrtId'], $productCodes);
	if ($prKey !== false)
	{
	    
	    $productMS = $productMSClass->findProductsByCode($productsWB[$prKey]['supplierVendorCode']);
		$productMS = !isset($productMS[0]) ? $productMS0 : $productMS;
	}
	else{
		$productMS = $productMS0;
	}
	$position = array();
	$position ['quantity'] = 1;
	$position ['reserve'] = 1;
	$position ['price'] = $order['totalPrice'];
	$position ['vat'] = isset ($productMS[0]['vat']) ? $productMS[0]['vat'] : $productMS[0]['effectiveVat'];
	$position ['assortment'] = array(
		'meta' => $productMS[0]['meta']
	);

	$positions[] = $position;
		
	$date = DateTime::createFromFormat('Y-m-d\TH:i:s', explode ('.', $order['dateCreated'])[0]);
	
	$newOrdersMS[] = array(
		'name' => 'WB' . (string)$order['orderId'],
		'organization' => array (
			'meta' => array (
				'href' => MS_KAORI,
				'type' => 'organization',
				'mediaType' => 'application/json'
			)
		),
		'externalCode' => (string)$order['orderId'],
		'moment' => $date->format('Y-m-d H:i:s'),
		'deliveryPlannedMoment' => $date->format('Y-m-d H:i:s'),
		'applicable' => true,
		'vatEnabled' => true,
		'vatIncluded' => true,
		'agent' => array(
			'meta' => array (
				'href' => MS_WB_AGENT,
				'type' => 'counterparty',
				'mediaType' => 'application/json'
			)
		),
		'state' => array(
			'meta' => array(
			    'href' => MS_NEW_STATE,
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
						'href' => MS_PAYMENTTYPE_SBERBANK,
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
					    'href' => MS_DELIVERY_VALUE0,
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
				'value' => (string)$order['orderId']
			),
			// адрес доставки
			4 => array(
				'meta' => array (
				    'href' => MS_ATTR . MS_ADDRESS_ATTR,
					'type' => 'attributemetadata',
					'mediaType' => 'application/json'
				),
			    'value' => (string)$order['deliveryAddress']
			),
			// ФИО
			5 => array(
				'meta' => array (
				    'href' => MS_ATTR . MS_FIO_ATTR,
					'type' => 'attributemetadata',
					'mediaType' => 'application/json'
				),
			    'value' => (string)$order['userInfo']['fio']
			),
		    // телефон
		    6 => array(
		        'meta' => array (
		            'href' => MS_ATTR . MS_PHONE_ATTR,
		            'type' => 'attributemetadata',
		            'mediaType' => 'application/json'
		        ),
		        'value' => (string)$order['userInfo']['phone']
		    )
		)
	);
	
	$changeStatus[] = array (
		'orderId' => $order['orderId'],
		'status' => 1
	);
	
}
if (count($newOrdersMS) > 0)
	$ordersMSClass->createCustomerorder($newOrdersMS);
//if (count($changeStatus) > 0)
//    $ordersWBClass->changeOrdersStatus($changeStatus);

	echo 'Processed ' . count ($newOrders) . ', created ' . count ($newOrdersMS);
?>

