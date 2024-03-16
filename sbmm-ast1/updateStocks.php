<?php
/**
 * @class Controllerextensiongoodsimportstocks
 * @author gpolyan
 */
class Controllerextensiongoodsupdatestocks extends Controller
{
    public function index()
    {
		$this->load->model('extension/prices/expprices');
		$products = $this->model_extension_prices_expprices->getProducts();
		
		$logger = new Log('goods - updateStocks.log'); //just passed the file name as file_name.log

		$curl_post_header = array (
				'Content-type: application/json'
		);
		$token = GOODS_TOKEN;
		$client_id = MS_LOGIN;
		$client_pass = MS_PASS;
		$curl_post_header = array (
				'Content-type: application/json', 
				'Authorization: Basic ' . base64_encode("$client_id:$client_pass")
		);
		$merchantId = GOODS_MERCHANT;
		
		foreach(array_chunk($products, 250, true) as $productsData)
		{
			// post body for search engine
			$curl_post_data = array(
				'data' => array (
					'token' => $token,
					'stocks' => array()
				),
				'meta' => array()
			);
			foreach($productsData as $key => $product)
			{
				$curl_post_data['data']['stocks'][] = array ('offerId' => $key, 'quantity' => (int)$product['quantity'] < 0 ? 0 : (int)$product['quantity']);
			}
			$logger->write(__LINE__ . ' curl_post_data - ' . json_encode($curl_post_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			// service request post
			$curl_update = curl_init('https://partner.sbermegamarket.ru/api/merchantIntegration/v1/offerService/stock/update');
			curl_setopt($curl_update, CURLOPT_HTTPHEADER, $curl_post_header);
			curl_setopt($curl_update, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl_update, CURLOPT_POST, true);
			curl_setopt($curl_update, CURLOPT_POSTFIELDS, json_encode($curl_post_data));
			$curl_update_response = curl_exec($curl_update);

			$logger->write(__LINE__ . ' curl_update_response - ' . $curl_update_response);
		}
		echo 'Updated ' . count($products) . ' quantities';
	}
}

