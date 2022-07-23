<?php
/**
 *
 * @class PriceList
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class PriceList
{
	private $logger;
	private $productsMSClass;
	
	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
		$this->logger = new Log ('priceList - Classes - PriceList.log');
		$this->productsMSClass = new ProductsMS();
	}	

	public function getPriceList ($productType_id, $productBrand_id, $productPriceTypes)
	{
		$sql = 'select * from prices_list_priceList where productType_id = ' . $productType_id . ' and productBrand_id = ' . $productBrand_id . ' order by sort_order';
		$priceList = Db::exec_query_array ($sql);
		
		$this->logger->write (__LINE__ . ' getPriceList.priceList - ' . json_encode ($priceList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$priceListModels = array_column($priceList, 'model');
		$this->logger->write (__LINE__ . ' getPriceList.productType_id - ' . $productType_id);
		$this->logger->write (__LINE__ . ' getPriceList.productBrand_id - ' . $productBrand_id);
		$this->logger->write (__LINE__ . ' getPriceList.priceListModels - ' . json_encode ($priceListModels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$productsMS = $this->productsMSClass->findProductsByCode($priceListModels);
		if (isset ($productsMS) && $productsMS != null){
    		foreach ($productsMS as $productMS)
    		{
    			//$this->logger->write (__LINE__ . ' getPriceList.salePrices - ' . json_encode ($productMS['salePrices'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    			$salesPrice = array ();
    			foreach ($productPriceTypes as $productPriceType)
    			{
    				$priceKey = false;
    				foreach ($productMS['salePrices'] as $key => $salesPriceMS)
    					if ($salesPriceMS['priceType']['name'] == $productPriceType['ms_price_name'])
    					{
    						$priceKey = $key;
    						break;
    					}
    				//$priceKey = array_search ($productPriceType['ms_price_name'], array_column ($productMS['salePrices'], 'name'));
    				if ($priceKey === false)
    					$salesPrice[$productPriceType['priceType_id']] == 0;
    				else
    					$salesPrice[$productPriceType['priceType_id']] = $productMS['salePrices'][$priceKey]['value'] / 100;
    			}
    			$productKey = array_search($productMS['code'], $priceListModels);
    			//$this->logger->write (__LINE__ . ' getPriceList.productKey - ' . $productKey);
    			//$this->logger->write (__LINE__ . ' getPriceList.code - ' . $productMS['code']);
    			$priceList[$productKey]['priceList'] = $salesPrice;
    			$priceList[$productKey]['id'] = $productMS['id'];
    		}
		}
		else {
		    return [];
		}
		return $priceList;
	}
}

?>