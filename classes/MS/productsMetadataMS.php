<?php
/**
 *
 * @class ProductsMetadataMS
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class ProductsMetadataMS
{
	private $logger;
	private $apiMSClass;
	private $productsMetadata;
	private $priceTypes;

	private $cache = array ();

	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/api/apiMS.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

		date_default_timezone_set('Europe/Moscow');
		$this->logger = new Log('classes-ms-productsMetadataMS.log');
		$this->apiMSClass = new APIMS();

		$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PRODUCT . MS_API_METADATA;
		$return = $this->apiMSClass->getData($service_url);
		$this->productsMetadata = $return['attributes'];

		$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PRICETYPES;
		$return = $this->apiMSClass->getData($service_url);
		$this->priceTypes = $return;
	}

	/**
	* function getMetadata - function find ms orders by ms filter passed
	*
	* @return array - result as array order of metadata
	*/
	
	public function getMetadata()
    {
		return $this->productsMetadata;
	}
	/**
	* function getPriceTypes - function find ms orders by ms filter passed
	*
	* @return array - result as array of price types
	*/
	public function getPriceTypes()
    {
		return $this->priceTypes;
	}
	/**
	* function getPriceTypes - function find ms orders by ms filter passed
	*
	* @return array - result as array of price types
	*/
	public function getPriceTypeByName($name)
    {
		$this->logger->write(__LINE__ . ' name - ' . $name);
		foreach ($this->priceTypes as $priceType)
			if ($priceType['name'] == $name)
				return $priceType;
			
		return array();
	}
	
	
}

?>