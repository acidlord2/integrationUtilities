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
        'ym' => 'Яндекс.Маркет',
        'sm' => 'Спортмастер'
    ];
    $organizations = [
        'ullo' => 'Юлло',
        'sammit' => 'Альянс',
        'kaori' => 'Каори',
        'kosmos' => 'Космос'
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
        <div class="compare-form-panel" style="background: #f7f7f7; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 32px 40px; min-width: 320px; max-width: 400px; margin: 0 auto;">
            <form id="compareForm" method="post" action="">
                <div style="margin-bottom: 22px;">
                    <label for="marketplaceSelect" style="font-weight: 600; margin-bottom: 8px; display: block;">Маркетплейс:</label>
                    <select id="marketplaceSelect" name="marketplace" style="width: 100%; padding: 10px 12px; border-radius: 6px; border: 1px solid #ccc; font-size: 16px;">
                        <?php foreach ($marketplaces as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="margin-bottom: 22px;">
                    <label for="organizationSelect" style="font-weight: 600; margin-bottom: 8px; display: block;">Организация:</label>
                    <select id="organizationSelect" name="organization" style="width: 100%; padding: 10px 12px; border-radius: 6px; border: 1px solid #ccc; font-size: 16px;">
                        <?php foreach ($organizations as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" style="width: 100%; padding: 12px 0; background: #F7971D; color: #fff; border: none; border-radius: 6px; font-weight: 700; font-size: 17px; cursor: pointer; margin-top: 10px;">Сравнить</button>
            </form>
        </div>
    </div>
    <div id="show-diff-container" style="display:none; text-align:center; margin: 16px 0;">
        <input type="checkbox" id="show-diff">
        <label for="show-diff">Показать только различия</label>
    </div>
    <div id="compare-block" style="display:none;">
        <div class="compare-table-scroll">
            <table id="compare-table" class="tableBig" style="display: none;">
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
    </div>
    <!-- Organization visibility logic moved to compare.js -->
    <script src="/js/compare.js?v=1"></script>
</body>
</html>
