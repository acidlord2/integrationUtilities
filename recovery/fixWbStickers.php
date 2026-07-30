<?php
/**
 * Fills in the WB sticker attributes on MoySklad orders that are missing them.
 *
 * The daily script writes «Штрихкод», «Номер доставки», «Служба доставки» and «QR WB» right after
 * it puts an order into a supply. An order that was created some other way - the MoySklad-only
 * backfill in recovery/backfillOrders.php, or a run that died between the create and the sticker
 * pass - has none of them, and the mop-up inside wildberries/<shop>/getNewOrders.php only looks at
 * orders sitting in a supply that is still open, so anything in a closed supply stays empty forever.
 *
 * This asks the question the other way round: which MoySklad orders are missing the barcode or the
 * delivery number, and what does WB have for them?
 *
 *   php recovery/fixWbStickers.php [all|ullo|kosmos] [dry|live] [--days=2] [--max=N]
 *
 * Writes to MoySklad only - it reads stickers from WB and never touches supplies or order status.
 *
 * @author Georgy Polyan <acidlord@yandex.ru>
 */

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

require_once($_SERVER['DOCUMENT_ROOT'] . '/docker-config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Api.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Orders.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Supplies.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/wildberries/order.php');

// the WB agent is shared by both organizations, so the organization is what separates the shops
$SHOPS = array(
    'ullo'   => array('shop' => 'Ullo',   'org' => MS_ULLO,   'label' => 'WB Ullo'),
    'kosmos' => array('shop' => 'Kosmos', 'org' => MS_KOSMOS, 'label' => 'WB Kosmos'),
);

// what has to be present for an order to count as done
$REQUIRED = array(
    MS_DELIVERYNUMBER_ATTR => 'Номер доставки',
    MS_BARCODE_ATTR_ID     => 'Штрихкод',
);

// -------------------------------------------------------------------------------- arguments
$selector = '';
$mode = 'dry';
$days = 2;
$max = 0;

