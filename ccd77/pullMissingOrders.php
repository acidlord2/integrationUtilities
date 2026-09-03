<?php
/**
 * "Загрузить недостающие заказы" for the ccd77 (WooCommerce) shop, from the integration page.
 *
 * The site pushes an order to MoySklad from a WordPress hook (woocommerce_order_status_processing),
 * so an order reaches MoySklad exactly once, at the moment it becomes "processing". If the hook did
 * not fire, or MoySklad was unreachable at that second, nothing ever retries it - the order simply
 * never appears. This page finds those orders and creates them.
 *
 * It does not reimplement anything: it collects the options and runs ccd77/backfillOrders.php, which
 * builds the very same payload the plugin builds and skips by order name, so re-running is safe and
 * cannot duplicate. Reads the shop database directly (localhost on this server) and writes only to
 * MoySklad - the shop itself is never modified.
 *
 * Dry run is the default. Creating requires ticking the confirmation, so a mis-click cannot write.
 *
 * @author Georgy Polyan <acidlord@yandex.ru>
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');

// auth.php sends the redirect but does not stop execution, so the session is re-checked here
// before anything is printed - this page reaches the shop database and MoySklad.
// No header() call here on purpose: auth.php emits a stray newline after its closing tag, so
// headers are already sent by this point and setting one only produces a warning.
if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== 'true')
{
    echo "not authenticated - log in first\n";
    exit(1);
}

date_default_timezone_set('Europe/Moscow');

// -------------------------------------------------------------------------------- options
$pageSince   = isset($_GET['since']) ? trim($_GET['since']) : date('Y-m-d', strtotime('-3 days'));
$pageStatus  = isset($_GET['status']) ? trim($_GET['status']) : 'wc-processing,wc-completed';
$pageMax     = isset($_GET['max']) ? (int)$_GET['max'] : 0;
$pageLive    = isset($_GET['live']) && $_GET['live'] === '1';
$pageConfirm = isset($_GET['confirm']) && $_GET['confirm'] === '1';
$pageRun     = isset($_GET['run']) && $_GET['run'] === '1';

// creating without the confirmation box falls back to a dry run rather than refusing outright
$pageMode = ($pageLive && $pageConfirm) ? 'live' : 'dry';

if ($pageSince !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $pageSince))
    $pageSince = date('Y-m-d', strtotime('-3 days'));

if (!preg_match('/^[a-z0-9_,\-]*$/i', $pageStatus))
    $pageStatus = 'wc-processing,wc-completed';

$pageWarning = '';
if ($pageRun && $pageLive && !$pageConfirm)
    $pageWarning = 'Создание не подтверждено — выполнена проверка без записи (dry).';
?>
<html>
	<head>
		<title>ccd77 — недостающие заказы</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<link rel = "stylesheet" type = "text/css"  href = "/css/styles.css?v=5" />
		<style>
			.ccd-form { margin: 0 auto 18px auto; max-width: 900px; text-align: left; }
			.ccd-form label { display: inline-block; margin: 6px 14px 6px 0; }
			.ccd-form input[type=text], .ccd-form input[type=number] { padding: 4px; }
			.ccd-note { max-width: 900px; margin: 0 auto 14px auto; text-align: left; color: #555; }
			.ccd-warn { max-width: 900px; margin: 0 auto 14px auto; text-align: left; color: #b00; font-weight: bold; }
			pre.ccd-out { max-width: 1200px; margin: 0 auto; text-align: left; background: #f6f6f6;
			              border: 1px solid #ddd; padding: 12px; overflow-x: auto; white-space: pre-wrap; }
		</style>
	</head>
	<body style="margin:0;padding:0">
		<?php require_once($_SERVER['DOCUMENT_ROOT'] . '/header.php'); ?>
		<div align="center">
			<div id="header">
				<div style="margin-bottom: 13px; margin-top: 14px; font-size: 200%; color:#F7971D;">
					ccd77 — недостающие заказы
				</div>
			</div>

			<div class = "ccd-note">
				Сайт отправляет заказ в МойСклад один раз — в момент перехода в статус «processing».
				Если в этот момент МойСклад был недоступен, заказ не появится уже никогда: повторной
				отправки нет. Здесь заказы читаются напрямую из базы магазина и сравниваются с
				МойСкладом по имени <code>ccd-&lt;номер заказа&gt;</code>; создаются только отсутствующие.
				Повторный запуск безопасен — дубликаты невозможны.
			</div>

			<form class = "ccd-form" method = "get" action = "/ccd77/pullMissingOrders.php">
				<input type = "hidden" name = "run" value = "1">
				<label>Заказы с даты:
					<input type = "text" name = "since" value = "<?php echo htmlspecialchars($pageSince, ENT_QUOTES, 'UTF-8'); ?>" size = "12" placeholder = "ГГГГ-ММ-ДД">
				</label>
				<label>Статусы:
					<input type = "text" name = "status" value = "<?php echo htmlspecialchars($pageStatus, ENT_QUOTES, 'UTF-8'); ?>" size = "30">
				</label>
				<label>Максимум (0 — без ограничения):
					<input type = "number" name = "max" value = "<?php echo (int)$pageMax; ?>" min = "0" size = "5">
				</label>
				<br>
				<label>
					<input type = "checkbox" name = "live" value = "1" <?php echo $pageLive ? 'checked' : ''; ?>>
					Создавать заказы в МойСкладе (без галочки — только проверка)
				</label>
				<label>
					<input type = "checkbox" name = "confirm" value = "1" <?php echo $pageConfirm ? 'checked' : ''; ?>>
					Подтверждаю создание
				</label>
				<br>
				<button class = "integration-button" type = "submit">Выполнить</button>
			</form>

<?php
if ($pageWarning !== '')
    echo '			<div class = "ccd-warn">' . htmlspecialchars($pageWarning, ENT_QUOTES, 'UTF-8') . "</div>\n";

if ($pageRun)
{
    echo '			<pre class = "ccd-out">';

    // backfillOrders.php exits on any hard failure, so the page tags are closed from a shutdown
    // handler instead of after the require - otherwise the output would be left unterminated.
    register_shutdown_function(function () {
        echo "</pre>\n		</div>\n	</body>\n</html>\n";
    });

    if (function_exists('set_time_limit'))
        @set_time_limit(600);

    // the script is command line shaped and reads its options from $argv, refusing to run when it
    // is missing - so build the same argument list a CLI invocation would have used
    $argv = array('pullMissingOrders.php', $pageMode);
    if ($pageSince !== '')
        $argv[] = '--since=' . $pageSince;
    if ($pageStatus !== '')
        $argv[] = '--status=' . $pageStatus;
    if ($pageMax > 0)
        $argv[] = '--max=' . $pageMax;
    $argc = count($argv);

    echo "run: " . htmlspecialchars(implode(' ', $argv), ENT_QUOTES, 'UTF-8') . "\n\n";
    @ob_implicit_flush(true);

    require(__DIR__ . '/backfillOrders.php');

    exit;
}
?>
		</div>
	</body>
</html>
