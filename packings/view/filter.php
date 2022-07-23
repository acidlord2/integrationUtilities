<div align="center">
	<div id="header">
		<div class = "title">
			Сборочный лист 
			<?php if ($agent == 'Ozon') { ?>Озон<?php } ?>
			<?php if ($agent == 'Beru') { ?>Беру<?php } ?>
			<?php if ($agent == 'Goods') { ?>Goods<?php } ?>
			<?php if ($agent == 'Aliexpress') { ?>Aliexpress<?php } ?>
			<?php if ($agent == 'Wildberries') { ?>Wildberries<?php } ?>
			<?php if ($agent == 'Curiers') { ?>для заказов с сайтов<?php } ?>
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
						<option value="4cleaning" <?php echo $org=='4cleaning' ? 'selected' : ''; ?>>ИП Гюмюш</option>
					</select>
				<?php } ?>
				<?php if ($agent == 'Goods') { ?>
					<select id="org" value="Kaori">
						<option value="Kaori" selected>Каори</option>
					</select>
				<?php } ?>
				<?php if ($agent == 'Aliexpress') { ?>
					<select id="org" value="<?php echo $org; ?>" disabled>
						<option value="Ullo" <?php echo $org=='Ullo' ? 'selected' : ''; ?>>Юлло</option>
					</select>
				<?php } ?>
				<?php if ($agent == 'Wildberries') { ?>
					<select id="org" value="Kaori" disabled>
						<option value="Kaori" selected>Каори</option>
					</select>
				<?php } ?>
				<?php if ($agent == 'Curiers') { ?>
					<select id="org" value="" disabled>
						<option value="" selected>Заказы сайтов</option>
					</select>
				<?php } ?>
				
			</span>
			<span id="s3" style="display:inline"> Вид товара: 
				<select id="goodstype" value="<?php echo $goodstype; ?>">
					<option value="Cosmetics" <?php echo $goodstype=='Cosmetics' ? 'selected' : ''; ?>>Косметика</option>
					<option value="Diapers" <?php echo $goodstype=='Diapers' ? 'selected' : ''; ?>>Подгузники и трусики</option>
					<option value="Others" <?php echo $goodstype=='Others' ? 'selected' : ''; ?>>Прочее</option>
				</select>
			</span>
			<button type="button" id = "refresh_button" onclick="refreshProducts()">Обновить данные</button>			
		</div>
		<div style="margin-bottom: 5px; margin-top: 5px;"> 
			Всего товаров: <b id = "productsCount">0</b>
			<button type="button" id = "downloadButton" onclick="printList()">Скачать перечень товаров XLS</button>
		</div>
		<table id="orderTableHead" class="tableBig">
			<colgroup>
				<col span="1" style="width: <?php echo $_SESSION['colWidth'][0]; ?>;">
				<col span="1" style="width: <?php echo $_SESSION['colWidth'][1]; ?>;">
				<col span="1" style="width: <?php echo $_SESSION['colWidth'][2]; ?>;">
				<col span="1" style="width: <?php echo $_SESSION['colWidth'][3]; ?>;">
			</colgroup>
			<thead>
				<tr id = "table_header" class="tableBig">
					<th class="tableBig">Товар</th>
					<th class="tableBig">Код</th>
					<th class="tableBig">Штрихкод</th>
					<th class="tableBig">Количество</th>
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
		</colgroup>
		<tbody id="productsBody"></tbody>
	</table>
</div>

