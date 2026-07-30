<?php
/**
 * Backfills marketplace orders that never made it into MoySklad.
 *
 * The daily scripts only ever see the *current* queue - WB /api/v3/orders/new empties as soon as
 * an order is handed to a supply, and the Ozon script asks for status awaiting_packaging only.
 * So whenever MoySklad is unreachable for a while (see diagnostics/apiHealth.php), the orders
 * that arrived during the outage become invisible to every later run and have to be re-read from
 * the marketplaces' historical endpoints. That is what this script does.
 *
 * The window is not guessed: for each shop it asks MoySklad for the newest order of that
 * (agent, organization) pair and starts from there, minus a safety pad. An order that already
 * exists is skipped by name, so overlapping windows are harmless - re-running is safe.
 *
 * WRITES TO MOYSKLAD ONLY. No WB supply adds, no sticker requests, no Ozon setExemplar/ship.
 * Marketplace state is deliberately left alone: the daily scripts and the sticker backfill in
 * wildberries/<shop>/getNewOrders.php pick the orders up once they exist in MoySklad.
 *
 *   php recovery/backfillOrders.php <all|wb-ullo|wb-kosmos|ozon-ullo|ozon-kaori> [dry|live] [options]
 *
 *     --max=N        at most N orders per shop (the rest are reported, not created)
 *     --since=DATE   ignore the MoySklad anchor and start from DATE ('2026-07-29 10:00:00', MSK)
 *     --pad=HOURS    how far before the anchor to start, default 24. Cheap: duplicates are
 *                    impossible (dedupe is by order name), so a wide pad only costs read calls.
 *
 * Dry run is the default and prints exactly what a live run would create.
 *
 * @author Georgy Polyan <acidlord@yandex.ru>
 */

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

require_once($_SERVER['DOCUMENT_ROOT'] . '/docker-config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Api.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ozon/ApiOzon.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/wildberries/order.php');

// -------------------------------------------------------------------------------- shops
// (agent, organization) is what identifies a shop inside MoySklad - the WB agent is shared by
// Ullo and Kosmos and the Ullo organization is shared by WB and Ozon, but the pair is unique.
$SHOPS = array(
    'wb-ullo' => array(
        'platform'  => 'wb',
        'label'     => 'WB Ullo',
        'shop'      => 'Ullo',                  // WBApiTokenUllo, MS_ULLO, MS_PROJECT_WB_ULLO
        'agent'     => MS_WB_AGENT,
        'org'       => MS_ULLO,
        'priceType' => 'Цена WB ULLO',          // see wildberries/ullo/updatePrices.php
    ),
    'wb-kosmos' => array(
        'platform'  => 'wb',
        'label'     => 'WB Kosmos',
        'shop'      => 'Kosmos',
        'agent'     => MS_WB_AGENT,
        'org'       => MS_KOSMOS,
        'priceType' => 'Цена WB',               // see wildberries/kosmos/updatePrices.php
    ),
    'ozon-ullo' => array(
        'platform'  => 'ozon',
        'label'     => 'Ozon Ullo',
        'shop'      => 'ullo',                  // ozon_client_id_ullo / ozon_api_key_ullo
        'agent'     => MS_OZON_AGENT,
        'org'       => MS_ULLO,
        'warehouse' => OZON_ULLO_WEARHOUSE_MAIN,
    ),
    'ozon-kaori' => array(
        'platform'  => 'ozon',
        'label'     => 'Ozon Kaori',
        'shop'      => 'kaori',
        'agent'     => MS_OZON_AGENT,
        'org'       => MS_KAORI,
        'warehouse' => OZON_WEARHOUSE1_ID,
    ),
);

// A cancelled posting must never become a MoySklad order - the daily script can only ever see
// awaiting_packaging, so anything that was cancelled during the gap is simply not our business.
$OZON_SKIP_STATUS = array('cancelled', 'not_accepted');

