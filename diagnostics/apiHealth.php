<?php
/**
 * API health check for MoySklad, Wildberries, Ozon and Yandex.Market.
 *
 * Answers one question: when the server "stops getting data", is it the network, the
 * credentials, or the remote API? Every check is read-only and reports the stage it got to -
 * DNS -> TCP -> TLS -> HTTP -> payload - so a blocked egress looks different from a rejected
 * token, which looks different from a marketplace outage.
 *
 * Run the same file locally and on the server and compare the two outputs.
 *
 *   CLI:     php diagnostics/apiHealth.php [ms|wb|ozon|yandex|db|env ...]
 *   browser: /diagnostics/apiHealth.php?only=ms,wb
 *
 * Exit code is 0 only when every check passed (usable from cron).
 *
 * @author Georgy Polyan <acidlord@yandex.ru>
 */

$isCli = (php_sapi_name() === 'cli');

if (!$isCli)
{
    if (!isset($_SERVER['DOCUMENT_ROOT']) || $_SERVER['DOCUMENT_ROOT'] === '')
        $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

    // Sent before auth.php on purpose: that file emits a stray newline after its closing tag,
    // so any header() call after requiring it fails with "headers already sent".
    header('Content-Type: text/plain; charset=utf-8');

    // The output carries the egress IP, the DB host and masked credentials, so over HTTP this
    // sits behind the normal app login like every other page. auth.php sends the redirect but
    // does not stop execution, so the session is re-checked here before anything is printed.
    require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
    if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== 'true')
    {
        echo "not authenticated - log in first\n";
        exit(1);
    }
}
else
{
    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/docker-config.php');

// ---------------------------------------------------------------- which sections to run
$all = array('env', 'db', 'net', 'ms', 'wb', 'ozon', 'yandex');
if ($isCli)
    $only = array_values(array_filter(array_slice($argv, 1)));
else
    $only = array_values(array_filter(explode(',', $_GET['only'] ?? '')));
if (!count($only))
    $only = $all;
$only = array_map('strtolower', $only);

$CONNECT_TIMEOUT = 8;
$TOTAL_TIMEOUT   = 20;

$pass = 0;
$fail = 0;
$warn = 0;

function out($s = '')
{
    echo $s . "\n";
    if (function_exists('flush'))
        flush();
}

function section($title)
{
    out();
    out('=============================================================================');
    out('  ' . $title);
    out('=============================================================================');
}

/**
 * Credentials must never end up in a log or a browser tab.
 */
function mask($v)
{
    $v = (string)$v;
    if ($v === '')
        return '(empty)';
    $len = strlen($v);
    if ($len <= 12)
        return substr($v, 0, 2) . str_repeat('*', max(1, $len - 2)) . ' [len ' . $len . ']';
    return substr($v, 0, 6) . str_repeat('*', 6) . substr($v, -4) . ' [len ' . $len . ']';
}

/**
 * One HTTP call, reported by the stage it reached.
 *
 * @return array
 */
function probe($label, $method, $url, $headers = array(), $body = null, $expect = '2xx + json', $resolve = null)
{
    global $CONNECT_TIMEOUT, $TOTAL_TIMEOUT;

    $respHeaders = array();

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $CONNECT_TIMEOUT);
    curl_setopt($curl, CURLOPT_TIMEOUT, $TOTAL_TIMEOUT);
    // 'gzip', not '': an empty string advertises every encoding the local curl supports
    // (brotli, zstd on newer builds) and a decode the runtime cannot finish surfaces as
    // CURLE_WRITE_ERROR(23) on an otherwise perfectly good 200. gzip is also what the
    // application's own clients ask for, so this matches production behaviour.
    curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    // pin the hostname to a known address, to tell a bad resolver apart from a blocked route
    if ($resolve !== null)
        curl_setopt($curl, CURLOPT_RESOLVE, array($resolve));
    curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($ch, $line) use (&$respHeaders) {
        $parts = explode(':', $line, 2);
        if (count($parts) === 2)
            $respHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
        return strlen($line);
    });
    if ($method === 'POST')
    {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body === null ? '{}' : $body);
    }

    $raw   = curl_exec($curl);
    $errNo = curl_errno($curl);
    $errMsg = curl_error($curl);
    $info  = curl_getinfo($curl);
    curl_close($curl);

    $code = (int)($info['http_code'] ?? 0);
    $json = is_string($raw) ? json_decode($raw, true) : null;

    // classify. Order matters: a curl error that arrives *after* a status line is a local
    // receive/decode problem, not a connectivity one - reporting it as NETWORK sends you
    // hunting for a firewall that is not there.
    if ($errNo && $code >= 200 && $code < 400)
    {
        $stage = 'LOCAL';
        $verdict = 'WARN';
        $note = 'remote answered HTTP ' . $code . ' but curl(' . $errNo . ') ' . $errMsg
              . ' - transport reached the API, the failure is on this host (decode/write)';
    }
    elseif ($errNo)
    {
        // 6 = DNS, 7 = refused/unreachable, 28 = timeout, 35/60 = TLS
        $stage = 'NETWORK';
        $verdict = 'FAIL';
        $note = 'curl(' . $errNo . ') ' . $errMsg;
    }
    elseif ($code === 401 || $code === 403)
    {
        $stage = 'AUTH';
        $verdict = 'FAIL';
        $note = 'credentials rejected';
    }
    elseif ($code === 429)
    {
        $stage = 'RATE LIMIT';
        $verdict = 'WARN';
        $note = 'throttled - retry later';
    }
    elseif ($code >= 500)
    {
        $stage = 'REMOTE';
        $verdict = 'FAIL';
        $note = 'remote API error';
    }
    elseif ($code >= 400)
    {
        $stage = 'HTTP';
        $verdict = 'FAIL';
        $note = 'unexpected client error';
    }
    elseif ($json === null)
    {
        $stage = 'PAYLOAD';
        $verdict = 'FAIL';
        $note = 'response is not json';
    }
    else
    {
        $stage = 'OK';
        $verdict = 'PASS';
        $note = '';
    }

    out(sprintf('  [%-4s] %-34s HTTP %-3s %6.0f ms  %s',
        $verdict, $label, $code ?: '---', ($info['total_time'] ?? 0) * 1000, $stage));
    out(sprintf('         %s %s', $method, $url));
    out(sprintf('         expected: %s', $expect));
    // cumulative milestones, not deltas - these are the numbers curl actually measures, and
    // a stage that reads 0 simply was not reached
    // some curl builds leave appconnect_time at 0 even on https - print n/a rather than a
    // zero that reads like a failed handshake
    $tls = ($info['appconnect_time'] ?? 0) > 0
        ? sprintf('%.0fms', $info['appconnect_time'] * 1000)
        : 'n/a';
    out(sprintf('         cumulative: dns %.0fms -> tcp %.0fms -> tls %s -> firstbyte %.0fms  peer %s',
        ($info['namelookup_time'] ?? 0) * 1000,
        ($info['connect_time'] ?? 0) * 1000,
        $tls,
        ($info['starttransfer_time'] ?? 0) * 1000,
        $info['primary_ip'] ?? '?'));

    // MoySklad and WB both advertise remaining quota - a shrinking number explains "no data"
    // without any error at all
    $limits = array();
    foreach (array('x-ratelimit-remaining', 'x-ratelimit-limit', 'x-lognex-retry-after',
                   'retry-after', 'x-ratelimit-retry-after') as $h)
        if (isset($respHeaders[$h]))
            $limits[] = $h . '=' . $respHeaders[$h];
    if (count($limits))
        out('         quota: ' . implode('  ', $limits));

    if ($note !== '')
        out('         -> ' . $note);

    $excerpt = is_string($raw) ? trim(preg_replace('/\s+/', ' ', substr($raw, 0, 220))) : '(no body)';
    out('         body: ' . $excerpt);

    return array('verdict' => $verdict, 'code' => $code, 'json' => $json,
                 'info' => $info, 'headers' => $respHeaders);
}

