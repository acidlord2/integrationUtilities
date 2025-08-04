document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('compareForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const type = document.getElementById('typeSelect').value;
        const marketplace = document.getElementById('marketplaceSelect').value;
        const organization = document.getElementById('organizationSelect').value;
        const marketplaceLabel = document.getElementById('marketplaceSelect').options[document.getElementById('marketplaceSelect').selectedIndex].text;
        document.getElementById('ms-col').textContent = (type === 'prices') ? 'Цена MS' : 'Остаток MS';
        document.getElementById('mp-col').textContent = (type === 'prices') ? `Цена ${marketplaceLabel}` : `Остаток ${marketplaceLabel}`;
        document.getElementById('compare-table').style.display = '';
        fetchCompareData(type, marketplace, organization);
    });
});

function fetchCompareData(type, marketplace, organization) {
    fetch(`/compare/getCompareData.php?type=${encodeURIComponent(type)}&marketplace=${encodeURIComponent(marketplace)}&organization=${encodeURIComponent(organization)}`)
        .then(response => response.json())
        .then(data => buildCompareTable(data))
        .catch(err => {
            const tbody = document.querySelector('#compare-table tbody');
            tbody.innerHTML = `<tr><td colspan='3'>Ошибка загрузки данных</td></tr>`;
        });
}

function buildCompareTable(data) {
    const tbody = document.querySelector('#compare-table tbody');
    tbody.innerHTML = '';
    if (!Array.isArray(data) || data.length === 0) {
        tbody.innerHTML = `<tr><td colspan='3'>Нет данных для отображения</td></tr>`;
        return;
    }
    data.forEach(row => {
        const ms = row.ms ? (typeof row.ms === 'object' ?
            ((row.ms.price !== null && row.ms.price !== undefined ? 'Цена: ' + row.ms.price : '') +
            (row.ms.quantity !== null && row.ms.quantity !== undefined ? '<br>Остаток: ' + row.ms.quantity : ''))
            : row.ms) : '';
        const mp = row.mp ? (typeof row.mp === 'object' ?
            ((row.mp.price !== null && row.mp.price !== undefined ? 'Цена: ' + row.mp.price : '') +
            (row.mp.quantity !== null && row.mp.quantity !== undefined ? '<br>Остаток: ' + row.mp.quantity : ''))
            : row.mp) : '';
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${row.code}</td><td>${ms}</td><td>${mp}</td>`;
        tbody.appendChild(tr);
    });
}
