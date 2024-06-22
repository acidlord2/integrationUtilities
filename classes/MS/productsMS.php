<?php
/**
 *
 * @class ProductsMS
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class ProductsMS
{
	private $logger;
	private $apiMSClass;
	
	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/api/apiMS.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

		$this->logger = new Log('classes - MS - productsMS.log');
		$this->apiMSClass = new APIMS();
	}	

	public function getAssortment ($codes = false)
	{
		$offset = 0;
		if ($codes !== false){
    		$filter = '?filter=';
    		if (is_array($codes)){
    		    foreach ($codes as $code){
    		        $filter .= 'code=' . $code . ';';
    		    }
    		}
    		else {
    		    $filter .= $codes;
    		}
		}
		$products = array();
		while (true)
		{
		    $url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_ASSORTMENT . ($codes !== false ? $filter : '') .'stockMode=all;quantityMode=all;&offset=' . $offset;
			$product_ms = $this->apiMSClass->getData($url);
			$this->logger->write (__LINE__ . ' getAssortment.url - ' . json_encode ($url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$products = array_merge($products, $product_ms['rows']);
			$offset += $product_ms['meta']['limit'];
			
			if ($offset > $product_ms['meta']['size'] || !isset ($product_ms['meta']['size']))
				break;
		}
		return $products;
	}
	
	public function findProductsByCode($codes)
	{
	    $return = array();
		$offset = 0;
		$filter = '?filter=';
		if (is_array($codes))
		{
		    foreach(array_chunk($codes, 40) as $chunk)
		    {
		        $filter = '?filter=';
		        foreach ($chunk as $code){
				    $filter .= 'code=' . $code . ';';
		        }
                $service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PRODUCT . $filter;
                $this->logger->write (__LINE__ . ' findProductsByCode.service_url - ' . $service_url);
                $product_ms = $this->apiMSClass->getData($service_url);
                //$this->logger->write (__LINE__ . ' findProductsByCode.product_ms - ' . json_encode ($product_ms, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                $return = array_merge($return, $product_ms['rows']);
		    }
		}
		else {
			$filter .= 'code=' . $codes;
			$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PRODUCT . $filter;
			$this->logger->write (__LINE__ . ' findProductsByCode.service_url - ' . $service_url);
			$product_ms = $this->apiMSClass->getData($service_url);
			//$this->logger->write (__LINE__ . ' findProductsByCode.product_ms - ' . json_encode ($product_ms, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$return = array_merge($return, $product_ms['rows']);
		}
		return $return;
	}

	public function findServicesByCode ($codes)
	{
	    $offset = 0;
	    $filter = '?filter=';
	    if (is_array($codes))
	        foreach ($codes as $code)
	            $filter .= 'code=' . $code . ';';
	            else
	                $filter .= 'code=' . $codes;
	                $url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_SERVICE . $filter;
	                $this->logger->write (__LINE__ . ' findServicesByCode.service_url - ' . $url);
	                $product_ms = $this->apiMSClass->getData($url);
	                $this->logger -> write (__LINE__ . ' findServicesByCode.product_ms - ' . json_encode ($product_ms, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	                return $product_ms['rows'];
	}
	
	public function createUpgradeProducts ($data)
	{
		$this->logger->write (__LINE__ . ' createUpgradeProducts.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PRODUCT;
		$return = $this->apiMSClass->postData($url, $data);
		$this->logger->write (__LINE__ . ' createUpgradeProducts.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    return $return;
	}
	
	public function getProduct ($productId)
	{
	    $this->logger->write (__LINE__ . ' getProduct.productId - ' . $productId);
	    $url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PRODUCT . '/' . $productId;
	    $this->logger->write (__LINE__ . ' getProduct.url - ' . $url);
	    $return = $this->apiMSClass->getData($url);
	    $this->logger->write (__LINE__ . ' getProduct.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    return $return;
	}
	
	public function getPrice ($assortment, $priceType = 'Цена продажи')
	{
	    $this->logger->write (__LINE__ . ' getPrice.assortment - ' . json_encode ($assortment, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $this->logger->write (__LINE__ . ' getPrice.priceType - ' . $priceType);
		// extract fileprice from assortment
		$price = 0;
		foreach ($assortment['salePrices'] as $salePrice)
		{
			if ($salePrice['priceType']['name'] == $priceType)
			{
				$price = $salePrice['value'] / 100;
				break;
			}
		}
		return $price;
	}
}

?>