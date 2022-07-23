<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/aliApi.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
	
	$logger = new Log ('aliexpress - syncProducts.log');
	//$logger -> write (json_encode ($data));
	
	$params = array (
		'setAeopAEProductListQuery' => array (
			'page_size' => 80,
			'product_status_type' => 'onSelling',
			'current_page' => 1
		)
	);
	
	$products = array();
	while (true)
	{
		AliAPI::getAliData('AliexpressSolutionProductListGetRequest', $params, $jsonOut, $arrayOut);
		//echo json_encode ($arrayOut->result->aeop_a_e_product_display_d_t_o_list->item_display_dto, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$products = array_merge ($products, json_decode (json_encode ($arrayOut->result->aeop_a_e_product_display_d_t_o_list->item_display_dto), true));
		$params['setAeopAEProductListQuery']['current_page']++;
		if ($arrayOut->result->current_page == $arrayOut->result->total_page)
			break;
	}
	//$logger->write (__LINE__ . ' products - ' . json_encode ($products, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	$i = 0;
	foreach ($products as $product)
	{
		$params = array(
			'setProductId' => $product['product_id']
		);
		
		$i++;
		$sql = "select * from product_mapping where ext_id = '" . $product['product_id'] . "' and ext_account = 'ali-ru1386043510mwbr'";
		$result = Db::exec_query_array ($sql);

		if ($result == null || !count ($result))
		{
		    AliAPI::getAliData('AliexpressSolutionProductInfoGetRequest', $params, $jsonOut, $arrayOut);
		    //$logger->write (__LINE__ . ' arrayOut - ' . json_encode ($arrayOut, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		    if (isset($arrayOut->result->aeop_ae_product_s_k_us->global_aeop_ae_product_sku[0]->sku_code) && $arrayOut->result->aeop_ae_product_s_k_us->global_aeop_ae_product_sku[0]->sku_code != '')
		    {
		        $sql = "insert into product_mapping (sku, ext_id, ext_account) values ('" . $arrayOut->result->aeop_ae_product_s_k_us->global_aeop_ae_product_sku[0]->sku_code . "', '" . $product['product_id'] . "', 'ali-ru1386043510mwbr')";
		        Db::exec_query ($sql);
		        $logger->write (__LINE__ . ' inserted product - ext_id: ' . $product['product_id'] . ', sku: ' . $arrayOut -> result -> aeop_ae_product_s_k_us -> global_aeop_ae_product_sku [0] -> sku_code);
		    }
		    else {
		        $logger -> write (__LINE__ . ' no sku on product - ext_id: ' . $product['product_id']);
		    }
		}
		else
		    $logger -> write (__LINE__ . ' no changes product - ext_id: ' . $product['product_id']);
		
	}
	echo 'Synced ' . count ($products) . ' products';
?>

