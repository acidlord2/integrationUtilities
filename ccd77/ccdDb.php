<?php
namespace Ccd77;

/**
 * Read-only access to the ccd77 WordPress/WooCommerce database.
 *
 * Standalone on purpose: its own connection, its own credentials, its own charset. Nothing here
 * touches the integration's shared Db/config classes, so running it cannot affect the marketplace
 * scripts - and it can be pointed at a tunnel or a copy of the database without editing anything.
 *
 * Credentials are resolved in this order, first hit wins:
 *   1. constructor arguments
 *   2. environment - CCD77_DB_HOST, CCD77_DB_USER, CCD77_DB_PASSWORD, CCD77_DB_NAME, CCD77_DB_PORT
 *   3. the defaults below, taken from the site's wp-config.php
 *
 * WooCommerce stores orders in one of two completely different layouts and the site can switch
 * between them at any time, so the layout is detected at connect time rather than assumed:
 *   legacy - wp_posts (post_type=shop_order) + wp_postmeta
 *   hpos   - wp_wc_orders + wp_wc_order_addresses + wp_wc_order_operational_data
 * Order *lines* live in wp_woocommerce_order_items(+meta) in both, so those queries are shared.
 *
 * @author Georgy Polyan <acidlord@yandex.ru>
 */
class CcdDb
{
    // from wp-config.php of the ccd77 site - only reachable as localhost on the hosting server
    const DEFAULT_HOST = 'localhost';
    const DEFAULT_USER = 'u1003281_ccd77';
    const DEFAULT_PASSWORD = 'xB1sI6pX5dcX6dI9';
    const DEFAULT_NAME = 'u1003281_ccd77';

    /** statuses a customerorder should exist in MoySklad for - see backfillOrders.php */
    const STATUS_EXPORTED = array('wc-processing', 'wc-completed');

    private $conn;
    private $prefix;
    private $schema;
    private $timezone;

    public function __construct($host = null, $user = null, $password = null, $name = null, $prefix = 'wp_', $port = null)
    {
        $host     = $host     !== null ? $host     : (getenv('CCD77_DB_HOST')     ?: self::DEFAULT_HOST);
        $user     = $user     !== null ? $user     : (getenv('CCD77_DB_USER')     ?: self::DEFAULT_USER);
        $password = $password !== null ? $password : (getenv('CCD77_DB_PASSWORD') ?: self::DEFAULT_PASSWORD);
        $name     = $name     !== null ? $name     : (getenv('CCD77_DB_NAME')     ?: self::DEFAULT_NAME);
        $port     = $port     !== null ? (int)$port : (int)(getenv('CCD77_DB_PORT') ?: 3306);

        $this->prefix = $prefix;

        $this->conn = @mysqli_connect($host, $user, $password, $name, $port);
        if (!$this->conn)
            throw new \Exception('ccd77 db connect failed (' . $host . ':' . $port . '/' . $name . '): ' . mysqli_connect_error());

        // utf8mb4, not utf8: WordPress stores 4-byte characters and a utf8 connection turns an
        // emoji in a customer name or address into '?' on the way to MoySklad
        $this->conn->set_charset('utf8mb4');

        $this->schema = $this->detectSchema();
    }

    public function __destruct()
    {
        if ($this->conn)
            @mysqli_close($this->conn);
    }

    public function schema()
    {
        return $this->schema;
    }

    public function table($name)
    {
        return '`' . $this->prefix . $name . '`';
    }

    // ---------------------------------------------------------------------------- plumbing
    private function rows($sql)
    {
        $result = mysqli_query($this->conn, $sql);
        if ($result === false)
            throw new \Exception('query failed: ' . mysqli_error($this->conn) . ' | ' . $sql);

        $rows = array();
        while ($row = $result->fetch_assoc())
            $rows[] = $row;
        $result->close();
        return $rows;
    }

    private function tableExists($name)
    {
        return count($this->rows("SHOW TABLES LIKE '" . mysqli_real_escape_string($this->conn, $this->prefix . $name) . "'")) > 0;
    }

    /**
     * Only ever used with values this class produced itself, but ids are still forced to int so a
     * meta value that happens to look like sql can never reach a query.
     */
    private function intList($values)
    {
        $ints = array();
        foreach ($values as $value)
            $ints[] = (int)$value;
        $ints = array_values(array_unique(array_filter($ints)));
        return count($ints) ? implode(',', $ints) : '0';
    }

