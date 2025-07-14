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
		$loggerName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
		$loggerName .= '.log';
		$this->logger = new Log($loggerName);
		$this->logger->write(__LINE__ . ' ' . __FUNCTION__ . ' Initializing ProductPriceTypes class');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');

		$this->productPriceTypes = Db::exec_query_array ('select * from prices_list_priceTypes order by sort_order');
		$this-logger->write(__LINE__ . ' ' . __FUNCTION__ . ' Loaded product price types: ' . json_encode($this->productPriceTypes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}	

	public function getProductPriceTypes ()
	{
		return $this->productPriceTypes;
	}
}

?>