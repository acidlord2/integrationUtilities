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
            <div class="tab" style="margin-bottom: 18px;">
                <?php foreach ($types as $key => $label): ?>
                    <button class="tablinks<?php if ($key === 'prices') echo ' active'; ?>" name="<?= htmlspecialchars($key) ?>" onclick="selectCompareType(this, '<?= htmlspecialchars($key) ?>')"><?= htmlspecialchars($label) ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="compare-form-panel">
            <form id="compareForm" method="post" action="">
                <label for="marketplaceSelect">Маркетплейс:</label>
                <select id="marketplaceSelect" name="marketplace">
                    <?php foreach ($marketplaces as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                <!-- Type selection is now handled by tab buttons -->
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
    <script>
        function selectCompareType(btn, type) {
            document.querySelectorAll('.tablinks').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('typeSelect').value = type;
        }
    </script>
    <script src="/js/compare.js?v=1"></script>
</body>
</html>
