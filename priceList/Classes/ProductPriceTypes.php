<?php
/**
 *
 * @class ProductTypes
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class ProductPriceTypes
{
	private $logger;
	private $productPriceTypes;
	
	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');

		$this->productPriceTypes = Db::exec_query_array ('select * from prices_list_priceTypes order by sort_order');
	}	

	public function getProductPriceTypes ()
	{
		return $this->productPriceTypes;
	}
}

?>