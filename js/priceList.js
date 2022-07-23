changeArray = {};
changeAttributesArray = {};

async function showButton() {
	var buttons = document.getElementsByClassName("buttons");
	for (var i=0; i<buttons.length; i++) {
		buttons[i].disabled = false;
	}
}

async function hideButton() {
	var buttons = document.getElementsByClassName("buttons");
	for (var i=0; i<buttons.length; i++) {
		buttons[i].disabled = true;
	}
}

async function addChangePrice(element) {
	var elementIdArray = element.id.split (':');
	var elementId = elementIdArray[1];
	var price = elementIdArray[0].substring (1);
	if (elementId in changeArray)
		changeArray[elementId][price] = element.value;
	else
	{
		changeArray[elementId] = {};
		changeArray[elementId][price] = element.value;
	}
	showButton();
}

async function addChangeAttribute(element) {
	var elementIdArray = element.id.split (':');
	var elementId = element.parentElement.parentElement.id;
	var attr = elementIdArray[0];
	if (elementId in changeAttributesArray)
		changeAttributesArray[elementId][attr] = element.value;
	else
	{
		changeAttributesArray[elementId] = {};
		changeAttributesArray[elementId][attr] = element.value;
	}
	showButton();
}

async function remove_change(element) {
	var elementIdArray = element.id.split (':');
	var elementId = elementIdArray[1];
	var price = elementIdArray[0].substring (1);
	if (elementId in changeArray ? price in changeArray[elementId] : false)
		delete changeArray[elementId][price];
	if (changeArray[elementId] != null ? Object.keys(changeArray[elementId]).length == 0 : false)
		delete changeArray[elementId];
	if (changeArray != null ? Object.keys(changeArray).length == 0 : true)
		hideButton();
}

async function changePrice(element) {
	var elementIdArray = element.id.split (':');
	var elementOld = document.getElementById("o" + elementIdArray[0].substring (1) + ":" + elementIdArray[1]);
	var oldVal = parseFloat (elementOld.innerText);
	var newVal = parseFloat (element.value);
	if (oldVal == newVal) {
		element.className = "price-input";
		elementOld.className = "";
		remove_change (element);
		return;
	}
	else if (oldVal > newVal && newVal / oldVal < 0.9)
	{
		element.className = "price-input changed-error";
		elementOld.className = "changed-error";
	}
	else if (oldVal < newVal && oldVal / newVal < 0.9)
	{
		element.className = "price-input changed-error";
		elementOld.className = "changed-error";
	}
	else
	{
		element.className = "price-input changed-ok";
		elementOld.className = "changed-ok";
	}
	
	//add to change array
	addChangePrice (element);
	
}

async function changeAttribute(element) {
	var elementIdArray = element.id.split (':');
	var newVal = parseFloat (element.value);
	element.className = "price-input changed-ok";
	
	//add to change array
	addChangeAttribute (element);
	
}

async function change (event) {
	if (event.target.id.indexOf('i') != -1)
		changePrice (event.target);
	else
		changeAttribute (event.target);
}

document.addEventListener('paste', function(event) {
	var cols = (event.clipboardData || window.clipboardData).getData('text').split ("\r\n");
	var firstElement = event.target;
	//var ind = parseInt (element.id.substring (1, 2)) - 1;
	if (firstElement.tagName != 'INPUT')
		return;
	
	var tbody = firstElement.parentElement.parentElement.parentElement;
	var firstTrIndex = [].indexOf.call (firstElement.parentElement.parentElement.parentElement.children, firstElement.parentElement.parentElement);
	var firstTdIndex = [].indexOf.call (firstElement.parentElement.parentElement.children, firstElement.parentElement);
	var firstThFlag = tbody.children[firstTrIndex].children[0].localName == 'th' ? 1 : 0;
	for (i = 0; i < cols.length; i++) {
		var currentTrElement = tbody.children[firstTrIndex + i];
		var currentThFlag = tbody.children[firstTrIndex + i].children[0].localName == 'th' ? 1 : 0;
		var rows = cols[i].split ("\t");
		for (j = 0; j < rows.length && cols[i] != ""; j++){
			var element = currentTrElement.children[firstTdIndex + currentThFlag - firstThFlag + j].children[0];
			element.value = rows[j];
			
			if (element.id.indexOf('i') != -1)
				changePrice (element);
			else
				changeAttribute (element);

			if (element.parentElement.nextElementSibling == null || element.parentElement.nextElementSibling.children.length == 0)
				break;
		}
		
		var nextTr = element.parentElement.parentElement.nextElementSibling;
		if (nextTr == null)
			break;
	}
	event.preventDefault();
});

async function save() {
	hideButton();
	showLoad('Загрузка данных... подождите пару секунд...');
	if (JSON.stringify(changeArray) != '{}')
	{
		var resp = await fetch('/priceList/savePrices.php', {
			method: 'POST',
			headers: {
			  'Content-Type': 'application/json'
			},
			body: JSON.stringify (changeArray)
		});
		var ret = await resp.text();
		
		if (!resp.ok)
			console.log (ret);
	}
	if (JSON.stringify(changeAttributesArray) != '{}')
	{
		var resp = await fetch('/priceList/saveAttributes.php', {
			method: 'POST',
			headers: {
			  'Content-Type': 'application/json'
			},
			body: JSON.stringify (changeAttributesArray)
		});
		var ret = await resp.text();
		
		if (!resp.ok)
			console.log (ret);
	}
	//updateLoad('Обновление цен на сайте... подождите пару секунд...');
	//var resp2 = fetch('https://4cleaning.ru/index.php?route=extension/prices/impprices');
	//var resp2 = fetch('https://10kids.ru/index.php?route=extension/prices/impprices');
	//if (!resp2.ok)
	//	console.log (await resp2.text());
	location.reload();
}
