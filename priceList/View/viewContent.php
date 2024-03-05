<?php
    require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/priceList/Classes/PriceListExcel.php');
    $log = new Log ('priceList - View - viewContent.log');
    
    $cols = array();
    $colsSizes = array();

    $data = file_get_contents('php://input');
    //$log->write(__LINE__ . ' data - ' . $data); // json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $json = json_decode(json_decode($data, true), true);
    //$log->write(__LINE__ . ' json - ' . json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    //var_dump($json);
    foreach($_SESSION['productPriceTypes'] as $productPriceType)
	{
	    if (in_array($productPriceType['excel_price_name'], array_keys($json['fileInfo'])))
	    {
	        array_push($cols, $productPriceType);
	    }
	}
	
	$colsCount = 5 + (count($cols) * 2);
	$pricesColsSize = count($cols) * 2 * 4;
	$tmpColSize = (100 - $pricesColsSize) / 5;
	
	for ($i = 0; $i < 5; $i++) {
	    array_push ($colsSizes, $tmpColSize . '%');
	}
	for ($i = 0; $i < count($cols) * 2; $i++) {
	    array_push ($colsSizes, '4%');
	}
?>
	
<div class="sticky">
	<table id="orderTableHead" class="tableBig">
		<colgroup>
			<?php foreach ($colsSizes as $col) { ?>
				<col span="1" style="width: <?php echo $col; ?>">
			<?php } ?>
		</colgroup>
		<thead>
			<tr id = "table_header1">
				<th colspan="5">ПОЗИЦИИ</th>
				<th colspan="<?php echo count($cols) ?>">ДЕЙСТВУЮЩИЕ ЦЕНЫ</th>
				<th colspan="<?php echo count($cols) ?>">ВВЕДИТЕ НОВЫЕ ЦЕНЫ</th>
			</tr>
			<tr id = "table_header">
				<th>Бренд</th>
				<th>Имя</th>
				<th>Размер</th>
				<th>Вложение</th>
				<th>Код</th>
				<?php foreach ($cols as $col) { ?>
					<th><?php echo $col['name']; ?></th>
				<?php } ?>
				<?php foreach ($cols as $col) { ?>
					<th><?php echo $col['name']; ?></th>
				<?php } ?>
			</tr>
		</thead>
	</table>
</div>
<?php 
    $priceListExcelClass = new PriceListExcel($json['prices']);
    $products = $priceListExcelClass->getPriceList($cols);
?>
<table id="orderTableBody">
	<colgroup>
		<?php foreach ($colsSizes as $col) { ?>
			<col span="1" style="width: <?php echo $col; ?>">
		<?php } ?>
	</colgroup>
	<tbody id="orderBody">
	<?php
	   foreach($products as $code => $product)
	   { ?>
			<tr id = <?php echo $code; ?>>
	       		<?php 
	       		$span = $priceListExcelClass->getAttributeRawSpan($code, 'brand');
	       		echo $span === 0 ? '' : '<td rowspan="' . $span . '">' . $product['newPriceList']['brand'] . '</td>';
	       		$span = $priceListExcelClass->getAttributeRawSpan($code, 'name');
	       		echo $span === 0 ? '' : '<td rowspan="' . $span . '">' . $product['newPriceList']['name'] . '</td>';
	       		$span = $priceListExcelClass->getAttributeRawSpan($code, 'size');
	       		echo $span === 0 ? '' : '<td rowspan="' . $span . '">' . $product['newPriceList']['size'] . '</td>';
	       		?>
	       		<td><?php echo $product['newPriceList']['addon']; ?>
	       		<td><?php echo $product['newPriceList']['barcode']; ?></td>
	       		<?php
	       		foreach($product['priceList'] as $key => $priceList) {
	       		    echo '<td id="o' . $key . ':' . $product['id'] . '">' . $priceList . '</td>';
	       		}
	       		foreach($product['newPriceList']['prices'] as $key => $priceList)
	       		{?>
	       		    <td class="input"><input type="number" id="<?php echo 'i' . $key . ':' . $product['id'];?>" onchange="change(event)" value="<?php echo $priceList; ?>" class="price-input"></td>
	       		<?php }
	       		?>
	       		
	   <?php } ?>
	</tbody>
</table>
