<?php

	$arr = array(
		'prices' => array(
			array(
				'priceType' => array(
					'id' => '12'
				),
				'value' => 21
			),
			array(
				'priceType' => array(
					'id' => '13'
				),
				'value' => 22
			)	
		)
	);
	var_dump($arr);
	echo '<br>';
	var_dump(array_map(function($item) {
		return $item['priceType']['id'] == '12' ? $item['value'] : null;
	}, $arr['prices']));
	echo '<br>';
	var_dump(array_filter(array_map(function($item) {
		return $item['priceType']['id'] == '12' ? $item['value'] : null;
	}, $arr['prices']), function($item) {
		return $item !== null;}));
?>