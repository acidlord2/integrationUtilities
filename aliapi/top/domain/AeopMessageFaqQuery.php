<?php

/**
 * 入参如下
 * @author auto create
 */
class AeopMessageFaqQuery
{
	
	/** 
	 * 类目id
	 **/
	public $category_code;
	
	/** 
	 * 类型：common 通用、category 类目
	 **/
	public $faq_type;
	
	/** 
	 * 语言：目前支持5种语言：en_US，es_ES，fr_FR，pt_BR，ru_RU
	 **/
	public $language;	
}
?>