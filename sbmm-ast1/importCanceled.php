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
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');

	$log = new Log('sbmm-ast1 - importCanceled.log');
	
	$sbmmOrdersClass = new \Classes\Sbermegamarket\Order(SBMM_SHOP_AST1);
	$dateFrom = date("Y-m-d", strtotime ("now")) . 'T00:00:00+03:00';
	$dateTo = date("Y-m-d", strtotime ("-30 days")) . 'T23:59:59+03:00';
	
	$orders = $sbmmOrdersClass->searchOrders(['MERCHANT_CANCELED', 'CUSTOMER_CANCELED'], $dateFrom, $dateTo);

	$conn = Db::get_connection();
	$cancelled = 0;
	$cancelledMarked = 0;
	$alreadyCancelled = 0;
	$noOrderFound = 0;
	$ordersMSClass = new OrdersMS(); 
	foreach($orders['shipments'] as $shipment)
	{
		$sql = 'select orderId from cancelled_orders where orderId == "' . $shipment['shipmentId'] . '"';
		$result = Db::exec_query_array($sql);
		if ($result)
		{
			$alreadyCancelled++;
			continue;
		}
		$sql = 'insert into cancelled_orders (orderId) values ("' . $shipment['shipmentId'] . '")';
		Db::exec_query($sql);
		$orderData = $ordersMSClass->findOrders(array('name' => $shipment['shipmentId']));
		if(count($orderData) === 0)
		{
			$noOrderFound++;
			continue;
		}
		$updateData = array (
			'attributes' => array(
				0 => array (
					'meta' => array(
						'href' => MS_MPCANCEL_ATTR,
						'type' => 'attributemetadata',
						'mediaType' => 'application/json'
					),
					'value' => true
				)
			)
		);
		if(in_array($orderData[0]['state']['meta']['href'], [MS_NEW_STATE,MS_MPNEW_STATE,MS_CONFIRM_STATE,MS_CONFIRMBERU_STATE]))
		{
			$updateData['state'] = array (
				'meta' => array(
					'href' => MS_CANCEL_STATE,
					'type' => 'state',
					'mediaType' => 'application/json'
				)
			);
			$cancelled++;
		}
		else $cancelledMarked++;
		$ordersMSClass->updateCustomerorder($orderData[0]['id'], $updateData);
	}
	echo 'Orders cancelled: ' . $cancelled . ', marked as cancelled: ' . $cancelled . ', already processed: ' . $alreadyCancelled . ', not found: '. $noOrderFound;
?>








	<?php
/**
 * @class ControllerFeed2Yamarket
 * @author Yandex.Money & Alexander Toporkov <toporchillo@gmail.com>
 *
 * @property ModelToolImage $model_tool_image
 * @property Loader $load
 * @property Config $config
 * @property ModelLocalisationCurrency $model_localisation_currency
 * @property \Cart\Currency $currency
 * @property \Cart\Tax $tax
 * @property ModelCatalogProduct $model_catalog_product
 */
