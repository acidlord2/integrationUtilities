<?php
/**
 * Backfills ccd77 (WooCommerce) orders into MoySklad.
 *
 * The site exports an order from a WordPress hook - woocommerce_order_status_processing - so an
 * order only ever reaches MoySklad at the moment it becomes "processing". If the hook did not run,
 * or MoySklad was unreachable, or the plugin was not installed yet, that order is never retried by
 * anything. This script reads the orders straight out of the shop database and creates whatever is
 * missing, building exactly the same payload the plugin builds.
 *
 * Self-contained by design: its own ccd77 database access (ccdDb.php) and its own MoySklad client,
 * using the entity ids from the plugin's own wp_gms_settings table. It requires nothing from the
 * marketplace integration and changes nothing outside MoySklad.
 *
 * Three ways to feed it, because the shop database only listens on localhost of the hosting server:
 *
 *   1. over http from the exporter on the site - no file to move around
 *      php ccd77/backfillOrders.php --from-url --since=2026-07-28 dry
 *
 *   2. from a json file that exporter produced
 *      php ccd77/backfillOrders.php --from-json=ccd77-orders-2026-07-30_0915.json dry
 *
 *   3. straight from the database, when running on the server itself
 *      php ccd77/backfillOrders.php dry --since=2026-07-28
 *
 *     --from-url[=URL]  fetch the export over http. Without a URL it uses EXPORT_URL below and
 *                       forwards --since/--status/--since-id/--limit to the exporter, so only the
 *                       orders actually wanted come over the wire.
 *     --export-key=KEY  key the exporter demands; also CCD77_EXPORT_KEY in the environment
 *     --save=FILE       keep a copy of the fetched export (it holds customer data - off by default)
 *     --from-json=FILE  read orders and settings from an export instead of the database
 *     --ms-token=TOKEN  MoySklad token; also MS_TOKEN in the environment. Needed with an export
 *                       unless it was made with --with-token.
 *     --status=LIST     comma separated WooCommerce statuses, default wc-processing,wc-completed
 *                       ('all' for every status present)
 *     --since-id=N      only orders with an id above N
 *     --since=DATE      only orders created at or after DATE ('2026-07-28', site time)
 *     --max=N           create at most N orders this run
 *     --no-counterparty do not create missing counterparties; such orders are reported and skipped
 *
 * Dry run is the default: it prints every order it would create, and nothing is sent anywhere.
 *
 * @author Georgy Polyan <acidlord@yandex.ru>
 */

require_once(__DIR__ . '/ccdDb.php');

date_default_timezone_set('Europe/Moscow');

/** where --from-url goes when it is given no url of its own, and the key that endpoint wants */
define('EXPORT_URL', 'https://kids-universe.ru/ccd77/exportOrders.php');
define('EXPORT_KEY', 'k7f2a9c4e1b83d');

// -------------------------------------------------------------------------------- arguments
// $argv is missing under the web SAPI and whenever register_argc_argv is off - reading it
// unguarded would silently drop every option, including the one that keeps this a dry run
if (!isset($argv) || !is_array($argv))
{
    echo "this script is command line only, and php cli has no \$argv here.\n";
    echo "enable register_argc_argv in php.ini, or run it from a machine that can reach MoySklad.\n";
    exit(1);
}

$args = array_slice($argv, 1);
$mode = 'dry';
$statusArg = '';
$sinceId = 0;
$since = null;
$max = 0;
$createCounterparties = true;
$fromJson = '';
$fromUrl = '';
$exportKey = getenv('CCD77_EXPORT_KEY') ?: EXPORT_KEY;
$saveTo = '';
$msToken = getenv('MS_TOKEN') ?: '';

foreach ($args as $arg)
{
    if ($arg === '--from-url')
        $fromUrl = EXPORT_URL;
    elseif (strpos($arg, '--from-url=') === 0)
        $fromUrl = substr($arg, 11);
    elseif (strpos($arg, '--export-key=') === 0)
        $exportKey = substr($arg, 13);
    elseif (strpos($arg, '--save=') === 0)
        $saveTo = substr($arg, 7);
    elseif (strpos($arg, '--status=') === 0)
        $statusArg = substr($arg, 9);
    elseif (strpos($arg, '--since-id=') === 0)
        $sinceId = (int)substr($arg, 11);
    elseif (strpos($arg, '--since=') === 0)
        $since = substr($arg, 8);
    elseif (strpos($arg, '--max=') === 0)
        $max = (int)substr($arg, 6);
    elseif (strpos($arg, '--from-json=') === 0)
        $fromJson = substr($arg, 12);
    elseif (strpos($arg, '--ms-token=') === 0)
        $msToken = substr($arg, 11);
    elseif ($arg === '--no-counterparty')
        $createCounterparties = false;
    elseif ($arg === 'dry' || $arg === 'live')
        $mode = $arg;
    else
        die("unknown argument: $arg\n");
}

