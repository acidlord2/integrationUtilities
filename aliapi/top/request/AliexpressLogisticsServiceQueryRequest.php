<?php
/**
 * TOP API: aliexpress.logistics.service.query request
 * 
 * @author auto create
 * @since 1.0, 2021.08.19
 */
class AliexpressLogisticsServiceQueryRequest
{
	/** 
	 * 入參对象
	 **/
	private $interfaceRequest;
	
	private $apiParas = array();
	
	public function setInterfaceRequest($interfaceRequest)
	{
		$this->interfaceRequest = $interfaceRequest;
		$this->apiParas["interface_request"] = $interfaceRequest;
	}

	public function getInterfaceRequest()
	{
		return $this->interfaceRequest;
	}

	public function getApiMethodName()
	{
		return "aliexpress.logistics.service.query";
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