function record($result)
{
    global $pass, $fail, $warn;
    if ($result['verdict'] === 'PASS')
        $pass++;
    elseif ($result['verdict'] === 'WARN')
        $warn++;
    else
        $fail++;
}

/**
 * Settings are the usual reason a "working" server suddenly stops: a token was rotated in one
 * place and not the other. Read them straight from the table, no app classes in the way.
 */
function settings($codes)
{
    $found = array();
    $conn = @mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
    if (!$conn)
        return $found;
    mysqli_set_charset($conn, 'utf8');
    $quoted = array();
    foreach ($codes as $c)
        $quoted[] = "'" . mysqli_real_escape_string($conn, $c) . "'";
    $res = mysqli_query($conn, 'select code, value from settings where code in (' . implode(',', $quoted) . ')');
    if ($res)
    {
        while ($row = mysqli_fetch_assoc($res))
            $found[$row['code']] = $row['value'];
        mysqli_free_result($res);
    }
    mysqli_close($conn);
    return $found;
}

/**
 * WB tokens are JWTs - an expired one is the single most common cause of a silent stop,
 * and it is readable without calling anything.
 */
function jwtExpiry($token)
{
    $parts = explode('.', $token);
    if (count($parts) !== 3)
        return null;
    $payload = base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4 ? strlen($parts[1]) + 4 - strlen($parts[1]) % 4 : strlen($parts[1]), '=', STR_PAD_RIGHT));
    $data = json_decode($payload, true);
    if (!is_array($data) || !isset($data['exp']))
        return null;
    return (int)$data['exp'];
}

