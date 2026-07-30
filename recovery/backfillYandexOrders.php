<?php
/**
 * Replays the Yandex.Market order workflow for orders the notification never processed.
 *
 * beru-<shop>/order/notification.php is a push endpoint: Yandex calls it once per status change and
 * it creates the order on PROCESSING/STARTED, flags it on CANCELLED, and would set it delivered on
 * DELIVERED. A notification that never arrives, or arrives while MoySklad is down, is not retried by
 * anything - the order simply never appears. This asks Yandex for the orders of the last days and
 * puts each one through the same branches, by its current status.
 *
 *   php recovery/backfillYandexOrders.php <all|kosmos|ullozza> [dry|live] [--days=2] [--max=N]
 *
 * Creates an order for a status that can only have been reached through PROCESSING/STARTED, so the
 * notification would have created it: PROCESSING, DELIVERY, PICKUP, DELIVERED. UNPAID is skipped -
 * it never got that far - and so is a CANCELLED order that is not in MoySklad, which is exactly what
 * the notification does with one.
 *
 * A CANCELLED order that *is* in MoySklad gets the cancel branch: «Отменен Маркетплейс» set, and the
 * state moved to отменен only from подтвержден, never from a later one - the notification's rule.
 *
 * DELIVERED is deliberately left alone. The notification's delivered branch passes the Yandex order
 * id where MoySklad expects its uuid, so it has never worked, and every delivered order in the
 * account sits in «отгружен (маркетплейс)». Moving a handful to «доставлен» would make those few
 * inconsistent with all the rest; that is a decision for whoever owns the process, not a backfill.
 *
 * Writes to MoySklad only. Nothing is sent to Yandex - no status is pushed back.
 *
 * @author Georgy Polyan <acidlord@yandex.ru>
 */

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

require_once($_SERVER['DOCUMENT_ROOT'] . '/docker-config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ordersBeru2.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Yandex/ordersYandex2.php');

$SHOPS = array(
    'kosmos' => array(
        'label'    => 'Yandex Kosmos',
        'campaign' => BERU_API_KOSMOS_CAMPAIGN,
        'org'      => MS_KOSMOS,
        'project'  => MS_PROJECT_YANDEX_KOSMOS,
    ),
    'ullozza' => array(
        'label'    => 'Yandex Ullozza',
        'campaign' => BERU_API_ULLOZZA_CAMPAIGN,
        'org'      => MS_ULLO,
        'project'  => MS_PROJECT_YANDEX_ULLO,
    ),
);

// statuses that can only have been reached by passing PROCESSING/STARTED
$CREATE_STATUS = array('PROCESSING', 'DELIVERY', 'PICKUP', 'DELIVERED');

// -------------------------------------------------------------------------------- arguments
$selector = '';
$mode = 'dry';
$days = 2;
$max = 0;

