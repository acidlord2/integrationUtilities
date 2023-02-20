<?php
	$_SESSION['cols'] = array();	

	$colsCount = count ($_SESSION['productAttributes']) + (count ($_SESSION['productPriceTypes']) * 2) + 2;
	$pricesCols = count ($_SESSION['productPriceTypes']) * 2 * 4;
	$tmpColSize = (100 - $pricesCols - 5) / (count ($_SESSION['productAttributes']) + 1);
	
	if($productTypeUrl != 'all') {
	    array_push ($_SESSION['cols'], '5%');
	}
	else {	    
	    array_push ($_SESSION['cols'], '10%');
	}
	array_push ($_SESSION['cols'], $tmpColSize . '%');
	foreach ($_SESSION['productAttributes'] as $productAttribute)
	{
		array_push ($_SESSION['cols'], (($tmpColSize * (count ($_SESSION['productAttributes']) + 2) - $tmpColSize * 2) / count ($_SESSION['productAttributes'])) . '%');
	}
	foreach ($_SESSION['productPriceTypes'] as $productPriceType)
	{
		array_push ($_SESSION['cols'], '4%');
	}
	foreach ($_SESSION['productPriceTypes'] as $productPriceType)
	{
		array_push ($_SESSION['cols'], '4%');
	}
?>

<div align="center">
	<div id="header" class="sticky">
		<div class = "title">
			Цены 
			<?php if($productTypeUrl != 'all') echo $productTypesClass->getProductTypeByCode($productTypeUrl)['add_name2']?>
		</div>
		<table id="orderTableHead" class="tableBig">
			<colgroup>
				<?php foreach ($_SESSION['cols'] as $col) { ?>
					<col span="1" style="width: <?php echo $col; ?>">
				<?php } ?>
			</colgroup>
			<thead>
				<tr id = "table_header1">
					<th colspan="<?php echo count ($_SESSION['productAttributes']) + 2; ?>">ПОЗИЦИИ</th>
					<th colspan="<?php echo count ($_SESSION['productPriceTypes'])?>">ДЕЙСТВУЮЩИЕ ЦЕНЫ</th>
					<th colspan="<?php echo count ($_SESSION['productPriceTypes'])?>">ВВЕДИТЕ НОВЫЕ ЦЕНЫ</th>
				</tr>
				<tr id = "table_header">
					<th>Бренд</th>
					<th>Код</th>
					<?php foreach ($_SESSION['productAttributes'] as $productAttribute) { ?>
						<th><?php echo $productAttribute['name']; ?></th>
					<?php } ?>
					<?php foreach ($_SESSION['productPriceTypes'] as $productPriceType) { ?>
						<th><?php echo $productPriceType['name']; ?></th>
					<?php } ?>
					<?php foreach ($_SESSION['productPriceTypes'] as $productPriceType) { ?>
						<th><?php echo $productPriceType['name']; ?></th>
					<?php } ?>
					
				</tr>
			</thead>
		</table>
	</div>
	<table id="orderTableBody">
		<colgroup>
			<?php foreach ($_SESSION['cols'] as $col) { ?>
				<col span="1" style="width: <?php echo $col; ?>">
			<?php } ?>
		</colgroup>
		<tbody id="orderBody"></tbody>
	</table>
</div>

