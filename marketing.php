<?php
	require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	
	error_reporting(E_ALL);
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	if(isset($_GET['shippingDate']))
		$shippingDate = $_GET['shippingDate'];
	else
		$shippingDate = Date ('Y-m-d', strtotime('+1 day'));
	
?>
<html>
	<head>
		<title>Анализатор цен</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<link rel = "stylesheet" type = "text/css"  href = "/css/styles.css?v=2" />
	</head>
	<body>
		<div align="center">
			<div id="header">
				<?php require_once ($_SERVER['DOCUMENT_ROOT'] . '/header.php'); ?>
				<div class = "title">
					Перечень товаров
				</div>
				<div style="margin-bottom: 13px; margin-top: 14px; display: inline-block"> 
					URL: <input type="text" id="url" size="200" value="https://www.ozon.ru/category/vodonagrevateli-10719/?typeheater=62024">
					<button type="button" id = "get-products" onclick="getProducts()">Получить товары</button>			
				</div>
			</div>
			<table id="products-table">
				<tr>
					<td>Тип</td>
					<td>Материал бака</td>
					<td>Объем, л</td>
					<td>Форма водонагревателя</td>
					<td>Артикул</td>
					<td>Наименование</td>
					<td>Фото</td>
					<td>Характеристики</td>
					<td>Цена</td>
					<td>Бренд</td>
				</tr>
			</table>
		</div>
		
		<script>
			async function getProducts() {
				var url = document.getElementById("url").value;
				//console.log (url);
				var resp = await fetch("marketingGetList.php", 
				{
					method: 'POST',
					headers: {
						'Content-Type': 'text/plain',
						'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.92 Safari/537.36',
						'Accept-Encoding': 'gzip, deflate, br',
						'Accept-Language': 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
						'Sec-Fetch-Dest': 'document',
						'SEC-FETCH-MODE': 'navigate'
					},
					body: url
				});
				let text = "";
				if (resp.ok)
				{
					text = await resp.text();
				}
				else
					return;
				
				//console.log (text);
				let parser = new DOMParser();
				let doc = parser.parseFromString(text, "text/html");
				//console.log (doc);
				let a3g1 = doc.getElementsByClassName("a3g1 a3g3 tile-hover-target");
				for (let item of a3g1) {
					
					///console.log ('https://www.ozon.ru' + item.getAttribute("href"));
					var resp = await fetch("marketingGetList.php", 
					{
						method: 'POST',
						headers: {
							'Content-Type': 'text/plain',
							'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.92 Safari/537.36',
							'Accept-Encoding': 'gzip, deflate, br',
							'Accept-Language': 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
							'Sec-Fetch-Dest': 'document',
							'SEC-FETCH-MODE': 'navigate'
						},
						body: 'https://www.ozon.ru' + item.getAttribute("href")
					});
					let text2 = "";
					if (resp.ok)
					{
						text2 = await resp.text();
					}
					else	
						return;
					let doc2 = parser.parseFromString(text2, "text/html");
					console.log (doc2);
					
					var pars = doc2.getElementsByClassName ("b6q9");
					
					
					console.log (pars);
					
					var tr = document.createElement("tr");
					var td1 = document.createElement("td");
					var img1 = document.createElement("img");
					img1.setAttribute("src", doc2.getElementsByClassName("magnifier-image shown o8")[0].getAttribute("src"));
					img1.setAttribute("width", 150);
					td1.appendChild (img1);
					tr.appendChild (td1);
					var td2 = document.createElement("td");
					td2.innerHTML = doc2.getElementsByClassName("b6c3")[0].innerHTML;
					tr.appendChild (td2);
					document.getElementById("products-table").appendChild(tr);
				}
			}
			
			async function getPar (pars, parStr)
			{

				for (let par of pars)
				{
					console.log (par.getElementsByClassName ("b7d4"));
					if (par.getElementsByClassName ("b7d4").innerHTML = parStr)
						console.log (par);
				}
			}
		</script>
	</body>
</html>


