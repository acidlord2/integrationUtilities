<?php
/**
 *
 * @class ProductsOzon
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class ProductsOzon
{
	private static $logFilename = 'productsOzon.log';
	public static function getOzonProducts ($kaori = false)
	{
		// get ozon products
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiOzon.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);
		
		$products_ozon = array();
		$postdata = array ('page_size' => 1000, 'page' => 1);
		
		while (true)
		{
			$logger -> write ('01-getOzonProducts.postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$return = ApiOzon::postOzonData(OZON_MAINURL . 'v1/product/list', $postdata, $kaori);
			$logger -> write ('02-getOzonProducts.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$products_ozon = array_merge($products_ozon, $return['result']['items']);
			
			if ($return ['result']['total'] > $postdata['page_size'] * $postdata['page'])
				$postdata['page'] += 1;
			else
				break;
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

		$logger -> write ('01-updateOzonProduct.productData - ' . json_encode ($productData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$return = ApiOzon::postOzonData(OZON_MAINURL . 'v1/product/update', $productData, $kaori);
		$logger -> write ('02-updateOzonProduct.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
	}
	public static function updatePrices ($pricesData, $kaori = false)
	{
		// update ozon products
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiOzon.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);

		$logger -> write ('01-updatePrices.pricesData - ' . json_encode ($pricesData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$return = ApiOzon::postOzonData(OZON_MAINURL . 'v1/product/import/prices', $pricesData, $kaori);
		$logger -> write ('02-updatePrices.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
	}
	public static function updateStock ($stockData, $kaori = false)
	{
		// update ozon products
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiOzon.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);

		$logger -> write ('01-updateStock.stockData - ' . json_encode ($stockData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$return = ApiOzon::postOzonData(OZON_MAINURL . 'v1/product/import/stocks', $stockData, $kaori);
		$logger -> write ('02-updateStock.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
	}
}

?>