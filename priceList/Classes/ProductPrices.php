<?php
/**
 *
 * @class ProductPrices
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class ProductPrices
{
	private $logger;
	private $db;
	private $productPrices;
	
	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');

		$this->productTypes = $this->db::exec_query_array ('select * from prices_list_priceList order by sort_order');
	}	

	public function getProductPrices ($productType_id = null)
	{
		if ($productType_id == null)
			return $this->productPrices;
		
		$return = array();
		foreach ($this->productPrices as $productPrice)
			if ($productPrice['productType_id'] == $productType_id)
				array_push($return, $productPrice);

		return $return;
	}
}

?>