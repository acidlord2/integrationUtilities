<?php
/**
 *
 * @class Order
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
namespace Classes\MS\v2;

/**
 * Class AssortmentApi
 * Handles MoySklad assortment API operations, caching, and logging.
 *
 * @author Georgy Polyan <acidlord@yandex.ru>
 */
class AssortmentApi
{
	private $log;
	private $api;
	
	/**
	 * AssortmentApi constructor.
	 * Initializes logging, API client, and Memcached.
	 */
	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/v2/Api.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/v2/AssortmentIterator.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');

		$logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
		$logName .= '.log';
		$this->log = new \Classes\Common\Log($logName);
		
		// Initialize API client
		$this->api = new \Classes\MS\v2\Api();
	}
	
	public function fetchAssortment($codes = false)
	{
		$url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_ASSORTMENT;
		if ($codes !== false) {
			$url .= '?filter=';
			if (is_array($codes)) {
				foreach ($codes as $code) {
					$url .= 'code=' . $code . ';';
				}
			} else {
				$url .= $codes;
			}
		}
		$this->log->write(__LINE__ . ' fetchAssortment.codes - ' . json_encode($codes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		// Fetch assortment from API
		$assortment = $this->api->getAssortment($codes);
		if (!$assortment) {
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' error - No assortment data found');
			return false;
		}

		$assortmentIterator = new \Classes\MS\v2\AssortmentIterator($assortment);
		
		return $assortmentIterator;
	}
}

?>