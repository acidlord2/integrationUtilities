<?php
/**
 * TOP API: aliexpress.logistics.order.shipment request
 * 
 * @author auto create
 * @since 1.0, 2021.08.19
 */
class AliexpressLogisticsOrderShipmentRequest
{
	/** 
	 * -
	 **/
	private $paramAeopSellerShipmentSubTradeOrderRequest;
	
	private $apiParas = array();
	
	public function setParamAeopSellerShipmentSubTradeOrderRequest($paramAeopSellerShipmentSubTradeOrderRequest)
	{
		$this->paramAeopSellerShipmentSubTradeOrderRequest = $paramAeopSellerShipmentSubTradeOrderRequest;
		$this->apiParas["param_aeop_seller_shipment_sub_trade_order_request"] = $paramAeopSellerShipmentSubTradeOrderRequest;
	}

	public function getParamAeopSellerShipmentSubTradeOrderRequest()
	{
		return $this->paramAeopSellerShipmentSubTradeOrderRequest;
	}

	public function getApiMethodName()
	{
		return "aliexpress.logistics.order.shipment";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
