<?php
/**
 * Dumps ccd77 (WooCommerce) orders to a json file. Run this ON THE HOSTING SERVER: the shop
 * database only listens on localhost there.
 *
 * Nothing is sent anywhere and nothing is written to the database - this reads and writes one file.
 * The json it produces is the input for ccd77/backfillOrders.php --from-json=FILE, which does the
 * MoySklad half and can run from anywhere.
 *
 *   php ccd77/exportOrders.php --since=2026-07-28
 *
 *     --since=DATE      only orders created at or after DATE, in site time ('2026-07-28' is fine)
 *     --since-id=N      only orders with an id above N
 *     --status=LIST     comma separated statuses, e.g. wc-processing,wc-completed
 *                       default: every status, so the file is a complete picture and the decision
 *                       about which ones to create can be made later without a second trip here
 *     --limit=N         stop after N orders
 *     --out=FILE        where to write, default ./ccd77-orders-<date>.json
 *     --with-token      include the MoySklad token from wp_gms_settings (masked by default)
 *     --db-name=NAME    another database, e.g. a copy. Also CCD77_DB_HOST/USER/PASSWORD/NAME/PORT.
 *
 * @author Georgy Polyan <acidlord@yandex.ru>
 */

require_once(__DIR__ . '/ccdDb.php');

date_default_timezone_set('Europe/Moscow');

$since = null;
$sinceId = 0;
$statuses = array();
$limit = 0;
$outFile = '';
$withToken = false;
$dbName = null;

foreach (array_slice($argv, 1) as $arg)
{
    if (strpos($arg, '--since=') === 0)
        $since = substr($arg, 8);
    elseif (strpos($arg, '--since-id=') === 0)
        $sinceId = (int)substr($arg, 11);
    elseif (strpos($arg, '--status=') === 0)
    {
        $value = substr($arg, 9);
        $statuses = ($value === '' || $value === 'all') ? array() : array_map('trim', explode(',', $value));
    }
    elseif (strpos($arg, '--limit=') === 0)
        $limit = (int)substr($arg, 8);
    elseif (strpos($arg, '--out=') === 0)
        $outFile = substr($arg, 6);
    elseif (strpos($arg, '--db-name=') === 0)
        $dbName = substr($arg, 10);
    elseif ($arg === '--with-token')
        $withToken = true;
    else
        die("unknown argument: $arg\nsee the header of this file for usage\n");
}

// '2026-07-28' means the whole day, not midnight-as-a-timestamp-in-a-string-comparison
if ($since !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $since))
    $since .= ' 00:00:00';

if ($since !== null && strtotime($since) === false)
    die("--since is not a date I can read: $since\n");

if ($outFile === '')
    $outFile = 'ccd77-orders-' . date('Y-m-d_Hi') . '.json';

echo "ccd77 order export - " . date('Y-m-d H:i:s T') . "\n";

try
{
    $db = new \Ccd77\CcdDb(null, null, null, $dbName);
}
catch (Exception $e)
{
    echo '[FAIL] ' . $e->getMessage() . "\n";
    echo "       Run this on the hosting server, or set CCD77_DB_HOST / CCD77_DB_PORT.\n";
    exit(1);
}

echo 'schema      ' . $db->schema() . "\n";
echo 'site tz     ' . $db->timezone()->getName() . "\n";
echo 'filters     since=' . ($since ?? '-') . '  since-id=' . $sinceId
   . '  status=' . (count($statuses) ? implode(',', $statuses) : 'all')
   . '  limit=' . ($limit ?: '-') . "\n";

try
{
    $export = $db->export($statuses, $since, $sinceId, $limit, $withToken);
}
catch (Exception $e)
{
    echo '[FAIL] ' . $e->getMessage() . "\n";
    exit(1);
}

$json = json_encode($export, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if ($json === false)
{
    echo '[FAIL] could not encode the export: ' . json_last_error_msg() . "\n";
    exit(1);
}

if (file_put_contents($outFile, $json) === false)
{
    echo '[FAIL] could not write ' . $outFile . " - is the directory writable?\n";
    exit(1);
}

// what is in the file, so a glance at the output is enough to know the export is sane
$byStatus = array();
$items = 0;
$noPhone = 0;
$firstDate = '';
$lastDate = '';
foreach ($export['orders'] as $order)
{
    $byStatus[$order['status']] = ($byStatus[$order['status']] ?? 0) + 1;
    $items += count($order['items']);
    if (trim($order['billing']['phone']) === '')
        $noPhone++;
    if ($firstDate === '' || $order['dateCreated'] < $firstDate)
        $firstDate = $order['dateCreated'];
    if ($order['dateCreated'] > $lastDate)
        $lastDate = $order['dateCreated'];
}

$statusOut = array();
foreach ($byStatus as $status => $count)
    $statusOut[] = $status . '=' . $count;

echo "\n";
echo 'exported    ' . count($export['orders']) . ' order(s), ' . $items . " line item(s)\n";
echo 'statuses    ' . (count($statusOut) ? implode('  ', $statusOut) : '-') . "\n";
echo 'dates       ' . ($firstDate ?: '-') . '  ->  ' . ($lastDate ?: '-') . " (site time)\n";
if ($noPhone)
    echo '[WARN]      ' . $noPhone . " order(s) have no billing phone - those cannot be matched to a counterparty\n";
echo 'settings    ' . count($export['settings']) . ' key(s) from wp_gms_settings'
   . ($withToken ? ' (ms_token INCLUDED)' : ' (ms_token masked)') . "\n";
echo 'file        ' . realpath($outFile) . '  (' . number_format(strlen($json) / 1024, 1) . " KB)\n";
echo "\nDownload that file and hand it to the backfill:\n";
echo "  php ccd77/backfillOrders.php --from-json=" . basename($outFile) . " dry\n";

exit(0);
?>
