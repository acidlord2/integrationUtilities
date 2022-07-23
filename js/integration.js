async function getSberOrders(element) {
	// Declare all variables
 	enableLoading (element, "Получаем перечень заказов...")
	var respOrderCount = await fetch('/integration/getSberOrders.php');
	var orderNumbers = await respOrderCount.json();
	var chunk = 50;
	var i = 0;
	while (true)
	{
		var orders = orderNumbers.splice(i, chunk);
		if (orders.length == 0)
			break;
		
		updateLoading("Получаем заказы с " + (i*chunk+1) + " по " + ((i*chunk+1)+orders.length-1));
		
		var resp = await fetch('/integration/importSberOrders.php',
		{
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify(orders)
		});
		
		i++;
	}
	
	disableLoading();
}


async function parseYandex(element)
{
	disableFileInfo();
	disableStory();
	disableInputs();
	enableLoading (element, 'Парсинг файла...');
	var file = document.getElementById("fileToUploadYandex").files;
	if (file.length == 0)
	{
		alert("Please select a file");
		disableLoading();
		enableInputs();
		return;
	}
	
	var formData = new FormData();
    formData.append("file", file[0]);

	var resp = await fetch('yandex/parse.php',
	{
		method: 'POST',
		//headers: {'Content-Type': 'multipart/form-data'},
		body: formData
	});
	
	if (!resp.ok)
	{
		alert ('can\'t parse file');
		disableLoading();
		enableInputs();
		return;
	}
    
	var str = await resp.text();
	
	try {
        var json = JSON.parse(str);
        var payments = JSON.parse(json);
    } catch (e) {
        console.log (str);
		disableLoading();
		enableInputs();
		return;
    }
	
	document.getElementById("fileName").innerText = 'Файл: ' + payments.fileInfo.fileName;
	document.getElementById("paymentNumber").innerText = 'Номер ПП: ' + payments.fileInfo.paymentNumber;
	document.getElementById("paymentDate").innerText = 'Дата ПП: ' + payments.fileInfo.paymentDate;
	//document.getElementById("shop").innerText = 'Магазин: ' + payments.fileInfo.shop;
	//document.getElementById("period").innerText = payments.fileInfo.period;
	document.getElementById("totalOrdersCharged").innerText = 'Заказов начислено всего: ' + payments.fileInfo.totalOrdersCharged;
	document.getElementById("totalOrdersStornoed").innerText = 'Заказов сторнировано всего: ' + payments.fileInfo.totalOrdersStornoed;
	document.getElementById("totalSumCharged").innerText = 'Сумма начислений всего: ' + payments.fileInfo.totalSumCharged.toLocaleString() + ' руб.';
	document.getElementById("totalSumStornoed").innerText = 'Сумма сторнировано всего: ' + payments.fileInfo.totalSumStornoed.toLocaleString() + ' руб.';
	//document.getElementById("totalCommission").innerText = 'Комиссия всего: ' + payments.fileInfo.totalCommission;
	
	//document.getElementById('upload_form').target = 'upload_iframe'; //'upload_iframe' is the name of the iframe
	
	//upload("upload_iframe", "loading", "loading2");
	enableFileInfo();
	disableLoading();
	enableInputs();
	
	if (payments.payments.length == 0)
		document.getElementById('uploadYandexSubmit').disabled = true;
	localStorage.payments = JSON.stringify (payments);
}
async function submitYandex(element)
{
	//document.getElementById('upload_form').target = 'upload_iframe'; //'upload_iframe' is the name of the iframe
	resetStory();
	var payments = JSON.parse (localStorage.payments);
	
	disableInputs();
	enableStory();
	enableLoading (element, 'Обработка платежей...');
	var c = 0;
	for (var payment of payments.payments)
	{
		c++;
		updateLoading ('Обработка платежа ' + c + ' из ' + payments.payments.length);
		var resp = await fetch('yandex/createPayment.php',
		{
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify(payment)
		});
		if (resp.ok)
		{
			addStory (await resp.text());
		}
	}
	disableLoading();
	//disableStory();
	enableInputs();
}

