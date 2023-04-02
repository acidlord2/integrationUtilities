<?php
	/**
	 * Creates new order
	 *
	 * @class ControllerExtensionBeruOrder
	 * @author GPOLYAN <acidlord@yandex.ru>
	 */
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/api/apiMS.php');
	
	$logger = new Log('beru-aruba - order - status.log'); //just passed the file name as file_name.log
	$logger->write(__LINE__ . ' _GET - ' . json_encode ($_GET, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	// check auth-token
	if (isset($_GET['auth-token']) ? (string)$_GET['auth-token'] != Settings::getSettingsValues('beru_auth_token_21994237') : true)
	{
		header('HTTP/1.0 403 Forbidden');
		echo 'You are forbidden!';
		return;
	}
	// check fake
	$fake = isset($_GET['fake']) ? (bool)$_GET['fake'] : false;
	
	if (($_SERVER['REQUEST_METHOD'] != 'POST'))
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

	if (!isset ($data['order']['status']))
	{
		header('HTTP/1.0 400 Bad Request');
		echo 'Missing required parameter "status"';
		return;
	}

	if ($data['order']['status'] == 'PROCESSING' && $data['order']['substatus'] == 'STARTED')
	{
		if ((int)date('G') >= 9 && $data['order']['delivery']['dates']['fromDate'] == date ('d-m-Y'))
		{
			$order_data = array();
			// статус
			$order_data['state'] = array(
				'meta' => array(
					'href' => MS_CONFIRMBERU_STATE,
					'type' => 'state',
					'mediaType' => 'application/json'
				)
			);
			
			$logger->write(__LINE__ . ' order_data - ' . json_encode ($order_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				
			$orderMS = Orders::findOrder($data['order']['id']);
			$orderUpd = Orders::updateOrder($orderMS['id'], $order_data);
			$logger->write(__LINE__ . ' orderUpd - ' . json_encode ($orderUpd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		}
	}
	
	if ($data['order']['status'] != 'CANCELLED' && $data['order']['status'] != 'DELIVERED')
		return;

	$order = Orders::findOrder($data['order']['id']);
	
	if ($order && $data['order']['status'] == 'CANCELLED') {
		if ($order['state']['meta']['href'] == MS_CONFIRMBERU_STATE || $order['state']['meta']['href'] == MS_CONFIRM_STATE || $order['state']['meta']['href'] == MS_MPNEW_STATE)
			
			$post_data = array (
				'state' => array(
					'meta' => array(
					    'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDERSTATE . '/' . MS_CANCEL_STATE_ID,
						'type' => 'state',
						'mediaType' => 'application/json'
					)
				),
				'attributes' => array(
					0 => array (
					    'meta' => APIMS::createMeta (MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_MPCANCEL_ATTR_ID, 'attributemetadata'),
						'value' => true
					)
				)
			);
		else
			$post_data = array (
				'attributes' => array(
					0 => array (
					    'meta' => APIMS::createMeta (MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_MPCANCEL_ATTR_ID, 'attributemetadata'),
						'value' => true
					)
				)
			);
		
		$return = Orders::updateOrder ($order['id'], $post_data);
	}
	
	else if ($order && $data['order']['status'] == 'DELIVERED ') {
		$post_data = array (
			'state' => array(
				'meta' => array(
				    'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDERSTATE . '/' . MS_DELIVERED_STATE_ID,
					'type' => 'state',
					'mediaType' => 'application/json'
				)
			)
		);
		$return = Orders::updateOrder ($order['id'], $post_data);
	}
	
	$logger->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>
