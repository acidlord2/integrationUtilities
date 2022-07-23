<?php
/**
 *
 * @class ProductsOzon
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class ProductsOzon
{
	private $log;
	private $apiOzonClass;
	private $organization;
	
	public function __construct($organization)
	{
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ozon/ApiOzon.php');
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	    $this->organization = $organization;
	    $this->apiOzonClass = new ApiOzon($organization);
	    $this->log = new Log('classes - Ozon - ProductsOzon.log');
	}
	
	public function getProducts()
	{
		// get ozon products
		$products_ozon = array();
		$postdata = array ('page_size' => 1000, 'page' => 1);
		
		while (true)
		{
		    $this->log->write(__LINE__ . ' getProducts.postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		    $return = $this->apiOzonClass->postData(OZON_MAINURL . 'v1/product/list', $postdata, $this->organization);
		    $this->log->write(__LINE__ . ' getProducts.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$products_ozon = array_merge($products_ozon, $return['result']['items']);
			
			if ($return ['result']['total'] > $postdata['page_size'] * $postdata['page'])
				$postdata['page'] += 1;
			else
				break;
		}
		return $products_ozon;
	}
	
// 	public function updateOzonProduct ($productData, $kaori = false)
// 	{
// 		// update ozon products
// 		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
// 		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiOzon.php');
// 		$logger = new Log (self::$logFilename);

// 		$logger -> write (__LINE__ . ' updateOzonProduct.productData - ' . json_encode ($productData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
// 		$return = ApiOzon::postOzonData(OZON_MAINURL . 'v1/product/update', $productData, $kaori);
// 		$logger -> write (__LINE__ . ' updateOzonProduct.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
// 		return $return;
// 	}
	public function updatePrices ($pricesData)
	{
		// update ozon prices
		$this->log->write(__LINE__ . ' updatePrices.pricesData - ' . json_encode ($pricesData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$return = $this->apiOzonClass->postData(OZON_MAINURL . 'v1/product/import/prices', $pricesData);
		$this->log->write(__LINE__ . ' updatePrices.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
	}
	public function updateStock ($stockData)
	{
		// update ozon stock
	    $this->log->write(__LINE__ . ' updateStock.stockData - ' . json_encode ($stockData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $return = $this->apiOzonClass->postData(OZON_MAINURL . 'v2/products/stocks', $stockData);
		$this->log->write(__LINE__ . ' updateStock.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
	}
}

?>