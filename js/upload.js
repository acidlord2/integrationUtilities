async function redirect()
{
	//document.getElementById('upload_form').target = 'upload_iframe'; //'upload_iframe' is the name of the iframe

	var loading = document.getElementById("loading");
	var loading2 = document.getElementById("loading2");
	loading.style.display = "inline-block";
	loading2.style.display = "inline-block";
	loading2.innerText = "Парсинг файла...";
	var iFrame = document.getElementById("upload_iframe");
	//iFrame.style.display = "block";
	iFrame.contentDocument.getElementsByTagName("body")[0].innerHTML = "";

	document.getElementById('upload_form').submit();
	
	upload("upload_iframe", "loading", "loading2");
}
async function redirectWildberries()
{
	//document.getElementById('upload_form').target = 'upload_iframe'; //'upload_iframe' is the name of the iframe

	var loading = document.getElementById("loading");
	var loading2 = document.getElementById("loading2");
	loading.style.display = "inline-block";
	loading2.style.display = "inline-block";
	loading2.innerText = "Парсинг файла...";
	var iFrame = document.getElementById("upload_iframe");
	//iFrame.style.display = "block";
	iFrame.contentDocument.getElementsByTagName("body")[0].innerHTML = "";

	document.getElementById('upload_form').submit();
	
	uploadWildberries("upload_iframe", "loading", "loading2");
}

async function redirectBeru()
{
	//document.getElementById('upload_form').target = 'upload_iframe'; //'upload_iframe' is the name of the iframe

	var loading = document.getElementById("loadingBeru");
	var loading2 = document.getElementById("loadingBeru2");
	loading.style.display = "inline-block";
	loading2.style.display = "inline-block";
	loading2.innerText = "Парсинг файла...";
	var iFrame = document.getElementById("uploadBeru_iframe");
	//iFrame.style.display = "block";
	iFrame.contentDocument.getElementsByTagName("body")[0].innerHTML = "";

	document.getElementById('uploadBeru_form').submit();
	
	upload("uploadBeru_iframe", "loadingBeru", "loadingBeru2");
}

async function redirectAli()
{
	//document.getElementById('upload_form').target = 'upload_iframe'; //'upload_iframe' is the name of the iframe

	var loading = document.getElementById("loadingAli");
	var loading2 = document.getElementById("loadingAli2");
	loading.style.display = "inline-block";
	loading2.style.display = "inline-block";
	loading2.innerText = "Парсинг файла...";
	var iFrame = document.getElementById("uploadAli_iframe");
	//iFrame.style.display = "block";
	iFrame.contentDocument.getElementsByTagName("body")[0].innerHTML = "";

	document.getElementById('uploadAli_form').submit();
	
	upload("uploadAli_iframe", "loadingAli", "loadingAli2");
}

async function redirectOzon()
{
	//document.getElementById('upload_form').target = 'upload_iframe'; //'upload_iframe' is the name of the iframe

	var loading = document.getElementById("loadingOzon");
	var loading2 = document.getElementById("loadingOzon2");
	loading.style.display = "inline-block";
	loading2.style.display = "inline-block";
	loading2.innerText = "Парсинг файла...";
	var iFrame = document.getElementById("uploadOzon_iframe");
	//iFrame.style.display = "block";
	iFrame.contentDocument.getElementsByTagName("body")[0].innerHTML = "";

	document.getElementById('uploadOzon_form').submit();
	
	upload("uploadOzon_iframe", "loadingOzon", "loadingOzon2");
}

async function sleep(ms) {
	return new Promise(resolve => setTimeout(resolve, ms));
}

var upload = async function (iFramepar, loadpar, load2par)
{
	//var iFrame2 = document.getElementById(iFramepar).contentDocument.getElementsByTagName("body");
	var iFrame = document.getElementById(iFramepar).contentDocument.getElementsByTagName("body")[0];
	var loading = document.getElementById(loadpar);
	var loading2 = document.getElementById(load2par);

	if(iFrame.innerHTML == "")
	{
		//loading.innerHTML = await checkStatus();
		//iFrame.innerHTML = "";
		setTimeout (upload, 1000, iFramepar, loadpar, load2par);
	}
	else
	{

		//loading2.innerText = "Поиск заказов...";
		//var respOrders = await fetch('uploadFindOrders.php',
		//{
		//	method: 'POST',
		//	headers: {'Content-Type': 'application/json'},
		//	body: iFrame.innerHTML
		//});

		//var payments = '';
		//if (respOrders.ok)
		//	payments = respOrders.text();
		
		//document.getElementById("upload_iframe").style.display = "block";
		var payments = JSON.parse (iFrame.innerHTML);
		loading2.innerHTML = 'Обработано платежей 0 из ' +  Object.keys(payments).length;
		let i = 0;
		for (let [index, payment] of Object.entries(payments)) 			
		{
			var resp = await fetch('/integration/uploadCreatePayment.php',
			{
				method: 'POST',
				headers: {'Content-Type': 'application/json'},
				body: JSON.stringify(payment)
			});
			i++;
			if (resp.ok) {
				loading2.innerHTML = 'Обработано платежей ' + i + ' из ' +  Object.keys(payments).length;
			}
		}
		loading.style.display = "none";
		
	}
}
var uploadWildberries = async function (iFramepar, loadpar, load2par)
{
	//var iFrame2 = document.getElementById(iFramepar).contentDocument.getElementsByTagName("body");
	var iFrame = document.getElementById(iFramepar).contentDocument.getElementsByTagName("body")[0];
	var loading = document.getElementById(loadpar);
	var loading2 = document.getElementById(load2par);

	if(iFrame.innerHTML == "")
	{
		//loading.innerHTML = await checkStatus();
		//iFrame.innerHTML = "";
		setTimeout (uploadWildberries, 1000, iFramepar, loadpar, load2par);
	}
	else
	{
		loading2.innerHTML = 'Обработано продуктов ' + iFrame.innerHTML;
		loading.style.display = "none";
	}
}
async function downloadWBvat()
{
	var resp = await fetch('/wildberries/exportWBvat.php');
	if (resp.ok)
	{
		var filename = await resp.text();
		console.log (filename);
		var element = document.createElement('a');
		element.setAttribute('href', filename);
		element.style.display = 'none';
		element.click();
		element.remove();
	}
}
