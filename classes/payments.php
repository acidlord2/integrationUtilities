<?php
/**
 *
 * @class Payments
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class Payments
{
	// create payment
    public static function createPayment($payment)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/api/apiMS.php');
		
		$service_url = MS_PAYINURL;
		$postdata = array(
			//'name' => (string)$payment['number'],
			'owner' => array (
				'meta' => array (
					'href' => $payment['ownerId']['href'],
					'type' => 'employee',
					'mediaType' => 'application/json'
				)
			),
			'organization' => array (
				'meta' => array (
					'href' => $payment['orgId']['href'],
					'type' => 'organization',
					'mediaType' => 'application/json'
				)
			),
			'organizationAccount' => array (
				'meta' => array (
					'href' => $payment['orgAccId']['href'],
					'type' => 'account',
					'mediaType' => 'application/json'
				)
			),
			'moment' => $payment['date'] . ' 00:00:00',
			'applicable' => true,
			'shared' => false,
			'vatSum' => 0,
			'sum' => (int)($payment['amount'] * 100),
			'agent' => array(
				'meta' => array(
					'href' => $payment['agentId']['href'],
					'type' => 'counterparty',
					'mediaType' => 'application/json'
				)
			),
			'state' => array(
				'meta' => array(
					'href' => MS_PAYIN_STATE,
					'type' => 'state',
					'mediaType' => 'application/json'
				)
			),
			'incomingNumber' => $payment['incomingNumber'],
			'incomingDate' => $payment['incomingDate'] . ' 00:00:00',
			'attributes' => array (
				// Исходная сумма
				0 => array (
				    'meta' => APIMS::createMeta (MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PAYMENTIN . MS_API_ATTRIBUTES . '/' . MS_API_PAYMENTIN_ATTRIBUTE_AMOUNT, 'attributemetadata'),
					'value' => (float)$payment['amount']
				),
				// Тип платежа
				1 => array (
				    'meta' => APIMS::createMeta (MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PAYMENTIN . MS_API_ATTRIBUTES . '/' . MS_API_PAYMENTIN_ATTRIBUTE_PAYTYPE, 'attributemetadata'),
					'value' => (string)$payment['paymentType']
				)
			),
			// create orders align
			'operations' => array(
				0 => array(
					'meta' => array(
						'href' => MS_COURL . $payment['orderId'],
						'type' => 'customerorder',
						'mediaType' => 'application/json'
					),
					'linkedSum' => (int)($payment['amount'] * 100)
				)
			)
		);
		$logger = new Log ('classes - payments.log');
		//echo json_encode($postdata, JSON_UNESCAPED_SLASHES);
		MSAPI::postMSData($service_url, $postdata, $jsonOut, $back);
		if (isset($back['errors']))
		{
			$logger->write (__LINE__ . ' createPayment.back - ' . json_encode ($back, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			return false;
		}
		//$logger->write ('createPayment.back - ' . json_encode ($back, JSON_UNESCAPED_SLASHES));
		return true;
	}
	// update payment
    public static function updatePayment($paymentId, $payment)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/api/apiMS.php');
		
		$service_url = MS_PAYINURL . $paymentId;
		$postdata = array(
			'sum' => (int)$payment['amount'] * 100,
			'attributes' => array (
				// Номер ПП сторно
				0 => array (
				    'id' => APIMS::createMeta (MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PAYMENTIN . MS_API_ATTRIBUTES . '/' . MS_API_PAYMENTIN_ATTRIBUTE_STORNONUMBER, 'attributemetadata'),
					'value' => (string)$payment['incomingNumber']
				),
				// Дата ПП сторно
				1 => array (
				    'id' => APIMS::createMeta (MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PAYMENTIN . MS_API_ATTRIBUTES . '/' . MS_API_PAYMENTIN_ATTRIBUTE_STORNODATE, 'attributemetadata'),
					'value' => $payment['incomingDate'] . ' 00:00:00'
				)
			),
			// create orders align
			'operations' => array(
				0 => array(
					'meta' => array(
						'href' => MS_COURL . $payment['orderId'],
						'type' => 'customerorder',
						'mediaType' => 'application/json'
					),
					'linkedSum' => (int)$payment['amount'] * 100
				)
			)
		);
		$logger = new Log ('classes - payments.log');
		//echo json_encode($postdata, JSON_UNESCAPED_SLASHES);
		MSAPI::putMSData($service_url, $postdata, $jsonOut, $back);
		if (isset($back['errors']))
		{
			$logger->write (__LINE__ . ' updatePayment.back - ' . json_encode ($back, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			return false;
		}
		return true;
	}
	
	// return payments by order
    public static function findPayments($payments)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		
		$return = false;
		$logger = new Log ('classes - payments.log');
		$logger->write (__LINE__ . ' findPayments.payments - ' . json_encode ($payments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		foreach ($payments['payments'] as $payment)
		{
			$service_url = $payment['meta']['href'];
			//$logger->write ('findPayments.service_url - ' . json_encode ($service_url, JSON_UNESCAPED_SLASHES));
			MSAPI::getMSData ($service_url, $jsonOut, $arrayOut);
			$logger->write (__LINE__ . ' findPayments.arrayOut - ' . json_encode ($arrayOut, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$logger->write (__LINE__ . ' findPayments.arrayOut[incomingDate] - ' . (explode(' ', $arrayOut['incomingDate']))[0]);
			
			/* if (isset($arrayOut['attributes']))
			{
				$idKey = array_search ('58fcc5f5-87e1-11ea-0a80-014d00155628', array_column ($arrayOut['attributes'], 'id'));
				if ($idKey ? ($arrayOut['attributes'][$idKey]['value'] == '1' || $arrayOut['attributes'][$idKey]['value'] == '2' || $arrayOut['attributes'][$idKey]['value'] == '3'): false)
					Payments::deletePayments($payment);
			} */
			if ($arrayOut['incomingNumber'] == $payments['incomingNumber'] && (explode(' ', $arrayOut['incomingDate']))[0] == $payments['incomingDate'])
			{
			    $logger->write (__LINE__ . ' findPayments.arrayOut - matched');
				if (isset($arrayOut['attributes']))
				{
				    $idKey = array_search (MS_API_PAYMENTIN_ATTRIBUTE_PAYTYPE, array_column ($arrayOut['attributes'], 'id'));
					$logger->write ('findPayments.idKey - ' . $idKey);
					if ($idKey ? $arrayOut['attributes'][$idKey]['value'] == $payments['paymentType'] : false)
						$return[] = $arrayOut;
				}
			}
			else
			{
			    $logger->write (__LINE__ . ' findPayments.jsonOut - ' . $jsonOut);
			}
		}
		return $return;
	}
	
	// return storno payments by order
    public static function findStornoPayments($payments, $paymentType)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		
		$return = false;
		$logger = new Log ('classes - payments.log');
		$logger->write (__LINE__ . ' findStornoPayments.payments - ' . json_encode ($payments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		foreach ($payments as $payment)
		{
			$service_url = $payment['meta']['href'];
			//$logger->write ('findStornoPayments.service_url - ' . json_encode ($service_url, JSON_UNESCAPED_SLASHES));
			MSAPI::getMSData ($service_url, $jsonOut, $arrayOut);
			if (isset($arrayOut['attributes']))
			{
			    $idKey = array_search (MS_API_PAYMENTIN_ATTRIBUTE_PAYTYPE, array_column ($arrayOut['attributes'], 'id'));
				if ($idKey ? $arrayOut['attributes'][$idKey]['value'] == $paymentType : false)
					$return[] = $arrayOut;
			}
			else
			{
			    $logger->write (__LINE__ . ' findStornoPayments.jsonOut - ' . $jsonOut);
			}
		}

		return $return;
	}
	// delete payments
    public static function deletePayments($payment)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		//$logger = new Log ('payments.log');
		$service_url = $payment['meta']['href'];
		//$logger->write ('deletePayments.service_url - ' . json_encode ($service_url, JSON_UNESCAPED_SLASHES));
		$result = MSAPI::deleteMSData ($service_url);			
		//$logger->write ('deletePayments.result - ' . json_encode ($result, JSON_UNESCAPED_SLASHES));
	}
}

?>