class Controllerextensionimportorderscanceled extends Controller
{
    public function index()
    {
		$client_id = MS_LOGIN;
		$client_pass = MS_PASS;
		$curl_post_headerms = array (
				'Content-type: application/json',
				'Accept-Encoding: gzip',
				'Authorization: Basic ' . base64_encode("$client_id:$client_pass")
		);
		
		header('Content-Type: text/html; charset=UTF-8');
		$token = GOODS_TOKEN;
		$curl_post_headergoods = array (
				'Content-type: application/json'
		);
		if (isset($_GET['status']))
			$paramStatus = array($_GET['status']);
		else
			$paramStatus = array ('MERCHANT_CANCELED', 'CUSTOMER_CANCELED');
		if (isset($_GET['from']))
			$paramFrom = ($_GET['from']);
		else
			$paramFrom = 30;
		if (isset($_GET['to']))
			$paramTo = ($_GET['to']);
		else
			$paramTo = -1;
		$dateFrom = date("Y-m-d", strtotime ("-" . $paramFrom . " days")) . 'T00:00:00+03:00';
		$dateTo = date("Y-m-d", strtotime ("-" . $paramTo . " days")) . 'T23:59:59+03:00';
		// post body for search engine
		$curl_post_data = array(
			'data' => array (
				'token' => $token,
				'dateFrom' => $dateFrom,
				'dateTo' => $dateTo,
				'statuses' => $paramStatus//('MERCHANT_CANCELED', 'CUSTOMER_CANCELED')
				//'statuses' => array ('SHIPPED')
			),
			'count' => 100,
			'meta' => array()
		);
		$log = new Log('importorders - canceled.log');
		// service request post
		$curl_search = curl_init('https://partner.sbermegamarket.ru/api/market/v1/orderService/order/search');
		curl_setopt($curl_search, CURLOPT_HTTPHEADER, $curl_post_headergoods);
		curl_setopt($curl_search, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($curl_search, CURLOPT_POST, true);
		curl_setopt($curl_search, CURLOPT_POSTFIELDS, json_encode($curl_post_data));
		$curl_response_search = curl_exec($curl_search);
		$response_search = json_decode ($curl_response_search, true);
		curl_close($curl_search);
		
		$log->write(__LINE__ . ' curl_response_search - ' . $curl_response_search);
		$valid_order_statuses = array ("https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/dd93e5be-4f86-11e6-7a69-8f5500000968", "https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/22a29bbb-0176-11e9-912f-f3d400132dd9", "https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/dd93ea57-4f86-11e6-7a69-8f5500000969", "https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/9d61e479-013c-11e9-9107-504800115e4b");
		$shipments_filter = '';
		$shipments_arr = array();
		foreach ($response_search['data']['shipments'] as $shipment) {
			$order_query = $this->db->query("select * from deleted_orders where order_number = '" . (string)$shipment . "'");
			if (!$order_query->num_rows)
			{
				$this->db->query("insert into deleted_orders values ('" . (string)$shipment . "')");
				$shipments_filter .= 'name=' . $shipment . ';';
				$shipments_arr[] = $shipment;
			}
		}
		
		if (count ($shipments_arr)>0) {
			//find orders
			$service_url = 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder?filter=' . $shipments_filter;
			$curl_order = curl_init($service_url);
			curl_setopt($curl_order, CURLOPT_HTTPHEADER, $curl_post_headerms);
			curl_setopt($curl_order, CURLOPT_RETURNTRANSFER, true); 
			$curl_response_order = gzdecode(curl_exec($curl_order));
			$response_order = json_decode ($curl_response_order, true);
			curl_close($curl_order);
		
			foreach ($response_order['rows'] as $shipment) {

	//			echo ($curl_response_print.'<br>');
				
				//echo ($img.'<br>');
				//update ms order
				if (in_array ($shipment['state']['meta']['href'], $valid_order_statuses)) {
					$post_data = array (
						'state' => array(
							'meta' => array(
								'href' => 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/dd940025-4f86-11e6-7a69-8f550000096e',
								'type' => 'state',
								'mediaType' => 'application/json'
							)
						),
						'attributes' => array(
							0 => array (
								'meta' => array(
									'href' => 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/attributes/05d3f45a-518d-11e9-9109-f8fc000a2635',
									'type' => 'attributemetadata',
									'mediaType' => 'application/json'
								),
								'value' => true
							)
						)
					);
				} else {
					$post_data = array (
						'attributes' => array(
							0 => array (
								'meta' => array(
									'href' => 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/attributes/05d3f45a-518d-11e9-9109-f8fc000a2635',
									'type' => 'attributemetadata',
									'mediaType' => 'application/json'
								),
								'value' => true
							)
						)
					);
				}
				
				$service_url = 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/'. $shipment['id'];
				$curl = curl_init($service_url);
				curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerms);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
				curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($post_data));
				$curl_response = gzdecode(curl_exec($curl));
				curl_close($curl);
			}
		}		
		echo 'Cancelled: ' . (string)(isset ($response_order['rows'])? (count ($response_order['rows'])) : 0) . ' from ' . (string)(count ($response_search['data']['shipments'])) . ' not found: ' . (string)(count ($shipments_arr) - (isset ($response_order['rows'])? (count ($response_order['rows'])) : 0)) . ' already processed: ' . (string)(count ($response_search['data']['shipments']) - count ($shipments_arr));
 	}
}
