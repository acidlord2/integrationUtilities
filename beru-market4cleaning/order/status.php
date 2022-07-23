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
	$logger = new Log('beru-market4cleaning-status.log'); //just passed the file name as file_name.log
	$logger->write("_GET - " . json_encode ($_GET, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	// check auth-token
	if (isset($_GET['auth-token']) ? (string)$_GET['auth-token'] != Settings::getSettingsValues('beru_auth_token_22113023') : true)
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
	$logger->write("data - " . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
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
		$order_data = array();
		
		// фио
		if (isset($data['order']['buyer']['lastName']) || isset ($data['order']['buyer']['firstName']))
			$order_data['attributes'][] = array ( 
				'id' => MS_FIO_ATTR,
				'value' => (isset($data['order']['buyer']['lastName']) ? $data['order']['buyer']['lastName'] : '') . (isset ($data['order']['buyer']['firstName']) ? ' ' . $data['order']['buyer']['firstName'] : '')
			);
		// телефон
		if (isset ($data['order']['buyer']['phone']))
			$order_data['attributes'][] = array (
				'id' => MS_PHONE_ATTR,
				'value' => $data['order']['buyer']['phone']);
		
		$logger->write("04 order_data - " . json_encode ($order_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		if (isset ($order_data['attributes']))
		{
			
			$orderMS = Orders::findOrder($data['order']['id']);
			$orderUpd = Orders::updateOrder($orderMS['id'], $order_data);
			$logger->write("05 orderUpd - " . json_encode ($orderUpd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		}
	}
	
	if ($data['order']['status'] != 'CANCELLED' && $data['order']['status'] != 'DELIVERED')
		return;

	$order = Orders::findOrder($data['order']['id']);
	
	if ($order && $data['order']['status'] == 'CANCELLED') {
		if ($order['state']['meta']['href'] == MS_CONFIRMGOODS_STATE || $order['state']['meta']['href'] == MS_CONFIRM_STATE || $order['state']['meta']['href'] == MS_NEW_STATE)
			
			$post_data = array (
				'state' => array(
					'meta' => array(
						'href' => MS_CANCEL_STATE,
						'type' => 'state',
						'mediaType' => 'application/json'
					)
				),
				'attributes' => array(
					0 => array (
						'id' => '05d3f45a-518d-11e9-9109-f8fc000a2635',
						'value' => true
					)
				)
			);
		else
			$post_data = array (
				'attributes' => array(
					0 => array (
						'id' => '05d3f45a-518d-11e9-9109-f8fc000a2635',
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
					'href' => MS_DELIVERED_STATE,
					'type' => 'state',
					'mediaType' => 'application/json'
				)
			)
		);
		$return = Orders::updateOrder ($order['id'], $post_data);
	}
	
	$logger->write("return - " . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>
