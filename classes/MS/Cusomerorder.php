<?php
/**
 *
 * @class Order
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
namespace MS\Cusomerorder;

use MS\Meta as Meta;
use MS\Api as Api;
use MS\Attribute as AttributeMS;
use MS\File as FileMS;
use MS\CusomerorderPosition as CoPositionMS;
use MS\CusomerorderFilter as CoFilterMS;
use MS\Demand as DemandMS;
use MS\Payment as PaymentMS;

class Customerorder
{
	private $log;
	private $apiMSClass;
	
	private Meta $meta;
	private string $name;
	private string $id;
	private float $sum;
	private string $accountId;
	private string $syncId;
	private DateTime $updated;
	private DateTime $deleted;
	private string $description;
	private string $code;
	private string $externalCode;
	private DateTime $moment;
	private bool $applicable;
	private bool $vatEnabled;
	private bool $vatIncluded;
	private Meta $rate;
	private Meta $owner;
	private bool $shared;
	private Meta $group;
	private Meta $organization;
	private Meta $store;
	private Meta $agent;
	private Meta $contract;
	private Meta $state;
	private Meta $organizationAccount;
	private Meta $agentAccount;
	private array $attributes;
	private array $files;
	private DateTime $created;
	private bool $printed;
	private bool $published;
	private float $vatSum;
	private array $positions;
	private DateTime $deliveryPlannedMoment;
	private float $payedSum;
	private float $shippedSum;
	private float $invoicedSum;
	private float $reservedSum;
	private Meta $project;
	private string $taxSystem;
	private array $demands;
	private array $payments;
	
	
	public function __construct($order = null)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/Api.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/Meta.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/OrderPosition.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

		date_default_timezone_set('Europe/Moscow');
		$this->logger = new Log('classes - MS - Customerorder.log');
		$this->apiMSClass = new ApiMS();
		
		if ($order == null) {
		    return;
		}
		
		$this->name = $order['name'];
		$this->id = $order['id'];
		$this->externalCode = $order['externalCode'];
		$this->applicable = $order['applicable'];
		$this->sum = floatval($order['sum']) / 100;
		$this->accountId = $order['accountId'];
		$this->owner = $order['accountId'];
		$this->shared = $order['shared'];
		$this->printed = $order['printed'];
		$this->published = $order['published'];
		$this->vatEnabled = $order['vatEnabled'];
		$this->vatIncluded = $order['vatIncluded'];
		$this->vatSum = floatval($order['vatSum']) / 100;
		$this->payedSum = floatval($order['payedSum']) / 100;
		$this->shippedSum = floatval($order['shippedSum']) / 100;
		$this->invoicedSum = floatval($order['invoicedSum']) / 100;
		$this->reservedSum = floatval($order['reservedSum']) / 100;
		
		$this->updated = DateTime::createFromFormat ('Y-m-d H:i:v', $order['updated']);
		$this->moment = DateTime::createFromFormat ('Y-m-d H:i:v', $order['moment']);
		$this->created = DateTime::createFromFormat ('Y-m-d H:i:v', $order['created']);
		$this->deliveryPlannedMoment = DateTime::createFromFormat ('Y-m-d H:i:v', $order['deliveryPlannedMoment']);
		
		$this->meta = new MetaMS ($order['meta']);
		$this->group = new MetaMS ($order['group']['meta']);
		$this->rate = new MetaMS ($order['rate']['currency']['meta']);
		$this->store = new MetaMS ($order['store']['meta']);
		$this->agent = new MetaMS ($order['agent']['meta']);
		$this->organization = new MetaMS ($order['organization']['meta']);
		$this->organizationAccount = new MetaMS ($order['organizationAccount']['meta']);
		$this->state = new MetaMS ($order['state']['meta']);
		
		//attributes
		foreach ($order['attributes'] as $attribute)
		{
		    $this->attributes[] = new AttributeMS($attribute);
		}
		
		//positions
		$this->positions = PositionMS::getOrderPositionsByMeta($order['positions']['meta']);
		
		//files
		$this->files = FileMS::getOrderPositionsByMeta($order['files']['meta']);
		
		//demands
		$this->demands = FileMS::getOrderPositionsByMeta($order['demands']['meta']);
		
		//payments
		foreach ($order['payments'] as $payment)
		{
		    $this->payments[] = PaymentMS::getPaymentByMeta($order['payments']['meta']);
		}
	}	
	/**
	* function findOrders - function find ms orders by ms filter passed
	*
	* @filter - ms filter 
	* @return array - result as array of orders
	*/
	public static function findOrdersByFilter($filter)
    {
	    $filterMS = '';
        foreach (get_object_vars($filter) as $filterName => $filterValue)
        {
            if (is_array($filterValue))
            {
                $filterMS .= $filterName . '=' . $filterValue . ';';
            }
        }
        
        $orders = array();
		$offset = 0;
		$this->log->write (__LINE__ . ' findOrdersByFilter.filter - ' . json_encode ($filters, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		while (true)
		{
			$filter = '';
			if (is_array($filters))
				foreach ($filters as $key => $value)
					$filter .= $key . '=' . $value . ';';
			else
				$filter = $filters;
			$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . '?filter=' . $filter . '&limit=' . MS_LIMIT . '&offset=' . $offset;
			$this->logger->write ('02-findOrders.service_url - ' . $service_url);
			$response_order = $this->apiMSClass->getData($service_url);
			$offset += MS_LIMIT;
			$orders = array_merge ($orders, $response_order['rows']);
			if ($offset >= $response_order['meta']['size'])
				break;			
		}

		$this->logger->write ('03-findOrders.orders - ' . json_encode ($orders, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $orders;
	}
	
	public function createCustomerorder($data)
	{
		$this->logger->write("01-createCustomerorder.data - " . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER;
		$return = $this->apiMSClass->postData ($service_url, $data);
		$this->logger->write("02-createCustomerorder.return - " . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
		//$logger->write("curl_response - " . $curl_response);
		
	}
	public function updateCustomerorder($id, $data)
	{
		$this->logger->write("01-updateCustomerorder.data - " . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . '/' . $id;
		$return = $this->apiMSClass->putData ($service_url, $data);
		$this->logger->write("02-updateCustomerorder.return - " . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
		//$logger->write("curl_response - " . $curl_response);
		
	}
}

?>