out('API health check - ' . date('Y-m-d H:i:s T'));
out('sections: ' . implode(', ', $only));

// ------------------------------------------------------------------------------- env
if (in_array('env', $only, true))
{
    section('ENVIRONMENT');
    out('  php            ' . PHP_VERSION . ' (' . php_sapi_name() . ')');
    out('  os             ' . php_uname('s') . ' ' . php_uname('r'));
    out('  host           ' . (gethostname() ?: '?'));
    out('  timezone       ' . date_default_timezone_get());
    $cv = function_exists('curl_version') ? curl_version() : array();
    out('  curl           ' . ($cv['version'] ?? '?') . ' / ssl ' . ($cv['ssl_version'] ?? '?'));
    foreach (array('curl', 'mysqli', 'zlib', 'json', 'openssl') as $ext)
        out('  ext ' . str_pad($ext, 10) . (extension_loaded($ext) ? 'yes' : '*** MISSING ***'));
    out('  document_root  ' . $_SERVER['DOCUMENT_ROOT']);

    // egress IP - marketplaces do block by IP, so it is worth seeing which address they see
    $ipProbe = @file_get_contents('https://api.ipify.org', false, stream_context_create(
        array('http' => array('timeout' => 6))));
    out('  egress ip      ' . ($ipProbe ? trim($ipProbe) : '(could not determine - outbound http may be blocked)'));
}

// -------------------------------------------------------------------------------- db
if (in_array('db', $only, true))
{
    section('DATABASE');
    out('  host ' . DB_HOSTNAME . '  db ' . DB_DATABASE . '  user ' . DB_USERNAME);
    $t0 = microtime(true);
    $conn = @mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
    $ms = (microtime(true) - $t0) * 1000;
    if (!$conn)
    {
        out(sprintf('  [FAIL] connect %36.0f ms  %s', $ms, mysqli_connect_error()));
        $fail++;
    }
    else
    {
        $res = mysqli_query($conn, 'select count(*) c from settings');
        $row = $res ? mysqli_fetch_assoc($res) : array('c' => '?');
        out(sprintf('  [PASS] connect + %s settings rows %14.0f ms', $row['c'], $ms));
        $pass++;
        mysqli_close($conn);
    }
}

