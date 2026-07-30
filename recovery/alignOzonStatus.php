<?php
/**
 * Packs Ozon postings that are still awaiting_packaging although their MoySklad order exists.
 *
 * Shipping is the only status transition this system performs on an FBS posting. The daily scripts
 * do it right after importing the order - setExemplar, then poll the exemplar status, then
 * posting/fbs/ship - and everything afterwards (delivering, delivered) is Ozon's own lifecycle.
 * The Ozon branches in mswh/whChangeOrder.php are commented out and were scoped to the DBS project,
 * so no MoySklad state change pushes a status to Ozon for these orders.
 *
 * Two things leave a posting behind:
 *   - an order created some other way, e.g. recovery/backfillOrders.php, which writes MoySklad only
 *   - the daily script's window: it asks Ozon for yesterday..today, so a posting whose in_process_at
 *     is older than that is never looked at again, however long it stays awaiting_packaging
 *
 * Only postings that already have a MoySklad order are touched: one without is not ours to ship,
 * the daily script will import and pack it.
 *
 *   php recovery/alignOzonStatus.php <all|ullo|kaori> [dry|live] [--since=2026-07-28] [--max=N]
 *
 *     --ms-created-from=DATE  only postings whose MoySklad record was written on or after DATE.
 *                             This is how "just the orders the backfill created" is expressed: a
 *                             posting that has been stuck since long before it is somebody's
 *                             decision to look at, not something to ship silently.
 *
 * @author Georgy Polyan <acidlord@yandex.ru>
 */

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

require_once($_SERVER['DOCUMENT_ROOT'] . '/docker-config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ozon/ApiOzon.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ozon/OrdersOzon.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');

$SHOPS = array(
    'ullo'  => array('label' => 'Ozon Ullo',  'warehouse' => OZON_ULLO_WEARHOUSE_MAIN),
    'kaori' => array('label' => 'Ozon Kaori', 'warehouse' => OZON_WEARHOUSE1_ID),
);

// -------------------------------------------------------------------------------- arguments
$selector = '';
$mode = 'dry';
$since = '2026-07-28';
$max = 0;
$msCreatedFrom = '';

foreach (array_slice($argv, 1) as $arg)
{
    if (strpos($arg, '--ms-created-from=') === 0)
        $msCreatedFrom = substr($arg, 18);
    elseif (strpos($arg, '--since=') === 0)
        $since = substr($arg, 8);
    elseif (strpos($arg, '--max=') === 0)
        $max = (int)substr($arg, 6);
    elseif ($arg === 'dry' || $arg === 'live')
        $mode = $arg;
    elseif ($selector === '')
        $selector = strtolower($arg);
    else
        die("unknown argument: $arg\n");
}

if ($selector === '' || ($selector !== 'all' && !isset($SHOPS[$selector])))
    die("usage: php recovery/alignOzonStatus.php <all|ullo|kaori> [dry|live] [--since=DATE] [--max=N]\n");

$selected = $selector === 'all' ? array_keys($SHOPS) : array($selector);
$sinceTs = strtotime($since . ' 00:00:00');
if ($sinceTs === false)
    die("--since is not a date I can read: $since\n");

$logger = new \Classes\Common\Log('recovery - alignOzonStatus.log');

function out($s = '')
{
    echo $s . "\n";
    flush();
}

function logline($s)
{
    global $logger;
    $logger->write($s);
}

/**
 * Declares the exemplars of a posting complete, so Ozon will allow it to be shipped.
 *
 * Not OrdersOzon::setExemplar, which cannot do it: that method invents exemplar_id 1..n, and
 * exemplar_id has to be one Ozon already issued (the api rejects anything else with "value must be
 * greater than 0" once you stop sending a counter). Ozon therefore kept its own exemplars with
 * is_gtd_absent=false, and a product that needs a customs declaration stayed unshippable with
 * EXEMPLAR_INFO_NOT_FILLED_COMPLETELY - which is why postings sit in awaiting_packaging for days.
 *
 * The working sequence is: read the exemplars Ozon created for the posting, then set those same ids
 * with both is_gtd_absent and is_rnpt_absent true. gtd_check_status then reads "passed" and the
 * exemplar status becomes ship_available.
 *
 * @return string - the exemplar status after setting, e.g. 'ship_available'
 */