// WB orders that are no longer going to be shipped. Same reasoning as the Ozon list above.
$WB_DEAD_SUPPLIER_STATUS = array('cancel', 'cancel_by_client');
$WB_DEAD_WB_STATUS = array('canceled', 'canceled_by_client', 'declined_by_client', 'defect', 'not_in_stock');

// -------------------------------------------------------------------------------- arguments
$args = array_slice($argv, 1);
$selector = '';
$mode = 'dry';
$max = 0;
$since = '';
$padHours = 24;
$withCancelled = false;

foreach ($args as $arg)
{
    if (strpos($arg, '--max=') === 0)
        $max = (int)substr($arg, 6);
    elseif (strpos($arg, '--since=') === 0)
        $since = substr($arg, 8);
    elseif (strpos($arg, '--pad=') === 0)
        $padHours = (int)substr($arg, 6);
    elseif ($arg === '--with-cancelled')
        $withCancelled = true;
    elseif ($arg === 'dry' || $arg === 'live')
        $mode = $arg;
    elseif ($selector === '')
        $selector = strtolower($arg);
}

if ($selector === '' || ($selector !== 'all' && !isset($SHOPS[$selector])))
    die("usage: php recovery/backfillOrders.php <all|" . implode('|', array_keys($SHOPS)) . "> [dry|live]"
      . " [--max=N] [--since='Y-m-d H:i:s'] [--pad=HOURS]\n");

$selected = $selector === 'all' ? array_keys($SHOPS) : array($selector);

$sinceOverride = 0;
if ($since !== '')
{
    $sinceOverride = strtotime($since);
    if ($sinceOverride === false || $sinceOverride <= 0)
        die("--since is not a date I can read: $since\n");
}

$logger = new \Classes\Common\Log('recovery - backfillOrders.log');

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

// -------------------------------------------------------------------------------- MoySklad
$ordersMSClass  = new OrdersMS();
$productMSClass = new ProductsMS();

/**
 * The newest order MoySklad holds for this (agent, organization) - the anchor the window starts
 * from. Deliberately a single ordered read: OrdersMS::findOrders() pages through the whole
 * filtered set, and the WB agent has about a million orders behind it.
 *
 * @return array|false - array(moment, name) or false when the read failed
 */