foreach (array_slice($argv, 1) as $arg)
{
    if (strpos($arg, '--days=') === 0)
        $days = (int)substr($arg, 7);
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
    die("usage: php recovery/backfillYandexOrders.php <all|kosmos|ullozza> [dry|live] [--days=2] [--max=N]\n");

$selected = $selector === 'all' ? array_keys($SHOPS) : array($selector);
$fromDate = date('d-m-Y', strtotime('-' . $days . ' days'));

$logger = new \Classes\Common\Log('recovery - backfillYandexOrders.log');

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
 * Builds the MoySklad order for a Yandex order, field for field as notification.php does.
 *
 * @return array|string - the payload, or a string saying why it cannot be built
 */
function buildPayload($shopCfg, $yandexOrder, $productClass)
{
    $orderId = (string)$yandexOrder['id'];

    if (!isset($yandexOrder['items']) || !count($yandexOrder['items']))
        return 'order has no items';

    $data = array();
    $data['name'] = $orderId;
    $data['organization'] = array('meta' => array(
        'href' => $shopCfg['org'], 'type' => 'organization', 'mediaType' => 'application/json'));
    $data['externalCode'] = $orderId;

    // The notification stamps the moment it runs, which is the order's creation time because it runs
    // immediately. Days later that would be plain wrong, so the order's own creationDate is used -
    // the value a notification that had arrived on time would have written.
    $created = isset($yandexOrder['creationDate'])
        ? DateTime::createFromFormat('d-m-Y H:i:s', $yandexOrder['creationDate'])
        : false;
    if ($created === false)
        $created = new DateTime();
    $data['moment'] = $created->format('Y-m-d H:i:s');

    if (isset($yandexOrder['delivery']['shipments'][0]['shipmentDate']))
    {
        $shipment = DateTime::createFromFormat('d-m-Y', $yandexOrder['delivery']['shipments'][0]['shipmentDate']);
        $data['deliveryPlannedMoment'] = $shipment === false
            ? (clone $created)->modify('+1 day')->format('Y-m-d H:i:s')
            : $shipment->format('Y-m-d H:i:s');
    }
    else
    {
        // the notification says "tomorrow"; tomorrow from the order, not from the backfill
        $data['deliveryPlannedMoment'] = (clone $created)->modify('+1 day')->format('Y-m-d H:i:s');
    }

    $data['agent'] = array('meta' => array(
        'href' => MS_BERU_AGENT, 'type' => 'counterparty', 'mediaType' => 'application/json'));
    $data['state'] = array('meta' => array(
        'href' => MS_CONFIRMBERU_STATE, 'type' => 'state', 'mediaType' => 'application/json'));
    $data['applicable'] = true;
    $data['store'] = array('meta' => array(
        'href' => MS_STORE, 'type' => 'store', 'mediaType' => 'application/json'));
    $data['project'] = array('meta' => array(
        'href' => $shopCfg['project'], 'type' => 'project', 'mediaType' => 'application/json'));
    $data['vatEnabled'] = false;
    $data['vatIncluded'] = false;

    $attrBase = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/';
    $data['attributes'] = array(
        // способ доставки
        array(
            'meta'  => array('href' => $attrBase . MS_DELIVERY_ATTR, 'type' => 'attributemetadata', 'mediaType' => 'application/json'),
            'value' => array('meta' => array('href' => MS_SHIPTYPE_BERU, 'type' => 'customentity', 'mediaType' => 'application/json'))
        ),
        // время доставки
        array(
            'meta'  => array('href' => $attrBase . MS_DELIVERYTIME_ATTR, 'type' => 'attributemetadata', 'mediaType' => 'application/json'),
            'value' => array('meta' => array('href' => MS_DELIVERYTIME_VALUE1, 'type' => 'customentity', 'mediaType' => 'application/json'))
        ),
        // тип оплаты
        array(
            'meta'  => array('href' => $attrBase . MS_PAYMENTTYPE_ATTR, 'type' => 'attributemetadata', 'mediaType' => 'application/json'),
            'value' => array('meta' => array('href' => MS_PAYMENTTYPE_SBERBANK, 'type' => 'customentity', 'mediaType' => 'application/json'))
        )
    );

    $data['positions'] = array();
    foreach ($yandexOrder['items'] as $item)
    {
        $product = $productClass->findProductsByCode($item['offerId']);
        // the notification answers Yandex with a 400 here so the order is retried later. There is
        // nobody to answer in a backfill, so the order is reported and left for a human instead of
        // being created with the wrong goods on it.
        if (!isset($product[0]))
            return 'offerId ' . $item['offerId'] . ' not found in MoySklad';

        $data['positions'][] = array(
            'assortment' => array('meta' => array(
                'href'      => $product[0]['meta']['href'],
                'type'      => $product[0]['meta']['type'],
                'mediaType' => 'application/json'
            )),
            'quantity' => $item['count'],
            'price'    => (int)$item['priceBeforeDiscount'] * 100,
            'vat'      => 0,
            'discount' => (int)0,
            'reserve'  => $item['count']
        );
    }

    return $data;
}

/**
 * The cancel branch of the notification: flag the order, and move it to отменен only from
 * подтвержден - a shipped order keeps its state and just gets the flag.
 *
 * @return array|false - the update payload, or false when nothing needs changing
 */
function buildCancelUpdate($msOrder)
{
    $flagged = false;
    foreach (($msOrder['attributes'] ?? array()) as $attribute)
        if (($attribute['id'] ?? '') === MS_MPCANCEL_ATTR_ID && !empty($attribute['value']))
            $flagged = true;

    $stateHref = $msOrder['state']['meta']['href'] ?? '';
    $isCancelled = strpos($stateHref, MS_CANCEL_STATE_ID) !== false;
    $fromConfirmed = strpos($stateHref, MS_CONFIRMBERU_STATE_ID) !== false
                  || strpos($stateHref, MS_CONFIRM_STATE_ID) !== false;

    if ($flagged && ($isCancelled || !$fromConfirmed))
        return false;

    $update = array('attributes' => array(array(
        'meta'  => array(
            'href'      => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_MPCANCEL_ATTR_ID,
            'type'      => 'attributemetadata',
            'mediaType' => 'application/json'
        ),
        'value' => true
    )));

    if ($fromConfirmed)
        $update['state'] = array('meta' => array(
            'href'      => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDERSTATE . '/' . MS_CANCEL_STATE_ID,
            'type'      => 'state',
            'mediaType' => 'application/json'
        ));

    return $update;
}

// -------------------------------------------------------------------------------- run
out('Yandex order backfill - ' . date('Y-m-d H:i:s T') . '  mode=' . strtoupper($mode));
out('shops: ' . implode(', ', $selected) . '   fromDate ' . $fromDate . ' (' . $days . ' days)'
  . ($max > 0 ? '   max=' . $max . '/shop' : ''));
out('creates for ' . implode('/', $CREATE_STATUS) . '; cancels what Yandex cancelled; leaves DELIVERED state alone');

$ordersMSClass = new OrdersMS();
$productClass = new ProductsMS();
$summary = array();

foreach ($selected as $key)
{
    $shopCfg = $SHOPS[$key];
    out('');
    out('=============================================================================');
    out('  ' . $shopCfg['label'] . '  (campaign ' . $shopCfg['campaign'] . ')');
    out('=============================================================================');

    $stat = array('yandex' => 0, 'inMs' => 0, 'toCreate' => 0, 'created' => 0,
                  'toCancel' => 0, 'cancelled' => 0, 'skipped' => 0, 'status' => 'ok');

    $orders = OrdersBeru2::getOrders($shopCfg['campaign'], array('fromDate' => $fromDate));
    if (!is_array($orders))
    {
        out('  [FAIL] Yandex order list failed - skipping this shop');
        $stat['status'] = 'Yandex read failed';
        $summary[$key] = $stat;
        continue;
    }

    $stat['yandex'] = count($orders);
    out('  yandex      ' . count($orders) . ' order(s) since ' . $fromDate);

    $byId = array();
    foreach ($orders as $order)
        $byId[(string)$order['id']] = $order;

    // ------------------------------------------------------------------ what MoySklad has
    $existing = array();
    $ok = true;
    foreach (array_chunk(array_keys($byId), 20) as $chunk)
    {
        $filter = '';
        foreach ($chunk as $name)
            $filter .= 'name=' . $name . ';';

        $found = null;
        for ($attempt = 1; $attempt <= 5; $attempt++)
        {
            $found = $ordersMSClass->findOrders($filter);
            if (is_array($found))
                break;
            out('     .. existence batch retry ' . $attempt);
            sleep(2);
        }
        if (!is_array($found))
        {
            $ok = false;
            break;
        }
        foreach ($found as $row)
            if (isset($row['name']))
                $existing[$row['name']] = $row;
        usleep(250000);
    }

    if (!$ok)
    {
        out('  [FAIL] a MoySklad existence batch never answered - refusing to risk duplicates');
        $stat['status'] = 'existence check failed';
        $summary[$key] = $stat;
        continue;
    }

    $stat['inMs'] = count($existing);
    out('  in MoySklad ' . count($existing) . ' of them');

    // ------------------------------------------------------------------ decide per order
    $toCreate = array();
    $toCancel = array();
    $ignored = array();

    foreach ($byId as $id => $order)
    {
        $status = $order['status'];
        $inMs = isset($existing[$id]);

        if ($status === 'CANCELLED')
        {
            if (!$inMs)
            {
                // the notification returns OK for a cancelled order it cannot find, and an unpaid
                // one never reached PROCESSING/STARTED at all - there is nothing to create
                $ignored[] = $id . ' cancelled (' . ($order['substatus'] ?? '-') . '), not in MoySklad';
                continue;
            }
            $update = buildCancelUpdate($existing[$id]);
            if ($update !== false)
                $toCancel[$id] = array('order' => $order, 'msOrder' => $existing[$id], 'update' => $update);
            continue;
        }

        if (!in_array($status, $CREATE_STATUS, true))
        {
            $ignored[] = $id . ' ' . $status . '/' . ($order['substatus'] ?? '-') . ' - not a status the notification creates from';
            continue;
        }

        if (!$inMs)
            $toCreate[$id] = $order;
    }

    out('  to create   ' . count($toCreate) . '   to cancel   ' . count($toCancel));
    if (count($ignored))
        out('  ignored     ' . count($ignored) . ' order(s) the notification would not have created');

    if ($max > 0 && count($toCreate) > $max)
        $toCreate = array_slice($toCreate, 0, $max, true);

    // ------------------------------------------------------------------ create
    $ordersYandexClass = new Yandex\v2\OrdersYandex($shopCfg['campaign']);
    $payloads = array();
    $skips = array();

    foreach ($toCreate as $id => $order)
    {
        // the same source the notification builds from, not the list row
        $full = $ordersYandexClass->getOrder($id);
        $yandexOrder = $full['order'] ?? null;
        if (!is_array($yandexOrder))
        {
            $skips[] = $id . ' - could not read the order from Yandex';
            continue;
        }

        $built = buildPayload($shopCfg, $yandexOrder, $productClass);
        if (is_string($built))
        {
            $skips[] = $id . ' - ' . $built;
            out(sprintf('   !! SKIP  %-12s %s', $id, $built));
            continue;
        }

        $sum = 0;
        foreach ($built['positions'] as $position)
            $sum += $position['price'] * $position['quantity'];

        out(sprintf('      %-12s %s  %-28s pos=%d sum=%.2f',
            $id, $built['moment'], $order['status'] . '/' . ($order['substatus'] ?? '-'),
            count($built['positions']), $sum / 100));

        $payloads[$id] = $built;
        usleep(200000);
    }

    $stat['toCreate'] = count($payloads);
    $stat['skipped'] = count($skips);

    foreach ($toCancel as $id => $entry)
        out(sprintf('   ~  %-12s cancelled at Yandex (%s) - %s', $id,
            $entry['order']['substatus'] ?? '-',
            isset($entry['update']['state']) ? 'flag + state отменен' : 'flag only, state kept'));

    $stat['toCancel'] = count($toCancel);

    if ($mode === 'dry')
    {
        out('  DRY RUN - nothing sent');
        if (count($payloads))
        {
            out('  sample payload:');
            out(json_encode(reset($payloads), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        }
        $summary[$key] = $stat;
        continue;
    }

    // ------------------------------------------------------------------ write
    foreach ($payloads as $id => $payload)
    {
        $result = $ordersMSClass->createCustomerorder($payload);
        logline(__LINE__ . ' create ' . $id . ' - ' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if (is_array($result) && isset($result['id']))
        {
            $stat['created']++;
            out('  created     ' . $id);
        }
        else
        {
            out('  [FAIL] ' . $id . ' not created: '
              . substr(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 250));
        }
        usleep(300000);
    }

    foreach ($toCancel as $id => $entry)
    {
        $result = $ordersMSClass->updateCustomerorder($entry['msOrder']['id'], $entry['update']);
        logline(__LINE__ . ' cancel ' . $id . ' - ' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if (is_array($result) && isset($result['id']))
        {
            $stat['cancelled']++;
            out('  cancelled   ' . $id);
        }
        else
            out('  [FAIL] ' . $id . ' cancel update failed: '
              . substr(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 250));
        usleep(300000);
    }

    // ------------------------------------------------------------------ verify
    if (count($payloads))
    {
        $names = array_keys($payloads);
        $verified = 0;
        foreach (array_chunk($names, 20) as $chunk)
        {
            $filter = '';
            foreach ($chunk as $name)
                $filter .= 'name=' . $name . ';';
            $found = $ordersMSClass->findOrders($filter);
            if (is_array($found))
                $verified += count($found);
            usleep(250000);
        }
        out('  verified    ' . $verified . ' of ' . count($names) . ' now in MoySklad');
    }

    if (count($skips))
    {
        out('  needing attention:');
        foreach ($skips as $skip)
            out('     ' . $skip);
    }

    $summary[$key] = $stat;
}

// -------------------------------------------------------------------------------- summary
out('');
out('=============================================================================');
out('  SUMMARY  (' . strtoupper($mode) . ')');
out('=============================================================================');
out(sprintf('  %-9s %8s %8s %10s %8s %10s %10s %8s', 'shop', 'yandex', 'in MS', 'to create', 'created', 'to cancel', 'cancelled', 'skipped'));

$failed = 0;
foreach ($summary as $key => $stat)
{
    out(sprintf('  %-9s %8d %8d %10d %8d %10d %10d %8d%s',
        $key, $stat['yandex'], $stat['inMs'], $stat['toCreate'], $stat['created'],
        $stat['toCancel'], $stat['cancelled'], $stat['skipped'],
        $stat['status'] === 'ok' ? '' : '   <- ' . $stat['status']));
    if ($stat['status'] !== 'ok' || $stat['skipped'])
        $failed++;
}

out('');
if ($mode === 'dry')
    out('  Dry run. Re-run with "live" to apply.');

exit($failed === 0 ? 0 : 1);
?>
