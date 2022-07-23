<?php
	require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once ($_SERVER['DOCUMENT_ROOT'] . '/classes/products.php');
	require_once ($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	
	$brands = products::getPriceListBrands();
	$priceTypes = products::getPriceTypes();
	$logger = new Log ('pricemod.log');
?>

<html>
	<head>
		<title>Изменение цен</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<link rel = "stylesheet" type = "text/css"  href = "/css/styles.css?n=<?php echo date("Y-m-d-H-i-s", strtotime("now")); ?>" />
	</head>
	<body style="margin:0;padding:0">
		<?php require_once($_SERVER['DOCUMENT_ROOT'] . '/header.php'); ?>
		<div align="center" style="margin-bottom: 50px">
			<div id="header">
				<div style="margin-bottom: 13px; margin-top: 14px; font-size: 200%; color:#F7971D;">
					Изменение цен
				</div>
			</div>
			<table id="pricesTable" class="fixed-headers">
				<colgroup span="5" style="width: 60%">
					<col style="width: 15%"/>
					<col style="width: 35%"/>
					<col style="width: 20%"/>
					<col style="width: 15%"/>
					<col style="width: 15%"/>
				</colgroup>
				<colgroup span="<?php echo count ($priceTypes)?>" style="width: 15%">
				</colgroup>
				<colgroup span="<?php echo count ($priceTypes)?>" style="width: 15%">
				</colgroup>
				<thead>
					<tr id = "table_header1">
						<th colspan="5">ПОЗИЦИИ</th>
						<th colspan="<?php echo count ($priceTypes)?>">ДЕЙСТВУЮЩИЕ ЦЕНЫ</th>
						<th colspan="<?php echo count ($priceTypes)?>">ВВЕДИТЕ НОВЫЕ ЦЕНЫ</th>
					</tr>
					<tr id = "table_header2">
						<th>Бренд</th>
						<th>Наименование</th>
						<th>Размер</th>
						<th>Вложение</th>
						<th>Код товара</th>
						<?php 
							for ($i=0;$i<2;$i++)
								foreach ($priceTypes as $priceType)
									echo '<th>' . $priceType['price_name'] . '</th>';
						?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($brands as $brand)
						{
							$products = products::getPriceList($brand['ms_group_name'], $brand['prices_list_brand_name']);
							//$logger -> write ('products - ' . json_encode ($products, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
							foreach ($products as $key => $product)
							{ ?>
								<tr id = <?php echo $product['id']; ?>>
									<?php if (!$key) { ?>
										<th rowspan=<?php echo count($products); ?>><?php echo $brand['prices_list_brand_name']; ?></th>
									<?php } ?>
									<td><?php echo $product['type']; ?></td>
									<td><?php echo $product['size']; ?></td>
									<td><?php echo $product['count']; ?></td>
									<td><b><?php echo $product['code']; ?></b></td>
									<?php foreach ($product['prices'] as $pricesKey => $pricesValue) { ?>
										<td id = <?php echo 'o' . $pricesKey . ':' . $product['id']; ?>><?php echo $pricesValue; ?></td>
									<?php } ?>
									<?php foreach ($product['prices'] as $pricesKey => $pricesValue) { ?>
										<td class="input"><input type="number" id="<?php echo 'i' . $pricesKey . ':' . $product['id'];?>" onfocusout="focusout('<?php echo 'i' . $pricesKey . ':' . $product['id'];?>')" onkeypress="checkNewPrice(event)" value="<?php echo $pricesValue; ?>" class="price-input"></td>
									<?php } ?>
								</tr>
					<?php }} ?>
				<tbody>
			</table>
		</div>
		<div class="footer">
			<button class="buttons" type="button" id = "savePrices" disabled="true" onclick="savePrices()">Сохранить</button></td>
			<button class="buttons" onclick = "window.open('https://4cleaning.ru/index.php?route=extension/prices/impprices', '_blank')">Обновить цены 4cleaning</button>
			<button class="buttons" onclick = "window.open('https://10kids.ru/index.php?route=extension/prices/impprices', '_blank')">Обновить цены 10kids</button>
		</div>
		<script src="/js/myjs.js?n=<?php echo date("Y-m-d-H-i-s", strtotime("now")); ?>"></script>
		<script>
			changeArray = {};
			
			function showButton() {
				var buttons = document.getElementsByClassName("buttons");
				for (var i=0; i<buttons.length; i++) {
					buttons[i].disabled = false;
				}
			}

			function hideButton() {
				var buttons = document.getElementsByClassName("buttons");
				for (var i=0; i<buttons.length; i++) {
					buttons[i].disabled = true;
				}
			}

			function add_change(element) {
				var elementIdArray = element.id.split (':');
				var elementId = elementIdArray[1];
				var price = elementIdArray[0].substring (1);
				if (elementId in changeArray)
					changeArray[elementId][price] = element.value;
				else
				{
					changeArray[elementId] = {};
					changeArray[elementId][price] = element.value;
				}
				showButton();
			}

			function remove_change(element) {
				var elementIdArray = element.id.split (':');
				var elementId = elementIdArray[1];
				var price = elementIdArray[0].substring (1);
				if (elementId in changeArray ? price in changeArray[elementId] : false)
					delete changeArray[elementId][price];
				if (changeArray[elementId] != null ? Object.keys(changeArray[elementId]).length == 0 : false)
					delete changeArray[elementId];
				if (changeArray != null ? Object.keys(changeArray).length == 0 : true)
					hideButton();
			}
			
			function changeElement(element) {
				var elementIdArray = element.id.split (':');
				var elementOld = document.getElementById("o" + elementIdArray[0].substring (1) + ":" + elementIdArray[1]);
				var oldVal = parseFloat (elementOld.innerText);
				var newVal = parseFloat (element.value);
				if (oldVal == newVal) {
					element.className = "price-input";
					elementOld.className = "";
					remove_change (element);
					return;
				}
				else if (oldVal > newVal && newVal / oldVal < 0.9)
				{
					element.className = "price-input changed-error";
					elementOld.className = "changed-error";
				}
				else if (oldVal < newVal && oldVal / newVal < 0.9)
				{
					element.className = "price-input changed-error";
					elementOld.className = "changed-error";
				}
				else
				{
					element.className = "price-input changed-ok";
					elementOld.className = "changed-ok";
				}
				
				//add to change array
				add_change (element);
				
			}
			
			function focusout (elementId) {
				changeElement(document.getElementById(elementId));
			}
			
			function checkNewPrice(event) {
				var x = event.charCode || event.keyCode;  // Get the Unicode value
				if (x==13) {
					var element = document.activeElement;
					changeElement (element);
				}
				
				var ctrl = event.ctrlKey ? event.ctrlKey : ((x === 17) ? true : false); // ctrl detection
				if ( x == 86 && ctrl ) {
					console.log (event.clipboardData);
				}
			}

			document.addEventListener('paste', function(event) {
				var cols = (event.clipboardData || window.clipboardData).getData('text').split ("\r\n");
				var element = document.activeElement;
				var ind = parseInt (element.id.substring (1, 2)) - 1;
				
				for (i = 0; i < cols.length; i++) {
					var rows = cols[i].split ("\t");
					for (j = 0; j < rows.length && cols[i] != ""; j++){
						if (j + ind > ind) {
							element2 = element.parentElement.nextElementSibling != null ? element.parentElement.nextElementSibling.children[0] : null;
							if (element2 != null) {
								element = element2;
								element.value = rows[j];
							}
						}
						else 
							element.value = rows[j];
						
						changeElement (element);
					}
					var nextTr = element.parentElement.parentElement.nextElementSibling;
					if (nextTr != null) {
						if (nextTr.children[0].localName == 'th')
							var td = nextTr.children[ind+5+<?php echo count ($priceTypes)?>];
						else
							var td = nextTr.children[ind+4+<?php echo count ($priceTypes)?>];
						
						element = td.children[0];
					}
					else element = null;
						
							
					//element = element.parentElement.parentElement.nextElementSibling.children[ind].children[0];
					if (element == null)
						break;
				}
				event.preventDefault();
			});

			async function savePrices() {
				hideButton();
				showLoad('Загрузка данных... подождите пару секунд...');
				var resp = await fetch('/prices/save_prices.php', {
					method: 'POST',
					headers: {
					  'Content-Type': 'application/json'
					},
					body: JSON.stringify (changeArray)
				});

				if (!resp.ok)
					console.log (await resp.text());
				
				updateLoad('Обновление цен на сайте... подождите пару секунд...');
				//var resp2 = fetch('https://4cleaning.ru/index.php?route=extension/prices/impprices');
				//var resp2 = fetch('https://10kids.ru/index.php?route=extension/prices/impprices');
				//if (!resp2.ok)
				//	console.log (await resp2.text());
				location.reload();
			}
		</script>
	</body>
</html>


