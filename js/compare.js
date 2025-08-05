// Organization visibility logic from compare.php
document.addEventListener('DOMContentLoaded', function() {
    const marketplaceSelect = document.getElementById('marketplaceSelect');
    const organizationSelect = document.getElementById('organizationSelect');
    if (!marketplaceSelect || !organizationSelect) return;
    const orgDiv = organizationSelect.closest('div');
    const orgOptions = Array.from(organizationSelect.options).map(opt => ({value: opt.value, text: opt.text}));

    function updateOrganizationVisibility() {
        const mp = marketplaceSelect.value;
        let orgs = [];
        if (mp === 'ccd') {
            orgDiv.style.display = 'none';
        } else {
            orgDiv.style.display = '';
            if (mp === 'ozon') {
                orgs = ['ullo', 'kaori'];
            } else if (mp === 'wb') {
                orgs = ['ullo', 'kosmos'];
            } else if (mp === 'ym') {
                orgs = ['ullo', 'sammit', 'kosmos'];
            } else if (mp === 'sm') {
                orgs = ['ullo', 'kosmos'];
            } else {
                orgs = orgOptions.map(o => o.value);
            }
            // Update options
            organizationSelect.innerHTML = '';
            orgOptions.forEach(opt => {
                if (orgs.includes(opt.value)) {
                    const o = document.createElement('option');
                    o.value = opt.value;
                    o.text = opt.text;
                    organizationSelect.appendChild(o);
                }
            });
        }
    }
    marketplaceSelect.addEventListener('change', updateOrganizationVisibility);
    updateOrganizationVisibility();
});
// Tab selection and hide/show logic moved from compare.php
function selectCompareType(btn, type) {
    document.querySelectorAll('.tablinks').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    hideCompareResults();
}

function hideCompareResults() {
    document.getElementById('compare-block').style.display = 'none';
    document.getElementById('compare-table').style.display = 'none';
    document.getElementById('show-diff-container').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('marketplaceSelect').addEventListener('change', hideCompareResults);
    document.getElementById('organizationSelect').addEventListener('change', hideCompareResults);
});
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('compareForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        enableLoading(submitBtn, 'Загрузка сравнения...');
        submitBtn.disabled = true;
        // Get type from active tab
        const activeTab = document.querySelector('.tablinks.active');
        const type = activeTab ? activeTab.getAttribute('name') : 'prices';
        const marketplace = document.getElementById('marketplaceSelect').value;
        const organization = document.getElementById('organizationSelect').value;
        const marketplaceLabel = document.getElementById('marketplaceSelect').options[document.getElementById('marketplaceSelect').selectedIndex].text;
        document.getElementById('ms-col').textContent = (type === 'prices') ? 'Цена MS' : 'Остаток MS';
        document.getElementById('mp-col').textContent = (type === 'prices') ? `Цена ${marketplaceLabel}` : `Остаток ${marketplaceLabel}`;
        document.getElementById('compare-table').style.display = '';
        fetchCompareData(type, marketplace, organization, function() {
            disableLoading();
            submitBtn.disabled = false;
        });
    });
});
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.getElementById('compare-table');
        if (!table) return;
        const tbody = table.querySelector('tbody');

        // Add checkbox for "Show only differences"
        const compareBlock = document.getElementById('compare-block');
        if (compareBlock && !document.getElementById('show-diff')) {
            const checkboxDiv = document.createElement('div');
            checkboxDiv.style.margin = '16px 0';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.id = 'show-diff';
            const label = document.createElement('label');
            label.htmlFor = 'show-diff';
            label.textContent = 'Показать только различия';
            checkboxDiv.appendChild(checkbox);
            checkboxDiv.appendChild(label);
            compareBlock.insertBefore(checkboxDiv, table);
        }

        function getCell(row, colId) {
            return row.querySelector(`#${colId}, .${colId}`) || row.cells[colId === 'ms-col' ? 1 : 2];
        }

        // Highlight rows with differences
        function highlightDifferences() {
            Array.from(tbody.rows).forEach(row => {
                const msCell = getCell(row, 'ms-col');
                const mpCell = getCell(row, 'mp-col');
                if (!msCell || !mpCell) return;
                if (msCell.textContent.trim() !== mpCell.textContent.trim()) {
                    row.style.backgroundColor = '#fffbe6'; // light yellow
                    row.classList.add('diff-row');
                } else {
                    row.style.backgroundColor = '';
                    row.classList.remove('diff-row');
                }
            });
        }

        // Show only differences
        function toggleShowDifferences() {
            const checkbox = document.getElementById('show-diff');
            if (!checkbox) return;
            const showOnlyDiff = checkbox.checked;
            Array.from(tbody.rows).forEach(row => {
                if (showOnlyDiff) {
                    if (!row.classList.contains('diff-row')) {
                        row.style.display = 'none';
                    } else {
                        row.style.display = '';
                    }
                } else {
                    row.style.display = '';
                }
            });
        }

        // Initial highlight and event binding
        highlightDifferences();
        const diffCheckbox = document.getElementById('show-diff');
        if (diffCheckbox) {
            diffCheckbox.addEventListener('change', toggleShowDifferences);
        }

        // If rows are dynamically inserted, re-highlight after update
        window.highlightCompareTableDifferences = highlightDifferences;
        window.toggleShowCompareTableDifferences = toggleShowDifferences;
    });

