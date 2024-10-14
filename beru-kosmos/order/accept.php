<?php
/**
 * Creates new order
 *
 * @class ControllerExtensionBeruOrder
 * @author GPOLYAN <acidlord@yandex.ru>
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php');
//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/products.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/apiOrderCache.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
$logger = new Log('beru-kosmos - order - accept.log'); //just passed the file name as file_name.log
$logger->write(__LINE__ . ' _GET - ' . json_encode ($_GET, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

// check auth-token
if (isset($_GET['auth-token']) ? (string)$_GET['auth-token'] != Settings::getSettingsValues('beru_auth_token_' . BERU_API_KOSMOS_CAMPAIGN) : true)
{
	header('HTTP/1.0 403 Forbidden');
	echo 'You are forbidden!';
	return;
}
// check fake
$fake = isset($_GET['fake']) ? (bool)$_GET['fake'] : false;

if ($_SERVER['REQUEST_METHOD'] != 'POST')
{
	header('HTTP/1.0 400 Bad Request');
	echo 'Request must be POST';
	return;
}

$data = json_decode (file_get_contents('php://input'), true);
$logger->write(__LINE__ . ' data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

if (!isset ($data['order']))
{
	header('HTTP/1.0 400 Bad Request');
	echo 'Missing required parameter "order"';
	return;
}

if (!isset ($data['order']['items']))
{
	header('HTTP/1.0 400 Bad Request');
	echo 'Missing required parameter "items"';
	return;
}

$cacheOrder = APIOrderCache::getOrderCache($data['order']['id']);

if (count($cacheOrder)) {
    $logger->write(__LINE__ . ' order ' . $data['order']['id'] . ' is still processing. Returned OK');
    $return ['order']['id'] = (string)$data['order']['id'];
    $return ['order']['accepted'] = true;
    
    header('Content-Type: application/json');
    echo json_encode($return);
    return;
    
}

APIOrderCache::saveOrderCache($data['order']['id'], 'processing');

$ok = true;
$orderClass = new OrdersMS();

$order = $orderClass->findOrders('name=' . $data['order']['id']);
if (isset($order[0]))
{
    $return ['order']['accepted'] = true;
    $return ['order']['id'] = (string)$order[0]['name'];
    
    header('Content-Type: application/json');
    echo json_encode($return);
    return;
}
// prepare data order
$order_data = array();

$fakeOrder = isset ($data['order']['fake']) ? (bool)$data['order']['fake'] : false;

$order_data['name'] = (string)$data['order']['id'];
$order_data['organization'] = array(
    'meta' => array(
        'href' => MS_KOSMOS,
        'type' => 'organization',
        'mediaType' => 'application/json'
    )
);
$order_data['externalCode'] = (string)$data['order']['id'];
$order_data['moment'] = date('Y-m-d H:i:s', strtotime('now'));
if (isset($data['order']['delivery']['shipments'][0]['shipmentDate']))
{
    $order_data['deliveryPlannedMoment'] = DateTime::createFromFormat('d-m-Y', $data['order']['delivery']['shipments'][0]['shipmentDate'])->format('Y-m-d H:i:s');
}
else
{
    if ($ok)
    {
        $ok = false;
        header('HTTP/1.0 400 Bad Request');
    }
    echo 'Shipment_date is not set';
    
}

$order_data['agent'] = array(
    'meta' => array(
        'href' => MS_BERU_AGENT,
        'type' => 'counterparty',
        'mediaType' => 'application/json'
    )
);

$order_data['state'] = array(
    'meta' => array(
        'href' => MS_CONFIRMBERU_STATE,
        'type' => 'state',
        'mediaType' => 'application/json'
    )
);


$return = array();

$order_data['applicable'] = !(bool)$fakeOrder;
$order_data['description'] = $fakeOrder ? 'ТЕСТ ЗАКАЗ' : '';

$order_data['store'] = array(
    'meta' => array(
        'href' => MS_STORE,
        'type' => 'store',
        'mediaType' => 'application/json'
    )
);

$order_data['project'] = array(
    'meta' => array(
        'href' => MS_PROJECT_YANDEX_KOSMOS,
        'type' => 'project',
        'mediaType' => 'application/json'
    )
);

$order_data['vatEnabled'] = false;
$order_data['vatIncluded'] = false;
$order_data['attributes'] = array();

// способ доставки
$order_data['attributes'][] = array (
    'meta' => array (
        'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_DELIVERY_ATTR,
        'type' => 'attributemetadata',
        'mediaType' => 'application/json'
    ),
    'value' => array(
        'meta' => array(
            'href' => MS_SHIPTYPE_BERU,
            'type' => 'customentity',
            'mediaType' => 'application/json'
        )
    )
);
// время доставки
$order_data['attributes'][] = array (
    'meta' => array (
        'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_DELIVERYTIME_ATTR,
        'type' => 'attributemetadata',
        'mediaType' => 'application/json'
    ),
    'value' => array(
        'meta' => array(
            'href' => MS_DELIVERYTIME_VALUE1,
            'type' => 'customentity',
            'mediaType' => 'application/json'
        )
    )
);
// тип оплаты
$order_data['attributes'][] = array (
    'meta' => array (
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
);

$order_data['positions'] = array();
$productClass = new ProductsMS();

foreach ($data['order']['items'] as $item)
{
    // if ($item['vat'] == 'VAT_10' || $item['vat'] == 'VAT_10_110') {
    //     $vat = 10;
    // }
    // else if ($item['vat'] == 'VAT_20' || $item['vat'] == 'VAT_20_120') {
    //     $vat = 20;
    // }
    // else {
    $vat = 0;
    // }
    
    $product = $productClass->findProductsByCode($item['offerId']);
    if (isset($product[0]))
    {
        $order_data['positions'][] = array(
            'assortment' => array(
                'meta' => array(
                    'href' => $product[0]['meta']['href'],
                    'type' => $product[0]['meta']['type'],
                    'mediaType' => 'application/json'
                )
            ),
            'quantity' => $item['count'],
            'price' => (int)(($item['price'] + $item['subsidy']) * 100),
            'vat' => $vat,
            'discount' => (int)0,
            'reserve' => $item['count']
        );
    }
	else
	{
		if ($ok)
		{
			$ok = false;
			header('HTTP/1.0 400 Bad Request');
		}
		echo 'Product sku ' . $item['offerId'] . ' did not found';
		return;
	}
}

if ($ok)
{
    $logger->write(__LINE__ . ' order_data - ' . json_encode ($order_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $order = $orderClass->createCustomerorder ($order_data);
    $logger->write(__LINE__ . ' order - ' . json_encode ($order, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    if (isset ($order['errors'][0]) ? ($order['errors'][0]['code'] == 3006 && $order['errors'][0]['parameter'] == 'name') : false) {
        $order['name'] = $data['order']['id'];
    }
    $return ['order']['id'] = (string)$data['order']['id'];
    $return ['order']['accepted'] = true;
    
	header('Content-Type: application/json');
	echo json_encode($return);
}

?>