// '2026-07-28' means the whole day
if ($since !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $since))
    $since .= ' 00:00:00';

function out($s = '')
{
    echo $s . "\n";
    flush();
}

// -------------------------------------------------------------------------------- MoySklad
/**
 * Minimal MoySklad client, deliberately not the integration's APIMS: this script authenticates
 * with the token the plugin itself uses, so a backfilled order lands in the same account and the
 * same entities as a webhook one even if the two configurations ever diverge.
 */
class MsClient
{
    const BASE = 'https://api.moysklad.ru/api/remap/1.2/entity/';

    private $token;
    private $lastCode = 0;
    private $lastError = '';

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function lastCode()
    {
        return $this->lastCode;
    }

    public function lastError()
    {
        return $this->lastError;
    }

    public function get($url)
    {
        return $this->request('GET', $url, null);
    }

    public function post($url, $body)
    {
        return $this->request('POST', $url, $body);
    }

    /**
     * Retries 429 and 5xx with a growing delay - a backfill is the one thing guaranteed to hit
     * the account's rate limit, and a throttled read must never look like "not found".
     */
    private function request($method, $url, $body, $attempts = 4)
    {
        $delay = 1;
        for ($attempt = 1; $attempt <= $attempts; $attempt++)
        {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($curl, CURLOPT_TIMEOUT, 60);
            // gzip explicitly, never '': an encoding this runtime cannot decode turns a good 200
            // into CURLE_WRITE_ERROR(23) - see diagnostics/apiHealth.php
            curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept-Encoding: gzip',
                'Authorization: Bearer ' . $this->token
            ));
            if ($method === 'POST')
            {
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            $raw = curl_exec($curl);
            $errNo = curl_errno($curl);
            $this->lastError = curl_error($curl);
            $this->lastCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if (!$errNo && $this->lastCode < 500 && $this->lastCode !== 429)
                return is_string($raw) ? json_decode($raw, true) : null;

            if ($attempt < $attempts)
            {
                sleep($delay);
                $delay = min($delay * 2, 8);
            }
        }

        if ($this->lastError === '')
            $this->lastError = 'http ' . $this->lastCode;
        return null;
    }
}

// -------------------------------------------------------------------------------- export over http
/**
 * Fetches the export from the site's own exportOrders.php endpoint.
 *
 * The filters are forwarded so the shop only sends the orders that are actually wanted: the full
 * history is several megabytes, and the endpoint can narrow it server side.
 *
 * @return array|string - the decoded export, or a string describing the failure
 */
