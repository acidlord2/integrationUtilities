<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
//	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

	if(isset($_GET['agent']))
		$agent = $_GET["agent"];
?>
<html>
	<head>
		<title>Разбор финансов</title>
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
			<button class="tablinks<?php if (isset ($agent)) echo $agent == 'Yandex' ? ' active' : ''; ?>" name="Yandex" onclick="openLink(this, 'Yandex')">Яндекс</button>
			<button class="tablinks<?php if (isset ($agent)) echo $agent == 'Goods' ? ' active' : ''; ?>" name="Goods" onclick="openLink(this, 'Goods')">Goods</button>
			<button class="tablinks<?php if (isset ($agent)) echo $agent == 'Ozon' ? ' active' : ''; ?>" name="Ozon" onclick="openLink(this, 'Ozon')">Озон</button>
		</div>
		
		<div id="tabcontent" class="tabcontent">
			<?php if (isset ($agent)) include ("view/view.php"); ?>
		</div>
		
		<script src="/js/finances.js?n=<?php echo date("Y-m-d-H-i-s", strtotime("now")); ?>"></script>
	</body>
</html>


