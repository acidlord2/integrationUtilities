<?php
	require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	
	ini_set("display_errors", 1);
	error_reporting(E_ALL);
	//require_once('classes/log.php')
	//var $log = new Log("log.txt");
//	log.write('aaa');
	try {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	}
	catch(Exception $e) {    
		echo "Message : " . $e->getMessage();
		echo "Code : " . $e->getCode();
	}						
	if(isset($_GET['shippingDate']))
		$shippingDate = $_GET['shippingDate'];
	else
		$shippingDate = Date ('Y-m-d', strtotime('now'));
	
	if(isset($_GET['agent']))
		$agent = $_GET["agent"];
	else
		$agent = 'Beru';
	
	if(isset($_GET['org']))
		$org = $_GET["org"];
	else
		$org = 'ullo';
	
	$_SESSION['colWidth'] = array('40px', '8%', '8%', '8%', '8%', '5%', '12%', '8%', '8%', '8%', '8%', '8%');
		
?>
<html>
	<head>
		<title>Откгрузка заказов</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<script>
			if (location.search == "") {
				location.search = "?shippingDate=<?php echo $shippingDate; ?>&agent=<?php echo $agent; ?>&org=<?php echo $org; ?>";
			}
		</script>
		<link rel = "stylesheet" type = "text/css"  href = "/css/styles.css?v=<?php echo date("Y-m-d-H-i-s", strtotime("now")); ?>" />
	</head>
	<body>
		<div align="center">
			<div id="header">
				<?php require_once ($_SERVER['DOCUMENT_ROOT'] . '/header.php'); ?>
				<div class = "title">
					Список готовых к отгрузке заказов
				</div>
				<div style="margin-bottom: 13px; margin-top: 14px;"> 
					Дата отгрузки: <input type="date" id="shippingDate" data-date-format="DD.MM.YYYY" value="<?php echo $shippingDate; ?>">
					Контрагент: <select id="agent" value="<?php echo $agent; ?> " onchange="change()">
						<option value="Beru" <?php echo $agent=='Beru' ? ' selected' : ''; ?>>Яндекс маркет</option>
						<option value="Ozon" <?php echo $agent=='Ozon' ? ' selected' : ''; ?>>Ozon</option>
						<option value="WB" <?php echo $agent=='WB' ? ' selected' : ''; ?>>Wildberries</option>
						<option value="SM" <?php echo $agent=='SM' ? ' selected' : ''; ?>>Спортмастер</option>
					</select>
					<?php if ($agent == 'Beru') { ?>
						<span id="s2">Организация: 
							<select id="org" value="<?php echo $org; ?>">
								<option value="ullo" <?php echo $org=='ullo' ? ' selected' : ''; ?>>Юлло</option>
								<option value="kosmos" <?php echo $org=='kosmos' ? ' selected' : ''; ?>>Космос</option>
								<option value="summit" <?php echo $org=='summit' ? ' selected' : ''; ?>>Саммит</option>
							</select>
						</span>
					<?php } ?>
					<?php if ($agent == 'Ozon') { ?>
						<span id="s2">Организация: 
							<select id="org" value="<?php echo $org; ?>">
								<option value="ullo" <?php echo $org=='ullo' ? ' selected' : ''; ?>>Юлло</option>
								<option value="kaori" <?php echo $org=='kaori' ? ' selected' : ''; ?>>Каори</option>
							</select>
						</span>
					<?php } ?>
					<?php if ($agent == 'WB') { ?>
						<span id="s2">Организация: 
							<select id="org" value="<?php echo $org; ?>">
							<option value="kosmos" <?php echo $org=='kosmos' ? ' selected' : ''; ?>>Космос</option>
							<option value="ullo" <?php echo $org=='ullo' ? ' selected' : ''; ?>>Юлло</option>
							</select>
						</span>
					<?php } ?>
					<?php if ($agent == 'SM') { ?>
						<span id="s2">Организация: 
							<select id="org" value="<?php echo $org; ?>">
								<option value="ullo" <?php echo $org=='ullo' ? ' selected' : ''; ?>>Юлло</option>
							</select>
						</span>
					<?php } ?>
					<!--<button type="button" id = "filter_button" onclick="filterOrders()">Фильтр</button>-->
					<button type="button" id = "refresh_button" onclick="refreshOrders()">Обновить данные из МС</button>
					<?php if($agent == 'Goods') { ?>
	`					<button type="button" id = "refresh_goods" onclick="refreshGoods()">Обновить данные из Сбер-а</button>
					<?php } ?>
				</div>
				<div style="margin-bottom: 5px; margin-top: 5px;"> 
					Всего заказов: <b id = "ordersCount">0</b>
					Сканировано заказов: <b id = "scanOrdersCount">0</b>
					Штрихкод заказа: <input type="text" id="barcodePack" size="20" onkeypress="searchBarcode(event)">
				</div>
				<table id="orderTableHead">
					<colgroup>
						<col span="1" style="width: <?php echo $_SESSION['colWidth'][0]; ?>;">
						<col span="1" style="width: <?php echo $_SESSION['colWidth'][1]; ?>;">
						<col span="1" style="width: <?php echo $_SESSION['colWidth'][2]; ?>;">
						<col span="1" style="width: <?php echo $_SESSION['colWidth'][3]; ?>;">
						<col span="1" style="width: <?php echo $_SESSION['colWidth'][4]; ?>;">
						<col span="1" style="width: <?php echo $_SESSION['colWidth'][5]; ?>;">
						<col span="1" style="width: <?php echo $_SESSION['colWidth'][6]; ?>;">
						<col span="1" style="width: <?php echo $_SESSION['colWidth'][7]; ?>;">
						<col span="1" style="width: <?php echo $_SESSION['colWidth'][9]; ?>;">
						<col span="1" style="width: <?php echo $_SESSION['colWidth'][10]; ?>;">
						<col span="1" style="width: <?php echo $_SESSION['colWidth'][11]; ?>;">
					</colgroup>
					<thead>
						<tr id = "table_header">
							<th><input type="checkbox" id="allOrders" onclick="checkAll(this)" disabled></th>
							<th>Номер заказа</th>
							<th>Штрихкод</th>
							<th>Дата заказа</th>
							<th>Дата отгузки</th>
							<th>Сумма заказа</th>
							<th>Контрагент</th>
							<th>Организация</th>
							<th>Статус</th>
							<th>Отмена маркетплейс</th>
							<th>
								<button class="changed-ok" type="button" id = "ball" onclick="shipAllOrders()">Отгрузить все</button>
							</th>
						</tr>
					</thead>
				</table>
			</div>
			<table id="orderTableBody">
				<colgroup>
					<col span="1" style="width: <?php echo $_SESSION['colWidth'][0]; ?>;">
					<col span="1" style="width: <?php echo $_SESSION['colWidth'][1]; ?>;">
					<col span="1" style="width: <?php echo $_SESSION['colWidth'][2]; ?>;">
					<col span="1" style="width: <?php echo $_SESSION['colWidth'][3]; ?>;">
					<col span="1" style="width: <?php echo $_SESSION['colWidth'][4]; ?>;">
					<col span="1" style="width: <?php echo $_SESSION['colWidth'][5]; ?>;">
					<col span="1" style="width: <?php echo $_SESSION['colWidth'][6]; ?>;">
					<col span="1" style="width: <?php echo $_SESSION['colWidth'][7]; ?>;">
					<col span="1" style="width: <?php echo $_SESSION['colWidth'][9]; ?>;">
					<col span="1" style="width: <?php echo $_SESSION['colWidth'][10]; ?>;">
					<col span="1" style="width: <?php echo $_SESSION['colWidth'][11]; ?>;">
				</colgroup>
				<tbody id="orderBody"></tbody>
			</table>
		</div>

		<div id="myModal" class="modal">

		  <!-- Modal content -->
		  <div class="modal-content">
			<span class="close">&times;</span>
			<p id="modal-text">Данного товара нет в заказе</p>
		  </div>

		</div>
		
		<script src="/js/myjs.js?n=<?php echo date("Y-m-d-H-i-s", strtotime("now")); ?>"></script>
		<script>
			//sel.addEventListener('change', function (e) {
			//	alert('changed');
			//});
			function change() {
				if (document.getElementById("agent").value == "Beru" || document.getElementById("agent").value == "Ozon")
					document.getElementById("s2").style.display = "inline";
				else
					document.getElementById("s2").style.display = "none";
			}


			async function filterOrders() {
				var shippingDate = document.getElementById("shippingDate").value;
				var agent = document.getElementById("agent").value;
				var org = document.getElementById("org").value;
				location.replace ("?shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org);

			}

			async function refreshOrders() {
				var shippingDate = document.getElementById("shippingDate").value;
				var agent = document.getElementById("agent").value;
				var org = document.getElementById("org").value;
				location.replace ("?shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org + "&refresh=1");
				
			}

			async function refreshGoods() {
				var url = new URL(location);
				var shippingDate = url.searchParams.get("shippingDate");
				var agent = url.searchParams.get("agent");
				var org = url.searchParams.get("org");

				showLoad('Обработка заказов... подождите пару секунд...');
				var names = document.getElementsByName ('orderNumber');
				var orderNumbers = [];
				for (var i=0; i < names.length; i++) {
					orderNumbers.push(names[i].innerText);
				}
				
				var resp = await fetch("smmSyncOrders.php",
            		{
            			method: 'POST',
            			headers: {'Content-Type': 'application/json'},
            			body: JSON.stringify(orderNumbers)
            		}
            	);
				var test = await resp.text();
				await updateTableBody();
				
				deleteLoad (window);
				location.replace ("?shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org + "&refresh=1");

			}


			window.onload = async function() {
				var url = new URL(location);
				var shippingDate = url.searchParams.get("shippingDate");
				var agent = url.searchParams.get("agent");
				var org = url.searchParams.get("org");
				var refresh = url.searchParams.get("refresh");
				
				//document.getElementById("filter_button").disabled = true;
				document.getElementById("refresh_button").disabled = true;
				//document.getElementById("refresh_Goods").disabled = true;
				showLoad('Загрузка данных... подождите пару секунд...');
				var resp = await fetch("getdata.php?shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org + "&refresh=" + refresh);

				if (resp.ok)
				{
					var orders = await resp.json();
					document.getElementById("ordersCount").innerHTML = Object.keys(orders).length;
				}
				
				await updateTableBody("");
				
				//document.getElementById("filter_button").disabled = false;
				document.getElementById("refresh_button").disabled = false;
				//document.getElementById("refresh_Goods").disabled = false;
				deleteLoad (window);
				document.getElementById("barcodePack").focus();
			}

			async function searchBarcode(event) {
				var x = event.charCode || event.keyCode;  // Get the Unicode value
				var y = String.fromCharCode(x);       // Convert the value into a character
				if (x==13) {
					var barcodeElement = document.getElementById("barcodePack");
					await updateTableBody(barcodeElement.value);
					barcodeElement.select();
				}
			}
			async function shipOrder(order) {
				var url = new URL(location);
				var shippingDate = url.searchParams.get("shippingDate");
				var agent = url.searchParams.get("agent");
				var org = url.searchParams.get("org");
				//document.getElementById("filter_button").disabled = true;
				showLoad('Обработка данных... подождите пару секунд...');

				var resp = await fetch("createShipping.php?order=" + order + "&shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org);
				
				var barcodeElement = document.getElementById("barcodePack");
				await updateTableBody();
				//document.getElementById("filter_button").disabled = false;
				deleteLoad (window);
				document.getElementById("barcodePack").focus();
				barcodeElement.select();
			}

			async function shipAllOrders() {
				
				var url = new URL(location);
				var shippingDate = url.searchParams.get("shippingDate");
				var agent = url.searchParams.get("agent");
				var org = url.searchParams.get("org");
				//document.getElementById("filter_button").disabled = true;
				showLoad('Обработка данных... подождите пару секунд...');
				disableScroll();
				var barcodeElement = document.getElementById("barcodePack");
				
				var checkboxes = document.querySelectorAll("input[type=checkbox][name=orderCheckbox]:checked");
				for(var i=0;i<checkboxes.length;i++)
				{
					try {
						var resp = await fetch("createShipping.php?order=" + checkboxes[i].id.substring(2) + "&shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org);
					}
					catch (exception) {
						console.log (exception);
					}
					updateLoad('Обработка данных... подождите пару секунд...<br>Обработано ' + (i + 1) + ' из ' + checkboxes.length + ' заказов');
				
					await updateTableBody();
				}
				//document.getElementById("filter_button").disabled = false;
				
				deleteLoad (window);
				enableScroll();
				document.getElementById("barcodePack").focus();
				barcodeElement.select();
			}
			
			async function cancelOrder(order) {
				var url = new URL(location);
				var shippingDate = url.searchParams.get("shippingDate");
				var agent = url.searchParams.get("agent");
				var org = url.searchParams.get("org");
				//document.getElementById("filter_button").disabled = true;
				showLoad('Обработка данных... подождите пару секунд...');
				var resp = await fetch("cancelOrder.php?order=" + order + "&shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org);
				
				var barcodeElement = document.getElementById("barcodePack");
				await updateTableBody();
				//document.getElementById("filter_button").disabled = false;
				deleteLoad (window);
				document.getElementById("barcodePack").focus();
				barcodeElement.select();
			}
			async function resetOrder(order) {
				var url = new URL(location);
				var shippingDate = url.searchParams.get("shippingDate");
				var agent = url.searchParams.get("agent");
				var org = url.searchParams.get("org");
				//document.getElementById("filter_button").disabled = true;
				showLoad('Обработка данных... подождите пару секунд...');

				var resp = await fetch("resetOrder.php?order=" + order + "&shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org);

				var barcodeElement = document.getElementById("barcodePack");
				await updateTableBody(barcodeElement.value);
				//document.getElementById("filter_button").disabled = false;
				deleteLoad (window);
				document.getElementById("barcodePack").focus();
				barcodeElement.select();
			}
			async function updateTableBody (text)
			{
				var url = new URL(location);
				var shippingDate = url.searchParams.get("shippingDate");
				var agent = url.searchParams.get("agent");
				var org = url.searchParams.get("org");
				var agent = "<?php echo $agent; ?>";
				if (text === "" || text == null)
					var resp = await fetch("reneworders.php?shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org);
				else
					var resp = await fetch("reneworders.php?order=" + encodeURIComponent(text.trim()) + "&shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org);
				if (resp.ok)
				{
					var html =  await resp.text();
					document.getElementById("orderBody").innerHTML = html;
				}
				if (text != null && text !== "")
				{
					var resp = await fetch("getOrder.php?order=" + encodeURIComponent(text.trim()) + "&shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org);
					if (resp.ok)
					{
						var id = await resp.text();
						if (id.trim() != "")
						{
							var resp = await fetch("getOrder.php?order=" + encodeURIComponent(text.trim()) + "&select=mpcancelFlag" + "&shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org);
							var mpcancelFlag = await resp.text();
							var resp = await fetch("getOrder.php?order=" + encodeURIComponent(text.trim()) + "&select=scanCount" + "&shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org);
							var scanCount = await resp.text();
							var str = document.getElementById(id.trim());
							str.scrollIntoView(false);

							var checkboxes = document.querySelectorAll("input[type=checkbox][name=orderCheckbox]:checked");
							document.getElementById("scanOrdersCount").innerHTML = Object.keys(checkboxes).length;

							if (mpcancelFlag.trim() == 1)
								playAudio('OOGAhorn.mp3');
							else if (scanCount > 1)
								playAudio('meu.mp3');
							else if (document.getElementById("scanOrdersCount").innerHTML == document.getElementById("ordersCount").innerHTML)
								playAudio('bingo.mp3');
							else
								playAudio('okay-1.mp3');
						}
						else
							playAudio('gun.mp3');
					}
				}
			}
			
			async function checkAll(checkbox)
			{
				var checkboxes = document.getElementsByName ('orderCheckbox');
				for (var i=0; i < checkboxes.length; i++)
					checkboxes[i].checked = checkbox.checked;
			}
		</script>
	</body>
</html>


