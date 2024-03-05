<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/priceList/Classes/ProductTypes.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/priceList/Classes/ProductAttributes.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/priceList/Classes/ProductPriceTypes.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/priceList/Classes/ProductBrands.php');

	$productTypesClass = new ProductTypes();
	$productAttributesClass = new ProductAttributes();
	$productPriceTypesClass = new ProductPriceTypes();
	$productBrandsClass = new ProductBrands();
	
	$_SESSION['productTypes'] = $productTypesClass->getproductTypes();
	$_SESSION['productPriceTypes'] = $productPriceTypesClass->getProductPriceTypes();

	if(isset($_GET['productType'])) {
		$productTypeUrl = $_GET['productType'];
    }
    else {
		$productTypeUrl = 'manualLoad';
    }
    
    if ($productTypeUrl == 'all'){
        $_SESSION['productAttributes'] = array();
        $_SESSION['productBrands'] = $productBrandsClass->getProductBrands();
    }
    elseif ($productTypeUrl != 'manualLoad') {
        $_SESSION['productAttributes'] = $productAttributesClass->getProductAttributes($productTypesClass->getProductTypeByCode($productTypeUrl)['productType_id']);
        $_SESSION['productBrands'] = $productBrandsClass->getProductBrands($productTypesClass->getProductTypeByCode($productTypeUrl)['productType_id']);
    }
    
		
?>
<html>
	<head>
		<title>Управление ценами</title>
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
			<button class="tablinks<?php if (isset ($productTypeUrl)) echo $productTypeUrl == 'manualLoad' ? ' active' : ''; ?>" name="manualLoad" onclick="openLink(this, 'manualLoad')">Загрузка из MS Excel</button>
			<button class="tablinks<?php if (isset ($productTypeUrl)) echo $productTypeUrl == 'all' ? ' active' : ''; ?>" name="all" onclick="openLink(this, 'all')">Все товары</button>
			<?php foreach ($_SESSION['productTypes'] as $productType) { ?>
				<button class="tablinks<?php if (isset ($productTypeUrl)) echo $productTypeUrl == $productType['code'] ? ' active' : ''; ?>" name="<?php echo $productType['code']; ?>" onclick="openLink(this, '<?php echo $productType['code']; ?>')"><?php echo $productType['name']; ?></button>
			<?php }	?>
		</div>
		<div id="tabcontent" class="tabcontent">
			<?php if (isset ($productTypeUrl) && $productTypeUrl != 'manualLoad') { ?>
			<?php if (isset ($productType)) include ($_SERVER['DOCUMENT_ROOT'] . '/priceList/View/filter.php'); ?>
			<?php } ?>
		</div>
		
		<div class="footer">
			<button class="buttons" type="button" id = "save" disabled="true" onclick="save()">Сохранить</button></td>
			<button class="buttons" onclick = "window.open('https://4cleaning.ru/index.php?route=extension/prices/impprices', '_blank')">Обновить цены 4cleaning</button>
			<button class="buttons" onclick = "window.open('https://10kids.ru/index.php?route=extension/prices/impprices', '_blank')">Обновить цены 10kids</button>
		</div>
		
		<script>

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
				
				var productType = name;
				
				location.replace ("?productType=" + productType);
			}
			//sel.addEventListener('change', function (e) {
			//	alert('changed');
			//});

			window.onload = async function() {
				
				var url = new URL(location);
				var productType = url.searchParams.get("productType");
				
				if (productType == null)
					location.replace ("?productType=" + '<?php echo $productTypeUrl; ?>');
				//document.getElementById("filter_button").disabled = true;
				//document.getElementById("refresh_button").disabled = true;
				showLoad('Загрузка данных... подождите пару секунд...');
				//var resp = await fetch("renewPriceList.php?productType=" + productType);

				//if (resp.ok)
				//{
				//	var orders = await resp.json();
				//	document.getElementById("ordersCount").innerHTML = Object.keys(orders).length;
				//}
				
				await updateTableBody();
				
				//document.getElementById("filter_button").disabled = false;
				//document.getElementById("refresh_button").disabled = false;
				deleteLoad (window);
				//document.getElementById("barcodePack").focus();
			}
			
			async function updateTableBody ()
			{
				var url = new URL(location);
				var productType = url.searchParams.get("productType");

				if (productType != "manualLoad")
				{
    				var resp = await fetch("renewPriceList.php?productType=" + productType);
    
    				if (resp.ok)
    				{
    					var html =  await resp.text();
    					document.getElementById("orderBody").innerHTML = html;
    				}
				}
				else
				{
    				var resp = await fetch("View/view.php");
					if (resp.ok)
					{
    					var html =  await resp.text();
    					document.getElementById("tabcontent").innerHTML = html;
    				}
				}
			}
		</script>
		
		<script src="/js/myjs.js?n=<?php echo date("Y-m-d-H-i-s", strtotime("now")); ?>"></script>
		<script src="/js/priceList.js?n=<?php echo date("Y-m-d-H-i-s", strtotime("now")); ?>"></script>
		

	</body>
</html>