    /**
     * @return string - an "AND ... IN (...)" clause, or '' when no status filter was asked for
     */
    private function statusClause($column, $statuses)
    {
        if (!is_array($statuses) || !count($statuses))
            return '';

        $quoted = array();
        foreach ($statuses as $status)
        {
            // WooCommerce status slugs only - anything else is a caller mistake, not data
            if (!preg_match('/^[a-z0-9_\-]+$/', $status))
                throw new \Exception('refusing to query a status that is not a slug: ' . $status);
            $quoted[] = "'" . $status . "'";
        }

        return ' AND ' . $column . ' IN (' . implode(',', $quoted) . ')';
    }

    /**
     * hpos when the new tables exist and actually hold orders. A site mid-migration has both, and
     * then the new tables are authoritative - that is what WooCommerce itself reads.
     */
    private function detectSchema()
    {
        if (!$this->tableExists('wc_orders'))
            return 'legacy';

        $rows = $this->rows('SELECT COUNT(*) AS c FROM ' . $this->table('wc_orders') . " WHERE type = 'shop_order'");
        return (int)($rows[0]['c'] ?? 0) > 0 ? 'hpos' : 'legacy';
    }

    /**
     * The site's own timezone. Legacy order dates are stored in it, HPOS dates are stored in UTC,
     * and the WordPress plugin formats whatever get_date_created() returns - which is site time.
     * Getting this wrong shifts every backfilled order by the offset.
     */
    public function timezone()
    {
        if ($this->timezone !== null)
            return $this->timezone;

        $options = array();
        foreach ($this->rows('SELECT option_name, option_value FROM ' . $this->table('options')
                           . " WHERE option_name IN ('timezone_string', 'gmt_offset')") as $row)
            $options[$row['option_name']] = $row['option_value'];

        if (!empty($options['timezone_string']))
        {
            try
            {
                $this->timezone = new \DateTimeZone($options['timezone_string']);
                return $this->timezone;
            }
            catch (\Exception $e)
            {
                // fall through to the offset
            }
        }

        $offset = (float)($options['gmt_offset'] ?? 3);
        $sign = $offset < 0 ? '-' : '+';
        $offset = abs($offset);
        $this->timezone = new \DateTimeZone(sprintf('%s%02d:%02d', $sign, (int)$offset, round(($offset - (int)$offset) * 60)));
        return $this->timezone;
    }

    // ---------------------------------------------------------------------------- settings
    /**
     * The plugin's own settings table - MoySklad token, base url and the entity ids for
     * organization/project/state/store plus the delivery and payment custom entities.
     *
     * Reading them from here rather than from this repo's config keeps a backfilled order
     * identical to one the webhook created, even after somebody re-points the plugin.
     *
     * @return array - map setting_key => setting_value
     */
    public function settings()
    {
        if (!$this->tableExists('gms_settings'))
            throw new \Exception('table ' . $this->prefix . 'gms_settings is missing - is this the ccd77 database?');

        $settings = array();
        foreach ($this->rows('SELECT setting_key, setting_value FROM ' . $this->table('gms_settings')) as $row)
            $settings[$row['setting_key']] = $row['setting_value'];

        return $settings;
    }

    // ---------------------------------------------------------------------------- orders
    /**
     * Orders in the given WooCommerce statuses, oldest first, normalised into one shape whichever
     * layout the site uses.
     *
     * @param array $statuses - e.g. array('wc-processing')
     * @param int $sinceId - only orders with a higher id
     * @param string $sinceDate - 'Y-m-d H:i:s' in site time, only orders created at or after it
     * @param int $limit - 0 for no limit
     * @return array - list of orders: id, status, dateCreated, paymentMethod, discountTotal,
     *                 total, billing{firstName,lastName,phone,email,address1}, items[], shipping[]
     */
    public function findOrders($statuses = self::STATUS_EXPORTED, $sinceId = 0, $sinceDate = null, $limit = 0)
    {
        $orders = $this->schema === 'hpos'
            ? $this->findOrdersHpos($statuses, $sinceId, $sinceDate, $limit)
            : $this->findOrdersLegacy($statuses, $sinceId, $sinceDate, $limit);

        if (!count($orders))
            return $orders;

        $this->attachItems($orders);
        return $orders;
    }

