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
    <link rel="stylesheet" type="text/css" href="/css/styles.css?v=<?php echo date("Y-m-d-H-i-s", strtotime("now")); ?>" />
</head>
<body style="overflow:hidden;">
    <div align="center">
        <div id="header">
            <?php require_once ($_SERVER['DOCUMENT_ROOT'] . '/header.php'); ?>
        </div>
    </div>

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
