<?php
	if(isset($_GET['shippingDate']))
		$shippingDate = $_GET['shippingDate'];
	else
		$shippingDate = Date ('Y-m-d', strtotime('now'));
	
	if(isset($_GET['agent']))
		$agent = $_GET["agent"];
	else
		$agent = 'Ozon';
	
	if(isset($_GET['org']))
		$org = $_GET["org"];
	else
		$org = 'Ullo';
	
	$_SESSION['colWidth'] = array('40px', '40px', '12%', '12%', '12%', '12%', '5%', '12%', '12%', '12%', '11%');
?>
<div align="center">
	<div id="header">
		<div class = "title">
			Список заказов на печать
		</div>
		<div style="margin-bottom: 13px; margin-top: 14px;"> 
			Дата отгрузки: <input type="date" id="shippingDate" data-date-format="DD.MM.YYYY" value="<?php echo $shippingDate; ?>">
			<span id="s2" style="display:inline"> Организация: 
				<select id="org" value="<?php echo $org; ?>">
					<option value="ullo" <?php echo $org=='ullo' ? 'selected' : ''; ?>>Юлло</option>
					<option value="kaori" <?php echo $org=='kaori' ? 'selected' : ''; ?>>Каори</option>
				</select>
			</span>
			<!--<button type="button" id = "filter_button" onclick="filterOrders()">Фильтр</button>-->
			<button type="button" id = "refresh_button" onclick="refreshOrdersOzon()">Обновить данныe</button>			
		</div>
		<div style="margin-bottom: 5px; margin-top: 5px;"> 
			Всего заказов: <b id = "ordersCount">0</b>
			Распечатано стикеров: <b id = "printedStickerCount">0</b>
			Распечатано владышей: <b id = "printedInvoiceCount">0</b>
			<button type="button" id = "printStickerButton" onclick="printSticker()">Распечатать следующие 20 стикеров</button>
			<button type="button" id = "printInvoiceButton" onclick="printInvoice()">Распечатать следующие 20 вкладышей</button>
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
				<col span="1" style="width: <?php echo $_SESSION['colWidth'][8]; ?>;">
				<col span="1" style="width: <?php echo $_SESSION['colWidth'][9]; ?>;">
				<col span="1" style="width: <?php echo $_SESSION['colWidth'][10]; ?>;">
			</colgroup>
			<thead>
				<tr id = "table_header">
					<th>OZ</th>
					<th>MS</th>
					<th>Номер заказа</th>
					<th>Штрихкод</th>
					<th>Дата заказа</th>
					<th>Дата отгузки</th>
					<th>Сумма заказа</th>
					<th>Контрагент</th>
					<th>Организация</th>
					<th>Статус</th>
					<th>Отмена маркетплейс</th>
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
			<col span="1" style="width: <?php echo $_SESSION['colWidth'][8]; ?>;">
			<col span="1" style="width: <?php echo $_SESSION['colWidth'][9]; ?>;">
			<col span="1" style="width: <?php echo $_SESSION['colWidth'][10]; ?>;">
		</colgroup>
		<tbody id="orderBody"></tbody>
	</table>
</div>

