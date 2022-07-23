<?php
/**
 *
 * @class ProductTypes
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class ProductTypes
{
	private $logger;
	private $productTypes;
	
	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');

		$this->productTypes = Db::exec_query_array ('select * from prices_list_productTypes');
	}	

	public function getProductTypes ()
	{
		return $this->productTypes;
	}
	
	public function getProductTypeByCode ($productTypeCode)
	{
		$key = array_search ($productTypeCode, array_column ($this->productTypes, 'code'));
		
		if ($key !== false)
			return $this->productTypes[$key];
		
		return '';
	}
	
	public function getProductType_id ($productTypeCode)
	{
		$key = array_search ($productTypeCode, array_column ($this->productTypes, 'code'));
		
		if ($key !== false)
			return $this->productTypes[$key]['productType_id'];
		
		return '';
	}
}

?>