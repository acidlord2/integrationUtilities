async function printSticker(productClass) {
	showLoad('Загрузка данных... подождите пару секунд...');
	var postData = [];
	var org = document.getElementById("org").value
	var url = new URL(location);
	var agent = url.searchParams.get("agent");
	var checkboxes = document.getElementsByName ('ozonCheckbox' + productClass);
	if (agent == "WB") {
		var checkboxes2 = document.getElementsByName ('msCheckbox' + productClass);
	} else {
		var checkboxes2 = document.getElementsByName ('ozonCheckbox' + productClass);
	}
	var t = 0;
	var orderclass = 0;
	for (var i=0; i < checkboxes.length && t < 20; i++) {
		if (!checkboxes[i].checked) {
			if (orderclass == 0)
				orderclass = checkboxes[i].getAttribute ('orderclass');
			if (orderclass != checkboxes[i].getAttribute ('orderclass'))
				break;
			postData.push (checkboxes2[i].id.substring(2));
			checkboxes[i].checked = true;
			t++;
		};
	};
	if (postData.length == 0)
	{
		deleteLoad (window);
		return;
	}

	var json = JSON.stringify(postData);
	var resp = await fetch("getLabels.php?org=" + org + "&count=" + (printStickerCount + 1) + "&agent=" + agent,
	{
		method: 'POST',
		headers: {
			'Content-Type': 'application/json'
		},
		body: json
	});
	deleteLoad (window);
	
	if (resp.ok)
	{
		var filename = await resp.text();
		console.log (filename);
		var element = document.createElement('a');
		element.setAttribute('href', filename);
		element.setAttribute('target', '_blank');
		element.style.display = 'none';
		element.click();
		element.remove();
		printStickerCount++;
		var c = 0;
		for (var i=0; i < checkboxes.length; i++)
			if (checkboxes[i].checked)
				c++;
		document.getElementById("printedStickerCount").textContent = c;
	}
}

async function printInvoice(productClass) {
	showLoad('Загрузка данных... подождите пару секунд...');
	var checkboxes = document.getElementsByName ('msCheckbox' + productClass);
	var postData = [];
	var org = document.getElementById("org").value
	var url = new URL(location);
	var agent = url.searchParams.get("agent");
	var t = 0;
	var orderclass = 0;
	for (var i=0; i < checkboxes.length && t < 20; i++) {
		if (!checkboxes[i].checked) {
			if (orderclass == 0)
				orderclass = checkboxes[i].getAttribute ('orderclass');
			if (orderclass != checkboxes[i].getAttribute ('orderclass'))
				break;
			postData.push (checkboxes[i].id.substring(2));
			checkboxes[i].checked = true;
			t++;
		};
	};
	if (postData.length == 0)
	{
		deleteLoad (window);
		return;
	}
	
	var json = JSON.stringify(postData);
	var resp = await fetch("getReports.php?org=" + org + "&count=" + (printStickerCount + 1) + "&agent=" + agent, 
	{
		method: 'POST',
		headers: {
			'Content-Type': 'application/json'
		},
		body: json
	});
	deleteLoad (window);
	
	if (resp.ok)
	{
		var filename = await resp.text();
		console.log (filename);
		var element = document.createElement('a');
		element.setAttribute('href', filename);
		element.setAttribute('target', '_blank');
		element.style.display = 'none';
		element.click();
		element.remove();
		printInvoiceCount++;
		var c = 0;
		for (var i=0; i < checkboxes.length; i++)
			if (checkboxes[i].checked)
				c++;
		document.getElementById("printedInvoiceCount").textContent = c;
	}
}

async function changeStatus(productClass) {
	showLoad('Загрузка данных... подождите пару секунд...');
	var checkboxesOzon = document.getElementsByName ('ozonCheckbox' + productClass);
	var checkboxesMS = document.getElementsByName ('msCheckbox' + productClass);
	var url = new URL(location);
	var agent = url.searchParams.get("agent");

	var orders = [];
	for (var i=0; i < checkboxesMS.length; i++) {
		if (agent == "Ozon") {
			if (checkboxesOzon[i].checked && checkboxesMS[i].checked) {
				orders.push (checkboxesMS[i].id.substring(2));
			}
		}
		if (agent == "Beru" || agent == "Goods" || agent == "Curiers") {
			if (checkboxesMS[i].checked) {
				orders.push (checkboxesMS[i].id.substring(2));
			}
		}
	}
	if (orders.length == 0)
	{
		deleteLoad (window);
		return;
	}

	var postData = {orders:orders, agent:agent};
	
	var resp = await fetch ("changeStatuses.php", 
	{
		method: 'POST',
		headers: {
			'Content-Type': 'application/json'
		},
		body: JSON.stringify(postData)
	});
	
	deleteLoad (window);
	var url = new URL(location);
	var shippingDate = url.searchParams.get("shippingDate");
	var agent = url.searchParams.get("agent");
	var org = url.searchParams.get("org");
	
	if (postData.orders.length > 0) {
		location.replace ("?shippingDate=" + shippingDate + "&agent=" + agent + "&org=" + org);
	}
}
