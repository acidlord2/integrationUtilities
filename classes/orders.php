<?php
/**
 *
 * @class Orders
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class Orders
{
	private static $client_id = false;
	private static $client_pass = false;
		

    public static function getList($shipDate, $organization, $goodsStatus, $beruStatus)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		static $orders;
		
		// get orders
		if ($organization != 'all') {
			$org = $organization == '4cleaning' ? 'organization=' . MS_4CLEANING . ';' : 'organization=' . MS_10KIDS . ';';
		} else {
			$org = '';
		}
		
		
		if ($goodsStatus == 2 && $beruStatus == 2)
		{
			$goodsSt = 'state=' . MS_CONFIRMGOODS_STATE . ';' . 'state=' . MS_CONFIRM_STATE . ';';
			$agent = '';
		}
		else if ($goodsStatus != 2 && $beruStatus == 2)
		{
			$goodsSt = $goodsStatus == 1 ? 'state=' . MS_CONFIRMGOODS_STATE . ';' : 'state=' . MS_CONFIRM_STATE . ';';
			$agent = $goodsStatus == 1 ? 'agent=' . MS_GOODS_AGENT . ';' : 'agent%21=' . MS_GOODS_AGENT  . ';';
		}
		else if ($goodsStatus == 2 && $beruStatus != 2)
		{
			$goodsSt = $beruStatus == 1 ? 'state=' . MS_CONFIRMBERU_STATE . ';' : 'state=' . MS_CONFIRM_STATE . ';';
			$agent = $beruStatus == 1 ? 'agent=' . MS_BERU_AGENT . ';' : 'agent%21=' . MS_BERU_AGENT  . ';';
		}
		else
		{
			$goodsSt = '';
			$agent = '';
		}
		$shipDateStart = 'deliveryPlannedMoment%3E=' . $shipDate . '%2000:00:00;';
		$shipDateEnd = 'deliveryPlannedMoment%3C=' . $shipDate . '%2023:59:59;';
		
		//$logger = new Log('tmp.log');
		$offset = 0;
		$orders = array();
		$cont = true;
		while ($cont)
		{
			$service_url = MS_COURL . '?filter=' . $shipDateStart . $shipDateEnd . $goodsSt . $org . $agent . '&limit=' . MS_LIMIT . '&offset=' . $offset;
			//echo $service_url;
			//$logger->write ($service_url);
			MSAPI::getMSData($service_url, $response_ordersJson, $response_orders);
			$cont = isset ($response_orders['rows'][0]);
			if ($cont) {
				$offset += MS_LIMIT;
				$orders = array_merge ($orders, $response_orders['rows']);
			}
		}
		$i = 0;
		//$logger = new Log('tmp.log');
		foreach ($orders as $order) {
			$orders[$i]['website'] = $order['organization']['meta']['href'] == MS_4CLEANING ? '4cleaning' : '10kids';
			$orders[$i]['goodsFlag'] = $order['agent']['meta']['href'] == MS_GOODS_AGENT ? utf8_encode("&#10004;") : utf8_encode("&#10008;");
			$orders[$i]['beruFlag'] = $order['agent']['meta']['href'] == MS_BERU_AGENT ? utf8_encode("&#10004;") : utf8_encode("&#10008;");
			$i++;
			//$logger->write (json_encode($order, true));
		}
		
		return $orders;
 	}
    public static function getOrderList($shipDate, $agent, $curier, $org)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		static $orders;
		
		$logger = new Log('orders.log');
		// get orders
		if ($agent == 'Goods')
			$agentFilter = 'agent=' . MS_GOODS_AGENT . ';';
		if ($agent == 'Beru')
			$agentFilter = 'agent=' . MS_BERU_AGENT . ';';
		if ($agent == 'Ozon')
			$agentFilter = 'agent=' . MS_OZON_AGENT . ';';
		if ($agent == 'Internal')
			$agentFilter = 'agent%21=' . MS_GOODS_AGENT . ';agent%21=' . MS_BERU_AGENT . ';agent%21=' . MS_OZON_AGENT . ';';

		if ($agent == 'Internal')
		{
			if ($curier == '1')
				$curierFilter = MS_SHIPTYPE_ATTR . '=' . MS_SHIPTYPE_CURIER1 . ';';
			if ($curier == '2')
				$curierFilter = MS_SHIPTYPE_ATTR . '=' . MS_SHIPTYPE_CURIER2 . ';';
			if ($curier == '3')
				$curierFilter = MS_SHIPTYPE_ATTR . '=' . MS_SHIPTYPE_CURIER3 . ';';
			if ($curier == '4')
				$curierFilter = MS_SHIPTYPE_ATTR . '=' . MS_SHIPTYPE_CURIER4 . ';';
			if ($curier == '5')
				$curierFilter = MS_SHIPTYPE_ATTR . '=' . MS_SHIPTYPE_CURIER5 . ';';
			if ($curier == '10')
				$curierFilter = MS_SHIPTYPE_ATTR . '=' . MS_SHIPTYPE_CURIER10 . ';';
		}
		else
			$curierFilter = '';
		
		if ($agent == 'Beru')
		{
			if ($org == 'ullo')
				$orgFilter = 'organization=' . MS_ULLO . ';';
			if ($org == '4cleaning')
				$orgFilter = 'organization=' . MS_4CLEANING . ';';
			if ($org == 'kaori')
				$orgFilter = 'organization=' . MS_KAORI . ';';
		}
		else if ($agent == 'Ozon')
		{
			if ($org == 'ullo')
				$orgFilter = 'organization=' . MS_ULLO . ';';
			if ($org == '4cleaning')
				$orgFilter = 'organization=' . MS_4CLEANING . ';';
			if ($org == 'kaori')
				$orgFilter = 'organization=' . MS_KAORI . ';';
		}
		else
			$orgFilter = '';
		
		$shipDateStart = 'deliveryPlannedMoment%3E=' . $shipDate . '%2000:00:00;';
		$shipDateEnd = 'deliveryPlannedMoment%3C=' . $shipDate . '%2023:59:59;';
		$state = 'state=' . MS_SHIP_STATE . ';state=' . MS_SHIPGOODS_STATE . ';';
		
		$offset = 0;
		$orders = array();
		while (true)
		{
			$service_url = MS_COURL . '?filter=' . $shipDateStart . $shipDateEnd . $agentFilter . $curierFilter . $orgFilter . $state . '&limit=' . MS_LIMIT . '&offset=' . $offset;
			MSAPI::getMSData($service_url, $response_ordersJson, $response_orders);
			if (isset ($response_orders['rows'][0])) {
				$offset += MS_LIMIT;
				$orders = array_merge ($orders, $response_orders['rows']);
			}
			else
				break;
		}
		// ship types
		$pickup = array (MS_SHIPTYPE_PICKUP);
		$mpship = array (MS_SHIPTYPE_BERU, MS_SHIPTYPE_GOODS, MS_SHIPTYPE_OZON);
		$selfship = array (MS_SHIPTYPE_CURIER1, MS_SHIPTYPE_CURIER2, MS_SHIPTYPE_CURIER3, MS_SHIPTYPE_CURIER4, MS_SHIPTYPE_CURIER5, MS_SHIPTYPE_CURIER10, MS_SHIPTYPE_IML);
		foreach ($orders as $key => $order) {
			MSAPI::getMSData($order['organization']['meta']['href'], $organizationJson, $organizationArray);
			//$logger->write ('getOrderList.organizationArray - ' . json_encode ($organizationArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$orders[$key]['organization']['name'] = $organizationArray['name'];
			MSAPI::getMSData($order['agent']['meta']['href'], $agentJson, $agentArray);
				
			$orders[$key]['agent']['name'] = $agentArray['name'];
			$orders[$key]['curier'] = '';
			$orders[$key]['mpcancel'] = MY_FALSE;
			$orders[$key]['mpcancelFlag'] = false;
			foreach ($order['attributes'] as $arrtKey => $attribute)
			{
				if ($attribute['meta']['href'] == MS_SHIPTYPE_ATTR)
				{
					$orders[$key]['curier'] = $attribute['value']['name'];
					$orders[$key]['shiptype'] = in_array ($attribute['value']['meta']['href'], $pickup) ? 'pickup' : (in_array ($attribute['value']['meta']['href'], $mpship) ? 'mpship' : 'selfship');
				}
				if ($attribute['meta']['href'] == MS_BARCODE_ATTR)
					$orders[$key]['barcode'] = $attribute['value'];
				if ($attribute['meta']['href'] == MS_MPCANCEL_ATTR)
				{
					$orders[$key]['mpcancel'] = $attribute['value'] ? MY_TRUE : MY_FALSE;
					$orders[$key]['mpcancelFlag'] = $attribute['value'];
				}
			}
			MSAPI::getMSData($order['state']['meta']['href'], $stateJson, $state);
			$orders[$key]['state']['name'] = $state['name'];
			$orders[$key]['shipped'] = isset ($order['demands']);
			$orders[$key]['cancelled'] = false;
			$orders[$key]['checked'] = false;
			$orders[$key]['scanCount'] = 0;
		}
		
		return $orders;
 	}
	// return order data
    public static function getOrder($orderId)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		$service_url = MS_COURL . $orderId;
		//echo $service_url;
		//$logger->write ($service_url);
		MSAPI::getMSData($service_url, $response_orderJson, $response_order);
		
		if (isset($response_order['errors'])) {
			return 'Заказ ID = ' . $orderId . ' не обнаружен';
		}
		// get agent
		$service_url = $response_order['agent']['meta']['href'];
		MSAPI::getMSData($service_url, $agentJson, $agent);
		$response_order['agent']['name'] = $agent['name'];
		// get shiptype
		if (isset ($response_order['attributes']))
			foreach ($response_order['attributes'] as $attribute)
			{
				if ($attribute ['meta']['href'] == MS_SHIPTYPE_ATTR)
					$response_order['shiptype'] = in_array ($attribute['value'], $pickup) ? 'pickup' : (in_array ($attribute['value'], $mpship) ? 'mpship' : 'selfship');					
				if ($attribute['meta']['href'] == MS_MPCANCEL_ATTR)
					$response_order['mpcancelFlag'] = $attribute['value'];
			}
		// get positions
		$orderPoses = array();
		$offset = 0;
		while (true) {
			$service_url = MS_COURL . $orderId . '/positions/?limit=' . MS_LIMIT . '&offset=' . $offset;
			MSAPI::getMSData($service_url, $response_orderPosesJson, $response_orderPoses);
			if (isset ($response_orderPoses['rows'][0])) {
				$offset += MS_LIMIT;
				$orderPoses = array_merge ($orderPoses, $response_orderPoses['rows']);
			}
			else
				break;
		}
		// add positions to order
		$pos = 0;
		foreach ($orderPoses as $orderPos) {
			// get product
			if ($orderPos['assortment']['meta']['type'] == 'product') {
				$orderPoses2[$pos] = $orderPos;
				$service_url = $orderPos['assortment']['meta']['href'];
				MSAPI::getMSData($service_url, $productJson, $product);
				$orderPoses2[$pos]['assortment']['productName'] = $product['name'];
				$orderPoses2[$pos]['assortment']['productCode'] = $product['code'];
				$orderPoses2[$pos]['assortment']['productBarcode'] = isset($product['barcodes']) ? implode(',', $product['barcodes']) : "";
				$orderPoses2[$pos]['quantityPack'] = 0;
				// find product in stock
				$service_url = MS_STOCKURL . '?stockMode=all&product.id=' . $product['id'];
				MSAPI::getMSData($service_url, $stockJson, $stock);
				if (isset($stock['rows'][0]['stock'])) {
					$orderPoses2[$pos]['stock'] = $stock['rows'][0]['stock'];
				} else {
					$orderPoses2[$pos]['stock'] = 0;
				}
				$pos++;
			}
		}
		$response_order['positions2'] = $orderPoses2;
		return $response_order;
	}

	// find order data
    public static function findOrders(&$orders)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log ('orders.log');
		// 50 orders get
		$i = 0;
		$n = 80;
		$service_url = '';
		$orders_temp = array ();
		foreach ($orders as $order)
		{
			if ($i % $n == 0)
				$service_url = MS_COURL . '?filter=';
			$service_url .= 'name='. $order['orderNumber'] . ';';
			if (isset ($order['orderNumber2']))
				$service_url .= 'name='. $order['orderNumber2'] . ';';
			//$logger->write ($service_url);
			if ($i % $n == $n - 1 || $i + 1 == count ($orders))
			{
				$service_url .= '&limit=' . MS_LIMIT;
				MSAPI::getMSData($service_url, $response_orderJson, $response_order);
				//$logger->write ($service_url);
				foreach ($response_order['rows'] as $response)
				{
					$orders_temp[$response['name']]['orderId'] = $response['id'];
					$orders_temp[$response['name']]['orgId'] = $response['organization']['meta'];
					$orders_temp[$response['name']]['orgAccId'] = $response['organizationAccount']['meta'];
					$orders_temp[$response['name']]['ownerId'] = $response['owner']['meta'];
					$orders_temp[$response['name']]['agentId'] = $response['agent']['meta'];
					if ($catCommKey = array_search (MS_CATCOMM, array_column ($response['attributes'], 'id')))
						$orders_temp[$response['name']]['catCommStorno'] = $response['attributes'][$catCommKey]['value'];
					if ($trCommKey = array_search (MS_TRCOMM, array_column ($response['attributes'], 'id')))
						$orders_temp[$response['name']]['trCommStorno'] = $response['attributes'][$trCommKey]['value'];
					if ($plCommKey = array_search (MS_PLCOMM, array_column ($response['attributes'], 'id')))
						$orders_temp[$response['name']]['plCommStorno'] = $response['attributes'][$plCommKey]['value'];
					if (isset ($response['payments']))
						$orders_temp[$response['name']]['payments'] = $response['payments'];
				}
			}
			$i++;
		}
		//echo $service_url;
		//$logger->write ($orders);
		//MSAPI::getMSData($service_url, $response_orderJson, $response_order);
		
		foreach ($orders as $key => $order)
			if (isset($orders_temp[$order['orderNumber']]))
			{
				$orders[$key] = array_merge ($orders[$key], $orders_temp[$order['orderNumber']]);
			}
			else if (isset($order['orderNumber2']))
				if (isset($orders_temp[$order['orderNumber2']]))
				{
					$orders[$key] = array_merge ($orders[$key], $orders_temp[$order['orderNumber2']]);
				}
			else
			{
				//echo 'Заказ номер = ' . $orderNumber . ' не обнаружен';
				$logger->write ('findOrders - Заказ номер = ' . $order['orderNumber'] . ' не обнаружен');
				$logger->write ('findOrders.orders - ' . json_encode ($order, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			}
		return;
	}

	// find order
    public static function findOrder($orderNumber)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log ('orders.log');
		// 50 orders get
		$service_url = MS_COURL . '?filter=name=' . $orderNumber . ';';
		$logger->write ($service_url);
		MSAPI::getMSData($service_url, $response_orderJson, $response_order);
		//echo $service_url;
		$logger->write ('findOrder.service_url - ' . $service_url);
		//MSAPI::getMSData($service_url, $response_orderJson, $response_order);
		if (!isset ($response_order['rows'][0]))
		{
			$service_url = MS_COURL . '?filter=https://online.moysklad.ru/api/remap/1.1/entity/customerorder/metadata/attributes/51ec2167-e895-11e8-9ff4-31500000db84=' . str_replace('%', '%25', $orderNumber) . ';';
			$logger->write ('findOrder.service_url - ' . $service_url);
			MSAPI::getMSData($service_url, $response_orderJson, $response_order);
		}
		if (isset ($response_order['rows'][0])) {
    		$response_order['rows'][0]['positions2'] = '';

			$service_url = $response_order['rows'][0]['positions']['meta']['href'] . '?limit=' . MS_LIMIT;
			MSAPI::getMSData($service_url, $response_orderPosesJson, $response_orderPoses);
			if (isset ($response_orderPoses['rows'][0]))
			{
				$orderPoses = $response_orderPoses['rows'];
				
    			foreach ($orderPoses as $orderPos) {
    				// get product
    				if ($orderPos['assortment']['meta']['type'] == 'product') {
    					
    					$service_url = $orderPos['assortment']['meta']['href'];
    					MSAPI::getMSData($service_url, $productJson, $product);
    
    /* 					if ($response_order['rows'][0]['positions2'] != '')
    						$response_order['rows'][0]['positions2'] .= '<br>';
     */					
    					$response_order['rows'][0]['positions2'] .= '<p>' . $product['code'] . ' | ' . $product['name'] . ': ' . $orderPos['quantity'] . '</p>';
    				}
    			}
			}
			MSAPI::getMSData($response_order['rows'][0]['agent']['meta']['href'], $response_agentJson, $response_agent);
			$response_order['rows'][0]['agent']['name'] = isset ($response_agent['name']) ? $response_agent['name'] : '';
			MSAPI::getMSData($response_order['rows'][0]['state']['meta']['href'], $response_stateJson, $response_state);
			$response_order['rows'][0]['state']['name'] = isset ($response_state['name']) ? $response_state['name'] : '';
			//$logger->write ('findOrder.response_order - ' . json_encode ($response_order, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			return $response_order['rows'][0];
		}
		else
			return false;
	}

	// find order
    public static function findOrders2($orderNumber)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log ('orders.log');
		// 50 orders get
		$service_url = MS_COURL . '?filter=deliveryPlannedMoment%3E' . date('Y-m-d%20H:i:s', strtotime("-5 days")) . ';name=' . str_replace('%', '%25', $orderNumber) . ';';
		//$logger->write ($service_url);
		MSAPI::getMSData($service_url, $response_orderJson, $response_order);
		//echo $service_url;
		//$logger->write ($orders);
		//self::getMSData($service_url, $response_orderJson, $response_order);
		if (!isset ($response_order['rows'][0]))
		{
			$service_url = MS_COURL . '?filter=deliveryPlannedMoment%3E' . date('Y-m-d%20H:i:s', strtotime("-5 days")) . ';https://online.moysklad.ru/api/remap/1.1/entity/customerorder/metadata/attributes/51ec2167-e895-11e8-9ff4-31500000db84=' . str_replace('%', '%25', $orderNumber) . ';';
			$logger->write ('findOrders2.service_url - ' . $service_url);
			MSAPI::getMSData($service_url, $response_orderJson, $response_order);
		}
		
		if (isset ($response_order['rows'][0])) {
			foreach ($response_order['rows'] as $key => $order)
			{
				MSAPI::getMSData($order['agent']['meta']['href'], $response_agentJson, $response_agent);
				$response_order['rows'][$key]['agent']['name'] = isset ($response_agent['name']) ? $response_agent['name'] : '';
				MSAPI::getMSData($order['state']['meta']['href'], $response_stateJson, $response_state);
				$response_order['rows'][$key]['state']['name'] = isset ($response_state['name']) ? $response_state['name'] : '';
				MSAPI::getMSData($order['organization']['meta']['href'], $response_orgJson, $response_org);
				$response_order['rows'][$key]['organization']['name'] = isset ($response_org['name']) ? $response_org['name'] : '';
				if (isset($order['attributes']))
				{
					$cancelledKey = array_search (MS_CANCELLED, array_column ($order['attributes'], 'id'));
					$response_order['rows'][$key]['cancelFlag'] = $cancelledKey === false ? utf8_encode("&#10008;") : ($order['attributes'][$cancelledKey]['value'] ? utf8_encode("&#10004;") : utf8_encode("&#10008;"));
				}
				else
					$response_order['rows'][$key]['cancelFlag'] = false;
			}
			return $response_order['rows'];
		}
		else
			return false;
	}


	//return report
    public static function getReport($orderId)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		//$logger = new Log ('tmp.log');
		$service_url = MS_COURL . $orderId;
		//echo $service_url;
		//$logger->write ($service_url);
		MSAPI::getMSData($service_url, $response_orderJson, $response_order);
		// get template
		if ($response_order['agent']['meta']['href'] == MS_GOODS_AGENT) {
			$var_templ = MS_REP_GOODS;
		} else if ($response_order['agent']['meta']['href'] == MS_BERU_AGENT) {
			$var_templ = MS_REP_BERU;
		} else if ($response_order['organization']['meta']['href'] == MS_4CLEANING) {
			$var_templ = MS_REP_4CLEANING;
		} else {
			$var_templ = MS_REP_10KIDS;
		}
		
		$service_url = MS_COURL . $orderId . '/export/';
		$postdata = array (
			'template' => array (
				'meta' => array (
					'href' => $var_templ,
					'type' => 'customtemplate',
					'mediaType' => 'application/json'
				)
			),
			'extension' => 'pdf'
		);
		// get agent
		MSAPI::postMSDataBlob($service_url, $postdata, $reportJson, $report);
		//$logger -> write ('service_url: ' . $service_url);
		//$logger -> write ('postdata: ' . json_encode($postdata));
		return $report;
	}
	//return report
    public static function ship($orderId)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		//$logger = new Log ('tmp.log');
		$service_url = MS_COURL . $orderId;
		//echo $service_url;
		//$logger->write ($service_url);
		MSAPI::getMSData($service_url, $response_orderJson, $response_order);
		// get template
		if ($response_order['agent']['meta']['href'] == MS_GOODS_AGENT || $response_order['agent']['meta']['href'] == MS_BERU_AGENT) {
			$var_state = MS_SHIPGOODS_STATE;
		} else {
			$var_state = MS_SHIP_STATE;
		}
		
		$service_url = MS_COURL . $orderId;
		$postdata = array (
			'state' => array (
				'meta' => array (
					'href' => $var_state,
					'type' => 'state',
					'mediaType' => 'application/json'
				)
			)
		);
		// get agent
		MSAPI::putMSData($service_url, $postdata, $backJson, $back);
		//$logger -> write ('back: ' . $back);
		//$logger -> write ('postdata: ' . json_encode($postdata));
		return $back;
	}
	// update order commision
    public static function updateCommision($order)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		//$logger = new Log ('tmp.log');
		$service_url = MS_COURL . $order['id'];
		//echo $service_url;
		//$logger->write ($service_url);
		$postdata = array (
			'attributes' => array()
		);
		// Комиссия за товарную категорию
		if (isset ($order['catComm']))
			$postdata['attributes'][] = array(
				'id' => MS_CATCOMM,
				'value' => $order['catComm'] > 0 ? $order['catComm'] : $order['catCommStorno'] + $order['catComm']
			);
		// Комиссия за транзакции
		if (isset ($order['trComm']))
			$postdata['attributes'][] = array(
				'id' => MS_TRCOMM,
				'value' => $order['trComm'] > 0 ? $order['trComm'] : $order['trCommStorno'] + $order['trComm']
			);
		// Вознаграждение оператора ПЛ
		if (isset ($order['plComm']))
			$postdata['attributes'][] = array(
				'id' => MS_PLCOMM,
				'value' => $order['plComm'] > 0 ? $order['plComm'] : $order['plCommStorno'] + $order['plComm']
			);
		// Комиссия за логистику
		if (isset ($order['logComm']))
			$postdata['attributes'][] = array(
				'id' => MS_LOGCOMM,
				'value' => $order['logComm'] > 0 ? $order['logComm'] : $order['logCommStorno'] + $order['logComm']
			);
		
		// put
		MSAPI::putMSData($service_url, $postdata, $backJson, $back);
		//$logger -> write ('back: ' . $back);
		//$logger -> write ('postdata: ' . json_encode($postdata));
		return $back;
	}
	// update order
    public static function updateOrder($orderId, $data)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log ('orders.log');
		$service_url = MS_COURL . $orderId;
		// put
		MSAPI::putMSData($service_url, $data, $backJson, $back);
		$logger -> write ('updateOrder.backJson - ' . $backJson);
		//$logger -> write ('postdata: ' . json_encode($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $back;
	}
	// check if some returns exist for demandUrl
    public static function checkDemand($demandUrl)
    {
		//require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		//$logger = new Log ('tmp.log');
		//echo $service_url;
		//$logger->write ($service_url);
		// put
		MSAPI::getMSData($demandUrl, $response_demandJson, $response_demand);
		if (isset ($response_demand['returns']))
			return true;
		//$logger -> write ('back: ' . $back);
		//$logger -> write ('postdata: ' . json_encode($postdata));
		return false;
	}
	// check if some returns exist for demandUrl
    public static function createReturn($demandUrl)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		//require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log ('orders.log');
		//echo $service_url;
		//$logger->write ($service_url);
		// demand post body
		$postdata = array (
			'demand' => array (
				'meta' => array (
					'href' => $demandUrl,
					'type' => 'demand',
					'mediaType' => 'application/json'
				)
			)
		);
		$service_url = MS_SRURL . 'new';
		MSAPI::putMSData($service_url, $postdata, $json, $salesreturnData);
		
		$logger -> write ('createReturn.json - ' . $json);
		//echo $salesreturnData;
		$service_url = MS_SRURL;
		MSAPI::postMSData($service_url, $salesreturnData, $back, $back2);
		//echo $back2;
		$logger -> write ('createReturn.back - ' . $back);
		return 'Ok';
	}
	// cancel order
	public static function cancelOrder ($orderId)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		//$logger = new Log ('tmp.log');
		$service_url = MS_COURL . $orderId;
		//echo $service_url;
		//$logger->write ($service_url);
		
		$service_url = MS_COURL . $orderId;
		$postdata = array (
			'state' => array (
				'meta' => array (
					'href' => MS_CANCEL_STATE,
					'type' => 'state',
					'mediaType' => 'application/json'
				)
			)
		);
		// get agent
		MSAPI::putMSData($service_url, $postdata, $backJson, $back);
		//$logger -> write ('back: ' . $back);
		//$logger -> write ('postdata: ' . json_encode($postdata));
		return 'Ok';
		
	}

    public static function postOzonData($service_url, $postdata, &$dataOut, $kaori = false)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		//$logger = new Log('orderozon.log'); //just passed the file name as file_name.log
		
		// REST Header
		$curl_post_header = array (
				'Content-type: application/json', 
				'Client-Id: ' . ($kaori ? OZON_CLIENT_ID_KAORI : OZON_CLIENT_ID),
				'Api-Key: ' . ($kaori ? OZON_API_KEY_KAORI : OZON_API_KEY)
		);

		try {
			//$logger->write("postOzonData.service_url - " . json_encode ($service_url));
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_header);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			$jsonOut = curl_exec($curl);
			//$logger->write("postOzonData.jsonOut - " . json_encode ($jsonOut));
			curl_close($curl);
			$dataOut = json_decode ($jsonOut, true);
 			
		}
		catch(Exception $e) {
			return false;
		}						
		return true;
	}

    public static function postOzonDataBlob($service_url, $postdata, &$dataOut, $kaori = false)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		//$logger = new Log('orderozon.log'); //just passed the file name as file_name.log
		
		// REST Header
		$curl_post_header = array (
				'Content-type: application/json', 
				'Client-Id: ' . ($kaori ? OZON_CLIENT_ID_KAORI : OZON_CLIENT_ID),
				'Api-Key: ' . ($kaori ? OZON_API_KEY_KAORI : OZON_API_KEY)
		);

		try {
			//$logger->write("postOzonData.service_url - " . json_encode ($service_url));
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_header);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			$dataOut = curl_exec($curl);
			//$logger->write("postOzonData.jsonOut - " . json_encode ($jsonOut));
			curl_close($curl);
 			
		}
		catch(Exception $e) {
			return false;
		}						
		return true;
	}


	public static function getOzonOrders ($since, $to, $status, $kaori = false)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log('orderozon.log'); //just passed the file name as file_name.log
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

		$postData = array ('dir' => 'asc',
							'filter' => array ('since' => $since, 'to' => $to, 'status' => $status),
							'limit' => 50,
							'offset' => 0,
							'with' => array ('barcodes' => true));
		$orders = array();
		while (true)
		{	
			$service_url = OZON_MAINURL . 'v2/posting/fbs/list';
			$logger->write("getOzonOrders.service_url - " . json_encode ($service_url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$logger->write("getOzonOrders.postData - " . json_encode ($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			self::postOzonData ($service_url, $postData, $order_list, $kaori);
			$logger->write("getOzonOrders.order_list - " . json_encode ($order_list, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			if (!isset($order_list['result']) || count ($order_list['result']) == 0)
				break;
			$orders = array_merge($orders, $order_list['result']);
			$postData['offset'] += 50;
		}
		$logger->write("getOzonOrders.orders - " . json_encode ($orders, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		if ($kaori)
			foreach ($orders as $key => $order)
				$orders[$key]['organization'] = MS_KAORI;
		return $orders;
		
	}
	
	public function createMSOrder($data) {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log('orderozon.log'); //just passed the file name as file_name.log
		$logger->write("createMSOrder.data - " . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		// add order in moysklad.ru
		
		$positions = array();
		
		if (isset($data['products'])) {
			$service_url = MS_PRODUCTURL . '?filter=code=000-0000';
			MSAPI::getMSData ($service_url, $product0json, $product0);
			$i = 0;
			// найдем нулевой продукт
			$total_goods = 0;			
			foreach ($data['products'] as $product) {
				$service_url = MS_PRODUCTURL . '?filter=code=' . $product['offer_id'];
				MSAPI::getMSData ($service_url, $productMSjson, $productMS);
				if (!isset($productMS['rows'][0]['id']))
				{
					$productMS = $product0;
				}
				//echo ($product_ms['rows'][0]['id']);
				$positions[$i]['quantity'] = (float)$product['quantity'];
				$positions[$i]['price'] = (float)$product['price'] * 100;
				$positions[$i]['discount'] = (float)0;
				$positions[$i]['vat'] = (float)0;
				$positions[$i]['assortment'] = array(
					'meta' => array(
						'href' => MS_PRODUCTURL . $productMS['rows'][0]['id'],
						'type' => 'product',
						'mediaType' => 'application/json'
					)
				);
				$positions[$i]['reserve'] = (float)$product['quantity'];
				$i++;
			}
		}
		// получим штрихкод заказа
		$postdata = array(
			'name' => (string)$data['posting_number'],
			'organization' => array (
				'meta' => array (
					'href' => isset ($data['organization']) ? $data['organization'] : MS_ULLO,
					'type' => 'organization',
					'mediaType' => 'application/json'
				)
			),
			'externalCode' => (string)$data['order_id'],
			'moment' => DateTime::createFromFormat('Y-m-d\TH:i:sO', $data['created_at'])->format('Y-m-d H:i:s'),
			'deliveryPlannedMoment' => DateTime::createFromFormat('Y-m-d\TH:i:sO', $data['shipment_date'])->format('Y-m-d H:i:s'),
			'applicable' => true,
			'vatEnabled' => false,
			'agent' => array(
				'meta' => array (
					'href' => isset ($data['agent']) ? MS_BERU_AGENT : MS_OZON_AGENT,
					'type' => 'counterparty',
					'mediaType' => 'application/json'
				)
			),
			'state' => array(
				'meta' => array(
					'href' => 'https://online.moysklad.ru/api/remap/1.1/entity/customerorder/metadata/states/9d61e479-013c-11e9-9107-504800115e4b',
					'type' => 'state',
					'mediaType' => 'application/json'
				)
			),
			'store' => array(
				'meta' => array(
					'href' => 'https://online.moysklad.ru/api/remap/1.1/entity/store/dd7ce917-4f86-11e6-7a69-8f550000094d',
					'type' => 'store',
					'mediaType' => 'application/json'
				)
			),
			'group' => array(
				'meta' => array(
					'href' => 'https://online.moysklad.ru/api/remap/1.1/entity/group/dd4ce7fe-4f86-11e6-7a69-971100000043',
					'type' => 'group',
					'mediaType' => 'application/json'
				)
			),
			'project' => array(
				'meta' => array(
					'href' => $data['project'],
					'type' => 'project',
					'mediaType' => 'application/json'
				)
			),
			'description' => isset ($data['description']) ? (string)$data['description'] : '',
			'applicable' => isset ($data['applicable']) ? (bool)$data['applicable'] : true,
			'positions' => $positions,
			'attributes' => array(
				// тип оплаты
				0 => array(
					'id' => '2ada6f00-d623-11e8-9109-f8fc0021e4d1',
					'value' => array(
						'meta' => array(
							'href' => 'https://online.moysklad.ru/api/remap/1.1/entity/customentity/e0430541-d622-11e8-9109-f8fc00212299/27155816-dd0b-11e8-9109-f8fc0015616b',
							'type' => 'customentity',
							'mediaType' => 'application/json'
						)
					)
				),
				// время доставки
				1 => array(
					'id' => '1f394750-d62e-11e8-9ff4-3150002139c8',
					'value' => array(
						'meta' => array(
							'href' => 'https://online.moysklad.ru/api/remap/1.1/entity/customentity/e7a5f365-d62d-11e8-9107-50480021c6c8/e0460dd7-d62e-11e8-9ff4-34e8002207d8',
							'type' => 'customentity',
							'mediaType' => 'application/json'
						)
					)
				),
				// способ доставки
				2 => array(
					'id' => '5c01b362-d61f-11e8-9107-504800214d3f',
					'value' => array(
						'meta' => array(
							'href' => isset ($data['agent']) ? 'https://online.moysklad.ru/api/remap/1.1/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/ec17ba6f-f3fd-11e9-0a80-0477001d4b07' : 'https://online.moysklad.ru/api/remap/1.1/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/3172d6aa-6fac-11ea-0a80-02c2000cf9f2',
							'type' => 'customentity',
							'mediaType' => 'application/json'
						)
					)
				),
				// штрихкод
				3 => array(
					'id' => '51ec2167-e895-11e8-9ff4-31500000db84',
					'value' => (string)$data['barcodes']['upper_barcode']
				)
			)
		);
		
		$logger->write('createMSOrder.postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		//echo (json_encode($curl_post_data));
		$service_url = MS_COURL;
		MSAPI::postMSData ($service_url, $postdata, $returnJson, $return);
		$logger->write("createMSOrder.return - " . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
		//$logger->write("curl_response - " . $curl_response);
		
	}

	public function createMSOrder2($data) {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log('orders.log'); //just passed the file name as file_name.log
		$logger->write("createMSOrder2.data - " . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		// add order in moysklad.ru
		
		$positions = array();
		
		if (isset($data['products'])) {
			// найдем нулевой продукт
			$service_url = MS_PRODUCTURL . '?filter=code=000-0000';
			MSAPI::getMSData ($service_url, $product0json, $product0);
			$total_goods = 0;			
			foreach ($data['products'] as $product) {
				if (in_array ($product['sku'], array (MS_DELIVERY_SERVICE, MS_SELFDELIVERY_SERVICE, MS_PICKUP_SERVICE)))
					$service_url = MS_SERVICEURL . '?filter=code=' . $product['sku'];
				else
					$service_url = MS_PRODUCTURL . '?filter=code=' . $product['sku'];
				
				MSAPI::getMSData ($service_url, $productMSjson, $productMS);
				if (!isset($productMS['rows'][0]['id']))
				{
					$productMS = $product0;
				}
				//echo ($product_ms['rows'][0]['id']);
				$positions[] = array (
					'quantity' => (int)$product['quantity'],
					'price' => (int)$product['price'] * 100,
					'discount' => (int)0,
					'vat' => isset ($product['vat']) ? (int)$product['vat'] : (int)0,
					'assortment' => array(
						'meta' => array(
							'href' => $productMS['rows'][0]['meta']['href'],
							'type' => $productMS['rows'][0]['meta']['type'],
							'mediaType' => 'application/json'
						)
					),
					'reserve' => $productMS['rows'][0]['meta']['type'] == 'product' ? (float)$product['quantity'] : 0
				);
			}
		}

		$postdata = array(
			'name' => (string)$data['name'],
			'organization' => array (
				'meta' => array (
					'href' => isset ($data['organization']) ? $data['organization'] : MS_ULLO,
					'type' => 'organization',
					'mediaType' => 'application/json'
				)
			),
			'externalCode' => isset ($data['externalCode']) ? (string)$data['externalCode'] : '',
			'moment' => $data['moment'],
			'deliveryPlannedMoment' => $data['deliveryPlannedMoment'],
			'applicable' => true,
			'vatEnabled' => isset ($data['vatEnabled']) ? $data['vatEnabled'] : false,
			'vatIncluded' => isset ($data['vatIncluded']) ? $data['vatIncluded'] : false,
			'agent' => array(
				'meta' => array (
					'href' => isset ($data['agent']) ? $data['agent'] : MS_OZON_AGENT,
					'type' => 'counterparty',
					'mediaType' => 'application/json'
				)
			),
			'state' => array(
				'meta' => array(
					'href' => $data['state'],
					'type' => 'state',
					'mediaType' => 'application/json'
				)
			),
			'store' => array(
				'meta' => array(
					'href' => 'https://online.moysklad.ru/api/remap/1.1/entity/store/dd7ce917-4f86-11e6-7a69-8f550000094d',
					'type' => 'store',
					'mediaType' => 'application/json'
				)
			),
			'group' => array(
				'meta' => array(
					'href' => 'https://online.moysklad.ru/api/remap/1.1/entity/group/dd4ce7fe-4f86-11e6-7a69-971100000043',
					'type' => 'group',
					'mediaType' => 'application/json'
				)
			),
			'project' => array(
				'meta' => array(
					'href' => $data['project'],
					'type' => 'project',
					'mediaType' => 'application/json'
				)
			),
			'description' => isset ($data['description']) ? (string)$data['description'] : '',
			'applicable' => isset ($data['applicable']) ? (bool)$data['applicable'] : true,
			'positions' => $positions
		);
		
		if (isset ($data['attributes']))
		{
			$postdata['attributes'] = array();
			foreach ($data['attributes'] as $attribute => $attributeValue)
			{
				if (in_array ($attribute, [MS_FIO_ATTR, MS_PHONE_ATTR, MS_ADDRESS_ATTR, MS_MPAMOUNT_ATTR]))
					$postdata['attributes'][] = array (
						'id' => $attribute,
						'value' => $attributeValue
					);
					
				if (in_array ($attribute, [MS_DELIVERY_ATTR, MS_DELIVERYTIME_ATTR, MS_PAYMENTTYPE_ATTR]))
					$postdata['attributes'][] = array (
						'id' => $attribute,
						'value' => array(
							'meta' => array(
								'href' => $attributeValue,
								'type' => 'customentity',
								'mediaType' => 'application/json'
							)
						)
					);
			}
		}
		
		$logger->write('createMSOrder2.postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		//echo (json_encode($curl_post_data));
		$service_url = MS_COURL;
		MSAPI::postMSData ($service_url, $postdata, $returnJson, $return);
		$logger->write("createMSOrder2.return - " . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
		//$logger->write("curl_response - " . $curl_response);
		
	}
	
	public function packOzonOrder($data, $kaori = false) {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log('orderozon.log'); //just passed the file name as file_name.log
		$logger->write("packOzonOrder.data - " . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$postdata = array(
			'packages' => array (
				0 => array (
					'items' => array ()
				)
			),
			'posting_number' => $data['posting_number']
		);

		if (isset($data['products']))
			foreach ($data['products'] as $product)
				$postdata['packages'][0]['items'][] = array ('quantity' => $product['quantity'], 'sku' => $product['sku']);
		
		$logger->write('packOzonOrder.postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		//echo (json_encode($curl_post_data));
		$service_url = OZON_MAINURL . 'v2/posting/fbs/ship';
		$i = 0;
		while (true)
		{
			$i += 1;
			self::postOzonData ($service_url, $postdata, $return, $kaori);
			$logger->write("packOzonOrder.return - " . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			if (isset ($return['error']) && $i < 3)
			{
				usleep (500000);
				continue;
			}
			break;
		}
		return $return;
		//$logger->write("curl_response - " . $curl_response);
		
	}
	public function getPackageLable($data, $msOrder, $kaori = false) {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log('orderozon.log'); //just passed the file name as file_name.log
		$logger->write("getPackageLable.data - " . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$postdata = array(			
			'posting_number' => array (0 => $data['posting_number'])
		);

		$logger->write('getPackageLable.$postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		//echo (json_encode($curl_post_data));
		$service_url = OZON_MAINURL . 'v2/posting/fbs/package-label';
		self::postOzonDataBlob ($service_url, $postdata, $pdf, $kaori);
		//$logger->write("getPackageLable.return - " . json_encode ($return));
		// put pdf to MS
		$postdata = array (
			'attributes' => array(
				0 => array (
					'id' => '87118b0a-fe2b-11e8-9107-5048000941de',
					'name' => $data['order_number'],
					'type' => 'file',
					'file' => array (
						'filename' => $shipment['order_number'] . '.pdf',
						'content' => $pdf
					)
				)
			)
		);
		$service_url = MS_COURL . $msOrder;
		MSAPI::putMSData($service_url, $postdata, $returnJson, $return);
		
		return $return;
		//$logger->write("curl_response - " . $curl_response);
		
	}
	
	public function cancelMSOrder($data) {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log('orders.log'); //just passed the file name as file_name.log
		$logger->write("cancelMSOrder.data - " . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		// get order in moysklad.ru
		
		$service_url = MS_COURL . '?filter=name=' . $data['posting_number'];
		$logger->write("cancelMSOrder.service_url - " . json_encode ($service_url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		MSAPI::getMSData ($service_url, $productMSjson, $orderMS);
		$logger->write("cancelMSOrder.orderMS - " . json_encode ($orderMS, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		if (!isset($orderMS['rows'][0]['id']))
		{
			return false;
		}

		$valid_order_statuses = array (MS_NEW_STATE, MS_MPNEW_STATE, MS_CONFIRM_STATE, MS_CONFIRMGOODS_STATE);
		$return = array(
			'cancelled' => 0,
			'marked' => 0
		);
		if (count($orderMS)>0&&isset($orderMS['rows'][0]['state']['meta']['href'])?
			in_array ($orderMS['rows'][0]['state']['meta']['href'], $valid_order_statuses):false) {

			$postdata = array (
				'state' => array(
					'meta' => array(
						'href' => MS_CANCEL_STATE,
						'type' => 'state',
						'mediaType' => 'application/json'
					)
				),
				'attributes' => array(
					0 => array (
						'id' => MS_CANCEL_ATTR,
						'value' => true
					)
				)
			);
			$return ['cancelled'] += 1;
		} else {
			$postdata = array (
				'attributes' => array(
					0 => array (
						'id' => '05d3f45a-518d-11e9-9109-f8fc000a2635',
						'value' => true
					)
				)
			);
			$return ['marked'] += 1;
		}
		$service_url = MS_COURL . $orderMS['rows'][0]['id'];
		$logger->write("cancelMSOrder.service_url - " . json_encode ($service_url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		MSAPI::putMSData($service_url, $postdata, $backJson, $back);
		$logger->write("cancelMSOrder.back - " . $backJson);
		return $return;
		
		
	}
	// get claim order list
    public static function getClaimList ($dateFrom, $dateTo, $agent, $organization)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log('orders.log');
		
		// get orders
		if ($agent = 'Goods')
			$agentHref = 'agent=' . MS_GOODS_AGENT . ';';
		if ($agent = 'Beru')
			$agentHref = 'agent=' . MS_BERU_AGENT . ';';
		if ($agent = 'Ozon')
			$agentHref = 'agent=' . MS_OZON_AGENT . ';';

		if ($organization = '4cleaning')
			$organizationHref = 'organization=' . MS_4CLEANING . ';';
		if ($organization = '10kids')
			$organizationHref = 'organization=' . MS_10KIDS . ';';
		if ($organization = 'Ullo')
			$organizationHref = 'organization=' . MS_ULLO . ';';
		
		$momentFrom = $dateFrom ? 'moment%3E=' . $dateFrom . '%2000:00:00;' : '';
		$momentTo = $dateTo ? 'moment%3C=' . $dateTo . '%2023:59:59;' : '';
		
		$state = 'state=' . MS_SHIPPED_MP_STATE . ';';
		
		$offset = 0;
		$orders = array();
		while (true)
		{
			$service_url = MS_COURL . '?filter=' . $momentFrom . $agentHref . $organizationHref . $state . '&limit=' . MS_LIMIT . '&offset=' . $offset;
			//echo $service_url;
			$logger->write ('getClaimList.service_url - ' . $service_url);
			MSAPI::getMSData($service_url, $response_ordersJson, $response_orders);
			$logger->write ('getClaimList.response_ordersJson - ' . $response_ordersJson);
			
			if (isset ($response_orders['rows'][0])) {
				$offset += MS_LIMIT;
				$orders = array_merge ($orders, $response_orders['rows']);
			}
			else
				break;
		}
		$return = array();
		foreach ($orders as $order) {
			if ($order['sum'] != $order['payedSum'])
			{
				$service_url = $order['meta']['href'] . '/positions';
				MSAPI::getMSData ($service_url, $positionsJson, $positions);
				foreach ($positions['rows'] as $position)
				{
					$service_url = $position['assortment']['meta']['href'];
					MSAPI::getMSData($service_url, $productJson, $product);
					$return[] = array (
						'order' => $order,
						'position' => $position,
						'product' => $product
					);
				}
			}
			//$logger->write (json_encode($order, true));
		}
		
		return $return;
 	}


}

?>