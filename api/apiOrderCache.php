<?php
/**
 *
 * @class APIOrderCache
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class APIOrderCache
{
    public static function saveOrderCache($orderId, $status)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		
		$c = self::getOrderCache($orderId);
		if (count($c)) {
    		Db::exec_query('update ms_order_cache set status = "' . $status . '" where order_id = "' . $orderId . '"');
		}
		else {
		    Db::exec_query('insert into ms_order_cache (order_id, status) values ("' . $orderId . '", "' . $status . '")');
		}
		return true;
	}
	
	public static function getOrderCache($orderId)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		
        $result = Db::exec_query_array('select * from ms_order_cache where order_id = "' . $orderId . '"');
			
	    return $result;
	}
}

?>