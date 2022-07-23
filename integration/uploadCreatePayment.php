<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/payments.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	
	$logger = new Log ('uploadCreatePayment.log');
	//$logger->clear();
	// create payments
	$payments = json_decode (file_get_contents('php://input'), true);
	//$logger->write ('payments - ' . json_encode ($payments, JSON_UNESCAPED_SLASHES));
	$logger->write ('start - ' . json_encode ($payments['number'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	//if ((int)explode ('-', $payments ['number'])[1] < 43380)
	//	return;
	
	$orders = array(0 => $payments);
	Orders::findOrders ($orders);
	$payment = $orders[0];

	$logger->write ('findOrders - ' . json_encode ($payments['number'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	$logger->write ('orders - ' . json_encode ($orders, JSON_UNESCAPED_SLASHES));
	$orders = array(0 => $payments);
	$logger->write ('payment - ' . json_encode ($payment, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	//$orderNumber = $_GET["order"];
	$order = array();
	
	if (isset ($payment['catComm']) ? $payment['catComm'] : false)
		$order['catComm'] = $payment['catComm'];
	if (isset ($payment['trComm']) ? $payment['trComm'] : false)
		$order['trComm'] = $payment['trComm'];
	if (isset ($payment['plComm']) ? $payment['plComm'] : false)
		$order['plComm'] = $payment['plComm'];
	if (isset ($payment['logComm']) ? $payment['logComm'] : false)
		$order['logComm'] = $payment['logComm'];
	$order['id'] = $payment['orderId'];
	
	// update order commision
	if ((isset ($order['catComm']) || isset ($order['trComm']) || isset ($order['plComm']) || isset ($order['logComm'])) && isset($order['id']))
	{
		$out = Orders::updateCommision($order);
		if (isset ($out['errors']))
			$logger->write ('Заказ №' . $payment['orderNumber'] . ' не обновлен ' . unicode_decode(json_encode($out['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
		else
			$logger->write ('Комиссия заказа №' . $payment['orderNumber'] . ' успешно обновлена');
	}
	
	//$logger->write ('Обработка заказа №' . $payment['number']);
	//$logger->write ('payment[amount] - ' . $payment['amount']);
	if (isset($payment['amount']) ? $payment['amount'] > 0 : false)
	{
		if (isset ($payment['payments']))
		{
			$currentPayments = Payments::findPayments ($payment);
			$logger->write ('findPayments - ' . json_encode ($payments['number'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			//$logger->write ('updateCreatePayment.currentPayments - ' . json_encode ($currentPayments, JSON_UNESCAPED_SLASHES));
			if ($currentPayments)
				$logger->write ('Платеж ' . $payment ['number'] . ' от ' . $payment['incomingDate'] . ' уже загружен. Удалите его перед загрузкой');
			else
			{
				$out = Payments::createPayment ($payment);
				$logger->write ('createPayment - ' . json_encode ($payments['number'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				if (!isset($out['errors']))
				{
					$logger->write ('Платеж №' . $payment['number'] . ' от ' . $payment['incomingDate'] . ' успешно создан');
				}
				else
				{
					$logger->write ('Платеж №' . $payment['number'] . ' от ' . $payment['incomingDate'] . ' не создан. Ошибка: ' . json_encode ($out['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				}
			}
		}
		else
		{
			$out = Payments::createPayment ($payment);
			$logger->write ('createPayment - ' . json_encode ($payments['number'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			//$logger->write ('out - ' . json_encode ($out, JSON_UNESCAPED_SLASHES));
			if (!isset($out['errors']))
			{
				$logger->write ('Платеж №' . $payment['number'] . ' от ' . $payment['incomingDate'] . ' успешно создан');
			}
			else
			{
				$logger->write ('Платеж №' . $payment['number'] . ' от ' . $payment['incomingDate'] . ' не создан. Ошибка: ' . json_encode ($out['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			}
		}
	}
	// create payment
	else if (isset($payment['amount']) ? $payment['amount'] < 0 : false)
	{
		// find payment
		if (!isset ($payment['payments']))
		{
			$logger->write ('Для сторно отсутствует платеж ' . $payment ['number'] . ' от ' . $payment['incomingDate']);
		} else {
			$currentPayments = Payments::findStornoPayments ($payment['payments'], $payment['paymentType']);
			$logger->write ('findPayments - ' . json_encode ($payments['number'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			//$logger->write ('updateCreatePayment.currentPayments - ' . json_encode ($currentPayments, JSON_UNESCAPED_SLASHES));
			if (!$currentPayments)
				$logger->write ('Для сторно ' . $payment ['number'] . ' от ' . $payment['incomingDate'] . ' не найден платеж');
			else
			{
				$originalAmountKey = array_search ('9b565d44-8745-11ea-0a80-05e80010dfa6', array_column ($currentPayments[0]['attributes'], 'id'));
				$stornoNumberKey = array_search ('58fcc195-87e1-11ea-0a80-014d00155626', array_column ($currentPayments[0]['attributes'], 'id'));
				$stornoDateKey = array_search ('58fcc4fa-87e1-11ea-0a80-014d00155627', array_column ($currentPayments[0]['attributes'], 'id'));
				//$logger->write ('currentPayments - ' . json_encode($currentPayments, JSON_UNESCAPED_SLASHES));
				//$logger->write ('originalAmountKey - ' . json_encode($originalAmountKey, JSON_UNESCAPED_SLASHES));
				//$logger->write ('stornoNumberKey - ' . json_encode($stornoNumberKey, JSON_UNESCAPED_SLASHES));
				//$logger->write ('stornoDateKey - ' . json_encode($stornoDateKey, JSON_UNESCAPED_SLASHES));

				if ($stornoNumberKey ? $currentPayments[0]['attributes'][$stornoNumberKey]['value'] == $payment ['incomingNumber'] : false)
					$logger->write ('Сторно платеж  ' . $payment ['number'] . ' от ' . $payment['incomingDate'] . ' уже проведен');
				else if ($stornoNumberKey ? $currentPayments[0]['attributes'][$stornoNumberKey]['value'] != $payment ['incomingNumber'] : false)
					$logger->write ('Сторно платеж  ' . $payment ['number'] . ' от ' . $payment['incomingDate'] . ' уже проведен другим платежом '  . $currentPayments[0]['attributes'][$stornoNumberKey]['value'] . ' от ' . $currentPayments[0]['attributes'][$stornoDateKey]['value']);
				else
				{
					$payment['amount'] = (int)$currentPayments[0]['attributes'][$originalAmountKey]['value'] + $payment['amount'];
					//$logger->write ('payment - ' . json_encode($payment, JSON_UNESCAPED_SLASHES));
					$out = Payments::updatePayment ($currentPayments[0]['id'], $payment);
					$logger->write ('updatePayment - ' . json_encode ($payments['number'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
					if (!isset($out['errors']))
					{
						$logger->write ('Платеж №' . $payment['number'] . ' от ' . $payment['incomingDate'] . ' успешно сторнирован');
					}
					else
					{
						$logger->write ('Платеж №' . $payment['number'] . ' от ' . $payment['incomingDate'] . ' не сторнрован. Ошибка: ' . json_encode ($out['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
					}
				}
			}
		}
	}
?>

