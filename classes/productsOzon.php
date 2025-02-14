<?php
/**
 *
 * @class ProductsOzon
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class ProductsOzon
{
	private static $logFilename = 'classes - productsOzon.log';
	public static function getOzonProducts ($kaori = false)
	{
		// get ozon products
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiOzon.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);
		
		$products_ozon = array();
		$postdata = array ('limit' => 1000, 'last_id' => '', 'filter' => (object) []);
		
		while (true)
		{
		    $logger -> write (__LINE__ . ' getOzonProducts.postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$return = ApiOzon::postOzonData(OZON_MAINURL . 'v3/product/list', $postdata, $kaori);
			$logger -> write (__LINE__ . ' getOzonProducts.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			if ($return ['result']['last_id'] == '')
			{
                break;			    
			}
			$products_ozon = array_merge($products_ozon, $return['result']['items']);
			$postdata['last_id'] = $return ['result']['last_id'];
		}
		return $products_ozon;
	}
	public static function updateOzonProduct ($productData, $kaori = false)
	{
		// update ozon products
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiOzon.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);

		$logger -> write (__LINE__ . ' updateOzonProduct.productData - ' . json_encode ($productData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$return = ApiOzon::postOzonData(OZON_MAINURL . 'v1/product/update', $productData, $kaori);
		$logger -> write (__LINE__ . ' updateOzonProduct.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
	}
	public static function updatePrices ($pricesData, $kaori = false)
	{
		// update ozon products
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiOzon.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);

		$logger -> write (__LINE__ . ' updatePrices.pricesData - ' . json_encode ($pricesData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$return = ApiOzon::postOzonData(OZON_MAINURL . 'v1/product/import/prices', $pricesData, $kaori);
		$logger -> write (__LINE__ . ' updatePrices.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
	}
	public static function updateStock ($stockData, $kaori = false)
	{
		// update ozon products
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiOzon.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);

		$logger -> write (__LINE__ . ' updateStock.stockData - ' . json_encode ($stockData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$return = ApiOzon::postOzonData(OZON_MAINURL . 'v1/product/import/stocks', $stockData, $kaori);
		$logger -> write (__LINE__ . ' updateStock.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
	}
}

?>