document.addEventListener("click", (e) => {
	if (e.id == "uploadBeruParse")
	{
		disableLoading();
		disableFileInfo();
	}
});

async function getOrdersCount(element)
{
	var url = new URL(location);
	var report = url.searchParams.get("report");
	var startDate = document.getElementById("startDate").value;
	var endDate = document.getElementById("endDate").value;
	var postData = {startDate:startDate, endDate: endDate, page: 1};

	disableInputs();
	enableLoading (element, 'Получение данных по заказам...');

	var resp = await fetch('Sales/getOrders.php',
	{
		method: 'POST',
		headers: {'Content-Type': 'application/json'},
		body: JSON.stringify(postData)
	});

	if (resp.ok)
	{
		var orders = await resp.json();
		document.getElementById("ordersCount").innerText = orders.size;
	}

	disableLoading();
	enableInputs();
	
}

async function createReport(element)
{
	var url = new URL(location);
	var report = url.searchParams.get("report");
	var startDate = document.getElementById("startDate").value;
	var endDate = document.getElementById("endDate").value;
	//disableFileInfo();
	//disableStory();
	disableInputs();
	if (report == 'Sales')
	{
		enableLoading (element, 'Получение данных по заказам c 1 по 100');
		var page = 1;
		var respClear = await fetch('Sales/clearTmpData.php');
		
		while (true)
		{
			var postData = {startDate:startDate, endDate: endDate, page: page};
			var resp = await fetch('Sales/getOrders.php',
			{
				method: 'POST',
				headers: {'Content-Type': 'application/json'},
				body: JSON.stringify(postData)
			});

			if (resp.ok)
			{
				var orders = await resp.json();
			}
			else
			{
				break;
			}
			if (orders.size == 0 || orders.demands.length == 0 || page * orders.limit >= orders.size)
			{
				break;
			}

			updateLoading ('Сохранение временных данных отчета по заказам c ' + ((page - 1) * orders.limit) + ' по ' + (page * orders.limit));
			
			var tmpData = JSON.stringify(orders.demands);
			var respSTD = await fetch('Sales/setTmpData.php',
			{
				method: 'POST',
				headers: {'Content-Type': 'application/json'},
				body: tmpData
			});
			
			if (!resp.ok)
				break;
			
			page++;
			updateLoading ('Получение данных по заказам c ' + ((page - 1) * orders.limit) + ' по ' + (page * orders.limit));
		}
		var respCreate = await fetch('Sales/createReport.php?date=' + startDate);

	}

	disableLoading();
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
