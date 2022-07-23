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
	$logger = new Log('beruRomashkaStatus.log'); //just passed the file name as file_name.log
	$logger->write("_GET - " . json_encode ($_GET, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	// check auth-token
	if (isset($_GET['auth-token']) ? (string)$_GET['auth-token'] != Settings::getSettingsValues('romashka_beru_auth_token') : true)
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
	
	if ($data['order']['status'] != 'CANCELLED' && $data['order']['status'] != 'DELIVERED')
		return;

	$order = Orders::findOrder($data['order']['id']);
	
	if ($order && $data['order']['status'] == 'CANCELLED') {
		if ($order['state']['meta']['href'] == 'https://online.moysklad.ru/api/remap/1.1/entity/customerorder/metadata/states/9d61e479-013c-11e9-9107-504800115e4b' || $order['state']['meta']['href'] == 'https://online.moysklad.ru/api/remap/1.1/entity/customerorder/metadata/states/dd93ea57-4f86-11e6-7a69-8f5500000969')
			
			$post_data = array (
				'state' => array(
					'meta' => array(
						'href' => 'https://online.moysklad.ru/api/remap/1.1/entity/customerorder/metadata/states/dd940025-4f86-11e6-7a69-8f550000096e',
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
					'href' => 'https://online.moysklad.ru/api/remap/1.1/entity/customerorder/metadata/states/dd93f734-4f86-11e6-7a69-8f550000096c',
					'type' => 'state',
					'mediaType' => 'application/json'
				)
			)
		);
		$return = Orders::updateOrder ($order['id'], $post_data);
	}
	
	$logger->write("return - " . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>
