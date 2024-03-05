<?php
/**
 *
 * @class PriceListExcel
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class PriceListExcel
{
	private $logger;
	private $productsMS;
	
	public function __construct($data)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
		$this->logger = new Log ('priceList - Classes - PriceListExcel.log');
		$this->logger->write(__LINE__ . ' __construct.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$codes = array_column($data, 'barcode');
		$this->logger->write(__LINE__ . ' __construct.codes - ' . json_encode ($codes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$productsMSClass = new ProductsMS();
		$productsMS = $productsMSClass->findProductsByCode($codes);
		//$this->logger->write(__LINE__ . ' __construct.productsMS - ' . json_encode ($productsMS, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$msCodes = array_column($productsMS, 'code');
		$this->logger->write(__LINE__ . ' __construct.msCodes - ' . json_encode ($msCodes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		foreach($data as $code)
		{
		    $msKey = array_search($code['barcode'], $msCodes);
		    if($msKey === false)
		    {
		        
		        $this->productsMS[] = array('excel' => $code);
		        
		    }
		    else 
		    {
		        $this->productsMS[] = array(
		            'ms' => $productsMS[$msKey],
		            'excel' => $code
		        );
		    }
		}
		//$this->logger->write(__LINE__ . ' __construct.products - ' . json_encode ($this->productsMS, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}	

	public function getPriceList ($productPriceTypes)
	{
		//$sql = 'select * from prices_list_priceList where productType_id = ' . $productType_id . ' and productBrand_id = ' . $productBrand_id . ' order by sort_order';
		//$priceList = Db::exec_query_array ($sql);
		
		//$this->logger->write (__LINE__ . ' getPriceList.priceList - ' . json_encode ($priceList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		//$priceListModels = array_column($priceList, 'model');
	    $this->logger->write(__LINE__ . ' getPriceList.productPriceTypes - ' . json_encode ($productPriceTypes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		//$this->logger->write (__LINE__ . ' getPriceList.productBrand_id - ' . $productBrand_id);
		//$this->logger->write (__LINE__ . ' getPriceList.priceListModels - ' . json_encode ($priceListModels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		//$productsMS = $this->productsMSClass->findProductsByCode($priceListModels);
	    $priceList = array();
	    foreach ($this->productsMS as $productMS)
		{
			//$this->logger->write (__LINE__ . ' getPriceList.salePrices - ' . json_encode ($productMS['salePrices'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		    $salesPriceMS = array ();
		    $salesPriceExcel = array ();
		    if(isset($productMS['ms']))
			{
			    $msPrices = array_column(array_column($productMS['ms']['salePrices'], 'priceType'), 'name');
			    $this->logger->write (__LINE__ . ' getPriceList.msPrices - ' . json_encode ($msPrices, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			    foreach($productPriceTypes as $productPriceType)
    		    {
    		        $priceKey = array_search($productPriceType['ms_price_name'], $msPrices);
        			//$priceKey = array_search ($productPriceType['ms_price_name'], array_column ($productMS['salePrices'], 'name'));
        			if ($priceKey === false)
        			    $salesPriceMS[$productPriceType['priceType_id']] == 0;
        			else
        			    $salesPriceMS[$productPriceType['priceType_id']] = $productMS['ms']['salePrices'][$priceKey]['value'] / 100;
    		    }
    		    $priceList[$productMS['excel']['barcode']]['id'] = $productMS['ms']['id'];
			}
			foreach($productPriceTypes as $productPriceType)
			{
			    if(isset($productMS['excel'][$productPriceType['excel_price_name']]))
			    {
			        $salesPriceExcel[$productPriceType['priceType_id']] = $productMS['excel'][$productPriceType['excel_price_name']];
			    }
			    else
			    {
			        $salesPriceExcel[$productPriceType['priceType_id']] = 0;
			    }
			}
			//$this->logger->write (__LINE__ . ' getPriceList.productKey - ' . $productKey);
			//$this->logger->write (__LINE__ . ' getPriceList.code - ' . $productMS['code']);
			$priceList[$productMS['excel']['barcode']]['priceList'] = $salesPriceMS;
			$priceList[$productMS['excel']['barcode']]['newPriceList'] = array_merge($productMS['excel'], array('prices' => $salesPriceExcel));
		}
		$this->logger->write (__LINE__ . ' getPriceList.priceList - ' . json_encode ($priceList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $priceList;
	}
	
	public function getAttributeRawSpan($code, $attribute)
	{
	    $this->logger->write (__LINE__ . ' getAttributeRawSpan.code - ' . $code);
	    $key = array_search($code, array_column(array_column($this->productsMS, 'excel'), 'barcode'));
	    $this->logger->write (__LINE__ . ' getAttributeRawSpan.key - ' . $key);
	    if($this->productsMS[$key]['excel'][$attribute] == null) return 0;
	    $count = 0;
	    $keyLocal = $key;
	    while (true)
	    {
	        $keyLocal++;
	        if(isset($this->productsMS[$keyLocal]) && $this->productsMS[$keyLocal]['excel'][$attribute] == null)
	            $count++;
	        else
	            return $count + 1;
	    }
	}
}

?>