function latestOrderMoment($shopCfg)
{
    $apiMS = new APIMS();
    $url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER
         . '?filter=agent=' . $shopCfg['agent'] . ';organization=' . $shopCfg['org']
         . '&order=moment,desc&limit=1';
    logline(__LINE__ . ' latestOrderMoment.url - ' . $url);

    for ($attempt = 1; $attempt <= 4; $attempt++)
    {
        $resp = $apiMS->getData($url);
        // rows may legitimately be an empty array; only a missing key means the read failed
        if (is_array($resp) && isset($resp['rows']))
        {
            if (!count($resp['rows']))
                return array('moment' => '', 'name' => '');
            return array(
                'moment' => $resp['rows'][0]['moment'] ?? '',
                'name'   => $resp['rows'][0]['name'] ?? ''
            );
        }
        logline(__LINE__ . ' latestOrderMoment.retry ' . $attempt . ' - ' . json_encode($resp, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        sleep(2);
    }
    return false;
}

/**
 * Which of these order names already exist in MoySklad.
 *
 * A read that quietly fails would look like "order missing" and produce a duplicate, so a batch
 * that never answers aborts the shop instead of being treated as empty.
 *
 * @return array|false - map name => true
 */
function existingNames($names, $ordersMSClass)
{
    $existing = array();
    foreach (array_chunk($names, 20) as $chunk)
    {
        $filter = '';
        foreach ($chunk as $name)
            $filter .= 'name=' . $name . ';';

        $ok = false;
        for ($attempt = 1; $attempt <= 5; $attempt++)
        {
            $found = $ordersMSClass->findOrders($filter);
            if (is_array($found))
            {
                foreach ($found as $row)
                    if (isset($row['name']))
                        $existing[$row['name']] = true;
                $ok = true;
                break;
            }
            out("     .. existence batch retry $attempt");
            sleep(2);
        }
        if (!$ok)
            return false;
        usleep(300000);
    }
    return $existing;
}

/**
 * Resolves product codes to MoySklad products in batches, with retries.
 *
 * One lookup per order hits the MoySklad rate limit, and a single failed read would silently
 * degrade an order to the 000-0000 placeholder - which for WB carries no price at all.
 *
 * @return array - map code => product
 */
function resolveProducts($codes, $productMSClass)
{
    $byCode = array();
    $codes = array_values(array_unique($codes));
    foreach (array_chunk($codes, 40) as $chunk)
    {
        for ($attempt = 1; $attempt <= 4; $attempt++)
        {
            $rows = $productMSClass->findProductsByCode($chunk);
            if (is_array($rows) && count($rows))
            {
                foreach ($rows as $row)
                    if (isset($row['code']))
                        $byCode[(string)$row['code']] = $row;
                break;
            }
            out("     .. product batch retry $attempt");
            sleep(2);
        }
        usleep(300000);
    }
    return $byCode;
}

// -------------------------------------------------------------------------------- marketplace reads
/**
 * WB orders created since $sinceTs, from /api/v3/orders.
 *
 * Not /api/v3/orders/new: an order that was handed to a supply has left that queue for good,
 * and those are exactly the ones a backfill is looking for.
 *
 * @return array|false
 */
function fetchWbOrders($shopCfg, $sinceTs)
{
    $api = new \Classes\Wildberries\v1\Api($shopCfg['shop']);
    $orders = array();
    $next = 0;

    while (true)
    {
        $url = WB_API_MARKETPLACE_API . WB_API_ORDERS . '?limit=1000&next=' . $next . '&dateFrom=' . $sinceTs;
        logline(__LINE__ . ' fetchWbOrders.url - ' . $url);
        $resp = $api->getData($url);
        $httpCode = (int)$api->getLastHttpCode();

        // an expired token answers 401, which otherwise reads exactly like "no orders"
        if ($httpCode !== 200)
        {
            out("     WB http $httpCode - " . json_encode($resp, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return false;
        }
        if (!isset($resp['orders']) || !count($resp['orders']))
            break;

        $orders = array_merge($orders, $resp['orders']);
        if (empty($resp['next']))
            break;
        $next = $resp['next'];
    }

    usort($orders, function ($a, $b) { return strcmp($a['createdAt'], $b['createdAt']); });
    return $orders;
}

/**
 * supplierStatus/wbStatus for WB orders, from /api/v3/orders/status.
 *
 * The daily script never needs this - everything in /orders/new is new by definition. A backfill
 * runs hours or days later though, and nothing in this codebase ever syncs a WB cancellation back
 * to MoySklad, so an order cancelled during the gap would become a MoySklad order that no script
 * will ever clean up - and its position keeps stock reserved.
 *
 * @return array|false - map id => array(supplierStatus, wbStatus)
 */
function fetchWbStatuses($shopCfg, $ids)
{
    $api = new \Classes\Wildberries\v1\Api($shopCfg['shop']);
    $statuses = array();

    foreach (array_chunk(array_values($ids), 1000) as $chunk)
    {
        $url = WB_API_MARKETPLACE_API . WB_API_ORDERS . '/status';
        $resp = $api->postData($url, array('orders' => array_map('intval', $chunk)));
        $httpCode = (int)$api->getLastHttpCode();

        if ($httpCode !== 200 || !isset($resp['orders']))
        {
            out("     WB status read http $httpCode - " . substr(json_encode($resp, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 0, 200));
            return false;
        }

        foreach ($resp['orders'] as $row)
            if (isset($row['id']))
                $statuses[(int)$row['id']] = array(
                    'supplierStatus' => $row['supplierStatus'] ?? '?',
                    'wbStatus'       => $row['wbStatus'] ?? '?'
                );

        usleep(300000);
    }

    return $statuses;
}

/**
 * Ozon postings in the window, every status.
 *
 * The daily script filters on awaiting_packaging, which is why a missed posting becomes
 * unreachable as soon as it moves on - so no status filter is sent here at all.
 *
 * @return array|false
 */
function fetchOzonPostings($shopCfg, $sinceTs, $toTs)
{
    $api = new ApiOzon($shopCfg['shop']);
    $postings = array();
    $offset = 0;

    while (true)
    {
        $postData = array(
            'dir'    => 'ASC',
            'filter' => array(
                'since'        => gmdate('Y-m-d\TH:i:s\Z', $sinceTs),
                'to'           => gmdate('Y-m-d\TH:i:s\Z', $toTs),
                'warehouse_id' => array($shopCfg['warehouse'])
            ),
            'limit'  => 50,
            'offset' => $offset,
            'with'   => array('barcodes' => true)
        );

        $url = OZON_MAINURL . OZON_API_V3 . OZON_API_ORDERS_LIST;
        logline(__LINE__ . ' fetchOzonPostings.postData - ' . json_encode($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $resp = $api->postData($url, $postData);

        if (!is_array($resp) || !isset($resp['result']['postings']))
        {
            out("     Ozon read failed - " . json_encode($resp, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return false;
        }
        if (!count($resp['result']['postings']))
            break;

        $postings = array_merge($postings, $resp['result']['postings']);
        $offset += 50;
    }

    return $postings;
}

// -------------------------------------------------------------------------------- payloads
/**
 * Builds the MoySklad order for a WB order, through the same transformer the daily script uses.
 *
 * The price is the one MoySklad itself holds for the shop's WB price type, not the order's
 * convertedPrice: /api/v3/orders does not return salePrice (only /orders/new does), and
 * convertedPrice is the amount in the currency of the country of sale. Prices are pushed
 * MoySklad -> WB, so the price type is what WB was selling at.
 *
 * @return array|string - the payload, or a string explaining why it was skipped
 */
function buildWbPayload($shopCfg, $order, $byCode)
{
    $code = (string)$order['article'];
    if (!isset($byCode[$code]))
        return "article $code not in MoySklad";

    $product = $byCode[$code];

    $price = 0;
    foreach (($product['salePrices'] ?? array()) as $salePrice)
        if (isset($salePrice['priceType']['name']) && $salePrice['priceType']['name'] === $shopCfg['priceType'])
        {
            $price = (int)$salePrice['value'];
            break;
        }

    if ($price <= 0)
        return "no '" . $shopCfg['priceType'] . "' price for article $code";

    // the transformer reads salePrice first and expects kopecks, which is what MoySklad stores
    $order['salePrice'] = $price;
    $order['productMS'] = array($product);

    $transformer = new \Wildberries\Order\OrderTransformation($shopCfg['shop'], $order);
    return $transformer->transformWildberriesToMS();
}

/**
 * Builds the MoySklad order for an Ozon posting, mirroring ozonUllo/ozonKaori getNewOrders.php.
 *
 * Unlike WB the posting carries its own prices, so an unknown offer_id falls back to the
 * 000-0000 placeholder exactly as the daily script does instead of being skipped.
 *
 * @return array
 */
function buildOzonPayload($shopCfg, $posting, $byCode, $placeholder)
{
    $data = array();
    $data['name'] = (string)$posting['posting_number'];
    $data['organization'] = array('meta' => array(
        'href' => $shopCfg['org'], 'type' => 'organization', 'mediaType' => 'application/json'));
    $data['externalCode'] = (string)$posting['order_id'];

    $date = (new DateTime($posting['in_process_at']))->setTimezone(new DateTimeZone('Europe/Moscow'));
    $data['moment'] = $date->format('Y-m-d H:i:s');

    $date = (new DateTime($posting['shipment_date']))->setTimezone(new DateTimeZone('Europe/Moscow'));
    $data['deliveryPlannedMoment'] = $date->format('Y-m-d H:i:s');

    $data['agent'] = array('meta' => array(
        'href' => MS_OZON_AGENT, 'type' => 'counterparty', 'mediaType' => 'application/json'));
    $data['state'] = array('meta' => array(
        'href' => MS_CONFIRMBERU_STATE, 'type' => 'state', 'mediaType' => 'application/json'));
    $data['applicable'] = true;
    $data['vatEnabled'] = false;
    $data['store'] = array('meta' => array(
        'href' => MS_STORE, 'type' => 'store', 'mediaType' => 'application/json'));
    $data['project'] = array('meta' => array(
        'href' => MS_PROJECT_OZON, 'type' => 'project', 'mediaType' => 'application/json'));
    $data['group'] = array('meta' => array(
        'href' => MS_GROUP, 'type' => 'group', 'mediaType' => 'application/json'));

    $data['attributes'] = array(
        // способ доставки
        array(
            'meta'  => array('href' => MS_ATTR . MS_DELIVERY_ATTR, 'type' => 'attributemetadata', 'mediaType' => 'application/json'),
            'value' => array('meta' => array('href' => MS_SHIPTYPE_OZON, 'type' => 'customentity', 'mediaType' => 'application/json'))
        ),
        // время доставки
        array(
            'meta'  => array('href' => MS_ATTR . MS_DELIVERYTIME_ATTR, 'type' => 'attributemetadata', 'mediaType' => 'application/json'),
            'value' => array('meta' => array('href' => MS_DELIVERYTIME_VALUE1, 'type' => 'customentity', 'mediaType' => 'application/json'))
        ),
        // тип оплаты
        array(
            'meta'  => array('href' => MS_ATTR . MS_PAYMENTTYPE_ATTR, 'type' => 'attributemetadata', 'mediaType' => 'application/json'),
            'value' => array('meta' => array('href' => MS_PAYMENTTYPE_SBERBANK, 'type' => 'customentity', 'mediaType' => 'application/json'))
        ),
        // штрихкод
        array(
            'meta'  => array('href' => MS_ATTR . MS_BARCODE2_ATTR, 'type' => 'attributemetadata', 'mediaType' => 'application/json'),
            'value' => (string)($posting['barcodes']['lower_barcode'] ?? '')
        )
    );

    $data['positions'] = array();
    foreach ($posting['products'] as $product)
    {
        $code = (string)$product['offer_id'];
        $productMS = isset($byCode[$code]) ? $byCode[$code] : $placeholder;
        if (!isset($productMS['meta']))
            continue;

        $data['positions'][] = array(
            'quantity'   => (int)$product['quantity'],
            'price'      => (int)($product['price'] * 100),
            'discount'   => (int)0,
            'vat'        => (int)0,
            'assortment' => array('meta' => array(
                'href'      => $productMS['meta']['href'],
                'type'      => $productMS['meta']['type'],
                'mediaType' => 'application/json'
            )),
            'reserve'    => (int)$product['quantity']
        );
    }

    return $data;
}

// -------------------------------------------------------------------------------- run
out('order backfill - ' . date('Y-m-d H:i:s T') . '  mode=' . strtoupper($mode));
out('shops: ' . implode(', ', $selected) . '   pad=' . $padHours . 'h'
  . ($max > 0 ? '   max=' . $max . '/shop' : '')
  . ($sinceOverride ? '   since=' . date('Y-m-d H:i:s', $sinceOverride) . ' (override)' : ''));
out('writes: MoySklad customerorders only - no WB supply/sticker calls, no Ozon exemplar/ship calls');

$summary = array();

foreach ($selected as $key)
{
    $shopCfg = $SHOPS[$key];
    out('');
    out('=============================================================================');
    out('  ' . $shopCfg['label'] . '  (' . $key . ')');
    out('=============================================================================');

    $stat = array('found' => 0, 'existing' => 0, 'todo' => 0, 'cancelled' => 0, 'skipped' => 0,
                  'created' => 0, 'verified' => 0, 'status' => 'ok');

    // ------------------------------------------------------------------ 1. window
    if ($sinceOverride)
    {
        $sinceTs = $sinceOverride;
        out('  anchor      --since override');
    }
    else
    {
        $anchor = latestOrderMoment($shopCfg);
        if ($anchor === false)
        {
            out('  [FAIL] MoySklad did not answer the anchor query - skipping this shop');
            $stat['status'] = 'anchor read failed';
            $summary[$key] = $stat;
            continue;
        }
        if ($anchor['moment'] === '')
        {
            out('  [FAIL] MoySklad holds no order at all for this agent+organization.');
            out('         Nothing to anchor to - re-run this shop with an explicit --since.');
            $stat['status'] = 'no anchor order';
            $summary[$key] = $stat;
            continue;
        }
        out('  anchor      ' . $anchor['name'] . '  moment ' . $anchor['moment'] . ' (newest in MoySklad)');
        $sinceTs = strtotime($anchor['moment']) - $padHours * 3600;
    }

    $toTs = time() + 86400;
    out('  window      ' . date('Y-m-d H:i:s', $sinceTs) . '  ->  now');

    // ------------------------------------------------------------------ 2. marketplace read
    if ($shopCfg['platform'] === 'wb')
    {
        $orders = fetchWbOrders($shopCfg, $sinceTs);
        if ($orders === false)
        {
            out('  [FAIL] WB order read failed - skipping this shop');
            $stat['status'] = 'WB read failed';
            $summary[$key] = $stat;
            continue;
        }
        $names = array();
        foreach ($orders as $order)
            $names[] = 'WB' . $order['id'];
    }
    else
    {
        $postings = fetchOzonPostings($shopCfg, $sinceTs, $toTs);
        if ($postings === false)
        {
            out('  [FAIL] Ozon posting read failed - skipping this shop');
            $stat['status'] = 'Ozon read failed';
            $summary[$key] = $stat;
            continue;
        }

        $dropped = array();
        $orders = array();
        foreach ($postings as $posting)
        {
            if (in_array($posting['status'], $OZON_SKIP_STATUS, true))
            {
                $dropped[] = $posting['posting_number'] . ' (' . $posting['status'] . ')';
                continue;
            }
            $orders[] = $posting;
        }
        if (count($dropped))
            out('  ignored     ' . count($dropped) . ' posting(s) in ' . implode('/', $OZON_SKIP_STATUS) . ': ' . implode(', ', array_slice($dropped, 0, 8))
              . (count($dropped) > 8 ? ' ...' : ''));

        $names = array();
        foreach ($orders as $posting)
            $names[] = (string)$posting['posting_number'];
    }

    $stat['found'] = count($orders);
    out('  marketplace ' . count($orders) . ' order(s) in the window');

    if (!count($orders))
    {
        out('  nothing to do');
        $summary[$key] = $stat;
        continue;
    }

    // ------------------------------------------------------------------ 3. what is missing
    $existing = existingNames($names, $ordersMSClass);
    if ($existing === false)
    {
        out('  [FAIL] a MoySklad existence batch never answered - refusing to risk duplicates');
        $stat['status'] = 'existence check failed';
        $summary[$key] = $stat;
        continue;
    }
    $stat['existing'] = count($existing);
    out('  in MoySklad ' . count($existing) . ' already there');

    $todo = array();
    foreach ($orders as $index => $order)
        if (!isset($existing[$names[$index]]))
            $todo[] = $order;

    $trimmed = 0;
    if ($max > 0 && count($todo) > $max)
    {
        $trimmed = count($todo) - $max;
        $todo = array_slice($todo, 0, $max);
    }

    $stat['todo'] = count($todo);
    out('  missing     ' . count($todo) . ($trimmed ? " (capped by --max, $trimmed left for the next run)" : ''));

    if (!count($todo))
    {
        out('  nothing missing');
        $summary[$key] = $stat;
        continue;
    }

    // ------------------------------------------------------------------ 3b. WB: still wanted?
    if ($shopCfg['platform'] === 'wb')
    {
        $statuses = fetchWbStatuses($shopCfg, array_column($todo, 'id'));
        if ($statuses === false)
            out('  [WARN] WB status read failed - creating without the cancellation check');
        else
        {
            $spread = array();
            foreach ($todo as $order)
            {
                $status = $statuses[(int)$order['id']] ?? array('supplierStatus' => 'unknown', 'wbStatus' => 'unknown');
                $label = $status['supplierStatus'] . '/' . $status['wbStatus'];
                $spread[$label] = ($spread[$label] ?? 0) + 1;
            }
            $spreadOut = array();
            foreach ($spread as $label => $count)
                $spreadOut[] = $label . ' x' . $count;
            out('  wb status   ' . implode(', ', $spreadOut) . '  (supplierStatus/wbStatus)');

            if (!$withCancelled)
            {
                $kept = array();
                $dropped = array();
                foreach ($todo as $order)
                {
                    $status = $statuses[(int)$order['id']] ?? array('supplierStatus' => 'unknown', 'wbStatus' => 'unknown');
                    if (in_array($status['supplierStatus'], $WB_DEAD_SUPPLIER_STATUS, true)
                     || in_array($status['wbStatus'], $WB_DEAD_WB_STATUS, true))
                    {
                        $dropped[] = 'WB' . $order['id'] . ' (' . $status['supplierStatus'] . '/' . $status['wbStatus'] . ')';
                        continue;
                    }
                    $kept[] = $order;
                }

                if (count($dropped))
                {
                    out('  cancelled   ' . count($dropped) . ' order(s) WB no longer wants - not created'
                      . ' (--with-cancelled overrides):');
                    foreach (array_slice($dropped, 0, 15) as $line)
                        out('     ' . $line);
                    if (count($dropped) > 15)
                        out('     ... and ' . (count($dropped) - 15) . ' more');
                    logline(__LINE__ . ' ' . $key . '.cancelledSkipped - ' . json_encode($dropped, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                }

                $stat['cancelled'] = count($dropped);
                $todo = $kept;
                $stat['todo'] = count($todo);
            }
        }

        if (!count($todo))
        {
            out('  nothing left to create');
            $summary[$key] = $stat;
            continue;
        }
    }

    // ------------------------------------------------------------------ 4. payloads
    $codes = array();
    foreach ($todo as $order)
        if ($shopCfg['platform'] === 'wb')
            $codes[] = (string)$order['article'];
        else
            foreach ($order['products'] as $product)
                $codes[] = (string)$product['offer_id'];

    $byCode = resolveProducts($codes, $productMSClass);
    $unresolved = array_values(array_diff(array_values(array_unique($codes)), array_keys($byCode)));
    out('  articles    ' . count($byCode) . ' resolved, ' . count($unresolved) . ' unresolved'
      . (count($unresolved) ? ' (' . implode(', ', array_slice($unresolved, 0, 10)) . (count($unresolved) > 10 ? ' ...' : '') . ')' : ''));

    $placeholder = array();
    if ($shopCfg['platform'] === 'ozon')
    {
        $rows = $productMSClass->findProductsByCode('000-0000');
        $placeholder = isset($rows[0]) ? $rows[0] : array();
        if (!count($placeholder))
            out('  [WARN] placeholder product 000-0000 not found - postings with unknown articles will lose those positions');
    }

    $payloads = array();
    $payloadNames = array();
    $skips = array();
    out('');
    foreach ($todo as $order)
    {
        if ($shopCfg['platform'] === 'wb')
        {
            $built = buildWbPayload($shopCfg, $order, $byCode);
            if (is_string($built))
            {
                $skips[] = 'WB' . $order['id'] . ' - ' . $built;
                out(sprintf('   !! SKIP  %-14s %s', 'WB' . $order['id'], $built));
                continue;
            }
            out(sprintf('      %-14s %s  art=%-14s price=%d',
                $built['name'], $order['createdAt'], $order['article'], $built['positions'][0]['price']));
        }
        else
        {
            $built = buildOzonPayload($shopCfg, $order, $byCode, $placeholder);
            if (!count($built['positions']))
            {
                $skips[] = $order['posting_number'] . ' - no resolvable positions';
                out(sprintf('   !! SKIP  %-22s no resolvable positions', $order['posting_number']));
                continue;
            }
            $sum = 0;
            foreach ($built['positions'] as $position)
                $sum += $position['price'] * $position['quantity'];
            out(sprintf('      %-22s %s  status=%-20s pos=%d sum=%d',
                $built['name'], $order['in_process_at'], $order['status'], count($built['positions']), $sum));
        }

        $payloads[] = $built;
        $payloadNames[] = $built['name'];
    }

    $stat['skipped'] = count($skips);
    out('');
    out('  payloads    ' . count($payloads) . ' ready' . (count($skips) ? ', ' . count($skips) . ' skipped' : ''));

    if (!count($payloads))
    {
        $stat['status'] = 'nothing buildable';
        $summary[$key] = $stat;
        continue;
    }

    if ($mode === 'dry')
    {
        out('  DRY RUN - nothing sent. Sample payload:');
        out(json_encode($payloads[0], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $summary[$key] = $stat;
        continue;
    }

    // ------------------------------------------------------------------ 5. create in MoySklad
    // in chunks: a bulk request answers all-or-nothing, so a single bad row must not cost the
    // whole shop, and whatever was written stays written if the run is interrupted
    foreach (array_chunk($payloads, 20) as $chunkNumber => $chunk)
    {
        $result = $ordersMSClass->createCustomerorder($chunk);

        if (!is_array($result) || !count($result) || isset($result['errors']) || isset($result[0]['errors']))
        {
            out('  [FAIL] chunk ' . ($chunkNumber + 1) . ' of ' . count($chunk) . ' rejected: '
              . substr(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 0, 400));
            logline(__LINE__ . ' createCustomerorder.rejected - ' . json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            continue;
        }

        $stat['created'] += count($result);
        out('  created     chunk ' . ($chunkNumber + 1) . ': ' . count($result) . ' of ' . count($chunk));
        usleep(300000);
    }

    // ------------------------------------------------------------------ 6. verify
    // the create response is not proof on its own - ask MoySklad what it actually holds now
    $verify = existingNames($payloadNames, $ordersMSClass);
    if ($verify === false)
        out('  [WARN] could not verify - re-run in dry mode to see what is still missing');
    else
    {
        $stat['verified'] = count($verify);
        $stillMissing = array_values(array_diff($payloadNames, array_keys($verify)));
        out('  verified    ' . count($verify) . ' of ' . count($payloadNames) . ' now in MoySklad');
        if (count($stillMissing))
            out('  [WARN] still missing: ' . implode(', ', array_slice($stillMissing, 0, 15))
              . (count($stillMissing) > 15 ? ' ...' : ''));
    }

    if (count($skips))
    {
        out('  skipped orders needing attention:');
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
out(sprintf('  %-12s %8s %8s %8s %10s %8s %8s %8s', 'shop', 'found', 'in MS', 'to create', 'cancelled', 'skipped', 'created', 'verified'));

$failed = 0;
foreach ($summary as $key => $stat)
{
    out(sprintf('  %-12s %8d %8d %8d %10d %8d %8d %8d%s',
        $key, $stat['found'], $stat['existing'], $stat['todo'], $stat['cancelled'], $stat['skipped'],
        $stat['created'], $stat['verified'],
        $stat['status'] === 'ok' ? '' : '   <- ' . $stat['status']));
    if ($stat['status'] !== 'ok')
        $failed++;
}

out('');
if ($mode === 'dry')
    out('  Dry run. Re-run with "live" to create the missing orders.');
else
{
    out('  Stickers and marketplace state were not touched: WB stickers are filled in by the');
    out('  backfill pass in wildberries/<shop>/getNewOrders.php on its next run.');
}

exit($failed === 0 ? 0 : 1);
?>