// ------------------------------------------------------------------------------- network
if (in_array('net', $only, true))
{
    section('NETWORK REACHABILITY  (dns + raw tcp:443, before any credentials)');
    out('  Separates the three ways an integration goes dark: the resolver, the route, or the');
    out('  remote refusing this particular source address.');
    out();

    $hosts = array(
        'api.moysklad.ru'                => '185.71.64.179',
        'marketplace-api.wildberries.ru' => null,
        'api-seller.ozon.ru'             => null,
        'api.partner.market.yandex.ru'   => null,
    );

    foreach ($hosts as $host => $knownIp)
    {
        $ips = @gethostbynamel($host);
        if (!$ips)
        {
            out(sprintf('  [FAIL] %-32s dns: no A record (resolver problem)', $host));
            $fail++;
            continue;
        }
        out(sprintf('         %-32s dns: %s', $host, implode(', ', $ips)));

        $t0 = microtime(true);
        $errno = 0;
        $errstr = '';
        $fp = @fsockopen('tcp://' . $ips[0], 443, $errno, $errstr, $CONNECT_TIMEOUT);
        $ms = (microtime(true) - $t0) * 1000;

        if ($fp)
        {
            fclose($fp);
            out(sprintf('  [PASS] %-32s tcp %s:443 open in %.0f ms', '', $ips[0], $ms));
            $pass++;
        }
        else
        {
            out(sprintf('  [FAIL] %-32s tcp %s:443 BLOCKED after %.0f ms (errno %d %s)',
                '', $ips[0], $ms, $errno, $errstr));
            $fail++;

            // resolved address unreachable - is the published address reachable instead?
            if ($knownIp !== null && $knownIp !== $ips[0])
            {
                $t0 = microtime(true);
                $fp2 = @fsockopen('tcp://' . $knownIp, 443, $errno, $errstr, $CONNECT_TIMEOUT);
                $ms2 = (microtime(true) - $t0) * 1000;
                if ($fp2)
                {
                    fclose($fp2);
                    out(sprintf('  [WARN] %-32s but %s:443 IS open -> this host resolves %s to the wrong address',
                        '', $knownIp, $host));
                    $warn++;
                }
                else
                    out(sprintf('         %-32s known address %s:443 also blocked (%d %s)',
                        '', $knownIp, $errno, $errstr));
            }
        }
    }
    out();
    out('  If dns resolves but tcp is blocked on one host only, the remote is dropping traffic');
    out('  from this server\'s address (or the host firewall is). If every host is blocked, it');
    out('  is this server\'s egress. Give the provider the "egress ip" from the ENVIRONMENT block.');
}

// ------------------------------------------------------------------------------- MoySklad
if (in_array('ms', $only, true))
{
    section('MOYSKLAD  (api.moysklad.ru, JSON API 1.2)');
    $cfg = settings(array('ms_token', 'ms_user', 'ms_password'));
    out('  ms_token    ' . mask($cfg['ms_token'] ?? ''));
    out('  ms_user     ' . ($cfg['ms_user'] ?? '(missing)'));
    out('  ms_password ' . mask($cfg['ms_password'] ?? ''));
    out();

    $base = 'https://api.moysklad.ru/api/remap/1.2/';

    if (!empty($cfg['ms_token']))
    {
        $r = probe('bearer / context/employee', 'GET', $base . 'context/employee',
            array('Content-type: application/json', 'Accept-Encoding: gzip',
                  'Authorization: Bearer ' . $cfg['ms_token']),
            null, '200 + json with "name" of the API user');
        record($r);
        if ($r['verdict'] === 'PASS')
            out('         user: ' . ($r['json']['name'] ?? '?') . '  uid: ' . ($r['json']['uid'] ?? '?'));
        elseif ($r['code'] === 0)
        {
            // no status line came back at all. Repeat against the published address: if that
            // works the resolver is wrong, if it also hangs the route itself is blocked.
            out('         no HTTP response - retrying pinned to the published address');
            $r2 = probe('bearer / pinned 185.71.64.179', 'GET', $base . 'context/employee',
                array('Content-type: application/json', 'Accept-Encoding: gzip',
                      'Authorization: Bearer ' . $cfg['ms_token']),
                null,
                '200 -> resolver at fault; another timeout -> route/IP block',
                'api.moysklad.ru:443:185.71.64.179');
            record($r2);
        }
        out();
    }

    // Basic auth (ms_user/ms_password) is no longer used by any call site - api/apiMS.php,
    // classes/apiMS.php, classes/msApi.php and classes/MS/apiMS.php all send Bearer ms_token.
    // Probing it here only produced a guaranteed failure that masked the real verdict.
    if (!empty($cfg['ms_user']) || !empty($cfg['ms_password']))
    {
        out('  note: ms_user/ms_password are still stored but unused - every MoySklad client');
        out('        authenticates with Bearer ms_token. Safe to delete from settings.');
        out();
    }
}

