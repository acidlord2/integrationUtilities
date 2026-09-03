<?php
/**
 * Shows the live `settings` table of this server - the tokens and keys every integration actually
 * authenticates with.
 *
 * Written because there is no other way to see them: the table lives in a database that only listens
 * on localhost of the hosting server, so "is the token on the server the one I just generated?"
 * could previously only be answered by guessing. Rotating a marketplace token silently invalidates
 * the previous one, and a stale copy shows up as a whole marketplace going quiet, so being able to
 * read the current value is the difference between a diagnosis and a guess.
 *
 * Read-only. Nothing here writes to the database or to any API.
 *
 * Values are masked unless «показать значения» is ticked, and the SQL/JSON views are there to seed a
 * development database - the same reason the page exists at all.
 *
 *   /diagnostics/settings.php                 masked list
 *   /diagnostics/settings.php?reveal=1        full values
 *   /diagnostics/settings.php?format=sql      upsert statement for a local database
 *   /diagnostics/settings.php?format=json     code => value
 *   &filter=wb                                only codes containing "wb"
 *
 * @author Georgy Polyan <acidlord@yandex.ru>
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');

// auth.php redirects but does not stop, so the session is re-checked before anything is printed.
// No header() call here: auth.php emits a stray newline after its closing tag, so headers are
// already sent and setting one would only warn.
if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== 'true')
{
    echo "not authenticated - log in first\n";
    exit(1);
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');

$reveal = isset($_GET['reveal']) && $_GET['reveal'] === '1';
$format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : '';
$filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';

if (!in_array($format, array('', 'sql', 'json'), true))
    $format = '';

// Deliberately not \Classes\Common\Settings: that class writes every row it reads, value included,
// into logs/classes - Common - Settings.log. Reading this page must not copy secrets into a log.
$rows = Db::exec_query_array('select code, value from settings order by code');
if (!is_array($rows))
    $rows = array();

if ($filter !== '')
{
    $needle = mb_strtolower($filter, 'UTF-8');
    $rows = array_values(array_filter($rows, function ($r) use ($needle) {
        return mb_strpos(mb_strtolower($r['code'], 'UTF-8'), $needle) !== false;
    }));
}

/** first and last few characters only - enough to compare two copies without exposing the value */
function maskValue($v)
{
    $len = strlen($v);
    if ($len === 0)
        return '(пусто)';
    if ($len <= 12)
        return str_repeat('*', $len);
    return substr($v, 0, 6) . str_repeat('*', 6) . substr($v, -4);
}