function fetchExport($url, $key, $since, $sinceId, $statuses, $limit)
{
    $query = array();
    if ($key !== '')
        $query['key'] = $key;
    if ($since !== null)
        $query['since'] = $since;
    if ($sinceId > 0)
        $query['since-id'] = $sinceId;
    if (count($statuses))
        $query['status'] = implode(',', $statuses);
    if ($limit > 0)
        $query['limit'] = $limit;

    // a url that already carries its own query string keeps it, ours is merged on top
    $separator = strpos($url, '?') === false ? '?' : '&';
    $full = $url . (count($query) ? $separator . http_build_query($query) : '');

    // the export scans the whole order table, so a shared host answers 503 if it is asked twice in
    // quick succession. Retried with a growing pause rather than failing the run.
    $attempts = 4;
    $delay = 5;
    $lastProblem = '';

    for ($attempt = 1; $attempt <= $attempts; $attempt++)
    {
        $curl = curl_init($full);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
        // the whole order history can be several MB over a shared host - be patient
        curl_setopt($curl, CURLOPT_TIMEOUT, 600);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json'));

        $raw = curl_exec($curl);
        $errNo = curl_errno($curl);
        $errMsg = curl_error($curl);
        $code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if (!$errNo && $code === 200)
        {
            $export = json_decode($raw, true);
            if (is_array($export) && isset($export['orders']) && isset($export['settings']))
            {
                $export['_bytes'] = strlen($raw);
                $export['_raw'] = $raw;
                return $export;
            }

            // a 200 that is not an export is the exporter's own error report - no point retrying
            return 'the exporter did not return an export (HTTP 200): ' . trim(substr((string)$raw, 0, 300));
        }

        if ($errNo)
            $lastProblem = 'could not reach the exporter: curl(' . $errNo . ') ' . $errMsg;
        else
            $lastProblem = 'exporter answered HTTP ' . $code . ': '
                         . trim(preg_replace('/\s+/', ' ', strip_tags((string)$raw)));

        // 4xx will not fix itself - a wrong key or a bad argument stays wrong
        if (!$errNo && $code >= 400 && $code < 500)
            return $lastProblem;

        if ($attempt < $attempts)
        {
            out('     .. export retry ' . $attempt . ' in ' . $delay . 's (' . substr($lastProblem, 0, 80) . ')');
            sleep($delay);
            $delay = min($delay * 2, 40);
        }
    }

    return $lastProblem . ' (after ' . $attempts . ' attempts)';
}

// -------------------------------------------------------------------------------- helpers
/**
 * Products by code, in batches. Returns a map code => product, so an unresolved code is visible
 * instead of silently becoming the placeholder.
 */
function resolveProducts($ms, $codes)
{
    $byCode = array();
    $codes = array_values(array_filter(array_unique($codes), function ($code) { return $code !== ''; }));

    foreach (array_chunk($codes, 40) as $chunk)
    {
        $filter = array();
        foreach ($chunk as $code)
            $filter[] = 'code=' . urlencode($code);

        $resp = null;
        for ($attempt = 1; $attempt <= 3; $attempt++)
        {
            $resp = $ms->get(MsClient::BASE . 'product?limit=100&filter=' . implode(';', $filter));
            if (is_array($resp) && isset($resp['rows']))
                break;
            out('     .. product batch retry ' . $attempt . ' (' . $ms->lastError() . ')');
            sleep(2);
        }

        if (!is_array($resp) || !isset($resp['rows']))
            continue;

        foreach ($resp['rows'] as $row)
            if (isset($row['code']))
                $byCode[(string)$row['code']] = $row;

        usleep(200000);
    }

    return $byCode;
}

/**
 * Which of these order names MoySklad already holds.
 *
 * A read that fails would look like "order missing" and produce a duplicate, so a batch that never
 * answers aborts the run instead of being treated as empty.
 *
 * @return array|false - map name => true
 */
function existingNames($ms, $names)
{
    $existing = array();

    foreach (array_chunk($names, 25) as $chunk)
    {
        $filter = array();
        foreach ($chunk as $name)
            $filter[] = 'name=' . urlencode($name);

        $ok = false;
        for ($attempt = 1; $attempt <= 5; $attempt++)
        {
            $resp = $ms->get(MsClient::BASE . 'customerorder?limit=100&filter=' . implode(';', $filter));
            if (is_array($resp) && isset($resp['rows']))
            {
                foreach ($resp['rows'] as $row)
                    if (isset($row['name']))
                        $existing[$row['name']] = true;
                $ok = true;
                break;
            }
            out('     .. existence batch retry ' . $attempt . ' (' . $ms->lastError() . ')');
            sleep(2);
        }

        if (!$ok)
            return false;

        usleep(200000);
    }

    return $existing;
}

/**
 * Counterparty for a phone, created if there is none - the plugin's behaviour, plus a per-run
 * cache so a customer with five backfilled orders does not become five counterparties.
 *
 * @return array|string - the counterparty, or a string explaining the failure
 */
