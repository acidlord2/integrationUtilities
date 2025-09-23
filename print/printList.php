<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

	if(isset($_GET['shippingDate']))
		$shippingDate = $_GET['shippingDate'];
	else
		$shippingDate = Date ('Y-m-d', strtotime('now'));
	
	if(isset($_GET['agent']))
		$agent = $_GET["agent"];
	
	if(isset($_GET['org']))
		$org = $_GET["org"];
	else
		$org = 'Ullo';

	$_SESSION['colWidth'] = array('40px', '40px', '12%', '12%', '12%', '12%', '5%', '12%', '12%', '12%', '11%');
?>
<html>
	<head>
		<title>Печать заказов</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<link rel = "stylesheet" type = "text/css"  href = "/css/styles.css?v=<?php echo date("Y-m-d-H-i-s", strtotime("now")); ?>" />
	</head>
	<body style="overflow:hidden;">
		<div align="center">
			<div id="header">
				<?php require_once ($_SERVER['DOCUMENT_ROOT'] . '/header.php'); ?>
			</div>
		</div>

		<div class="tab">
			<button class="tablinks<?php if (isset ($agent)) echo $agent == 'Ozon' ? ' active' : ''; ?>" name="Ozon" onclick="openLink(this, 'Ozon')">Озон</button>
			<button class="tablinks<?php if (isset ($agent)) echo $agent == 'Beru' ? ' active' : ''; ?>" name="Beru" onclick="openLink(this, 'Beru')">Яндекс</button>
			<button class="tablinks<?php if (isset ($agent)) echo $agent == 'WB' ? ' active' : ''; ?>" name="WB" onclick="openLink(this, 'WB')">Wildberries</button>
			<button class="tablinks<?php if (isset ($agent)) echo $agent == 'SM' ? ' active' : ''; ?>" name="SM" onclick="openLink(this, 'SM')">Спортмастер</button>
		</div>
		
		<div id="tabcontent" class="tabcontent">
			<?php if (isset ($agent)) include ("view/filter.php"); ?>
		</div>
		
		<script>
			let printStickerCount = 0;
			let printInvoiceCount = 0;

			async function openLink(element, name) {
				// Declare all variables
				var i, tabcontent, tablinks;

				// Get all elements with class="tabcontent" and hide them
				//tabcontent = document.getElementsByClassName("tabcontent");
				//for (i = 0; i < tabcontent.length; i++) {
				//	tabcontent[i].style.display = "none";
				//}

				// Get all elements with class="tablinks" and remove the class "active"
				tablinks = document.getElementsByClassName("tablinks");
				for (i = 0; i < tablinks.length; i++) {
					tablinks[i].className = tablinks[i].className.replace(" active", "");
				}

				// Show the current tab, and add an "active" class to the link that opened the tab
				//document.getElementById(name).style.display = null;
				//element.className += " active";
				
				var url = new URL(location);
				var shippingDate = url.searchParams.get("shippingDate");
				var agent = name;
				var org = url.searchParams.get("org");

				if (shippingDate == null)
					shippingDate = "<?php echo Date ('Y-m-d', strtotime('now')); ?>";
				if (org == null)
					org = 'Ullo';
				
				location.replace ("?shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org);
			}
			//sel.addEventListener('change', function (e) {
			//	alert('changed');
			//});

			async function refreshOrders() {
				
				var url = new URL(location);
				var shippingDate = document.getElementById("shippingDate").value;
				var agent = url.searchParams.get("agent");
				var org = document.getElementById("org").value;
				var shipmentElement = document.getElementById("shipment");
				var shipment = shipmentElement ? shipmentElement.value : "";
				var url = "?shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org;
				if (shipment) {
					url += "&shipment=" + encodeURIComponent(shipment);
				}
				location.replace(url);
			}

			window.onload = async function() {
				
				var url = new URL(location);
				var shippingDate = url.searchParams.get("shippingDate");
				var agent = url.searchParams.get("agent");
				var org = url.searchParams.get("org");
				var shipment = url.searchParams.get("shipment") || "";
				
				if (org == null || shippingDate == null || agent == null)
					return;
				//document.getElementById("filter_button").disabled = true;
				document.getElementById("refresh_button").disabled = true;
				showLoad('Загрузка данных... подождите пару секунд...');
				var url = "getData.php?shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org;
				if (shipment) {
					url += "&shipment=" + encodeURIComponent(shipment);
				}
				var resp = await fetch(url);

				if (resp.ok)
				{
					var orders = await resp.json();
					document.getElementById("ordersCount").innerHTML = Object.keys(orders).length;
				}
				
				await updateTableBody("");
				
				//document.getElementById("filter_button").disabled = false;
				document.getElementById("refresh_button").disabled = false;
				deleteLoad (window);
				//document.getElementById("barcodePack").focus();
			}
			
			async function updateTableBody (text)
			{
				var url = new URL(location);
				var shippingDate = url.searchParams.get("shippingDate");
				var agent = url.searchParams.get("agent");
				var org = url.searchParams.get("org");
				if (text === "" || text == null)
					var resp = await fetch("renewOrdersList.php?shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org);
				else
					var resp = await fetch("renewOrdersList.php?order=" + encodeURI(text.trim()) + "&shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org);
				if (resp.ok)
				{
					var html =  await resp.text();
					document.getElementById("orderBody").innerHTML = html;
				}
			}
			
			async function changeOzon()
			{
				let c = 0;
				var checkboxes = document.getElementsByName ('ozonCheckbox');
				for (var i=0; i < checkboxes.length; i++)
					if (checkboxes[i].checked)
						c++;
				document.getElementById("printedStickerCount").textContent = c;
			}

			async function changeMS()
			{
				let c = 0;
				var checkboxes = document.getElementsByName ('msCheckbox');
				for (var i=0; i < checkboxes.length; i++)
					if (checkboxes[i].checked)
						c++;
				document.getElementById("printedInvoiceCount").textContent = c;
			}
		</script>
		
		<script src="/js/myjs.js?n=<?php echo date("Y-m-d-H-i-s", strtotime("now")); ?>"></script>
		<script src="/js/print.js?n=<?php echo date("Y-m-d-H-i-s", strtotime("now")); ?>"></script>
		

	</body>
</html>