async function parseGoods(element)
{
	disableFileInfo();
	disableStory();
	disableInputs();
	enableLoading (element, 'Парсинг файла...');
	var file = document.getElementById("fileToUploadGoods").files;
	if (file.length == 0)
	{
		alert("Please select a file");
		disableLoading();
		enableInputs();
		return;
	}
	
	var formData = new FormData();
    formData.append("file", file[0]);

	var resp = await fetch('goods/parse.php',
	{
		method: 'POST',
		//headers: {'Content-Type': 'multipart/form-data'},
		body: formData
	});
	
	if (!resp.ok)
	{
		alert ('can\'t parse file');
		disableLoading();
		enableInputs();
		return;
	}
    
	var str = await resp.text();
	
	try {
        var json = JSON.parse(str);
        var payments = JSON.parse(json);
    } catch (e) {
        console.log (str);
		disableLoading();
		enableInputs();
		return;
    }
	
	document.getElementById("fileName").innerText = 'Файл: ' + payments.fileInfo.fileName;
	document.getElementById("shop").innerText = 'Продавец: ' + payments.fileInfo.shop;
	document.getElementById("paymentNumber").innerText = 'Продавец: ' + payments.fileInfo.paymentNumber;
	document.getElementById("paymentDate").innerText = 'Продавец: ' + payments.fileInfo.paymentDate;
	document.getElementById("totalOrders").innerText = 'Заказов всего: ' + payments.fileInfo.totalOrders;
	document.getElementById("payments").innerText = 'Перечислено: ' + payments.fileInfo.payments.toLocaleString() + ' руб.';
	if (payments.fileInfo.totalCommission1 > 0)
		document.getElementById("totalCommission1").innerText = 'Комиссия за доставку: ' + payments.fileInfo.totalCommission1.toLocaleString() + ' руб.';
	if (payments.fileInfo.totalCommission2 > 0)
		document.getElementById("totalCommission2").innerText = 'Комиссия за товарную категорию: ' + payments.fileInfo.totalCommission2.toLocaleString() + ' руб.';
	if (payments.fileInfo.totalCommission3 > 0)
		document.getElementById("totalCommission3").innerText = 'Комиссия за транзакции: ' + payments.fileInfo.totalCommission3.toLocaleString() + ' руб.';
	if (payments.fileInfo.totalCommission4 > 0)
		document.getElementById("totalCommission4").innerText = 'Вознаграждение оператора ПЛ: ' + payments.fileInfo.totalCommission4.toLocaleString() + ' руб.';
	//document.getElementById("totalCommission").innerText = 'Комиссия всего: ' + payments.fileInfo.totalCommission;
	
	//document.getElementById('upload_form').target = 'upload_iframe'; //'upload_iframe' is the name of the iframe
	
	//upload("upload_iframe", "loading", "loading2");
	enableFileInfo();
	disableLoading();
	enableInputs();
	
	if (payments.payments.length == 0)
		document.getElementById('uploadYandexSubmit').disabled = true;
	localStorage.payments = JSON.stringify (payments);
}
async function submitGoods(element)
{
	//document.getElementById('upload_form').target = 'upload_iframe'; //'upload_iframe' is the name of the iframe
	resetStory();
	var payments = JSON.parse (localStorage.payments);
	
	disableInputs();
	enableStory();
	enableLoading (element, 'Обработка платежей...');
	var c = 0;
	for (var payment of payments.payments)
	{
		c++;
		updateLoading ('Обработка платежа ' + c + ' из ' + payments.payments.length);
		var resp = await fetch('goods/createPayment.php',
		{
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify(payment)
		});
		if (resp.ok)
		{
			addStory (await resp.text());
		}
	}
	disableLoading();
	//disableStory();
	enableInputs();
}

async function enableLoading(element, text)
{
	var div = document.createElement('div');
	var loading = document.createElement('span');
	loading.id = 'loading';
	loading.className = 'loading';
	var loadingText = document.createElement('span');
	loadingText.id = 'loadingText';
	loadingText.className = 'loadingText';
	loadingText.innerHTML = text;
	div.appendChild (loading);
	div.appendChild (loadingText);
	element.after (div);
}
async function updateLoading(text)
{
	document.getElementById('loadingText').innerHTML = text;
}

async function enableFileInfo()
{
	document.getElementById('fileInfo').style.display = 'block';
}

async function disableFileInfo()
{
	document.getElementById('fileInfo').style.display = 'none';
}
async function enableInputs()
{
	var inputs = document.getElementsByTagName('input');
	for (var input of inputs)
	{
		input.disabled = false;
	}
}
async function disableInputs()
{
	var inputs = document.getElementsByTagName('input');
	for (var input of inputs)
	{
		input.disabled = true;
	}
}

async function disableLoading()
{
	document.getElementById('loading').remove();
	document.getElementById('loadingText').remove();
}

async function enableStory()
{
	let story = document.getElementById("story");
	story.style.display = 'block';
}

async function disableStory()
{
	let story = document.getElementById("story");
	story.style.display = 'none';
}


async function resetStory() {
	let story = document.getElementById("story");
	story.value = 'Статистика';
}

async function addStory(str) {
	textarea = document.getElementById("story");
	textarea.value += (String.fromCharCode(13, 10) + str);
	textarea.scrollTop = textarea.scrollHeight;
}

async function clearPayments()
{
	//document.getElementById('upload_form').target = 'upload_iframe'; //'upload_iframe' is the name of the iframe
	resetStory();
	var payments = JSON.parse (localStorage.payments);
	
	disableInputs();
	var resp = await fetch('yandex/clearPayments.php',
	{
		method: 'POST',
		headers: {'Content-Type': 'application/json'},
		body: JSON.stringify(payments.fileInfo)
	});
	//disableStory();
	enableInputs();
}
