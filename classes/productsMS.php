<?php
/**
 *
 * @class Products
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class ProductsMS
{
	private static $logFilename = 'classes - productsMS.log';
	public static function getAssortment ($filter = false)
	{
		// get ms products
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiMS.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);
		$offset = 0;
		$products = array();
		while (true)
		{
			$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_ASSORTMENT . '?filter='. ($filter !== false ? $filter : '') .'stockMode=all;quantityMode=all;&offset=' . $offset;
			APIMS::getMSData($service_url, $product_msJson, $product_ms);
			$logger -> write (__LINE__ . ' getAssortment.service_url - ' . json_encode ($service_url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			//$logger -> write (__LINE__ . ' getAssortment.product_ms - ' . json_encode ($product_ms, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$products = array_merge($products, $product_ms['rows']);
			$offset += $product_ms['meta']['limit'];
			
			if ($offset > $product_ms['meta']['size'] || !isset ($product_ms['meta']['size']))
				break;
		}
		return $products;
	}
	
	public static function findProductsByCode ($codes)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiMS.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		
		$logger = new Log (self::$logFilename);
		$offset = 0;
		$filter = '?filter=';
		if (is_array($codes))
			foreach ($codes as $code)
				$filter .= 'code=' . $code;
		else
			$filter .= 'code=' . $codes;
		$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PRODUCT . $filter;
		$logger -> write (__LINE__ . ' findProductsByCode.service_url - ' . $service_url);
		APIMS::getMSData($service_url, $product_msJson, $product_ms);
		$logger -> write (__LINE__ . ' findProductsByCode.product_ms - ' . json_encode ($product_ms, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $product_ms['rows'];
	}
}

?>