    private function findOrdersLegacy($statuses, $sinceId, $sinceDate, $limit)
    {
        $sql = 'SELECT ID AS id, post_status AS status, post_date AS date_created'
             . ' FROM ' . $this->table('posts')
             . " WHERE post_type = 'shop_order'"
             . $this->statusClause('post_status', $statuses)
             . ' AND ID > ' . (int)$sinceId;

        if ($sinceDate !== null)
            $sql .= " AND post_date >= '" . mysqli_real_escape_string($this->conn, $sinceDate) . "'";

        $sql .= ' ORDER BY ID ASC';
        if ($limit > 0)
            $sql .= ' LIMIT ' . (int)$limit;

        $orders = array();
        $ids = array();
        foreach ($this->rows($sql) as $row)
        {
            $id = (int)$row['id'];
            $ids[] = $id;
            $orders[$id] = array(
                'id'            => $id,
                'status'        => $row['status'],
                'dateCreated'   => $row['date_created'],     // already site time
                'paymentMethod' => '',
                'discountTotal' => 0.0,
                'total'         => 0.0,
                'billing'       => array('firstName' => '', 'lastName' => '', 'phone' => '', 'email' => '', 'address1' => ''),
                'items'         => array(),
                'shipping'      => array()
            );
        }

        if (!count($ids))
            return array();

        $keys = array('_billing_first_name', '_billing_last_name', '_billing_phone', '_billing_email',
                      '_billing_address_1', '_payment_method', '_cart_discount', '_order_total');
        $quoted = array();
        foreach ($keys as $key)
            $quoted[] = "'" . $key . "'";

        $metaSql = 'SELECT post_id, meta_key, meta_value FROM ' . $this->table('postmeta')
                 . ' WHERE post_id IN (' . $this->intList($ids) . ')'
                 . ' AND meta_key IN (' . implode(',', $quoted) . ')';

        foreach ($this->rows($metaSql) as $row)
        {
            $id = (int)$row['post_id'];
            if (!isset($orders[$id]))
                continue;

            switch ($row['meta_key'])
            {
                case '_billing_first_name': $orders[$id]['billing']['firstName'] = $row['meta_value']; break;
                case '_billing_last_name':  $orders[$id]['billing']['lastName']  = $row['meta_value']; break;
                case '_billing_phone':      $orders[$id]['billing']['phone']     = $row['meta_value']; break;
                case '_billing_email':      $orders[$id]['billing']['email']     = $row['meta_value']; break;
                case '_billing_address_1':  $orders[$id]['billing']['address1']  = $row['meta_value']; break;
                case '_payment_method':     $orders[$id]['paymentMethod']        = $row['meta_value']; break;
                // WC_Order::get_total_discount() reads the discount_total prop, stored here
                case '_cart_discount':      $orders[$id]['discountTotal']        = (float)$row['meta_value']; break;
                case '_order_total':        $orders[$id]['total']                = (float)$row['meta_value']; break;
            }
        }

        return array_values($orders);
    }

