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
	/**
	 * Chunk size for splitting code filters in API requests
	 */
	private const CHUNK_SIZE = 200;
	private $log;
	private $api;
	
	/**
	 * AssortmentApi constructor.
	 * Initializes logging, API client, and Memcached.
	 */
	public function __construct()
	{
		$docroot = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
		require_once($docroot . '/config.php');
		require_once($docroot . '/classes/MS/v2/Api.php');
		require_once($docroot . '/classes/MS/v2/AssortmentIterator.php');
		require_once($docroot . '/classes/Common/Log.php');

		$logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($docroot, '', __FILE__)), " -");
		$logName .= '.log';
		$this->log = new \Classes\Common\Log($logName);
		
		// Initialize API client
		$this->api = new \MS\v2\Api();
	}
	
	public function fetchAssortment($codes = false)
	{
		$allAssortments = [];
		if ($codes !== false && is_array($codes)) {
			$chunks = array_chunk($codes, self::CHUNK_SIZE);
			foreach ($chunks as $chunk) {
				$url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_ASSORTMENT . '?filter=';
				foreach ($chunk as $code) {
					$url .= 'code=' . $code . ';';
				}
				$url .= 'stockMode=all;quantityMode=all;';
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' codes - ' . json_encode($chunk, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$assortment = $this->api->getData($url);
				if (is_string($assortment)) {
					$assortment = json_decode($assortment, true);
				}
				if (
					$assortment &&
					is_array($assortment) &&
					isset($assortment['rows']) &&
					is_array($assortment['rows'])
				) {
					$allAssortments = array_merge($allAssortments, $assortment['rows']);
				}
			}
		} else {
			$url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_ASSORTMENT;
			if ($codes !== false) {
				$url .= '?filter=' . $codes;
			}
			$url .= 'stockMode=all;quantityMode=all;';
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' codes - ' . json_encode($codes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$assortment = $this->api->getData($url);
			if (is_string($assortment)) {
				$assortment = json_decode($assortment, true);
			}
			if (
				$assortment &&
				is_array($assortment) &&
				isset($assortment['rows']) &&
				is_array($assortment['rows'])
			) {
				$allAssortments = array_merge($allAssortments, $assortment['rows']);
			}
		}
		$this->log->write(__LINE__ . ' ' . __METHOD__ . ' fetched codes - ' . json_encode(array_column($allAssortments, 'code'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		if (empty($allAssortments)) {
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' error - No assortment data found');
			return false;
		}
		$assortmentIterator = new \Classes\MS\v2\AssortmentIterator($allAssortments);
		return $assortmentIterator;
	}
}

?>