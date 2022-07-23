<?php
/**
 *
 * @class ProductBrands
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class ProductBrands
{
	private $log;
	private $productBrands;
	
	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
        
		$this->log = new Log ('priceList - classes - ProductBrands.log');
		$this->productBrands = Db::exec_query_array ('select * from prices_list_productBrands order by sort_order');
	}

	public function getProductBrands ($productType_id = null)
	{
	    //$this->log->write (__LINE__ . ' getProductBrands.productBrands - ' . json_encode ($this->productBrands, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    if ($productType_id == null)
			return $this->productBrands;
		
		$return = array();
		foreach ($this->productBrands as $productBrand)
			if ($productBrand['productType_id'] == $productType_id)
				array_push($return, $productBrand);

		return $return;
	}
}

?>