    private function findOrdersHpos($statuses, $sinceId, $sinceDate, $limit)
    {
        $hasOperational = $this->tableExists('wc_order_operational_data');

        $sql = 'SELECT o.id AS id, o.status AS status, o.date_created_gmt AS date_created_gmt,'
             . ' o.payment_method AS payment_method, o.total_amount AS total_amount'
             . ($hasOperational ? ', od.discount_total_amount AS discount_total_amount' : '')
             . ' FROM ' . $this->table('wc_orders') . ' o'
             . ($hasOperational ? ' LEFT JOIN ' . $this->table('wc_order_operational_data') . ' od ON od.order_id = o.id' : '')
             . " WHERE o.type = 'shop_order'"
             . $this->statusClause('o.status', $statuses)
             . ' AND o.id > ' . (int)$sinceId;

        // the caller thinks in site time; this column is UTC
        if ($sinceDate !== null)
        {
            $utc = new \DateTime($sinceDate, $this->timezone());
            $utc->setTimezone(new \DateTimeZone('UTC'));
            $sql .= " AND o.date_created_gmt >= '" . $utc->format('Y-m-d H:i:s') . "'";
        }

        $sql .= ' ORDER BY o.id ASC';
        if ($limit > 0)
            $sql .= ' LIMIT ' . (int)$limit;

        $orders = array();
        $ids = array();
        foreach ($this->rows($sql) as $row)
        {
            $id = (int)$row['id'];
            $ids[] = $id;

            $date = new \DateTime($row['date_created_gmt'], new \DateTimeZone('UTC'));
            $date->setTimezone($this->timezone());

            $orders[$id] = array(
                'id'            => $id,
                'status'        => $row['status'],
                'dateCreated'   => $date->format('Y-m-d H:i:s'),
                'paymentMethod' => (string)$row['payment_method'],
                'discountTotal' => (float)($row['discount_total_amount'] ?? 0),
                'total'         => (float)$row['total_amount'],
                'billing'       => array('firstName' => '', 'lastName' => '', 'phone' => '', 'email' => '', 'address1' => ''),
                'items'         => array(),
                'shipping'      => array()
            );
        }

        if (!count($ids))
            return array();

        $addressSql = 'SELECT order_id, first_name, last_name, phone, email, address_1'
                    . ' FROM ' . $this->table('wc_order_addresses')
                    . ' WHERE order_id IN (' . $this->intList($ids) . ")"
                    . " AND address_type = 'billing'";

        foreach ($this->rows($addressSql) as $row)
        {
            $id = (int)$row['order_id'];
            if (!isset($orders[$id]))
                continue;

            $orders[$id]['billing'] = array(
                'firstName' => (string)$row['first_name'],
                'lastName'  => (string)$row['last_name'],
                'phone'     => (string)$row['phone'],
                'email'     => (string)$row['email'],
                'address1'  => (string)$row['address_1']
            );
        }

        return array_values($orders);
    }

    // ---------------------------------------------------------------------------- lines
    /**
     * Line items and shipping lines for the given orders, plus the sku of each line.
     *
     * Every meta row of a line is kept in ['meta']: WooCommerce has changed which key holds a
     * shipping cost more than once, so the caller can fall back instead of this class guessing.
     */
    private function attachItems(&$orders)
    {
        $byId = array();
        foreach ($orders as $index => $order)
            $byId[$order['id']] = $index;

        $itemSql = 'SELECT order_item_id, order_id, order_item_name, order_item_type'
                 . ' FROM ' . $this->table('woocommerce_order_items')
                 . ' WHERE order_id IN (' . $this->intList(array_keys($byId)) . ')'
                 . " AND order_item_type IN ('line_item', 'shipping')"
                 . ' ORDER BY order_item_id ASC';

        $items = $this->rows($itemSql);
        if (!count($items))
            return;

        $meta = array();
        foreach (array_chunk(array_column($items, 'order_item_id'), 2000) as $chunk)
        {
            $metaSql = 'SELECT order_item_id, meta_key, meta_value'
                     . ' FROM ' . $this->table('woocommerce_order_itemmeta')
                     . ' WHERE order_item_id IN (' . $this->intList($chunk) . ')';

            foreach ($this->rows($metaSql) as $row)
                $meta[(int)$row['order_item_id']][$row['meta_key']] = $row['meta_value'];
        }

        // resolve skus for every product and variation referenced by a line
        $productIds = array();
        foreach ($items as $item)
        {
            if ($item['order_item_type'] !== 'line_item')
                continue;
            $itemMeta = $meta[(int)$item['order_item_id']] ?? array();
            $productIds[] = (int)($itemMeta['_product_id'] ?? 0);
            $productIds[] = (int)($itemMeta['_variation_id'] ?? 0);
        }
        $skus = $this->skusFor($productIds);

        foreach ($items as $item)
        {
            $orderId = (int)$item['order_id'];
            if (!isset($byId[$orderId]))
                continue;

            $itemId = (int)$item['order_item_id'];
            $itemMeta = $meta[$itemId] ?? array();
            $index = $byId[$orderId];

            if ($item['order_item_type'] === 'shipping')
            {
                $orders[$index]['shipping'][] = array(
                    'itemId'   => $itemId,
                    'name'     => (string)$item['order_item_name'],
                    'methodId' => (string)($itemMeta['method_id'] ?? ''),
                    'qty'      => (int)($itemMeta['_qty'] ?? 1),
                    // 'total' on current WooCommerce, 'cost' on older installs
                    'total'    => (float)($itemMeta['total'] ?? $itemMeta['cost'] ?? 0),
                    'meta'     => $itemMeta
                );
                continue;
            }

            $productId = (int)($itemMeta['_product_id'] ?? 0);
            $variationId = (int)($itemMeta['_variation_id'] ?? 0);

            // WC_Product_Variation::get_sku() falls back to the parent sku when the variation has
            // none of its own, which is what the plugin's $product->get_sku() returned
            $sku = '';
            if ($variationId && isset($skus[$variationId]) && $skus[$variationId] !== '')
                $sku = $skus[$variationId];
            elseif ($productId && isset($skus[$productId]))
                $sku = $skus[$productId];

            $orders[$index]['items'][] = array(
                'itemId'      => $itemId,
                'name'        => (string)$item['order_item_name'],
                'productId'   => $productId,
                'variationId' => $variationId,
                'sku'         => (string)$sku,
                'qty'         => (int)($itemMeta['_qty'] ?? 0),
                'subtotal'    => (float)($itemMeta['_line_subtotal'] ?? 0),
                'total'       => (float)($itemMeta['_line_total'] ?? 0),
                'meta'        => $itemMeta
            );
        }
    }

