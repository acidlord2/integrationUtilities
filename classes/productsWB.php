<?php
/**
 *
 * @class Products
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class ProductsWB
{
	private static $logFilename = 'productsWB.log';
	public static function getProducts ($filter = false)
	{
		// get wb products from sync table
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);

		$products = Db::exec_query_array ('select * from product_mapping_wildberries' . ($filter !== false ? 'where ' . $filter : ''));
		
		$logger->write ('getProducts.products - ' . json_encode ($products, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		if ($products)
			return $products;
		else
			return array();
	}
	public static function updateStock ($company, $postData)
	{
		// get wb products from sync table
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiWB.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);

		$service_url = WB_STOCKS;
		$logger->write ('updateStock.service_url - ' . $service_url);
		$logger->write ('updateStock.postData - ' . json_encode ($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		APIWB::postData($company, $service_url, $postData, $returnJson, $return);
		$logger->write ('updateStock.returnJson - ' . $returnJson);

		return;
	}
}

?>