<div align="center">
	<div id="header">
		<div class = "title">
			Загрузка цена из MS Excel файла
		</div>
	</div>
	<div class="body"> 
		<div class = "panel">
			<div class = "panel-header">Загрузка файла</div>
			<div class = "panel-content">
				<span>Выберите файл для загрузки:</span>
				<input type="file" name="fileToUpload" id="fileToUpload" accept=".xlsx">
				<input type="button" value="Загрузить" name="uploadParse" id="uploadParse"  onclick="parse(this)">
				<p id="status"></p>
			</div>
		</div>

		<div class = "panel" id="fileInfo" style="display:none">
			<div class="panel-header">Информация о файле</div>
			<div class="panel-content">
				<div class="left-block">
					<p id="priceTypesCount"></p>
					<p id="pricesCount"></p>
					<input type="button" value="Сохранить в MS" name="uploadSubmit" id="uploadSubmit"  onclick="submit(this)">
				</div>
			</div>
		</div>
        <div id="tableContainer" align="center"/>
	</div>
</div>

