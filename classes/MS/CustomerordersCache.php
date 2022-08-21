<?php
/**
 *
 * @class OrdersMS
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
declare(strict_types=1);

namespace MS;

final class CustomerordersCache
{
	private $log;
	private static array $instances = array();
    private $orderId;
    
	private function __construct($orderId)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/api/apiMS.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

		$this->log = new \Log('classes - MS - CustomerordersCache.log');
		$this->apiMSClass = new \APIMS();
		$this->orderId = $orderId;
		$this->log->write(__LINE__ . ' $instances - ' . json_encode(self::$instances, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}	
	public function __destruct() {
	    $key = array_search($this, self::$instances);
	    if($key !== null)
	    {
	        unset(self::$instances[$key]);
	    }
	}
	    /**
	* function findOrders - function find ms orders by ms filter passed
	*
	* @filters string - ms filter 
	* @return array - result as array of orders
	*/
	private function __clone()
	{
	}
	
	public static function getInstance($orderId): CustomerordersCache
	{
	    if (count(self::$instances))
	    {
	        foreach(self::$instances as $instance)
	        {
	            if($instance->orderId == $orderId)
	            {
	                return $instance;
	            }
	        }
	    }
	    $instance = new CustomerordersCache($orderId);
	    array_push(self::$instances, $instance);
	    return $instance;
	}
	
	public static function isInstanceExists($orderId): bool
	{
	    if (count(self::$instances))
	    {	        
	        foreach(self::$instances as $instance)
	        {
	            if($instance->orderId == $orderId)
	            {
	                return true;
	            }
	        }
	    }
	    return false;
	}
}

?>