foreach (array_slice($argv, 1) as $arg)
{
    if (strpos($arg, '--days=') === 0)
        $days = (float)substr($arg, 7);
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
    die("usage: php recovery/fixWbStickers.php <all|ullo|kosmos> [dry|live] [--days=2] [--max=N]\n");

$selected = $selector === 'all' ? array_keys($SHOPS) : array($selector);

$logger = new \Classes\Common\Log('recovery - fixWbStickers.log');

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
 * MoySklad orders of this shop since $since, read straight through APIMS.
 *
 * Not OrdersMS::findOrders(): that concatenates the filter into the url unescaped, and a
 * 'moment>=' filter carries a space.
 *
 * @return array|false
 */
function ordersSince($org, $since)
{
    $apiMS = new APIMS();
    $orders = array();
    $offset = 0;

    while (true)
    {
        $url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER
             . '?limit=100&offset=' . $offset
             . '&order=moment,desc'
             . '&filter=' . urlencode('agent=' . MS_WB_AGENT)
             . ';' . urlencode('organization=' . $org)
             . ';' . urlencode('moment>=' . $since);
        logline(__LINE__ . ' ordersSince.url - ' . $url);

        $page = null;
        for ($attempt = 1; $attempt <= 4; $attempt++)
        {
            $page = $apiMS->getData($url);
            if (is_array($page) && isset($page['rows']))
                break;
            out('     .. MoySklad page retry ' . $attempt);
            sleep(2);
        }

        if (!is_array($page) || !isset($page['rows']))
            return false;

        $orders = array_merge($orders, $page['rows']);
        $size = (int)($page['meta']['size'] ?? 0);
        $offset += 100;
        if (!count($page['rows']) || $offset >= $size)
            break;
        usleep(200000);
    }

    return $orders;
}

/**
 * True when the order already carries every required attribute with a value.
 */
function isComplete($order, $required)
{
    $have = array();
    foreach (($order['attributes'] ?? array()) as $attribute)
    {
        if (!isset($attribute['id']))
            continue;
        $value = $attribute['value'] ?? null;
        // a file attribute has no 'value' at all, it carries 'download' once stored
        $filled = isset($attribute['download']) || isset($attribute['file'])
               || (is_array($value) ? count($value) > 0 : trim((string)$value) !== '');
        if ($filled)
            $have[$attribute['id']] = true;
    }

    foreach (array_keys($required) as $id)
        if (!isset($have[$id]))
            return false;

    return true;
}

// -------------------------------------------------------------------------------- run
$sinceTs = time() - (int)round($days * 86400);
$since = date('Y-m-d H:i:s', $sinceTs);

out('WB sticker repair - ' . date('Y-m-d H:i:s T') . '  mode=' . strtoupper($mode));
out('shops: ' . implode(', ', $selected) . '   since ' . $since . ' (' . $days . ' days)'
  . ($max > 0 ? '   max=' . $max . '/shop' : ''));
out('looking for orders missing: ' . implode(' / ', $REQUIRED));
out('writes: MoySklad attributes only - no supply or status changes on WB');

$ordersMSClass = new OrdersMS();
$summary = array();

foreach ($selected as $key)
{
    $shopCfg = $SHOPS[$key];
    out('');
    out('=============================================================================');
    out('  ' . $shopCfg['label']);
    out('=============================================================================');

    $stat = array('scanned' => 0, 'incomplete' => 0, 'noSticker' => 0,
                  'written' => 0, 'verified' => 0, 'status' => 'ok');

    // ------------------------------------------------------------------ 1. MoySklad side
    $orders = ordersSince($shopCfg['org'], $since);
    if ($orders === false)
    {
        out('  [FAIL] MoySklad did not answer - skipping this shop');
        $stat['status'] = 'MoySklad read failed';
        $summary[$key] = $stat;
        continue;
    }

    $stat['scanned'] = count($orders);
    out('  scanned     ' . count($orders) . ' WB order(s) in MoySklad since ' . $since);

    $todo = array();
    foreach ($orders as $order)
    {
        if (!isset($order['name']) || strpos($order['name'], 'WB') !== 0)
            continue;
        if (isComplete($order, $REQUIRED))
            continue;
        $todo[] = $order;
    }

    $stat['incomplete'] = count($todo);
    out('  incomplete  ' . count($todo) . ' missing the barcode or the delivery number');

    if ($max > 0 && count($todo) > $max)
    {
        out('  capped      to ' . $max . ' this run (' . (count($todo) - $max) . ' left)');
        $todo = array_slice($todo, 0, $max);
    }

    if (!count($todo))
    {
        out('  nothing to fix');
        $summary[$key] = $stat;
        continue;
    }

    $ordersMSByName = array();
    $orderIds = array();
    foreach ($todo as $order)
    {
        $ordersMSByName[$order['name']] = $order;
        $orderIds[] = (int)substr($order['name'], 2);
    }

    // ------------------------------------------------------------------ 2. which supply each is in
    // «Служба доставки» is the supply name, so the order has to be mapped back to its supply.
    // /api/v3/orders carries supplyId; the window is widened because a MoySklad moment is the WB
    // createdAt converted to Moscow time.
    $apiWBClass = new \Classes\Wildberries\v1\Api($shopCfg['shop']);
    $supplyIdByOrder = array();
    $next = 0;
    $wbReadOk = true;

    while (true)
    {
        $url = WB_API_MARKETPLACE_API . WB_API_ORDERS . '?limit=1000&next=' . $next
             . '&dateFrom=' . ($sinceTs - 2 * 86400);
        $resp = $apiWBClass->getData($url);
        if ((int)$apiWBClass->getLastHttpCode() !== 200)
        {
            out('  [WARN] WB order list answered http ' . $apiWBClass->getLastHttpCode()
              . ' - «Служба доставки» will be left alone');
            $wbReadOk = false;
            break;
        }
        if (!isset($resp['orders']) || !count($resp['orders']))
            break;
        foreach ($resp['orders'] as $wbOrder)
            if (!empty($wbOrder['supplyId']))
                $supplyIdByOrder[(int)$wbOrder['id']] = $wbOrder['supplyId'];
        if (empty($resp['next']))
            break;
        $next = $resp['next'];
    }

    $supplyById = array();
    if ($wbReadOk)
    {
        $suppliesWBClass = new \Classes\Wildberries\v1\Supplies($shopCfg['shop']);
        foreach ($suppliesWBClass->getSupplies() as $supply)
            if (isset($supply['id']))
                $supplyById[$supply['id']] = $supply;
        out('  supplies    ' . count($supplyById) . ' known, ' . count($supplyIdByOrder) . ' of the orders are placed in one');
    }

    // ------------------------------------------------------------------ 3. stickers from WB
    $ordersWBClass = new \Classes\Wildberries\v1\Orders($shopCfg['shop']);
    $stickers = $ordersWBClass->getStickersMap($orderIds);
    out('  stickers    ' . count($stickers) . ' of ' . count($orderIds) . ' returned by WB');

    // ------------------------------------------------------------------ 4. build the updates
    $updates = array();
    $missing = array();

    foreach ($orderIds as $orderId)
    {
        $name = 'WB' . $orderId;
        if (!isset($stickers[$orderId]))
        {
            // no sticker means WB has not confirmed the order - it is still in status new, or it
            // never made it into a supply. Nothing to write, and nothing this script should fix.
            $missing[] = $name;
            continue;
        }

        $supplyId = $supplyIdByOrder[$orderId] ?? null;
        $supply = ($supplyId !== null && isset($supplyById[$supplyId])) ? $supplyById[$supplyId] : array('name' => '');

        $transformer = new \Wildberries\Order\OrderTransformation($shopCfg['shop'], $stickers[$orderId]);
        $update = $transformer->transformWildberriesStickerToMS($ordersMSByName[$name], $supply);

        // the transformer always writes «Служба доставки» from the supply name; drop it rather than
        // overwriting a real value with an empty one when the supply could not be resolved
        if ($supply['name'] === '')
            $update['attributes'] = array_values(array_filter($update['attributes'], function ($attribute) {
                return !isset($attribute['meta']['href'])
                    || strpos($attribute['meta']['href'], MS_DELIVERYSERVICE_ATTR) === false;
            }));

        out(sprintf('      %-14s barcode=%-12s delivery=%s-%s  supply=%s',
            $name, $stickers[$orderId]['barcode'],
            $stickers[$orderId]['partA'], $stickers[$orderId]['partB'],
            $supply['name'] !== '' ? $supply['name'] : '(unknown - left alone)'));

        $updates[] = $update;
    }

    $stat['noSticker'] = count($missing);
    if (count($missing))
    {
        out('  [WARN] no sticker from WB for ' . count($missing) . ' order(s) - still status new, or never in a supply:');
        out('         ' . implode(', ', array_slice($missing, 0, 20)) . (count($missing) > 20 ? ' ...' : ''));
        logline(__LINE__ . ' ' . $key . '.noSticker - ' . json_encode($missing, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    if (!count($updates))
    {
        out('  nothing to write');
        $summary[$key] = $stat;
        continue;
    }

    if ($mode === 'dry')
    {
        out('  DRY RUN - ' . count($updates) . ' order(s) would be updated, nothing sent');
        $summary[$key] = $stat;
        continue;
    }

    // ------------------------------------------------------------------ 5. write
    // small batches: every sticker is a png, so one request with the whole list is megabytes and
    // minutes - and if the run dies half way, whatever was written stays written
    foreach (array_chunk($updates, 20) as $chunkNumber => $chunk)
    {
        $result = $ordersMSClass->createCustomerorder($chunk);
        if (is_array($result) && isset($result[0]['id']))
        {
            $stat['written'] += count($chunk);
            out('  written     chunk ' . ($chunkNumber + 1) . ': ' . count($chunk));
        }
        else
        {
            out('  [FAIL] chunk ' . ($chunkNumber + 1) . ' of ' . count($chunk) . ' rejected: '
              . substr(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 300));
            logline(__LINE__ . ' ' . $key . '.writeFailed - ' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        usleep(300000);
    }

    // ------------------------------------------------------------------ 6. verify
    // read the orders back rather than trusting the write response
    $verified = 0;
    foreach ($updates as $update)
    {
        $fresh = $ordersMSClass->getOrderById($update['id']);
        if (is_array($fresh) && isComplete($fresh, $REQUIRED))
            $verified++;
        usleep(150000);
    }
    $stat['verified'] = $verified;
    out('  verified    ' . $verified . ' of ' . count($updates) . ' now carry both attributes');

    $summary[$key] = $stat;
}

// -------------------------------------------------------------------------------- summary
out('');
out('=============================================================================');
out('  SUMMARY  (' . strtoupper($mode) . ')');
out('=============================================================================');
out(sprintf('  %-10s %9s %12s %11s %9s %9s', 'shop', 'scanned', 'incomplete', 'no sticker', 'written', 'verified'));

$failed = 0;
foreach ($summary as $key => $stat)
{
    out(sprintf('  %-10s %9d %12d %11d %9d %9d%s',
        $key, $stat['scanned'], $stat['incomplete'], $stat['noSticker'],
        $stat['written'], $stat['verified'],
        $stat['status'] === 'ok' ? '' : '   <- ' . $stat['status']));
    if ($stat['status'] !== 'ok')
        $failed++;
}

out('');
if ($mode === 'dry')
    out('  Dry run. Re-run with "live" to write the attributes.');

exit($failed === 0 ? 0 : 1);
?>