function fillExemplars($api, $posting, $attempts = 5)
{
    $number = $posting['posting_number'];

    $current = $api->postData(OZON_MAINURL . OZON_API_V5 . OZON_API_EXEMPLAR_STATUS,
        array('posting_number' => $number));

    $idsByProduct = array();
    foreach ((array)($current['products'] ?? array()) as $row)
        foreach ((array)($row['exemplars'] ?? array()) as $exemplar)
            $idsByProduct[(int)$row['product_id']][] = (int)$exemplar['exemplar_id'];

    $payload = array('posting_number' => $number, 'multi_box_qty' => 1, 'products' => array());

    foreach ($posting['products'] as $product)
    {
        $productId = (int)$product['sku'];
        $known = $idsByProduct[$productId] ?? array();
        $exemplars = array();

        for ($i = 0; $i < (int)$product['quantity']; $i++)
            $exemplars[] = array(
                // fall back to a counter only when Ozon issued no exemplar, which is what the
                // daily script always does - it is rejected for goods needing a declaration
                'exemplar_id'    => $known[$i] ?? ($i + 1),
                'is_gtd_absent'  => true,
                'is_rnpt_absent' => true
            );

        $payload['products'][] = array(
            'product_id' => $productId,
            'quantity'   => (int)$product['quantity'],
            'exemplars'  => $exemplars
        );
    }

    $set = $api->postData(OZON_MAINURL . OZON_API_V6 . OZON_API_EXEMPLAR_SET, $payload);
    logline(__LINE__ . ' fillExemplars ' . $number . ' payload - ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    logline(__LINE__ . ' fillExemplars ' . $number . ' set - ' . json_encode($set, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    if (is_array($set) && isset($set['code']))
        return 'set failed: ' . substr(json_encode($set, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 200);

    $status = '';
    for ($attempt = 1; $attempt <= $attempts; $attempt++)
    {
        sleep(1);
        $check = $api->postData(OZON_MAINURL . OZON_API_V5 . OZON_API_EXEMPLAR_STATUS,
            array('posting_number' => $number));
        $status = $check['status'] ?? '?';
        if ($status === 'ship_available')
            break;
    }

    return $status;
}

/**
 * Postings in the given status, straight from the api so the window is ours to choose.
 *
 * @return array|false
 */
function postings($shop, $warehouse, $sinceTs, $status)
{
    $api = new ApiOzon($shop);
    $found = array();
    $offset = 0;

    while (true)
    {
        $postData = array(
            'dir'    => 'ASC',
            'filter' => array(
                'since'        => gmdate('Y-m-d\TH:i:s\Z', $sinceTs),
                'to'           => gmdate('Y-m-d\TH:i:s\Z', time() + 86400),
                'status'       => $status,
                'warehouse_id' => array($warehouse)
            ),
            'limit'  => 50,
            'offset' => $offset,
            'with'   => array('barcodes' => true)
        );

        $resp = $api->postData(OZON_MAINURL . OZON_API_V3 . OZON_API_ORDERS_LIST, $postData);
        if (!is_array($resp) || !isset($resp['result']['postings']))
        {
            out('     Ozon read failed - ' . substr(json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 200));
            return false;
        }
        if (!count($resp['result']['postings']))
            break;

        $found = array_merge($found, $resp['result']['postings']);
        $offset += 50;
    }

    return $found;
}

// -------------------------------------------------------------------------------- run
out('Ozon status alignment - ' . date('Y-m-d H:i:s T') . '  mode=' . strtoupper($mode));
out('shops: ' . implode(', ', $selected) . '   since ' . $since . ($max > 0 ? '   max=' . $max . '/shop' : ''));
out('action: setExemplar -> exemplar status -> posting/fbs/ship, exactly what the daily script does');

$ordersMSClass = new OrdersMS();
$summary = array();

foreach ($selected as $key)
{
    $shopCfg = $SHOPS[$key];
    out('');
    out('=============================================================================');
    out('  ' . $shopCfg['label']);
    out('=============================================================================');

    $stat = array('awaiting' => 0, 'ours' => 0, 'shipped' => 0, 'failed' => 0,
                  'split' => 0, 'verified' => 0, 'status' => 'ok');

    $awaiting = postings($key, $shopCfg['warehouse'], $sinceTs, 'awaiting_packaging');
    if ($awaiting === false)
    {
        out('  [FAIL] could not read postings - skipping this shop');
        $stat['status'] = 'Ozon read failed';
        $summary[$key] = $stat;
        continue;
    }

    $stat['awaiting'] = count($awaiting);
    out('  awaiting    ' . count($awaiting) . ' posting(s) in awaiting_packaging since ' . $since);

    // ------------------------------------------------------------------ ours only
    $todo = array();
    $olderThanFilter = array();
    foreach ($awaiting as $posting)
    {
        $found = $ordersMSClass->findOrders('name=' . $posting['posting_number']);
        if (!is_array($found) || !count($found))
            continue;

        $posting['_msCreated'] = substr($found[0]['created'] ?? '', 0, 19);

        if ($msCreatedFrom !== '' && substr($posting['_msCreated'], 0, 10) < $msCreatedFrom)
        {
            $olderThanFilter[] = $posting['posting_number'] . ' (MoySklad record written ' . $posting['_msCreated'] . ')';
            continue;
        }

        $todo[] = $posting;
        usleep(150000);
    }

    $stat['ours'] = count($todo);
    out('  in MoySklad ' . count($todo) . ' of them - the rest are not ours to ship yet');

    if (count($olderThanFilter))
    {
        out('  left alone  ' . count($olderThanFilter) . ' posting(s) whose MoySklad record predates --ms-created-from='
          . $msCreatedFrom . ':');
        foreach ($olderThanFilter as $line)
            out('              ' . $line);
        out('              Stuck awaiting_packaging for a while - worth a human look, not a silent ship.');
    }

    if ($max > 0 && count($todo) > $max)
    {
        out('  capped      to ' . $max . ' this run');
        $todo = array_slice($todo, 0, $max);
    }

    if (!count($todo))
    {
        out('  nothing to align');
        $summary[$key] = $stat;
        continue;
    }

    out('');
    foreach ($todo as $posting)
        out(sprintf('      %-22s in_process=%s  products=%d  MoySklad record written %s',
            $posting['posting_number'], $posting['in_process_at'],
            count($posting['products']), $posting['_msCreated']));
    out('');

    if ($mode === 'dry')
    {
        out('  DRY RUN - ' . count($todo) . ' posting(s) would be shipped, nothing sent to Ozon');
        $summary[$key] = $stat;
        continue;
    }

    // ------------------------------------------------------------------ ship
    $ordersOzonClass = new OrdersOzon($key);
    $api = new ApiOzon($key);

    foreach ($todo as $posting)
    {
        $number = $posting['posting_number'];

        $exemplarStatus = fillExemplars($api, $posting);

        if ($exemplarStatus !== 'ship_available')
            out(sprintf('  [WARN] %-22s exemplar status is %s - shipping anyway, as the daily script does',
                $number, $exemplarStatus));

        $result = $ordersOzonClass->packOrder($posting);
        logline(__LINE__ . ' ship ' . $number . ' exemplar=' . $exemplarStatus . ' result - '
              . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if (!is_array($result) || isset($result['error']) || isset($result['code']))
        {
            $stat['failed']++;
            out(sprintf('   !! %-22s exemplar=%-16s ship FAILED: %s', $number, $exemplarStatus,
                substr(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 220)));
            continue;
        }

        // v4 ship answers with the resulting postings: more than one means Ozon split the posting,
        // and the new numbers no longer match the MoySklad order name
        $resulting = array();
        foreach ((array)($result['result'] ?? array()) as $entry)
            if (isset($entry['posting_number']))
                $resulting[] = $entry['posting_number'];
            elseif (is_string($entry))
                $resulting[] = $entry;

        $stat['shipped']++;

        if (count($resulting) && !in_array($number, $resulting, true))
        {
            $stat['split']++;
            out(sprintf('   ~  %-22s shipped, but Ozon returned %s - the posting was split, so the'
                      . ' MoySklad order name no longer matches', $number, implode(', ', $resulting)));
        }
        else
            out(sprintf('   ok %-22s exemplar=%-16s shipped', $number, $exemplarStatus));

        usleep(500000);
    }

    // ------------------------------------------------------------------ verify
    // Each posting is read back individually with posting/fbs/get. The list endpoint is eventually
    // consistent - it still reported a posting as awaiting_packaging right after a ship that had in
    // fact moved it to awaiting_deliver, which reads as a failure that never happened.
    out('');
    foreach ($todo as $posting)
    {
        $number = $posting['posting_number'];
        $fresh = $api->postData(OZON_MAINURL . OZON_API_V3 . OZON_API_ORDER_GET,
            array('posting_number' => $number));
        $status = $fresh['result']['status'] ?? '?';

        if ($status !== 'awaiting_packaging')
        {
            $stat['verified']++;
            out(sprintf('  verified    %-22s is now %s', $number, $status));
        }
        else
            out(sprintf('  [WARN]      %-22s is still awaiting_packaging', $number));

        usleep(300000);
    }

    out('  verified    ' . $stat['verified'] . ' of ' . count($todo) . ' moved out of awaiting_packaging');

    $summary[$key] = $stat;
}

// -------------------------------------------------------------------------------- summary
out('');
out('=============================================================================');
out('  SUMMARY  (' . strtoupper($mode) . ')');
out('=============================================================================');
out(sprintf('  %-8s %10s %11s %9s %8s %7s %9s', 'shop', 'awaiting', 'in MoySklad', 'shipped', 'failed', 'split', 'verified'));

$failed = 0;
foreach ($summary as $key => $stat)
{
    out(sprintf('  %-8s %10d %11d %9d %8d %7d %9d%s',
        $key, $stat['awaiting'], $stat['ours'], $stat['shipped'], $stat['failed'],
        $stat['split'], $stat['verified'],
        $stat['status'] === 'ok' ? '' : '   <- ' . $stat['status']));
    if ($stat['status'] !== 'ok' || $stat['failed'])
        $failed++;
}

out('');
if ($mode === 'dry')
    out('  Dry run. Re-run with "live" to ship these postings.');

exit($failed === 0 ? 0 : 1);
?>
