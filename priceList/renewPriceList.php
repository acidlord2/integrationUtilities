<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/priceList/Classes/PriceList.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/priceList/Classes/ProductAttributes.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/priceList/Classes/ProductTypes.php');
	
	$logger = new Log ('priceList - renewPriceList.log');
	$productType = $_REQUEST["productType"];
	$priceListClass = new PriceList();
	$productAttributesClass = new ProductAttributes();
	$productTypesClass = new ProductTypes();

?>
	<?php foreach ($_SESSION['productBrands'] as $brand)
		{
			$products = $priceListClass->getPriceList ($brand['productType_id'], $brand['productBrand_id'], $_SESSION['productPriceTypes']);
			$logger->write (__LINE__ . ' products - ' . json_encode ($products, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			foreach ($products as $key => $product)
			{ 
			    if ($productType != 'all') {
			         $productAttributeValues = $productAttributesClass->getProductAttributeValues($product['price_id']);
			    }
			    
				?>
				<tr id = <?php echo $product['price_id']; ?>>
					<?php if (!$key) { ?>
						<th rowspan=<?php echo count($products); ?>><?php echo $brand['name']; ?></th>
					<?php } ?>
					<td><b><?php echo $product['model']; ?></b></td>
					<?php foreach ($_SESSION['productAttributes'] as $attribute) 
					{
						$productAttributeValueKey = array_search($attribute['productAttribute_id'], array_column($productAttributeValues, 'productAttribute_id'))
					?>
					<td class="input">
						<input type="text" id="<?php echo $attribute['productAttribute_id'] . ':' . $product['price_id'];?>" onchange="change(event)" value="<?php echo $productAttributeValueKey !== false ? $productAttributeValues[$productAttributeValueKey]['attributeValue'] : ''; ?>" class="price-input">
					</td>
					<?php } ?>
					<?php if (isset ($product['priceList']) && count ($product['priceList'])) {
					   foreach ($product['priceList'] as $pricesKey => $pricesValue) { ?>
						<td id = <?php echo 'o' . $pricesKey . ':' . $product['id']; ?>><?php echo $pricesValue; ?></td>
					<?php } ?>
					<?php foreach ($product['priceList'] as $pricesKey => $pricesValue) { ?>
						<td class="input"><input type="number" id="<?php echo 'i' . $pricesKey . ':' . $product['id'];?>" onchange="change(event)" value="<?php echo $pricesValue; ?>" class="price-input"></td>
					<?php }} ?>
				</tr>
	<?php }} ?>


