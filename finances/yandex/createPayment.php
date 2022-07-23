<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/paymentsMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	
	$logger = new Log ('finances-yandex-createPayment.log');

	$ordersClass = new OrdersMS();
	$paymentsClass = new PaymentsMS();

	// create payments
	$payment = json_decode (file_get_contents('php://input'), true);
	$logger->write ('01-payment - ' . json_encode ($payment, JSON_UNESCAPED_SLASHES));
	//$logger->write ('start - ' . json_encode ($payments['number'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	//if ((int)explode ('-', $payments ['number'])[1] < 43380)
	//	return;
	
	$order = $ordersClass->findOrders (array ('name' => $payment['orderNumber']));

	$logger->write ('02-order - ' . json_encode ($order, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	if (count ($order) === 0)
	{
		echo 'Заказ ' . $payment['orderNumber'] . 'не найден';
		return;
	}
	if (isset($payment['amount']) ? $payment['amount'] > 0 : false)
	{
		if (isset ($order[0]['payments']))
		{
			$currentPayments = $paymentsClass->matchPaymentsByOrder ($payment, $order[0]);
			//$logger->write ('updateCreatePayment.currentPayments - ' . json_encode ($currentPayments, JSON_UNESCAPED_SLASHES));
			if ($currentPayments)
			{
				echo 'Заказ ' . $payment['orderNumber'] . '. Платеж ' . $payment ['incomingNumber'] . ' от ' . $payment['incomingDate'] . ' уже загружен. Удалите его перед загрузкой';
				return;
			}
		}

		$postData = array(
			'organization' => $order[0]['organization'],
			'organizationAccount' => $order[0]['organizationAccount'],
			'moment' => $payment['date'] . ' 00:00:00',
			'applicable' => true,
			'shared' => false,
			'vatSum' => 0,
			'sum' => (int)($payment['amount'] * 100),
			'agent' => $order[0]['agent'],
			'state' => array(
				'meta' => array(
					'href' => MS_PAYIN_STATE,
					'type' => 'state',
					'mediaType' => 'application/json'
				)
			),
			'incomingNumber' => $payment['incomingNumber'],
			'incomingDate' => $payment['incomingDate'] . ' 00:00:00',
			'paymentPurpose' => isset ($payment['paymentDescription']) ? $payment['paymentDescription'] : '',
			'attributes' => array (
				// Исходная сумма
				0 => array (
					"meta" => array (
						"href" => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PAYMENTIN . MS_API_ATTRIBUTES . '/' . MS_API_PAYMENTIN_ATTRIBUTE_AMOUNT,
						"type" => "attributemetadata",
						"mediaType" => "application/json"
					),
					'value' => (float)$payment['amount']
				),
				// Тип платежа
				1 => array (
					"meta" => array (
						"href" => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PAYMENTIN . MS_API_ATTRIBUTES . '/' . MS_API_PAYMENTIN_ATTRIBUTE_PAYTYPE,
						"type" => "attributemetadata",
						"mediaType" => "application/json"
					),
					'value' => (string)$payment['paymentType']
				)
			),
			// create orders align
			'operations' => array(
				0 => array (
					'meta' => $order[0]['meta'],
					'linkedSum' => (int)($payment['amount'] * 100)
				)
			)
		);

		$out = $paymentsClass->createPayment ($postData);
		$logger->write ('03-out - ' . json_encode ($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		if (!isset($out['errors']))
		{
			echo 'Заказ ' . $payment['orderNumber'] . '. Платеж ' . $payment['incomingNumber'] . ' от ' . $payment['incomingDate'] . ' успешно создан';
			return;
		}
		else
		{
			echo 'Заказ ' . $payment['orderNumber'] . '. Платеж ' . $payment['incomingNumber'] . ' от ' . $payment['incomingDate'] . ' не создан. Ошибка: ' . json_encode ($out['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			return;
		}
		
	}
	// if storno payment
	else if (isset($payment['amount']) ? $payment['amount'] < 0 : false)
	{
		// find payment
		if (!isset ($order[0]['payments']))
		{
			echo 'Заказ ' . $payment['orderNumber'] . '. Для сторно отсутствует платеж ' . $payment ['incomingNumber'] . ' от ' . $payment['incomingDate'];
			return;
		} else {
			$currentPayments = $paymentsClass->matchStornoPaymentsByOrder ($payment, $order[0]);
			$logger->write ('04-currentPayments - ' . json_encode ($currentPayments, JSON_UNESCAPED_SLASHES));
			if (!$currentPayments)
			{
				echo ('Заказ ' . $payment['orderNumber'] . '. Обработка ' . $payment ['incomingNumber'] . ' от ' . $payment['incomingDate'] . ' - не найден платеж для сторнирования');
				return;
			}
			else
			{
				$originalAmountKey = array_search (MS_API_PAYMENTIN_ATTRIBUTE_AMOUNT, array_column ($currentPayments['attributes'], 'id'));
				$stornoNumberKey = array_search (MS_API_PAYMENTIN_ATTRIBUTE_STORNONUMBER, array_column ($currentPayments['attributes'], 'id'));
				$stornoDateKey = array_search (MS_API_PAYMENTIN_ATTRIBUTE_STORNODATE, array_column ($currentPayments['attributes'], 'id'));

				if ($stornoNumberKey ? $currentPayments['attributes'][$stornoNumberKey]['value'] == $payment ['incomingNumber'] : false)
					echo 'Заказ ' . $payment['orderNumber'] . '. Сторно платеж ' . $payment ['incomingNumber'] . ' от ' . $payment['incomingDate'] . ' уже проведен';
				else if ($stornoNumberKey ? $currentPayments['attributes'][$stornoNumberKey]['value'] != $payment ['incomingNumber'] : false)
					echo 'Заказ ' . $payment['orderNumber'] . '. Сторно платеж ' . $payment ['incomingNumber'] . ' от ' . $payment['incomingDate'] . ' уже проведен другим платежом '  . $currentPayments['attributes'][$stornoNumberKey]['value'] . ' от ' . $currentPayments['attributes'][$stornoDateKey]['value'];
				else
				{
					$updateData = array(
						'sum' => (int)($currentPayments['attributes'][$originalAmountKey]['value'] + $payment['amount']) * 100,
						'attributes' => array (
							// Номер ПП сторно
							0 => array (
								"meta" => array (
									"href" => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PAYMENTIN . MS_API_ATTRIBUTES . '/' . MS_API_PAYMENTIN_ATTRIBUTE_STORNONUMBER,
									"type" => "attributemetadata",
									"mediaType" => "application/json"
								),
								'value' => (string)$payment['incomingNumber']
							),
							// Дата ПП сторно
							1 => array (
								"meta" => array (
									"href" => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PAYMENTIN . MS_API_ATTRIBUTES . '/' . MS_API_PAYMENTIN_ATTRIBUTE_STORNODATE,
									"type" => "attributemetadata",
									"mediaType" => "application/json"
								),
								'value' => $payment['incomingDate'] . ' 00:00:00'
							)
						),
						'operations' => array (
							0 => array (
								'meta' => $order[0]['meta'],
								'linkedSum' => (int)($currentPayments['attributes'][$originalAmountKey]['value'] + $payment['amount']) * 100
							)
						)
					);
					
					$out = $paymentsClass->updatePayment ($currentPayments['id'], $updateData);
					
					$logger->write ('03-out - ' . json_encode ($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
					if (!isset($out['errors']) && $out)
					{
						echo 'Заказ ' . $payment['orderNumber'] . '. Платеж №' . $payment['incomingNumber'] . ' от ' . $payment['incomingDate'] . ' успешно сторнирован';
						return;
					}
					else
					{
						echo 'Заказ ' . $payment['orderNumber'] . '. Платеж №' . $payment['incomingNumber'] . ' от ' . $payment['incomingDate'] . ' не сторнрован. Ошибка: ' . json_encode ($out['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
						return;
					}
				}
			}
		}
	}
	else
		echo 'Заказ ' . $payment['orderNumber'] . '. Сумма платежа не указана ' . $payment ['incomingNumber'] . ' от ' . $payment['incomingDate'];
?>