// ------------------------------------------------------------------------------- Wildberries
if (in_array('wb', $only, true))
{
    section('WILDBERRIES  (per-host ping + real order read)');
    $cfg = settings(array('WBApiTokenUllo', 'WBApiTokenKosmos'));

    foreach (array('Ullo' => 'WBApiTokenUllo', 'Kosmos' => 'WBApiTokenKosmos') as $shop => $code)
    {
        $token = $cfg[$code] ?? '';
        out('  --- ' . $shop . ' (' . $code . ') ' . mask($token));
        if ($token === '')
        {
            out('  [FAIL] token missing from settings');
            $fail++;
            out();
            continue;
        }

        $exp = jwtExpiry($token);
        if ($exp === null)
            out('         token is not a readable JWT - cannot check expiry');
        else
        {
            $left = $exp - time();
            $when = gmdate('Y-m-d H:i:s', $exp) . ' UTC';
            if ($left <= 0)
            {
                out('  [FAIL] token EXPIRED at ' . $when);
                $fail++;
            }
            elseif ($left < 7 * 86400)
            {
                out('  [WARN] token expires ' . $when . ' (in ' . round($left / 3600) . ' h) - rotate it');
                $warn++;
            }
            else
                out('  [PASS] token valid until ' . $when . ' (' . round($left / 86400) . ' days)');
        }

        $hdr = array('Content-type: application/json', 'Authorization: ' . $token);

        // ping each host the app actually talks to: one blocked host looks like "no data"
        foreach (array(
            'marketplace-api'    => 'https://marketplace-api.wildberries.ru/ping',
            'content-api'        => 'https://content-api.wildberries.ru/ping',
            'discounts-prices'   => 'https://discounts-prices-api.wildberries.ru/ping',
        ) as $name => $url)
            record(probe('ping ' . $name, 'GET', $url, $hdr, null, '200 + {"TS":..,"Status":"OK"}'));

        $r = probe('orders/new', 'GET', 'https://marketplace-api.wildberries.ru/api/v3/orders/new',
            $hdr, null, '200 + json with "orders" array (may legitimately be empty)');
        record($r);
        if ($r['verdict'] === 'PASS')
            out('         new orders waiting: ' . (is_array($r['json']['orders'] ?? null) ? count($r['json']['orders']) : '?'));
        out();
    }
}

// ------------------------------------------------------------------------------- Ozon
if (in_array('ozon', $only, true))
{
    section('OZON  (api-seller.ozon.ru)');
    $cfg = settings(array('ozon_client_id_ullo', 'ozon_api_key_ullo',
                          'ozon_client_id_kaori', 'ozon_api_key_kaori'));

    $shops = array(
        'Ullo'  => array($cfg['ozon_client_id_ullo'] ?? '',  $cfg['ozon_api_key_ullo'] ?? ''),
        'Kaori' => array($cfg['ozon_client_id_kaori'] ?? '', $cfg['ozon_api_key_kaori'] ?? ''),
    );

    foreach ($shops as $shop => $pair)
    {
        list($clientId, $apiKey) = $pair;
        out('  --- ' . $shop . '  Client-Id ' . ($clientId ?: '(missing)') . '  Api-Key ' . mask($apiKey));
        if ($clientId === '' || $apiKey === '')
        {
            out('  [FAIL] credentials missing from settings');
            $fail++;
            out();
            continue;
        }

        $hdr = array('Content-type: application/json', 'Client-Id: ' . $clientId, 'Api-Key: ' . $apiKey);

        // v2, not v1: Ozon retired v1/warehouse/list ({"code":9,"obsolete method cannot be used"})
        $r = probe('v2/warehouse/list', 'POST', 'https://api-seller.ozon.ru/v2/warehouse/list',
            $hdr, '{}', '200 + json with "warehouses" array');
        record($r);
        if ($r['verdict'] === 'PASS')
            out('         warehouses: ' . (is_array($r['json']['warehouses'] ?? null) ? count($r['json']['warehouses']) : '?'));

        // Ozon's equivalent of WB /orders/new - the queue the integration actually drains, so
        // this is the probe that matters when "no data is arriving"
        $r = probe('v3/posting/fbs/unfulfilled/list', 'POST',
            'https://api-seller.ozon.ru/v3/posting/fbs/unfulfilled/list', $hdr,
            json_encode(array('dir' => 'ASC', 'limit' => 1, 'offset' => 0,
                'filter' => array(
                    'cutoff_from' => gmdate('Y-m-d\TH:i:s\Z', time() - 30 * 86400),
                    'cutoff_to'   => gmdate('Y-m-d\TH:i:s\Z', time() + 30 * 86400)))),
            '200 + json with result.postings (unshipped orders, may be empty)');
        record($r);
        if ($r['verdict'] === 'PASS')
            out('         unfulfilled postings reported: ' . ($r['json']['result']['count'] ?? '?'));
        out();
    }
}