function h($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$qs = function ($overrides) use ($reveal, $format, $filter) {
    $p = array();
    if ($reveal) $p['reveal'] = '1';
    if ($format !== '') $p['format'] = $format;
    if ($filter !== '') $p['filter'] = $filter;
    foreach ($overrides as $k => $v)
    {
        if ($v === null) unset($p[$k]);
        else $p[$k] = $v;
    }
    return '/diagnostics/settings.php' . (count($p) ? '?' . http_build_query($p) : '');
};
?>
<html>
	<head>
		<title>Настройки сервера</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<link rel = "stylesheet" type = "text/css"  href = "/css/styles.css?v=5" />
		<style>
			.s-wrap { max-width: 1100px; margin: 0 auto; text-align: left; }
			.s-note { color: #555; margin-bottom: 12px; }
			.s-warn { color: #b00; font-weight: bold; margin-bottom: 12px; }
			table.s-tbl { border-collapse: collapse; width: 100%; }
			table.s-tbl th, table.s-tbl td { border: 1px solid #ddd; padding: 5px 8px; font-size: 13px;
			                                 text-align: left; vertical-align: top; }
			table.s-tbl th { background: #f2f2f2; }
			table.s-tbl td.val { font-family: monospace; word-break: break-all; }
			.s-tools { margin-bottom: 14px; }
			.s-tools a { margin-right: 12px; }
			pre.s-out { background: #f6f6f6; border: 1px solid #ddd; padding: 12px;
			            overflow-x: auto; white-space: pre-wrap; word-break: break-all; }
		</style>
	</head>
	<body style="margin:0;padding:0">
		<?php require_once($_SERVER['DOCUMENT_ROOT'] . '/header.php'); ?>
		<div align="center">
			<div id="header">
				<div style="margin-bottom: 13px; margin-top: 14px; font-size: 200%; color:#F7971D;">
					Настройки сервера
				</div>
			</div>
			<div class = "s-wrap">
				<div class = "s-note">
					Таблица <code>settings</code> этого сервера — те самые значения, которыми
					авторизуются интеграции. Страница только читает, ничего не меняет.
				</div>
<?php if ($reveal): ?>
				<div class = "s-warn">
					Значения показаны полностью. Это действующие токены и пароли — не открывайте
					страницу при посторонних и не сохраняйте её.
				</div>
<?php endif; ?>

				<form method = "get" action = "/diagnostics/settings.php" class = "s-tools">
					<?php if ($format !== ''): ?><input type = "hidden" name = "format" value = "<?php echo h($format); ?>"><?php endif; ?>
					<label>Фильтр по коду:
						<input type = "text" name = "filter" value = "<?php echo h($filter); ?>" size = "20" placeholder = "например wb">
					</label>
					<label>
						<input type = "checkbox" name = "reveal" value = "1" <?php echo $reveal ? 'checked' : ''; ?>>
						показать значения
					</label>
					<button class = "integration-button" type = "submit">Показать</button>
				</form>

				<div class = "s-tools">
					<a href = "<?php echo h($qs(array('format' => null))); ?>">Таблица</a>
					<a href = "<?php echo h($qs(array('format' => 'sql', 'reveal' => '1'))); ?>">SQL для локальной БД</a>
					<a href = "<?php echo h($qs(array('format' => 'json', 'reveal' => '1'))); ?>">JSON</a>
					<span style = "color:#888">всего: <?php echo count($rows); ?></span>
				</div>

<?php
if ($format === 'sql' || $format === 'json')
{
    if (!$reveal)
    {
        echo '				<div class = "s-warn">Для выгрузки нужно отметить «показать значения».</div>' . "\n";
    }
    else
    {
        echo '				<pre class = "s-out">';
        if ($format === 'sql')
        {
            echo "-- settings from " . h($_SERVER['HTTP_HOST'] ?? 'server') . ' at ' . date('Y-m-d H:i:s') . "\n";
            echo "-- upsert by code: existing rows are updated, nothing is deleted\n";
            echo "INSERT INTO `settings` (`code`, `value`) VALUES\n";
            $lines = array();
            foreach ($rows as $r)
                $lines[] = "  ('" . h(addslashes($r['code'])) . "', '" . h(addslashes($r['value'])) . "')";
            echo implode(",\n", $lines);
            echo "\nON DUPLICATE KEY UPDATE `value` = VALUES(`value`);\n";
        }
        else
        {
            $map = array();
            foreach ($rows as $r)
                $map[$r['code']] = $r['value'];
            echo h(json_encode($map, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        echo "</pre>\n";
    }
}
else
{
?>
				<table class = "s-tbl">
					<tr><th style = "width:320px">Код</th><th style = "width:70px">Длина</th><th>Значение</th></tr>
<?php
    foreach ($rows as $r)
    {
        echo '					<tr><td>' . h($r['code']) . '</td>'
           . '<td>' . strlen($r['value']) . '</td>'
           . '<td class = "val">' . h($reveal ? $r['value'] : maskValue($r['value'])) . "</td></tr>\n";
    }
    if (!count($rows))
        echo '					<tr><td colspan = "3">ничего не найдено</td></tr>' . "\n";
?>
				</table>
<?php
}
?>
			</div>
		</div>
	</body>
</html>
