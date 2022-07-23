<?php
/**
 *
 * @class PaymentsMS
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class PaymentsMS
{
	private $logger;
	private $apiMSClass;

	private $cache = array ();

	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/api/apiMS.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

		date_default_timezone_set('Europe/Moscow');
		$this->logger = new Log('classes-ms-paymentsMS.log');
		$this->apiMSClass = new APIMS();
	}	
	// get payments
    public function getPayments($filter)
    {
		$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PAYMENTIN . '?filter=' . $filter;
		$this->logger->write ('01-getPayments.filter - ' . json_encode ($filter, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$return = $this->apiMSClass->getData($service_url);
		$this->logger->write ('02-getPayments.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return['rows'];
	}
	// create payment
    public function createPayment($payment)
    {
		$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PAYMENTIN;
		$this->logger->write ('01-createPayment.payment - ' . json_encode ($payment, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$return = $this->apiMSClass->postData($service_url, $payment);
		$this->logger->write ('02-createPayment.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
	}
	// update payment
    public function updatePayment($paymentId, $payment)
    {
		$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PAYMENTIN . '/' . $paymentId;
		$this->logger->write ('01-updatePayment.payment - ' . json_encode ($payment, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$return = $this->apiMSClass->putData($service_url, $payment);
		$this->logger->write ('02-updatePayment.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
	}
	
	// return payments by order
    public function matchPaymentsByOrder($payment, $order)
    {
		$return = false;
		$this->logger->write ('01-matchPaymentsByOrder.payment - ' . json_encode ($payment, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$this->logger->write ('02-matchPaymentsByOrder.order - ' . json_encode ($order, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		foreach ($order['payments'] as $orderPayment)
		{
			$service_url = $orderPayment['meta']['href'];
			$paymentData = $this->apiMSClass->getData ($service_url);
			$this->logger->write ('03-matchPaymentsByOrder.paymentData - ' . json_encode ($paymentData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			
			if ((isset ($paymentData['incomingNumber']) && isset ($paymentData['incomingDate'])) ? ($paymentData['incomingNumber'] == $payment['incomingNumber'] && (explode(' ', $paymentData['incomingDate']))[0] == $payment['incomingDate']) : false)
			{
				if (isset($paymentData['attributes']))
				{
					$idKey = array_search (MS_API_PAYMENTIN_ATTRIBUTE_PAYTYPE, array_column ($paymentData['attributes'], 'id'));
					if ($idKey !== false ? $paymentData['attributes'][$idKey]['value'] == $payment['paymentType'] : false)
						return $paymentData;
				}
			}
		}
		return $return;
	}
	
	// return storno payments by order
    public function matchStornoPaymentsByOrder($payment, $order)
    {
		$return = false;
		$this->logger->write ('01-matchStornoPaymentsByOrder.payment - ' . json_encode ($payment, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$this->logger->write ('02-matchStornoPaymentsByOrder.order - ' . json_encode ($order, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		foreach ($order['payments'] as $orderPayment)
		{
			$service_url = $orderPayment['meta']['href'];
			$paymentData = $this->apiMSClass->getData ($service_url);
			$this->logger->write ('03-matchStornoPaymentsByOrder.paymentData - ' . json_encode ($paymentData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			if (isset($paymentData['attributes']))
			{
				$idKey = array_search (MS_API_PAYMENTIN_ATTRIBUTE_PAYTYPE, array_column ($paymentData['attributes'], 'id'));
				if ($idKey !== false ? $paymentData['attributes'][$idKey]['value'] == $payment['paymentType'] : false)
					return $paymentData;
			}
		}
		return $return;
	}
	// delete payments
    public function deletePayments($payment)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		//$this->logger = new Log ('payments.log');
		if (isset ($payment['meta']['href']))
		{	
			$service_url = $payment['meta']['href'];
			$result = MSAPI::deleteMSData ($service_url);			
		}
		else 
		{
			$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PAYMENTIN . '/delete';
			$return = $this->apiMSClass->postData($service_url, $payment);
		}
		//$this->logger->write ('deletePayments.service_url - ' . json_encode ($service_url, JSON_UNESCAPED_SLASHES));
		//$this->logger->write ('deletePayments.result - ' . json_encode ($result, JSON_UNESCAPED_SLASHES));
	}
}

?>