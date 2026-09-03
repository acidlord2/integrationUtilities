<?php
namespace Classes\Common;

/**
 * Client-side rate limiter for MoySklad.
 *
 * On 2026-09-03 MoySklad disabled JSON API access for the integration user: "более 400 запросов
 * за минуту, которые завершились ошибкой 429". Not 400 requests - 400 *rejected* ones, which is
 * what an unbounded retry loop produces. The clients here retried 1049/1073 in `while (true)`
 * with a flat 1 s sleep, so the moment MoySklad asked for less traffic they gave it more, and the
 * account was cut off.
 *
 * So the limit is enforced here rather than discovered from 429s. Two windows:
 *   - a per-second cap, because MoySklad counts requests per second per user
 *   - a per-minute cap, because that is the window the account was suspended on
 *
 * State lives in one file under sys_get_temp_dir(), guarded by flock, so **every PHP process on
 * the box shares the budget**. That matters: the crons overlap, and a per-process limiter would
 * let five simultaneous runs send five times the intended rate.
 *
 * Fails open. A throttle that breaks the integration when its own state file is unreadable is
 * worse than the problem it solves - it just stops throttling and says so in the log.
 *
 * @author Georgy Polyan <acidlord@yandex.ru>
 */
class MsThrottle
{
    /** requests allowed in any one second, across all processes */
    const PER_SECOND = 3;

    /** requests allowed in any 60 s window, across all processes */
    const PER_MINUTE = 120;

    /** never wait longer than this for a slot; beyond it, let the caller through and log */
    const MAX_WAIT_SECONDS = 20;

    private static $log = null;
    private static $disabled = false;

    private static function statePath()
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ms-throttle.json';
    }

    private static function log($message)
    {
        if (self::$log === null)
        {
            require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
            self::$log = new Log('classes - Common - MsThrottle.log');
        }
        self::$log->write($message);
    }

    /**
     * Blocks until a request may be sent. Call immediately before the curl_exec.
     *
     * @param string $context - what is being called, for the log when a wait is long
     */
    public static function acquire($context = '')
    {
        if (self::$disabled)
            return;

        $waited = 0.0;

        while (true)
        {
            $wait = self::reserve();

            if ($wait === false)          // could not read/lock state - stop throttling
            {
                self::$disabled = true;
                self::log('throttle disabled (state file unusable) - ' . self::statePath());
                return;
            }

            if ($wait <= 0)
            {
                if ($waited >= 1.0)
                    self::log(sprintf('waited %.1fs for a slot%s', $waited,
                        $context !== '' ? ' - ' . $context : ''));
                return;
            }

            if ($waited + $wait > self::MAX_WAIT_SECONDS)
            {
                self::log(sprintf('giving up waiting after %.1fs, letting the call through%s',
                    $waited, $context !== '' ? ' - ' . $context : ''));
                return;
            }

            usleep((int)($wait * 1000000));
            $waited += $wait;
        }
    }

    /**
     * Records a request if there is room, otherwise returns how long to wait.
     *
     * @return float|false seconds to wait (0 = go ahead), or false if the limiter cannot work
     */
    private static function reserve()
    {
        $path = self::statePath();
        $handle = @fopen($path, 'c+');
        if ($handle === false)
            return false;

        if (!@flock($handle, LOCK_EX))
        {
            fclose($handle);
            return false;
        }

        $raw = stream_get_contents($handle);
        $stamps = json_decode((string)$raw, true);
        if (!is_array($stamps))
            $stamps = array();

        $now = microtime(true);

        // drop anything outside the longest window we care about
        $stamps = array_values(array_filter($stamps, function ($t) use ($now) {
            return is_numeric($t) && $t > $now - 60;
        }));

        $lastSecond = 0;
        foreach ($stamps as $t)
            if ($t > $now - 1)
                $lastSecond++;

        $wait = 0.0;

        if ($lastSecond >= self::PER_SECOND)
        {
            // wait until the oldest request inside the 1 s window falls out of it
            $oldestInSecond = $now;
            foreach ($stamps as $t)
                if ($t > $now - 1 && $t < $oldestInSecond)
                    $oldestInSecond = $t;
            $wait = max($wait, ($oldestInSecond + 1) - $now);
        }

        if (count($stamps) >= self::PER_MINUTE)
        {
            $oldest = min($stamps);
            $wait = max($wait, ($oldest + 60) - $now);
        }

        if ($wait <= 0)
        {
            $stamps[] = $now;
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($stamps));
            fflush($handle);
        }

        flock($handle, LOCK_UN);
        fclose($handle);

        // round up slightly so a rounding error cannot spin
        return $wait <= 0 ? 0.0 : min($wait + 0.01, 60.0);
    }

    /**
     * MoySklad sometimes says how long to back off. Honour it rather than guessing.
     *
     * @param array $headers - curl_getinfo does not give headers, so pass what was captured
     * @return int seconds, or 0
     */
    public static function retryAfter($headers)
    {
        foreach ((array)$headers as $name => $value)
        {
            $lower = strtolower((string)$name);
            if ($lower === 'x-lognex-retry-after')
                return (int)ceil(((int)$value) / 1000);   // milliseconds
            if ($lower === 'retry-after')
                return (int)$value;
        }
        return 0;
    }
}

?>