function fetchCompareData(type, marketplace, organization, callback) {
    fetch(`/compare/getCompareData.php?type=${encodeURIComponent(type)}&marketplace=${encodeURIComponent(marketplace)}&organization=${encodeURIComponent(organization)}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(text => {
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                throw new Error('Invalid JSON');
            }
            buildCompareTable(data);
            if (typeof callback === 'function') callback();
        })
        .catch(err => {
            const tbody = document.querySelector('#compare-table tbody');
            tbody.innerHTML = `<tr><td colspan='3'>Ошибка загрузки данных</td></tr>`;
            if (typeof callback === 'function') callback();
        });
}
// Loader functions copied from finances.js for local use
function enableLoading(element, text) {
    var div = document.createElement('div');
    var loading = document.createElement('span');
    loading.id = 'loading';
    loading.className = 'loading';
    var loadingText = document.createElement('span');
    loadingText.id = 'loadingText';
    loadingText.className = 'loadingText';
    loadingText.innerHTML = text;
    div.appendChild(loading);
    div.appendChild(loadingText);
    element.after(div);
}
function disableLoading() {
    var loading = document.getElementById('loading');
    var loadingText = document.getElementById('loadingText');
    if (loading) loading.remove();
    if (loadingText) loadingText.remove();
}

function buildCompareTable(data) {
    const tbody = document.querySelector('#compare-table tbody');
    tbody.innerHTML = '';
    // Always show the table and checkbox when building table
    const compareBlock = document.getElementById('compare-block');
    const compareTable = document.getElementById('compare-table');
    const showDiffContainer = document.getElementById('show-diff-container');
    compareBlock.style.display = 'table-cell';
    compareTable.style.display = '';
    showDiffContainer.style.display = '';
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
    // Highlight differences and apply filter after table is rebuilt
    if (window.highlightCompareTableDifferences) window.highlightCompareTableDifferences();
    if (window.toggleShowCompareTableDifferences) window.toggleShowCompareTableDifferences();
    // Add event listener for checkbox if not already
    const diffCheckbox = document.getElementById('show-diff');
    if (diffCheckbox && !diffCheckbox.hasListener) {
        diffCheckbox.addEventListener('change', function() {
            if (window.toggleShowCompareTableDifferences) window.toggleShowCompareTableDifferences();
        });
        diffCheckbox.hasListener = true;
    }
}
