<div align="center">
	<div id="header">
		<div class = "title">
			Список заказов на печать 
			<?php if ($agent == 'Ozon') { ?>Озон<?php } ?>
			<?php if ($agent == 'Beru') { ?>Яндекс<?php } ?>
			<?php if ($agent == 'Goods') { ?>Сбермегамаркет<?php } ?>
			<?php if ($agent == 'WB') { ?>Wildberries<?php } ?>
			<?php if ($agent == 'SM') { ?>Sportmaster<?php } ?>
		</div>
		<div style="margin-bottom: 13px; margin-top: 14px;"> 
			Дата отгрузки: <input type="date" id="shippingDate" data-date-format="DD.MM.YYYY" value="<?php echo $shippingDate; ?>">
			<span id="s2" style="display:inline"> Организация: 
				<?php if ($agent == 'Ozon') { ?>
					<select id="org" value="<?php echo $org; ?>">
						<option value="Ullo" <?php echo $org=='Ullo' ? 'selected' : ''; ?>>Юлло</option>
						<option value="Kaori" <?php echo $org=='Kaori' ? 'selected' : ''; ?>>Каори</option>
					</select>
				<?php } ?>
				<?php if ($agent == 'Beru') { ?>
					<select id="org" value="<?php echo $org; ?>">
						<option value="Ullo" <?php echo $org=='Ullo' ? 'selected' : ''; ?>>Юлло</option>
						<option value="Kosmos" <?php echo $org=='Kosmos' ? 'selected' : ''; ?>>Космос</option>
						<option value="Summit" <?php echo $org=='Summit' ? 'selected' : ''; ?>>Саммит</option>
					</select>
				<?php } ?>
				<?php if ($agent == 'Goods') { ?>
					<select id="org" value="Kaori">
						<option value="Kaori" <?php echo $org=='Kaori' ? 'selected' : ''; ?>>Каори</option>
						<option value="Ullo" <?php echo $org=='Ullo' ? 'selected' : ''; ?>>Юлло</option>
					</select>
				<?php } ?>
				<?php if ($agent == 'WB') { ?>
					<select id="org" value="Kosmos">
						<option value="Kosmos" <?php echo $org=='Kosmos' ? 'selected' : ''; ?>>Космос</option>
						<option value="Ullo" <?php echo $org=='Ullo' ? 'selected' : ''; ?>>Юлло</option>
					</select>
				<?php } ?>				
				<?php if ($agent == 'SM') { ?>
					<select id="org" value="<?php echo $org; ?>" disabled>
						<option value="Ullo" <?php echo $org=='Ullo' ? 'selected' : ''; ?>>Юлло</option>
					</select>
				<?php } ?>
			</span>
			<?php if ($agent == 'WB') { ?>
				Отгрузка: <input type="text" id="shipment" style="width: 80px;">
			<?php } ?>				
			<button type="button" id = "refresh_button" onclick="refreshOrders()">Обновить данные</button>			
		</div>
		<div style="margin-bottom: 5px; margin-top: 5px;"> 
			Всего заказов: <b id = "ordersCount">0</b>
			<?php if ($agent == 'Ozon' || $org == 'aruba' || $agent == 'WB' || $agent == 'SM') { ?>
				Распечатано стикеров: <b id = "printedStickerCount">0</b>
			<?php } ?>
			Распечатано вкладышей: <b id = "printedInvoiceCount">0</b>
		</div>
		<table id="orderTableHead" class="tableBig">
			<colgroup>
				<?php if ($agent == 'Ozon' || $org == 'aruba' || $agent == 'WB' || $agent == 'SM') { ?>
					<col span="1" style="width: <?php echo $_SESSION['colWidth'][0]; ?>;">
				<?php } ?>
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
					<?php if ($agent == 'Ozon' || $org == 'aruba') { ?>
						<th>OZ</th>
					<?php } ?>
					<?php if ($agent == 'WB') { ?>
						<th>WB</th>
					<?php } ?>
					<?php if ($agent == 'SM') { ?>
						<th>SM</th>
					<?php } ?>
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
			<?php if ($agent == 'Ozon' || $org == 'aruba' || $agent == 'WB' || $agent == 'SM') { ?>
				<col span="1" style="width: <?php echo $_SESSION['colWidth'][0]; ?>;">
			<?php } ?>
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

