<div align="center">
	<div id="header">
		<div class = "title">
			Разбор финансов 
			<?php if ($agent == 'Ozon') { ?>Озон<?php } ?>
			<?php if ($agent == 'Yandex') { ?>Яндекс<?php } ?>
			<?php if ($agent == 'Goods') { ?>Goods<?php } ?>
			<?php if ($agent == 'Aliexpress') { ?>Aliexpress<?php } ?>
			<?php if ($agent == 'Wildberries') { ?>Wildberries<?php } ?>
		</div>
	</div>
	<div class="body"> 
		<?php if ($agent == 'Ozon') { ?>
		<?php } ?>
		<?php if ($agent == 'Yandex') { ?>
			<div class = "panel">
				<div class = "panel-header">Загрузка файла</div>
				<div class = "panel-content">
					<span>Выберите файл для загрузки:</span>
					<input type="file" name="fileToUploadYandex" id="fileToUploadYandex">	
					<input type="button" value="Загрузить" name="uploadYandexParse" id="uploadYandexParse"  onclick="parseYandex(this)" accept=".xlsx">
					<p id="status"></p>
				</div>
			</div>
			<div class = "panel" id="fileInfo" style="display:none">
				<div class="panel-header">Информация о файле</div>
				<div class="panel-content">
					<div class="left-block">
						<p id="paymentNumber"></p>
						<p id="paymentDate"></p>
						<p id="fileName"></p>
						<p id="shop"></p>
						<p id="period"></p>
						<p id="totalOrdersCharged"></p>
						<p id="totalOrdersStornoed"></p>
						<p id="totalSum"></p>
						<p id="totalSumCharged"></p>
						<p id="totalSumStornoed"></p>
						<p id="totalCommission"></p>
						<input type="button" value="Сохранить в MS" name="uploadYandexSubmit" id="uploadYandexSubmit"  onclick="submitYandex(this)">
					</div>
					<div class="right-block">
						<p><textarea id="story" name="story" rows="15" cols="100" style="display:none">Статистика</textarea></p>
					</div>
				</div>
			</div>
		<?php } ?>
		<?php if ($agent == 'Goods') { ?>
			<div class = "panel">
				<div class = "panel-header">Загрузка файла</div>
				<div class = "panel-content">
					<span>Выберите файл для загрузки:</span>
					<input type="file" name="fileToUploadGoods" id="fileToUploadGoods">	
					<input type="button" value="Загрузить" name="uploadGoodsParse" id="uploadGoodsParse"  onclick="parseGoods(this)" accept=".xlsx">
					<p id="status"></p>
				</div>
			</div>
			<div class = "panel" id="fileInfo" style="display:none">
				<div class="panel-header">Информация о файле</div>
				<div class="panel-content">
					<div class="left-block">
						<p id="fileName"></p>
						<p id="shop"></p>
						<p id="period"></p>
						<p id="paymentNumber"></p>
						<p id="paymentDate"></p>
						<p id="totalOrders"></p>
						<p id="payments"></p>
						<p id="totalCommission1"></p>
						<p id="totalCommission2"></p>
						<p id="totalCommission3"></p>
						<p id="totalCommission4"></p>
						<input type="button" value="Сохранить в MS" name="uploadGoodsSubmit" id="uploadGoodsSubmit"  onclick="submitGoods(this)">
					</div>
					<div class="right-block">
						<p><textarea id="story" name="story" rows="15" cols="100" style="display:none">Статистика</textarea></p>
					</div>
				</div>
			</div>
		<?php } ?>
		<?php if ($agent == 'Aliexpress') { ?>
		<?php } ?>
		<?php if ($agent == 'Wildberries') { ?>
		<?php } ?>
		<?php if ($agent == 'Curiers') { ?>
		<?php } ?>
	</div>
</div>