// ------------------------------------------------------------------------------- Yandex
if (in_array('yandex', $only, true))
{
    section('YANDEX.MARKET  (api.partner.market.yandex.ru)');

    // campaign => label, from the BERU_API_* constants in config.php
    $campaigns = array(
        BERU_API_ULLOZZA_CAMPAIGN => 'Ullo',
        BERU_API_SUMMIT_CAMPAIGN  => 'Summit',
        BERU_API_KOSMOS_CAMPAIGN  => 'Kosmos',
    );

    $codes = array();
    foreach (array_keys($campaigns) as $c)
    {
        $codes[] = 'beru_oauth_token_' . $c;
        $codes[] = 'beru_oauth_client_id_' . $c;
    }
    $cfg = settings($codes);

    foreach ($campaigns as $campaign => $label)
    {
        $token = $cfg['beru_oauth_token_' . $campaign] ?? '';
        $client = $cfg['beru_oauth_client_id_' . $campaign] ?? '';
        out('  --- ' . $label . ' (campaign ' . $campaign . ')  token ' . mask($token));
        if ($token === '')
        {
            out('  [FAIL] beru_oauth_token_' . $campaign . ' missing from settings');
            $fail++;
            out();
            continue;
        }

        $url = BERU_API_BASE_URL . BERU_API_VERSION . 'campaigns/' . $campaign;

        // current scheme (classes/apiBeru2.php)
        $r = probe('Api-Key / campaigns/' . $campaign, 'GET', $url,
            array('Content-type: application/json', 'Api-Key: ' . $token),
            null, '200 + json with "campaign" object');

        if ($r['verdict'] !== 'PASS' && $client !== '')
        {
            // legacy scheme (classes/apiBeru.php) - if this one works the caller is on the old client
            out('         Api-Key rejected, retrying the legacy OAuth scheme');
            $r = probe('OAuth / campaigns/' . $campaign, 'GET', $url,
                array('Content-type: application/json',
                      'Authorization: OAuth oauth_token="' . $token . '",oauth_client_id="' . $client . '"'),
                null, '200 + json with "campaign" object');
        }
        record($r);
        if ($r['verdict'] === 'PASS')
            out('         shop: ' . ($r['json']['campaign']['domain'] ?? '?'));
        out();
    }
}

// ------------------------------------------------------------------------------- summary
section('SUMMARY');
out(sprintf('  passed %d   warnings %d   failed %d', $pass, $warn, $fail));
out();
if ($fail === 0 && $warn === 0)
    out('  ALL CLEAR - credentials and connectivity are fine from this host.');
else
{
    out('  How to read a failure:');
    out('    NETWORK     - DNS/TCP/TLS never completed. Server egress, firewall or DNS,');
    out('                  not the marketplace. Compare "peer" and "egress ip" with a host that works.');
    out('    AUTH        - reached the API, credentials rejected. Token rotated or expired.');
    out('    RATE LIMIT  - throttled; the app is calling too often, data will lag but is not lost.');
    out('    REMOTE      - 5xx from the marketplace. Wait it out, but check that the app does not');
    out('                  treat the failure as "no data" (that is how orders get skipped).');
    out('    PAYLOAD     - 2xx but not json, usually an HTML error/captcha page from a proxy.');
}

exit($fail === 0 ? 0 : 1);
?>
