<?php
    require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
    // Example values, replace with your actual data sources
    $types = [
        'prices' => 'Цены',
        'stock' => 'Остатки'
    ];
    $marketplaces = [
        'ccd' => 'CCD77',
        'ozon' => 'Ozon',
        'wb' => 'Wildberries',
        'ym' => 'Яндекс.Маркет'
    ];
    $organizations = [
        'ullo' => 'Юлло',
        'smmp' => 'СММ Альянс'
    ];
?>

<html>
<head>
    <title>Сравнение данных</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <link rel="stylesheet" type="text/css" href="/css/styles.css?v=4" />
    <style>
        .compare-container { display: flex; gap: 32px; margin: 40px auto; max-width: 800px; }
        .compare-left-panel {
            min-width: 180px; background: #f7f7f7; border-radius: 8px; padding: 24px 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; flex-direction: column; align-items: flex-start;
        }
        .compare-form-panel {
            flex: 1; background: #fff; border-radius: 8px; padding: 24px 32px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .compare-form-panel label { display: block; margin-top: 18px; margin-bottom: 6px; font-weight: 500; }
        .compare-form-panel select { width: 100%; padding: 7px 10px; border-radius: 4px; border: 1px solid #ccc; margin-bottom: 12px; }
        .compare-form-panel button { margin-top: 18px; padding: 10px 24px; background: #F7971D; color: #fff; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; }
        .compare-form-panel button:hover { background: #e6860f; }
        #compare-block {
            margin: 48px auto;
            width: 800px;
            height: 500px;
            position: relative;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            overflow: auto;
        }
        #compare-table {
            width: 100%;
            border-collapse: collapse;
            display: table;
        }
        #compare-table th, #compare-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        #compare-table th { background-color: #f2f2f2; }
    </style>
</head>
<body>
<div class="compare-container">
    <div class="compare-left-panel">
        <form id="typeForm">
            <label for="typeSelect">Тип сравнения:</label>
            <select id="typeSelect" name="type">
                <?php foreach ($types as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="compare-form-panel">
        <form id="compareForm" method="post" action="">
            <label for="marketplaceSelect">Маркетплейс:</label>
            <select id="marketplaceSelect" name="marketplace">
                <?php foreach ($marketplaces as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="organizationSelect">Организация:</label>
            <select id="organizationSelect" name="organization">
                <?php foreach ($organizations as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Сравнить</button>
        </form>
    </div>
</div>
<div id="compare-block">
    <div style="margin: 16px 0;">
        <input type="checkbox" id="show-diff">
        <label for="show-diff">Показать только различия</label>
    </div>
    <table id="compare-table" class="tableBig">
        <thead>
            <tr>
                <th>Код</th>
                <th id="ms-col">MS</th>
                <th id="mp-col">Маркетплейс</th>
            </tr>
        </thead>
        <tbody>
            <!-- rows will be dynamically inserted here -->
        </tbody>
    </table>
</div>
<script src="/js/compare.js?v=1"></script>
</body>
</html>
