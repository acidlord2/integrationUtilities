<?php

class Controllerextensionberuupdatedate extends Controller
{
    public function index()
    {
 		$data = json_decode (file_get_contents('php://input'), true);
		
       //$this->load->model('checkout/order');
	//	$this->load->model('catalog/product');

		//$ret = $this->model_checkout_order->addOrderHistory(41314, 1);
		//var_dump($ret);
		$curl_post_header = array (
				'Content-type: application/json', 
				'Authorization: OAuth oauth_token="AgAAAAAyTOdvAAX8gEljxoMMKETqucS1sg4dtWs",oauth_client_id="c99dba091e2f4229a9145d1f02077952"'
		);
		
		$client_id = MS_LOGIN;
		$client_pass = MS_PASS;
		$curl_post_headerms = array (
				'Content-type: application/json', 
				'Authorization: Basic ' . base64_encode("$client_id:$client_pass")
		);
		
		foreach ($data['rows'] as $order)
		{
	
			$service_url = 'https://api.partner.market.yandex.ru/v2/campaigns/21587057/orders/' . $order['name'] . '.JSON';
			$curl_beruorder = curl_init($service_url);
			curl_setopt($curl_beruorder, CURLOPT_HTTPHEADER, $curl_post_header);
			curl_setopt($curl_beruorder, CURLOPT_RETURNTRANSFER, true); 
			$curl_response_beruorder = curl_exec($curl_beruorder);
			$response_beruorder = json_decode ($curl_response_beruorder, true);
			curl_close($curl_beruorder);
			//echo $curl_response_beruorder;
			
			if (isset ($response_beruorder['order']['delivery']['shipments'][0]['shipmentDate']))
			{
				$post_data = array (
					'deliveryPlannedMoment' => DateTime::createFromFormat('d-m-Y', $response_beruorder['order']['delivery']['shipments'][0]['shipmentDate'])->format('Y-m-d H:i:s')
				);
				$service_urlMS = 'https://online.moysklad.ru/api/remap/1.1/entity/customerorder/'. $order['id'];
				$curl = curl_init($service_urlMS);
				curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerms);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
				curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($post_data));
				$curl_response = curl_exec($curl);
				curl_close($curl);
			}
		}
		//echo '<br><br>';
		echo 'ок';
	}
}

