<?php
	/**
	 * Creates new order
	 *
	 * @author GPOLYAN <acidlord@yandex.ru>
	 */
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sbermegamarket/Order.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');

	$log = new Log('sbmm-ast1 - importOrders.log');
	
	$sbmmOrdersClass = new \Classes\Sbermegamarket\Order(SBMM_SHOP_AST2);
	$orders = $sbmmOrdersClass->searchOrders(['CONFIRMED']);
	
	if (count($orders['shipments']) == 0)
	{
		echo 'Imported: 0 orders';
		return;
	}
	$merchantId = SBMM_SHOP_AST2;
	$msOrdersClass = new OrdersMS();
	$msProductsClass = new ProductsMS();

	$shipments = $sbmmOrdersClass->getOrders($orders['shipments']);

	foreach($shipments['shipments'] as $shipment)
	{
		$positions = array();
		$total_goods = 0;
		if (isset($shipment['items'])) {
			$items = array();
			$i = 0;
			// items
			foreach ($shipment['items'] as $product) 
			{
				//$positions[$i] = array();
				$product_ms = $msProductsClass->findProductsByCode($product['offerId']);
								
				if (!isset($product_ms[0]['id']))
				{
					$product_ms = $msProductsClass->findProductsByCode('000-0000');
				}
				$positions[] = array(
					'quantity' => (float)$product['quantity'],
					'price' => (float)$product['price']*100,
					'discount' => (float)0,
					'vat' => (int)$product_ms[0]['effectiveVat'],
					'vatEnabled' => true,
					'assortment' => array(
						'meta' => array(
							'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PRODUCT . '/' . $product_ms[0]['id'],
							'type' => 'product',
							'mediaType' => 'application/json'
						)
					),
					'reserve' => (float)$product['quantity']
				);

				$total_goods += (float)$product['quantity'] * (float)$product['finalPrice'];
				$items[] = array(
					'itemIndex' => $product['itemIndex'],
					'quantity' => $product['quantity'],
					'boxes' => array(
						0 => array (
							'boxIndex' => 1,
							'boxCode' => (string)$merchantId . '*' . (string)$shipment['shipmentId'] . '*1'
						)
					)
				);
			}
			
			if (isset ($shipment['shipmentDateTo']))
				$deliveryPlannedMoment = DateTime::createFromFormat('Y-m-d\TH:i:sO', $shipment['shipmentDateTo'])->format('Y-m-d H:i:s');
			else
				$deliveryPlannedMoment = Date ('Y-m-d H:i:s', strtotime('+1 day'));
				
			$orderData = array(
				'name' => $shipment['shipmentId'],
				'organization' => array (
					'meta' => array (
						'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_ORGANIZATION . '/' . MS_ALIANS_ID,
						'type' => 'organization',
						'mediaType' => 'application/json'
					)
				),
				'externalCode' => $shipment['shipmentId'],
				'moment' => date("Y-m-d H:i:s"),
				'deliveryPlannedMoment' => $deliveryPlannedMoment,
				'applicable' => true,
				'vatEnabled' => true,
				'agent' => array(
					'meta' => array(
						'href' => MS_GOODS_AGENT,
						'type' => 'counterparty',
						'mediaType' => 'application/json'
					)
				),
				'state' => array(
					'meta' => array(
						'href' => MS_CONFIRMGOODS_STATE,
						'type' => 'state',
						'mediaType' => 'application/json'
					)
				),
				'store' => array(
					'meta' => array(
						'href' => MS_STORE,
						'type' => 'store',
						'mediaType' => 'application/json'
					)
				),
				'project' => array(
					'meta' => array(
						'href' => MS_PROJECT_SBMM_AST2,
						'type' => 'project',
						'mediaType' => 'application/json'
					)
				),
				'positions' => $positions,
				'attributes' => array(
					// Cумма Маркетплейс
					0 => array(
						'meta' => array(
							'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_MPAMOUNT_ATTR,
							'type' => 'attributemetadata',
							'mediaType' => 'application/json'
						),
						'value' => (int)$total_goods
					),
					// Тип оплаты
					1 => array(
						'meta' => array(
							'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_PAYMENTTYPE_ATTR,
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
					// Время доставки
					2 => array(
						'meta' => array(
							'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_DELIVERYTIME_ATTR,
							'type' => 'attributemetadata',
							'mediaType' => 'application/json'
						),
						'value' => array(
							'meta' => array(
								'href' => MS_DELIVERY_TIME0,
								'type' => 'customentity',
								'mediaType' => 'application/json'
							)
						)
					),
					// Способ доставки
					3 => array(
						'meta' => array(
							'href' => MS_SHIPTYPE_ATTR,
							'type' => 'attributemetadata',
							'mediaType' => 'application/json'
						),
						'value' => array(
							'meta' => array(
								'href' => MS_SHIPTYPE_GOODS,
								'type' => 'customentity',
								'mediaType' => 'application/json'
							)
						)
					),
					// номер доставки
					4 => array(
						'meta' => array(
							'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_DELIVERYNUMBER_ATTR,
							'type' => 'attributemetadata',
							'mediaType' => 'application/json'
						),
						'value' => (string)$shipment['deliveryId']
					),
					// ФИО
					5 => array(
						'meta' => array(
							'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_FIO_ATTR,
							'type' => 'attributemetadata',
							'mediaType' => 'application/json'
						),
						'value' => (string)$shipment['customerFullName']
					),
					// адрес доставки
					6 => array(
						'meta' => array(
							'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_ADDRESS_ATTR,
							'type' => 'attributemetadata',
							'mediaType' => 'application/json'
						),
						'value' => (string)$shipment['customerAddress']
					),
					// штрихкод
					7 => array(
						'meta' => array(
							'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_BARCODE2_ATTR,
							'type' => 'attributemetadata',
							'mediaType' => 'application/json'
						),
						'value' => (string)$merchantId . '*' .(string)$shipment['shipmentId'] . '*1'
					)
				)
			);
			$orderResponse = $msOrdersClass->createCustomerorder($orderData);
			if (!isset ($orderResponse['error']))
			// move orders in goods
			$packData = array (
				'data' => array (
					'shipments' => array (
						0 => array (
						
							'shipmentId' => $shipment['shipmentId'],
							'orderCode' => $shipment['shipmentId'],
							'items' => $items
						)
					)
				)
			);
			$sbmmOrdersClass->packing($packData);
		}
	};
	
	echo 'Confirmed: ' . count($shipments['shipments']) . ' orders';
?>
