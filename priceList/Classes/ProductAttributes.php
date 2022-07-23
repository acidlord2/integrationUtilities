<?php
/**
 *
 * @class ProductAttributes
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class ProductAttributes
{
	private $logger;
	private $productAttributes;
	
	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');

		$this->logger = new Log('priceList-Classes-productAttributes.log');
		
		$this->productAttributes = Db::exec_query_array ('select * from prices_list_productAttributes order by sort_order');
	}

	public function getProductAttributes ($productType_id = null)
	{
		if ($productType_id == null)
			return $this->productAttributes;
		
		$return = array();
		foreach ($this->productAttributes as $productAttribute)
			if ($productAttribute['productType_id'] == $productType_id)
				array_push($return, $productAttribute);

		return $return;
	}
	
	public function getProductAttributeValues ($price_id)
	{
		$productAttributeValues = Db::exec_query_array ('select * from prices_list_productAttributesValues where price_id = ' . $price_id);
		
		return $productAttributeValues;
	}

	public function setProductAttributeValue ($price_id, $productAttribute_id, $value)
	{
		$deleteSql = 'delete from prices_list_productAttributesValues where price_id = ' . $price_id . ' and productAttribute_id = ' . $productAttribute_id;
		$this->logger->write (__LINE__ . ' setProductAttributeValue.deleteSql - ' . json_encode ($deleteSql, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		Db::exec_query ($deleteSql);
		
		if ($value == '')
			return;
		
		$insertSql = 'insert into prices_list_productAttributesValues (price_id, productAttribute_id, attributeValue) values (' . $price_id . ', ' . $productAttribute_id . ', "' . $value . '")';
		$this->logger->write (__LINE__ . ' setProductAttributeValue.insertSql - ' . json_encode ($insertSql, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		Db::exec_query ($insertSql);
	}
}

?>