    /**
     * sku per product/variation id, from the WooCommerce lookup table with wp_postmeta as the
     * fallback for rows the lookup table never got.
     *
     * @return array - map id => sku
     */
    private function skusFor($productIds)
    {
        $list = $this->intList($productIds);
        if ($list === '0')
            return array();

        $skus = array();

        if ($this->tableExists('wc_product_meta_lookup'))
            foreach ($this->rows('SELECT product_id, sku FROM ' . $this->table('wc_product_meta_lookup')
                               . ' WHERE product_id IN (' . $list . ')') as $row)
                if ((string)$row['sku'] !== '')
                    $skus[(int)$row['product_id']] = (string)$row['sku'];

        foreach ($this->rows('SELECT post_id, meta_value FROM ' . $this->table('postmeta')
                           . " WHERE meta_key = '_sku' AND post_id IN (" . $list . ')') as $row)
            if (!isset($skus[(int)$row['post_id']]) && (string)$row['meta_value'] !== '')
                $skus[(int)$row['post_id']] = (string)$row['meta_value'];

        return $skus;
    }

    /**
     * Everything the backfill needs, in one structure that survives a trip through a file.
     *
     * The shop database only listens on localhost of the hosting server, so the normal way to run
     * this is: export to json there, copy the file, and let ccd77/backfillOrders.php --from-json
     * do the MoySklad half from anywhere.
     *
     * ms_token is masked unless $includeToken: the file gets copied around, and MoySklad has to be
     * reachable from wherever the backfill runs anyway, so its token belongs there and not in here.
     *
     * @return array
     */
    public function export($statuses = array(), $sinceDate = null, $sinceId = 0, $limit = 0, $includeToken = false)
    {
        $settings = $this->settings();
        if (!$includeToken && isset($settings['ms_token']))
            $settings['ms_token'] = 'MASKED:' . strlen($settings['ms_token']) . ' chars';

        $orders = $this->findOrders($statuses, $sinceId, $sinceDate, $limit);

        return array(
            'generated'    => date('Y-m-d H:i:s T'),
            'source'       => array(
                'schema'   => $this->schema,
                'timezone' => $this->timezone()->getName(),
                'prefix'   => $this->prefix,
                'host'     => gethostname()
            ),
            'filters'      => array(
                'statuses' => count($statuses) ? $statuses : 'all',
                'since'    => $sinceDate,
                'sinceId'  => $sinceId,
                'limit'    => $limit
            ),
            'statusCounts' => $this->statusCounts(),
            'settings'     => $settings,
            'orderCount'   => count($orders),
            'orders'       => $orders
        );
    }

    /**
     * How many orders exist per status - the cheapest way to see what a backfill is about to face.
     *
     * @return array - map status => count
     */
    public function statusCounts()
    {
        $sql = $this->schema === 'hpos'
            ? 'SELECT status, COUNT(*) AS c FROM ' . $this->table('wc_orders') . " WHERE type = 'shop_order' GROUP BY status"
            : 'SELECT post_status AS status, COUNT(*) AS c FROM ' . $this->table('posts') . " WHERE post_type = 'shop_order' GROUP BY post_status";

        $counts = array();
        foreach ($this->rows($sql) as $row)
            $counts[$row['status']] = (int)$row['c'];

        arsort($counts);
        return $counts;
    }
}
?>