function counterpartyFor($ms, $order, &$cache, $create)
{
    $phone = trim($order['billing']['phone']);
    if ($phone === '')
        return 'no billing phone to match a counterparty on';

    if (isset($cache[$phone]))
        return $cache[$phone];

    $resp = $ms->get(MsClient::BASE . 'counterparty?limit=1&filter=phone=' . urlencode($phone));
    if (!is_array($resp) || !isset($resp['rows']))
        return 'counterparty lookup failed (' . $ms->lastError() . ')';

    if (isset($resp['rows'][0]['meta']['href']))
    {
        $cache[$phone] = $resp['rows'][0];
        return $cache[$phone];
    }

    if (!$create)
        return 'counterparty missing and --no-counterparty was given';

    $created = $ms->post(MsClient::BASE . 'counterparty', array(
        'name'          => $order['billing']['firstName'],
        'phone'         => $phone,
        'email'         => $order['billing']['email'],
        'actualAddress' => $order['billing']['address1'],
        'state'         => array('meta' => array(
            'href'      => MsClient::BASE . 'counterparty/metadata/states/dd9087e9-4f86-11e6-7a69-8f5500000962',
            'type'      => 'state',
            'mediaType' => 'application/json'
        ))
    ));

    if (!is_array($created) || !isset($created['meta']['href']))
        return 'counterparty create failed (' . $ms->lastError() . '): '
             . substr(json_encode($created, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 200);

    $cache[$phone] = $created;
    return $created;
}

/**
 * Joins ms_base_url with an entity path from wp_gms_settings.
 *
 * The plugin concatenates the two raw, which works because the site stores the base without a
 * trailing slash and every value with a leading one. Normalising only one side produces
 * '.../entity//organization/...', so both sides are trimmed here.
 */
function msHref($base, $path)
{
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

/**
 * Builds the MoySklad customerorder for a WooCommerce order.
 *
 * This mirrors the WordPress plugin field for field, on purpose: a backfilled order has to be
 * indistinguishable from one the webhook created. Two of the plugin's quirks are reproduced rather
 * than fixed here, both flagged inline - fixing them would make old and new orders differ.
 *
 * @return array|string - the payload, or a string explaining why it was skipped
 */
function buildPayload($order, $settings, $byCode, $placeholder, $counterparty)
{
    $base = $settings['ms_base_url'];

    if (!count($order['items']))
        return 'order has no line items';

    // the plugin's discount is spread over the positions as a percentage
    $subtotal = 0;
    foreach ($order['items'] as $item)
        $subtotal += $item['subtotal'];

    // plugin divides by this without checking - a 100% coupon order would be a division by zero
    $discountPercentage = $subtotal > 0 ? round(($order['discountTotal'] / $subtotal) * 100, 2) : 0;

    $positions = array();
    $lastVat = 0;

    foreach ($order['items'] as $item)
    {
        $quantity = (int)$item['qty'];
        if ($quantity <= 0)
            return 'line item ' . $item['itemId'] . ' has quantity ' . $quantity;

        $product = ($item['sku'] !== '' && isset($byCode[$item['sku']])) ? $byCode[$item['sku']] : $placeholder;
        if (!isset($product['id']))
            return 'neither sku "' . $item['sku'] . '" nor the 000-0000 placeholder resolved in MoySklad';

        $lastVat = (int)($product['effectiveVat'] ?? 0);

        $positions[] = array(
            'assortment' => array('meta' => array(
                'href'      => MsClient::BASE . 'product/' . $product['id'],
                'type'      => 'product',
                'mediaType' => 'application/json'
            )),
            'quantity' => $quantity,
            'reserve'  => $quantity,
            'price'    => floatval($item['subtotal'] / $quantity) * 100,
            'discount' => $discountPercentage,
            'vat'      => $lastVat
        );
    }

    // delivery method decides both the shipping position and the "способ доставки" attribute
    $delivery = msHref($base, $settings['delivery_pickup']);
    foreach ($order['shipping'] as $shipping)
    {
        $positions[] = array(
            'assortment' => array('meta' => array(
                'href'      => msHref($base, $settings['delivery']),
                'type'      => 'service',
                'mediaType' => 'application/json'
            )),
            'quantity' => max(1, (int)$shipping['qty']),
            'price'    => floatval($shipping['total']) * 100,
            // the plugin puts the last *product's* vat on the shipping service. Kept identical so
            // backfilled and webhook orders agree; worth fixing in both places, not just here.
            'vat'      => $lastVat
        );

        if ($shipping['methodId'] === 'KC2008_Pickup_Method')
            $delivery = msHref($base, $settings['delivery_yandex']);
        elseif ($shipping['methodId'] === 'wc_russian_post_postal')
            $delivery = msHref($base, $settings['delivery_rpost']);
        elseif ($shipping['methodId'] === 'official_cdek')
            $delivery = msHref($base, $settings['delivery_sdek']);
        else
            $delivery = msHref($base, $settings['delivery_pickup']);
    }

    $payment = in_array($order['paymentMethod'], array('rbspayment', 'alfabank'), true)
        ? msHref($base, $settings['payment_sber'])
        : msHref($base, $settings['payment_cash']);

    // moment is site time, exactly what the plugin's get_date_created()->format() produced
    $created = new DateTime($order['dateCreated']);
    $planned = clone $created;
    if ((int)$created->format('H') >= 12)
        $planned->modify('+1 day');

    return array(
        'name'         => 'ccd-' . $order['id'],
        'organization' => array('meta' => array(
            'href' => msHref($base, $settings['organization']), 'type' => 'organization', 'mediaType' => 'application/json')),
        'externalCode' => (string)$order['id'],
        'moment'       => $created->format('Y-m-d H:i:s'),
        'deliveryPlannedMoment' => $planned->format('Y-m-d H:i:s'),
        'applicable'   => true,
        'vatEnabled'   => true,
        'agent'        => array('meta' => array(
            'href' => $counterparty['meta']['href'], 'type' => 'counterparty', 'mediaType' => 'application/json')),
        'state'        => array('meta' => array(
            'href' => msHref($base, $settings['state']), 'type' => 'state', 'mediaType' => 'application/json')),
        'store'        => array('meta' => array(
            'href' => msHref($base, $settings['store']), 'type' => 'store', 'mediaType' => 'application/json')),
        'project'      => array('meta' => array(
            'href' => msHref($base, $settings['project']), 'type' => 'project', 'mediaType' => 'application/json')),
        'positions'    => $positions,
        'attributes'   => array(
            // Тип оплаты
            array(
                'meta'  => array('href' => MsClient::BASE . 'customerorder/metadata/attributes/2ada6f00-d623-11e8-9109-f8fc0021e4d1',
                                 'type' => 'attributemetadata', 'mediaType' => 'application/json'),
                'value' => array('meta' => array('href' => $payment, 'type' => 'customentity', 'mediaType' => 'application/json'))
            ),
            // Способ доставки
            array(
                'meta'  => array('href' => MsClient::BASE . 'customerorder/metadata/attributes/5c01b362-d61f-11e8-9107-504800214d3f',
                                 'type' => 'attributemetadata', 'mediaType' => 'application/json'),
                'value' => array('meta' => array('href' => $delivery, 'type' => 'customentity', 'mediaType' => 'application/json'))
            ),
            // ФИО
            array(
                'meta'  => array('href' => MsClient::BASE . 'customerorder/metadata/attributes/d948e4fe-d621-11e8-9ff4-34e80021aea5',
                                 'type' => 'attributemetadata', 'mediaType' => 'application/json'),
                'value' => $order['billing']['firstName']
            ),
            // адрес доставки
            array(
                'meta'  => array('href' => MsClient::BASE . 'customerorder/metadata/attributes/b73f3a67-d62e-11e8-9109-f8fc002175da',
                                 'type' => 'attributemetadata', 'mediaType' => 'application/json'),
                'value' => $order['billing']['address1']
            ),
            // телефон
            array(
                'meta'  => array('href' => MsClient::BASE . 'customerorder/metadata/attributes/5f9f5c95-d622-11e8-9ff4-34e80021bc5f',
                                 'type' => 'attributemetadata', 'mediaType' => 'application/json'),
                'value' => $order['billing']['phone']
            )
        )
    );
}

// -------------------------------------------------------------------------------- run
out('ccd77 order backfill - ' . date('Y-m-d H:i:s T') . '  mode=' . strtoupper($mode));

// an empty list means "no status filter", which is what 'all' asks for
$statuses = \Ccd77\CcdDb::STATUS_EXPORTED;
if ($statusArg === 'all')
    $statuses = array();
elseif ($statusArg !== '')
    $statuses = array_map('trim', explode(',', $statusArg));

$db = null;
$exported = null;

if ($fromUrl !== '')
{
    // ------------------------------------------------------------- from the site over http
    out('source      ' . $fromUrl . '  (key ' . ($exportKey === '' ? 'none' : 'sent') . ')');

    $exported = fetchExport($fromUrl, $exportKey, $since, $sinceId, $statuses, 0);
    if (is_string($exported))
    {
        out('[FAIL] ' . $exported);
        exit(1);
    }

    out('fetched     ' . number_format($exported['_bytes'] / 1024, 1) . ' KB');

    if ($saveTo !== '')
    {
        if (file_put_contents($saveTo, $exported['_raw']) === false)
            out('[WARN] could not save a copy to ' . $saveTo);
        else
            out('saved       ' . $saveTo . '  (contains customer data - delete it when done)');
    }

    unset($exported['_raw'], $exported['_bytes']);

    $settings = $exported['settings'];
    $counts = $exported['statusCounts'] ?? array();

    out('exported    ' . ($exported['generated'] ?? '?') . '  on ' . ($exported['source']['host'] ?? '?'));
    out('schema      ' . ($exported['source']['schema'] ?? '?') . '  tz ' . ($exported['source']['timezone'] ?? '?'));
    out('in export   ' . count($exported['orders']) . ' order(s)');
}
elseif ($fromJson !== '')
{
    // ------------------------------------------------------------- from a json file
    if (!is_readable($fromJson))
    {
        out('[FAIL] cannot read ' . $fromJson);
        exit(1);
    }

    $exported = json_decode(file_get_contents($fromJson), true);
    if (!is_array($exported) || !isset($exported['orders']) || !isset($exported['settings']))
    {
        out('[FAIL] ' . $fromJson . ' is not an export from ccd77/exportOrders.php');
        exit(1);
    }

    $settings = $exported['settings'];
    $counts = $exported['statusCounts'] ?? array();

    out('source      ' . $fromJson);
    out('exported    ' . ($exported['generated'] ?? '?') . '  on ' . ($exported['source']['host'] ?? '?'));
    out('schema      ' . ($exported['source']['schema'] ?? '?') . '  tz ' . ($exported['source']['timezone'] ?? '?'));
    out('in file     ' . count($exported['orders']) . ' order(s)');
}
else
{
    // ------------------------------------------------------------- straight from the shop database
    try
    {
        $db = new \Ccd77\CcdDb();
    }
    catch (Exception $e)
    {
        out('[FAIL] ' . $e->getMessage());
        out('       The shop database only listens on localhost of the hosting server. Either run');
        out('       this there, point it elsewhere with CCD77_DB_HOST / CCD77_DB_PORT, or - simplest -');
        out('       use --from-url to pull the export from the site over http.');
        exit(1);
    }

    out('schema      ' . $db->schema() . ($db->schema() === 'hpos' ? '  (wp_wc_orders)' : '  (wp_posts + wp_postmeta)'));
    out('site tz     ' . $db->timezone()->getName());

    try
    {
        $settings = $db->settings();
    }
    catch (Exception $e)
    {
        out('[FAIL] ' . $e->getMessage());
        exit(1);
    }

    $counts = $db->statusCounts();
}

$countsOut = array();
foreach ($counts as $status => $count)
    $countsOut[] = $status . '=' . $count;
out('shop orders ' . (count($countsOut) ? implode('  ', $countsOut) : '(not reported)'));

// the export masks the token on purpose - it has to be supplied here
if ($msToken !== '')
    $settings['ms_token'] = $msToken;

if (!isset($settings['ms_token']) || $settings['ms_token'] === '' || strpos($settings['ms_token'], 'MASKED:') === 0)
{
    out('[FAIL] no MoySklad token. The export masks it - pass --ms-token=... or set MS_TOKEN,');
    out('       or re-export with --with-token if you would rather carry it in the file.');
    exit(1);
}

$required = array('ms_base_url', 'ms_token', 'organization', 'project', 'state', 'store', 'delivery',
                  'delivery_rpost', 'delivery_sdek', 'delivery_yandex', 'delivery_pickup',
                  'payment_sber', 'payment_cash');
$missing = array();
foreach ($required as $key)
    if (!isset($settings[$key]) || $settings[$key] === '')
        $missing[] = $key;

if (count($missing))
{
    out('[FAIL] wp_gms_settings is missing: ' . implode(', ', $missing));
    out('       These are the same values the plugin uses - fix them in the shop, not here.');
    exit(1);
}

$base = rtrim($settings['ms_base_url'], '/') . '/';
if (strpos($base, 'http') !== 0)
{
    out('[FAIL] ms_base_url does not look like a url: ' . $settings['ms_base_url']);
    exit(1);
}

out('ms base     ' . $base);
out('ms token    ' . substr($settings['ms_token'], 0, 6) . str_repeat('*', 6) . substr($settings['ms_token'], -4)
  . ' [len ' . strlen($settings['ms_token']) . ']');

$ms = new MsClient($settings['ms_token']);

// the token has to be good before anything else is attempted
$who = $ms->get('https://api.moysklad.ru/api/remap/1.2/context/employee');
if (!is_array($who) || !isset($who['name']))
{
    out('[FAIL] MoySklad rejected the plugin token: http ' . $ms->lastCode() . ' ' . $ms->lastError());
    exit(1);
}
out('ms user     ' . $who['name']);

// ------------------------------------------------------------------ orders
out('statuses    ' . (count($statuses) ? implode(', ', $statuses) : 'all')
  . ($sinceId ? '   since-id=' . $sinceId : '')
  . ($since !== null ? '   since=' . $since : '')
  . ($max ? '   max=' . $max : ''));

if ($exported !== null)
{
    // the export is usually wider than one backfill needs, so the same filters are applied here
    $orders = array();
    foreach ($exported['orders'] as $order)
    {
        if (count($statuses) && !in_array($order['status'], $statuses, true))
            continue;
        if ($sinceId && (int)$order['id'] <= $sinceId)
            continue;
        if ($since !== null && $order['dateCreated'] < $since)
            continue;
        $orders[] = $order;
    }

    usort($orders, function ($a, $b) { return (int)$a['id'] - (int)$b['id']; });
    out('candidates  ' . count($orders) . ' of ' . count($exported['orders']) . ' order(s) in the file match');
}
else
{
    try
    {
        $orders = $db->findOrders($statuses, $sinceId, $since);
    }
    catch (Exception $e)
    {
        out('[FAIL] ' . $e->getMessage());
        exit(1);
    }

    out('candidates  ' . count($orders) . ' order(s) in the shop database');
}

if (!count($orders))
{
    out('nothing to do');
    exit(0);
}

$names = array();
foreach ($orders as $order)
    $names[] = 'ccd-' . $order['id'];

$existing = existingNames($ms, $names);
if ($existing === false)
{
    out('[FAIL] a MoySklad existence batch never answered - refusing to risk duplicates');
    exit(1);
}
out('in MoySklad ' . count($existing) . ' already there');

$todo = array();
foreach ($orders as $order)
    if (!isset($existing['ccd-' . $order['id']]))
        $todo[] = $order;

$trimmed = 0;
if ($max > 0 && count($todo) > $max)
{
    $trimmed = count($todo) - $max;
    $todo = array_slice($todo, 0, $max);
}

out('missing     ' . count($todo) . ($trimmed ? " (capped by --max, $trimmed left for the next run)" : ''));

if (!count($todo))
{
    out('nothing missing');
    exit(0);
}

// ------------------------------------------------------------------ products
$codes = array();
foreach ($todo as $order)
    foreach ($order['items'] as $item)
        $codes[] = $item['sku'];

$byCode = resolveProducts($ms, $codes);
$wanted = array_values(array_filter(array_unique($codes), function ($code) { return $code !== ''; }));
$unresolved = array_values(array_diff($wanted, array_keys($byCode)));

out('skus        ' . count($byCode) . ' of ' . count($wanted) . ' resolved in MoySklad'
  . (count($unresolved) ? ' - falling back to 000-0000 for: ' . implode(', ', array_slice($unresolved, 0, 10))
    . (count($unresolved) > 10 ? ' ...' : '') : ''));

$placeholderRows = resolveProducts($ms, array('000-0000'));
$placeholder = $placeholderRows['000-0000'] ?? array();
if (!count($placeholder) && count($unresolved))
    out('[WARN] the 000-0000 placeholder is missing too - orders with unknown skus will be skipped');

// ------------------------------------------------------------------ payloads
$payloads = array();
$payloadNames = array();
$skips = array();
$counterpartyCache = array();
$created = 0;
$verified = 0;
$newCustomers = 0;

out('');
foreach ($todo as $order)
{
    $counterparty = counterpartyFor($ms, $order, $counterpartyCache, $createCounterparties && $mode === 'live');
    $newCustomer = false;

    if (is_string($counterparty))
    {
        // A dry run must not create anything, so a customer who has no counterparty yet cannot get
        // a real agent href. Preview the order against a stand-in rather than hiding it: otherwise
        // the first run of a new shop shows nothing at all and there is no payload to check.
        if ($mode === 'dry' && strpos($counterparty, 'counterparty missing') === 0)
        {
            $newCustomer = true;
            $counterparty = array('meta' => array(
                'href' => MsClient::BASE . 'counterparty/WOULD-BE-CREATED-ON-A-LIVE-RUN'));
        }
        else
        {
            $skips[] = 'ccd-' . $order['id'] . ' - ' . $counterparty;
            out(sprintf('   !! SKIP  ccd-%-8s %s', $order['id'], $counterparty));
            continue;
        }
    }

    $built = buildPayload($order, $settings, $byCode, $placeholder, $counterparty);
    if (is_string($built))
    {
        $skips[] = 'ccd-' . $order['id'] . ' - ' . $built;
        out(sprintf('   !! SKIP  ccd-%-8s %s', $order['id'], $built));
        continue;
    }

    $sum = 0;
    foreach ($built['positions'] as $position)
        $sum += $position['price'] * ($position['quantity'] ?? 1);

    out(sprintf('   %s  %-12s %s  %-14s pos=%d sum=%.2f  %s',
        $newCustomer ? '+' : ' ', $built['name'], $order['dateCreated'], $order['status'],
        count($built['positions']), $sum / 100, $order['billing']['phone']));

    if ($newCustomer)
        $newCustomers++;

    $payloads[] = $built;
    $payloadNames[] = $built['name'];
}

out('');
out('payloads    ' . count($payloads) . ' ready' . (count($skips) ? ', ' . count($skips) . ' skipped' : '')
  . ($newCustomers ? '   (+ marks ' . $newCustomers . ' order(s) whose counterparty does not exist yet)' : ''));

if ($mode === 'dry')
{
    if (count($payloads))
    {
        out('DRY RUN - nothing sent. Sample payload:');
        out(json_encode($payloads[0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
}
elseif (count($payloads))
{
    // chunked: a bulk request is all-or-nothing, so one bad row must not cost the whole run
    foreach (array_chunk($payloads, 20) as $chunkNumber => $chunk)
    {
        $result = $ms->post(MsClient::BASE . 'customerorder', $chunk);

        if (!is_array($result) || !count($result) || isset($result['errors']) || isset($result[0]['errors']))
        {
            out('[FAIL] chunk ' . ($chunkNumber + 1) . ' of ' . count($chunk) . ' rejected: http ' . $ms->lastCode()
              . ' ' . substr(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 400));
            continue;
        }

        $created += count($result);
        out('created     chunk ' . ($chunkNumber + 1) . ': ' . count($result) . ' of ' . count($chunk));
        usleep(300000);
    }

    // the create response is not proof - ask MoySklad what it holds now
    $verify = existingNames($ms, $payloadNames);
    if ($verify === false)
        out('[WARN] could not verify - re-run in dry mode to see what is still missing');
    else
    {
        $verified = count($verify);
        $stillMissing = array_values(array_diff($payloadNames, array_keys($verify)));
        out('verified    ' . $verified . ' of ' . count($payloadNames) . ' now in MoySklad');
        if (count($stillMissing))
            out('[WARN] still missing: ' . implode(', ', array_slice($stillMissing, 0, 15))
              . (count($stillMissing) > 15 ? ' ...' : ''));
    }
}

// -------------------------------------------------------------------------------- summary
out('');
out('=============================================================================');
out('  SUMMARY  (' . strtoupper($mode) . ')');
out('=============================================================================');
out('  candidates      ' . count($orders));
out('  already in MS   ' . count($existing));
out('  to create       ' . count($todo));
out('  payloads built  ' . count($payloads));
out('  skipped         ' . count($skips));
if ($mode === 'live')
{
    out('  created         ' . $created);
    out('  verified        ' . $verified);
}

if (count($skips))
{
    out('');
    out('  needing attention:');
    foreach ($skips as $skip)
        out('     ' . $skip);
}

out('');
if ($mode === 'dry')
    out('  Dry run. Re-run with "live" to create these orders.');

exit(count($skips) ? 1 : 0);
?>
