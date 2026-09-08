<?php
/**
 * Read-only JSON feed of ccd77 (WooCommerce) orders, for the AWS reconcile job.
 *
 * Why this exists rather than the WooCommerce REST API: **the shop database only listens on
 * localhost**, and this app happens to be on the same hosting account (`u1003281_*`), which is why
 * `ccd77/importStock.php` and `ccdDb.php` can read it directly at all. Nothing in AWS can open that
 * connection. The alternative was a WooCommerce REST consumer key/secret, which nobody has
 * generated - and which would be a second credential to store, rotate and lose.
 *
 * So this page is the bridge: it runs where the database is reachable, and hands out the orders as
 * JSON over HTTPS to a caller that knows the shared key.
 *
 *   /ccd77/ordersApi.php?key=<ORDERS_API_KEY>&since=2026-08-25
 *
 *     key         required. Compared with hash_equals, so a wrong one costs nothing to guess from.
 *     since       ISO date or datetime, site time. Defaults to 14 days back.
 *     sinceId     only orders with an id above this. Cheaper than a date for a catch-up loop.
 *     statuses    comma separated, default wc-processing,wc-completed - the ones that should exist
 *                 in MoySklad. 'all' for every status.
 *     limit       stop after N orders, default 500. A hard ceiling of 2000 applies regardless.
 *
 * What it deliberately does **not** return:
 *
 *   - the site's `wp_gms_settings` row, which holds a live MoySklad token. `CcdDb::export()`
 *     includes it (masked); this endpoint drops it entirely. An integration that does not need a
 *     credential should never be handed one.
 *   - per-item WooCommerce meta. It is bulky, it changes between plugin versions, and the reconcile
 *     job needs a sku, a quantity and a price.
 *
 * Read-only: no UPDATE, no INSERT, and it holds its own connection through `CcdDb`, so a mistake
 * here cannot touch the marketplace scripts.
 *
 * @author Georgy Polyan <acidlord@yandex.ru>
 */

require_once(__DIR__ . '/ccdDb.php');

date_default_timezone_set('Europe/Moscow');

/**
 * Shared key. The AWS side reads the same value from SSM at /integration-sw/bridge/ccd77/key;
 * change both together, and an empty value here refuses every request rather than opening a door.
 *
 * `CCD77_ORDERS_API_KEY` in the environment wins, so the key can be rotated without editing a file
 * that lives in git. It is deliberately *not* read through \Classes\Common\Settings: that class
 * logs the whole row it reads, and /logs/ is served over plain HTTP - which is how live tokens came
 * to be publicly readable for months.
 */
$envKey = getenv('CCD77_ORDERS_API_KEY');
define('ORDERS_API_KEY', $envKey !== false && $envKey !== ''
    ? $envKey
    : 'q7Zt4mB9xR2vLp6WsN1kEyU8');

/** Never return more than this, whatever the caller asks for. */
define('MAX_LIMIT', 2000);

/** Statuses an order should exist in MoySklad for. */
define('DEFAULT_STATUSES', 'wc-processing,wc-completed');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

/**
 * Fails the request with a JSON body. No detail about *why* the key was refused - a probing
 * client should learn nothing from the difference between "missing" and "wrong".
 *
 * @param int $status
 * @param string $message
 * @return void
 */
function fail($status, $message)
{
    http_response_code($status);
    echo json_encode(array('error' => $message), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$given = isset($_GET['key']) ? (string)$_GET['key'] : '';
if ($given === '' && isset($_SERVER['HTTP_X_API_KEY']))
    $given = (string)$_SERVER['HTTP_X_API_KEY'];

if (ORDERS_API_KEY === '' || !hash_equals(ORDERS_API_KEY, $given))
    fail(401, 'unauthorised');

$since = isset($_GET['since']) && $_GET['since'] !== ''
    ? (string)$_GET['since']
    : date('Y-m-d 00:00:00', strtotime('-14 days'));

// A date that cannot be parsed must not silently become "all orders ever".
if (strtotime($since) === false)
    fail(400, 'since is not a date');
$since = date('Y-m-d H:i:s', strtotime($since));

$sinceId = isset($_GET['sinceId']) ? (int)$_GET['sinceId'] : 0;

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 500;
if ($limit <= 0)
    $limit = 500;
$limit = min($limit, MAX_LIMIT);

$statusesParam = isset($_GET['statuses']) ? (string)$_GET['statuses'] : DEFAULT_STATUSES;
$statuses = array();
if (strtolower(trim($statusesParam)) !== 'all')
{
    foreach (explode(',', $statusesParam) as $status)
    {
        $status = trim($status);
        // WooCommerce statuses are wc-prefixed slugs; anything else is a caller mistake, and
        // passing it through would quietly match nothing.
        if ($status !== '' && preg_match('/^[a-z0-9\-]+$/', $status))
            $statuses[] = $status;
    }
    if (!count($statuses))
        fail(400, 'no valid statuses given');
}

try
{
    $db = new \Ccd77\CcdDb();
    $orders = $db->findOrders(count($statuses) ? $statuses : array(), $sinceId, $since, $limit);
}
catch (\Exception $e)
{
    // The message names the stage rather than "something went wrong": the caller has to be able to
    // tell "the database is unreachable" from "there are no orders".
    fail(502, 'ccd77 database: ' . $e->getMessage());
}

$out = array();
foreach ($orders as $order)
{
    $items = array();
    foreach ($order['items'] as $item)
    {
        $items[] = array(
            'sku'   => (string)$item['sku'],
            'name'  => (string)$item['name'],
            'qty'   => (int)$item['qty'],
            'total' => (float)$item['total'],
            // per-unit, which is what a MoySklad position wants. Guard the division: a refunded
            // line can legitimately carry qty 0.
            'price' => (int)$item['qty'] > 0
                ? round(((float)$item['total']) / (int)$item['qty'], 2)
                : 0.0
        );
    }

    $billing = isset($order['billing']) ? $order['billing'] : array();
    $out[] = array(
        'id'          => (int)$order['id'],
        // the MoySklad order is named ccd-<number>; on this shop the number is the id
        'number'      => (string)$order['id'],
        'status'      => (string)$order['status'],
        'dateCreated' => (string)$order['dateCreated'],
        'total'       => (float)$order['total'],
        'discount'    => (float)$order['discountTotal'],
        'payment'     => (string)$order['paymentMethod'],
        'customer'    => array(
            'name'    => trim((isset($billing['firstName']) ? $billing['firstName'] : '') . ' ' .
                              (isset($billing['lastName']) ? $billing['lastName'] : '')),
            'phone'   => isset($billing['phone']) ? (string)$billing['phone'] : '',
            'email'   => isset($billing['email']) ? (string)$billing['email'] : '',
            'address' => isset($billing['address1']) ? (string)$billing['address1'] : ''
        ),
        'items'       => $items
    );
}

echo json_encode(array(
    'generated' => date('c'),
    'source'    => array(
        'schema'   => $db->schema(),          // legacy or hpos - the site can switch at any time
        'timezone' => $db->timezone()->getName(),
        'host'     => gethostname()
    ),
    'filters'   => array(
        'since'    => $since,
        'sinceId'  => $sinceId,
        'statuses' => count($statuses) ? $statuses : 'all',
        'limit'    => $limit
    ),
    // The caller has to be able to tell "there are none" from "we stopped early", or a reconcile
    // reports nothing missing when it simply did not look far enough.
    'truncated' => count($out) >= $limit,
    'count'     => count($out),
    'orders'    => $out
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
