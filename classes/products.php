<?php
/**
 *
 * @class Products
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class Products
{
	private static $priceTypes = array();
	
    public static function postOzonData($service_url, $postdata, &$dataOut, $kaori = false)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		
		// REST Header
		$curl_post_header = array (
				'Content-type: application/json', 
				'Client-Id: ' . ($kaori ? OZON_CLIENT_ID_KAORI : OZON_CLIENT_ID),
				'Api-Key: ' . ($kaori ? OZON_API_KEY_KAORI : OZON_API_KEY)
		);

		try {
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_header);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			$jsonOut = curl_exec($curl);
			curl_close($curl);
			$dataOut = json_decode ($jsonOut, true);
 			
		}
		catch(Exception $e) {
			return false;
		}						
		return true;
	}
	
    public static function getPriceListBrands()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		
		$result = Db::exec_query_array ("SELECT * FROM prices_list_brands order by sort_order");
		return $result;
	}
	
    public static function getPriceTypes()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		if (count (self::$priceTypes) == 0)
			self::$priceTypes = Db::exec_query_array ("select * from prices_list_prices order by sort_order");

		return self::$priceTypes;
	}
	
    public static function getPriceList($brend, $brendDB)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log ('products.log');
		
		$products = array();

		$result = Db::exec_query_array ("select * from prices_list where brand = '" . $brendDB . "' order by sort_order");
		$logger -> write ('getPriceList.result - ' . json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$priceTypes = self::getPriceTypes();
		$service_url = MS_PRODUCTURL . '?filter=pathName~' . urlencode($brend) . '&limit=' . MS_LIMIT;			
		$logger -> write ('getPriceList.service_url - ' . $service_url);
		MSAPI::getMSData($service_url, $response_productsJson, $response_products);
		$logger -> write ('getPriceList.response_products - ' . json_encode($response_products, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		foreach ($result as $price) {
			$key = array_search($price['code'], array_column($response_products['rows'], 'code'), true);
			$temp = $price;
			//$temp['salePrices'] = $response_products['rows'][$key]['salePrices'];
			$temp['prices'] = array();
			if ($key !== false) {
				foreach ($priceTypes as $priceType)
				{
					$priceKey = array_search($priceType['ms_price_name'], array_column($response_products['rows'][$key]['salePrices'], 'priceType')); 
					$temp['prices'][$priceType['sort_order']] = $priceKey !== false ? $response_products['rows'][$key]['salePrices'][$priceKey]['value']/100 : 0;
				}
				$temp['id'] = $response_products['rows'][$key]['id'];
			}
			else
			{
/* 		$logger = new Log ('tmp.log');
		$logger -> write ($brendDB);
		$logger -> write (json_encode($result), true);
		$logger -> write (json_encode($response_products['rows']), true);
		$logger -> write (gettype ($price['code']), true); */
				
				$temp['id'] = null;
			}
			$products[] = $temp;
		}
		return $products;
 	}
	// return order data
    public static function updateProduct($productId, $prices)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log ('products.log');
		$service_url = MS_PRODUCTURL . $productId;
		//echo $service_url;
		//$logger->write ('productId - ' . $productId);
		$postdata = array(
			'salePrices' => array()
		);
		
		$priceTypes = self::getPriceTypes();
		$logger->write ('updateProduct.priceTypes - ' . json_encode ($priceTypes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		foreach ($prices as $key => $price)
		{
			$priceKey = array_search($key, array_column($priceTypes, 'sort_order')); 
			$logger -> write ('updateProduct.priceKey - ' . $priceKey);
			if ($priceKey !== false)
				$postdata['salePrices'][] = array ('value' => (int)$price * 100, 'priceType' => $priceTypes[$priceKey]['ms_price_name']);
			$logger -> write ('updateProduct.postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		}
		
		MSAPI::putMSData($service_url, $postdata, $returnJson, $return);
		//$logger -> write (json_encode ($return));
		return $return;
	}
	
	public static function getMSStock ($productCodes)
	{
		// get ms products
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log ('products.log');

		$codes = '';
		$products_ms = array();
		foreach ($productCodes as $key => $productCode)
		{
			if ($productCode != "") 
				$codes .= 'code=' . $productCode. ';';
			if (($key + 1) % 99 === 0 || $key == count ($productCodes) - 1)
			{
				$logger -> write ('getMSStock.codes - ' . $codes);
				$service_url = MS_ASSORTURL . '?filter=' . $codes . '&limit=' . MS_LIMIT;
				MSAPI::getMSData($service_url, $product_msJson, $product_ms);
				//$logger -> write ('getMSStock.service_url - ' . json_encode ($service_url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				//$logger -> write ('getMSStock.product_ms - ' . json_encode ($product_ms, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				if (isset ($product_ms['rows']))
    				$products_ms = array_merge($products_ms, $product_ms['rows']);
				$codes = '';
			}	
		}
		return $products_ms;
	}
	
	public static function getOzonProducts ($kaori = false)
	{
		// get ozon products
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		$ms_flag = true;
		$products_ozon = array();

		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log ('ozon.log');
		
		$postdata = array ('page_size' => 1000, 'page' => 1);
		//$logger -> write ('getOzonProducts.postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		while ($ms_flag)
		{
			self::postOzonData(OZON_MAINURL . 'v1/product/list', $postdata, $return, $kaori);
			$logger -> write ('getOzonProducts.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$products_ozon = array_merge($products_ozon, $return['result']['items']);
			if ($return ['result']['total'] > $postdata['page_size'] * $postdata['page'])
				$postdata['page'] += 1;
			else
				$ms_flag = false;
		}
		return $products_ozon;
	}
	public static function updateOzonProducts ($products_ms, $kaori = false)
	{
		// update ozon products
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log ('classes - products.log');
		$prices = array ('prices' => array());
		$stocks = array ('stocks' => array());
		//$logger -> write ('updateOzonProducts.products_ms - ' . json_encode ($products_ms, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$i = 0;
		//$logger -> write ('updateOzonProducts.count (products_ms): ' . json_encode (count ($products_ms)));
		foreach ($products_ms as $product_ms)
		{
			$i++;
			//get ozon price
			$keyozon = array_search('Цена Ozon', array_column($product_ms['salePrices'], 'priceType')); 
			//$logger -> write ('updateOzonProducts.key4cl: ' . $product_ms['code'] . ' - ' . json_encode ($key4cl));
			//$logger -> write ('updateOzonProducts.keyozon: ' . $product_ms['code'] . ' - ' . json_encode ($keyozon));
			$price = $keyozon !== false ? $product_ms['salePrices'][$keyozon]['value'] / 100 : 0;
			$quantity = $keyozon !== false ? ($product_ms['quantity'] < 0 ? 0 : $product_ms['quantity']) : 0;
			if ($price == 0)
				$quantity = 0;
			
			array_push ($prices['prices'], array ('offer_id' => $product_ms['code'], 'price' => (string)$price, 'old_price' => '0'));
			array_push ($stocks['stocks'], array ('offer_id' => $product_ms['code'], 'stock' => $quantity));
			//array_push ($stocks['stocks'], array ('offer_id' => $product_ms['code'], 'stock' => 0)); // обнуляем
			if (count ($stocks['stocks']) == 100 || count ($products_ms) == $i)
			{
				$logger -> write ('updateOzonProducts.prices: ' . json_encode ($prices, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$logger -> write ('updateOzonProducts.stocks: ' . json_encode ($stocks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				self::postOzonData(OZON_MAINURL . 'v1/product/import/prices', $prices, $return, $kaori);
				$logger -> write ('updateOzonProducts.returnprices: ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				self::postOzonData(OZON_MAINURL . 'v1/product/import/stocks', $stocks, $return, $kaori);
				$logger -> write ('updateOzonProducts.returnstocks: ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$prices = array ('prices' => array());
				$stocks = array ('stocks' => array());
			}
		}
	
		//$logger -> write ('updateOzonProducts.prices: ' . json_encode ($prices));
		//$logger -> write ('updateOzonProducts.stocks: ' . json_encode ($stocks));

		return true;
	}
}

?>