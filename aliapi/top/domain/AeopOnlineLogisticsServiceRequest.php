<?php

/**
 * 入參对象
 * @author auto create
 */
class AeopOnlineLogisticsServiceRequest
{
	
	/** 
	 * 高
	 **/
	public $goods_height;
	
	/** 
	 * 长
	 **/
	public $goods_length;
	
	/** 
	 * 重量
	 **/
	public $goods_weight;
	
	/** 
	 * 宽
	 **/
	public $goods_width;
	
	/** 
	 * 语言
	 **/
	public $locale;
	
	/** 
	 * 主交易单id
	 **/
	public $order_id;
	
	/** 
	 * 子交易单列表
	 **/
	public $sub_order_list;	
}
?>