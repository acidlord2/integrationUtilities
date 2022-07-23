<div align="center">
	<div id="header">
		<div class = "title">
			<?php if ($report == 'Sales') { ?>Отчет по продажам<?php } ?>
		</div>
		<div style="margin-bottom: 13px; margin-top: 14px;"> 
			Дата начала: <input type="date" id="startDate" data-date-format="DD.MM.YYYY" value="<?php echo $startDate; ?>" onfocusout="formatMonth()">
			Дата окончания: <input type="date" id="endDate" data-date-format="DD.MM.YYYY" value="<?php echo $endDate; ?>" disabled>
			<button type="button" id = "refresh_button" onclick="createReport(this)">Сформировать отчет</button>			
		</div>
		<div style="margin-bottom: 5px; margin-top: 5px;"> 
			<?php if ($report == 'Sales') { ?>
				Всего заказов: <b id = "ordersCount">0</b>
			<?php } ?>
		</div>
	</div>
</div>

