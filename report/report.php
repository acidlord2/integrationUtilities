<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

	if(isset($_GET['startDate'])) {
	    $startDate = $_GET['startDate'];
	}
	else
	{
	    $startDate = Date ('Y-m-01', strtotime('-1 month'));
	}
	
	if(isset($_GET['endDate'])) {
	    $endDate = $_GET['endDate'];
	}
	else {
	    $endDate = Date ('Y-m-t', strtotime('-1 month'));
	}

	if(isset($_GET['report'])) {
	    $report = $_GET['report'];
	}
	else {
	    $report = 'Sales';
	}
	
?>
<html>
	<head>
		<title>Построение отчета</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<link rel = "stylesheet" type = "text/css"  href = "/css/styles.css?v=<?php echo date("Y-m-d-H-i-s", strtotime("now")); ?>" />
		<script>
			window.onload = async function() {
				
				var url = new URL(location);
				var startDate = url.searchParams.get("startDate");
				var endDate = url.searchParams.get("endDate");
				var report = url.searchParams.get("report");
				
				if (startDate == null || endDate == null || report == null)
					location.replace ("?startDate=<?php echo $startDate; ?>&endDate=<?php echo $endDate; ?>&report=<?php echo $report; ?>");
				
			}
		</script>
	</head>
	<body style="overflow:hidden;">
		<div align="center">
			<div id="header">
				<?php include ($_SERVER['DOCUMENT_ROOT'] . '/header.php'); ?>
			</div>
		</div>

		<div class="tab">
			<button class="tablinks<?php if (isset ($report)) echo $report == 'Sales' ? ' active' : ''; ?>" name="Sales" onclick="openLink(this, 'Sales')">Продажи</button>
		</div>
		
		<div id="tabcontent" class="tabcontent">
			<?php if (isset ($report)) include ("view/filter.php"); ?>
			<?php if (isset ($report)) include ("view/files.php"); ?>
		</div>
		<script>
			let printStickerCount = 0;
			let printInvoiceCount = 0;

			window.onload = async function() {
				
				var url = new URL(location);
				var startDate = url.searchParams.get("startDate");
				var endDate = url.searchParams.get("endDate");
				var report = url.searchParams.get("report");
				
				if (startDate == null || endDate == null || report == null)
					location.replace ("?startDate=<?php echo $startDate; ?>&endDate=<?php echo $endDate; ?>&report=<?php echo $report; ?>");
				
				var element = document.getElementById('refresh_button');
				getOrdersCount(element);
			}
			
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
				var startDate = document.getElementById('startDate');
				var endDate = document.getElementById('endDate');
				startDate.value = url.searchParams.get("startDate");
				endDate.value = url.searchParams.get("endDate");
				var report = name;
				
				location.replace ("?startDate=" + startDate + "&endDate=" + endDate + "&report=" + report);
			}

			
			async function formatMonth()
			{
				var url = new URL(location);
				var report = url.searchParams.get("report");
				
				var startDate = document.getElementById('startDate');
				var endDate = document.getElementById('endDate');
				var date = new Date (startDate.value);
				date.setDate(1);
                var ye = new Intl.DateTimeFormat('en', { year: 'numeric' }).format(date);
                var mo = new Intl.DateTimeFormat('en', { month: '2-digit' }).format(date);
                var da = new Intl.DateTimeFormat('en', { day: '2-digit' }).format(date);

				if (startDate.value != ye + "-" + mo + "-" + da) {
					startDate.value = ye + "-" + mo + "-" + da;
    				var date2 = new Date(date.getFullYear(), date.getMonth()+1, 0);
                    ye = new Intl.DateTimeFormat('en', { year: 'numeric' }).format(date2);
                    mo = new Intl.DateTimeFormat('en', { month: '2-digit' }).format(date2);
                    da = new Intl.DateTimeFormat('en', { day: '2-digit' }).format(date2);
    				endDate.value = ye + "-" + mo + "-" + da;
    				location.replace ("?startDate=" + startDate.value + "&endDate=" + endDate.value + "&report=" + report);
				}
				else
				{
					startDate.value = ye + "-" + mo + "-" + da;
				}
				
			}
		</script>
		
		<script src="/js/reports.js?n=<?php echo date("Y-m-d-H-i-s", strtotime("now")); ?>"></script>
	</body>
</html>


