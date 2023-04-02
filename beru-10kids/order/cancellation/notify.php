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
	$log = new Log('beru-10kids - order - cancellation - notify.log'); //just passed the file name as file_name.log
	$log->write(__LINE__ . ' _GET - ' . json_encode ($_GET, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	// check auth-token
	if (isset($_GET['auth-token']) ? (string)$_GET['auth-token'] != Settings::getSettingsValues('beru_auth_token_22064982') : true)
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
	$log->write(__LINE__ . ' data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	if (!isset ($data['order']))
	{
		header('HTTP/1.0 400 Bad Request');
		echo 'Missing required parameter "order"';
		return;
	}

	$order = Orders::findOrder($data['order']['id']);
	
	if ($order) {
		if ($order['state']['meta']['href'] == MS_CONFIRMGOODS_STATE || $order['state']['meta']['href'] == MS_CONFIRM_STATE || $order['state']['meta']['href'] == MS_NEW_STATE || $order['state']['meta']['href'] == MS_MPNEW_STATE)
			
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
					    'id' => APIMS::createMeta (MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_MPCANCEL_ATTR_ID, 'attributemetadata'),
						'value' => true
					)
				)
			);
		else
			$post_data = array (
				'attributes' => array(
					0 => array (
					    'id' => APIMS::createMeta (MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_MPCANCEL_ATTR_ID, 'attributemetadata'),
						'value' => true
					)
				)
			);
		
		$return = Orders::updateOrder ($order['id'], $post_data);
	}
		
	$log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>
