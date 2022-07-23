<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

	if(isset($_GET['shippingDate']))
		$shippingDate = $_GET['shippingDate'];
	
	if(isset($_GET['agent']))
		$agent = $_GET["agent"];
	
	if(isset($_GET['org']))
		$org = $_GET["org"];

	if(isset($_GET['goodstype']))
		$goodstype = $_GET["goodstype"];

	$_SESSION['colWidth'] = array('50%', '20%', '20%', '10%');
?>
<html>
	<head>
		<title>Сборка заказов</title>
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
			<button class="tablinks<?php if (isset ($agent)) echo $agent == 'Ozon' ? ' active' : ''; ?>" name="Ozon" onclick="openLink(this, 'Ozon')">Ozon</button>
			<button class="tablinks<?php if (isset ($agent)) echo $agent == 'Beru' ? ' active' : ''; ?>" name="Beru" onclick="openLink(this, 'Beru')">Beru</button>
			<button class="tablinks<?php if (isset ($agent)) echo $agent == 'Goods' ? ' active' : ''; ?>" name="Goods" onclick="openLink(this, 'Goods')">Goods</button>
			<button class="tablinks<?php if (isset ($agent)) echo $agent == 'Aliexpress' ? ' active' : ''; ?>" name="Aliexpress" onclick="openLink(this, 'Aliexpress')">Aliexpress</button>
			<button class="tablinks<?php if (isset ($agent)) echo $agent == 'Wildberries' ? ' active' : ''; ?>" name="Wildberries" onclick="openLink(this, 'Wildberries')">Wildberries</button>
			<button class="tablinks<?php if (isset ($agent)) echo $agent == 'Curiers' ? ' active' : ''; ?>" name="Curiers" onclick="openLink(this, 'Curiers')">Курьеры</button>
		</div>
		
		<div id="tabcontent" class="tabcontent">
			<?php if (isset ($agent)) include ("view/filter.php"); ?>
		</div>
		
		<script src="/js/myjs.js?n=<?php echo date("Y-m-d-H-i-s", strtotime("now")); ?>"></script>
		
		<script>
			function openLink(element, name) {
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
				element.className += " active";
				
				var url = new URL(location);
				var shippingDate = url.searchParams.get("shippingDate");
				var agent = name;
				var org = url.searchParams.get("org");
				var goodstype = url.searchParams.get("goodstype");
				
				if (shippingDate == null)
					shippingDate = "<?php echo Date ('Y-m-d', strtotime('now')); ?>";
				if (org == null)
					org = 'Ullo';
				if (goodstype == null)
					goodstype = 'Cosmetics';
				
				location.replace ("?shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org + "&goodstype=" + goodstype);
			}
			//sel.addEventListener('change', function (e) {
			//	alert('changed');
			//});


			async function refreshProducts() {
				var url = new URL(location);
				var shippingDate = document.getElementById("shippingDate").value;
				var agent = url.searchParams.get("agent");
				var org = document.getElementById("org").value;
				var goodstype = document.getElementById("goodstype").value;
				location.replace ("?shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org + "&goodstype=" + goodstype);
			}


			window.onload = async function() {
				
				var url = new URL(location);
				var shippingDate = url.searchParams.get("shippingDate");
				var agent = url.searchParams.get("agent");
				var org = url.searchParams.get("org");
				var goodstype = url.searchParams.get("goodstype");
				
				if (org == null || goodstype == null || shippingDate == null || agent == null)
					return;
				
				document.getElementById("refresh_button").disabled = true;
				showLoad('Загрузка данных... подождите пару секунд...');
				var resp = await fetch("getData.php?shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org + "&goodstype=" + goodstype);

				if (resp.ok)
				{
					var products = await resp.json();
					document.getElementById("productsCount").innerHTML = Object.keys(products).length;
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
				var goodstype = url.searchParams.get("goodstype");
				
				var resp = await fetch("renewProductsList.php?shippingDate=" + shippingDate + "&agent=" + agent + "&goodstype=" + goodstype + "&org=" + org);

				if (resp.ok)
				{
					var html =  await resp.text();
					document.getElementById("productsBody").innerHTML = html;
				}
			}
			
			async function printList ()
			{
				var url = new URL(location);
				var shippingDate = url.searchParams.get("shippingDate");
				var agent = url.searchParams.get("agent");
				var org = url.searchParams.get("org");
				var goodstype = url.searchParams.get("goodstype");
				
				var resp = await fetch("exportPackingList.php?shippingDate=" + shippingDate + "&agent=" + agent + "&goodstype=" + goodstype + "&org=" + org);

				if (resp.ok)
				{
					var filename = await resp.text();
					var element = document.createElement('a');
					element.setAttribute('href', filename);
					element.style.display = 'none';
					element.click();
					element.remove();
				}
			}
		</script>
	</body>
</html>


