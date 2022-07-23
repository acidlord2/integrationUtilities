<?php

/**
 * 发布属性list
 * @author auto create
 */
class AeopAttributeDto
{
	
	/** 
	 * 发布属性展现样式
	 **/
	public $attribute_show_type_value;
	
	/** 
	 * sku属性是否可自定义名称
	 **/
	public $customized_name;
	
	/** 
	 * sku属性是否可自定义图片
	 **/
	public $customized_pic;
	
	/** 
	 * 类目属性的feature集合。因为AE的类目属性有国别化的需求，有的国家需要必填有的国家需要非必填，所以属性上会有一个AE_FEATURE_PRequireStrategy 这样的feature字段，对应的值是国家的缩写（逗号分隔），表示哪些国家需要必填，例如: {"AE_FEATURE_PRequireStrategy": "IT,RU,TR,CN,FR,ES"}。目前支持的feature有 AE_FEATURE_PRequireStrategy和AE_FEATURE_car_cascade_property。AE_FEATURE_car_cascade_property是车型库相关特征，需要使用请咨询对应技术支持。
	 **/
	public $features;
	
	/** 
	 * 属性id
	 **/
	public $id;
	
	/** 
	 * 文本输入框型属性输入格式（文本|数字）
	 **/
	public $input_type;
	
	/** 
	 * 发布属性是否关键
	 **/
	public $key_attribute;
	
	/** 
	 * 属性名称
	 **/
	public $names;
	
	/** 
	 * 发布属性是否必填
	 **/
	public $required;
	
	/** 
	 * 发布属性是否是sku
	 **/
	public $sku;
	
	/** 
	 * sku属性展现样式（色卡|普通）
	 **/
	public $sku_style_value;
	
	/** 
	 * sku维度（1维~6维）
	 **/
	public $spec;
	
	/** 
	 * 发布属性单位
	 **/
	public $units;
	
	/** 
	 * 发布属性值
	 **/
	public $values;
	
	/** 
	 * 属性是否可见
	 **/
	public $visible;	
}
?>