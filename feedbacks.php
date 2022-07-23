<?php
	require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	
	ini_set("display_errors", 1);
	error_reporting(E_ALL);
	//require_once('classes/log.php')
	//var $log = new Log("log.txt");
//	log.write('aaa');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/feedbacks.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	
	//$feedbacks = Feedbacks::getYandexFeedbacks('21533937', date('d-m-Y', strtotime ('-1 month')));
?>
<html>
	<head>
		<title>Поиск отзывов</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<link rel = "stylesheet" type = "text/css"  href = "/css/styles.css?v=2" />
	</head>
	<body>
		<div align="center">
			<div id="header">
				<?php require_once ($_SERVER['DOCUMENT_ROOT'] . '/header.php'); ?>
				<div class = "title">
					Поиск отзывов
				</div>
				<div style="margin-bottom: 5px; margin-top: 5px;"> 
					Номера заказов для поиска: <input type="text" id="order" size="20" onkeypress="onSearchFeedback(event)">
					<button type="button" id="filter_button" onclick="searchFeedback()">Найти отзыв</button>			
				</div>
			</div>
			<table id="feedbacks_table"></table>
		</div>

		<div id="myModal" class="modal">
		  <!-- Modal content -->
		  <div class="modal-content">
			<span class="close">&times;</span>
			<p id="modal-text">Отзыв не найден</p>
		  </div>
		</div>
		<script>
			async function onSearchFeedback(event) {
				var x = event.charCode || event.keyCode;  // Get the Unicode value
				var y = String.fromCharCode(x);       // Convert the value into a character
				if (x==13) {
					var barcodeElement = document.getElementById("order");
					if (barcodeElement.value === "")
						return;

					searchFeedback();
				}
				var a = document.getElementById("order").value + y;
				if (a.match(/[^\d*(,\d*)?]/g))
					event.preventDefault();
				//var a = document.getElementById("order").value.replace (/[^\d*(,\d*)?]/g, '');
				//document.getElementById("order").value = a;
				//event.preventDefault();
			}
			
			async function searchFeedback()
			{
				if (document.getElementById("order").value === "")
					return;
				var resp = await fetch('searchFeedbacks.php?orders=' + document.getElementById("order").value);
				if (resp.ok) {
					document.getElementById("feedbacks_table").innerHTML = await resp.text();
				}
				
			}
			async function updateFeedbackStatus (feedback)
			{
				var resp = await fetch('updateFeedbackStatus.php?feedback=' + feedback);
				if (resp.ok) {
					searchFeedback();
				}
				
			}
			
		</script>
	</body>
</html>


