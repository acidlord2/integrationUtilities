<?php
namespace Classes\Ccd77\v2;

/**
 * Class ProductApi
 * Handles operations for CCD77 products, including fetching and caching.
 */
class ProductApi {
    private $log;

    /**
     * ProductApi constructor.
     * Initializes logging.
     */
    public function __construct() {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');

        $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
        $logName .= '.log';
        $this->log = new \Classes\Common\Log($logName);
    }

    /**
     * Fetches product data from the database using wp_wc_product_meta_lookup table, with optional filtering.
     * @param array $filters Optional associative array of filters (column => value)
     * @return array Array of product data
     */
    public function fetchProducts(array $filters = []) {
        $products = [];
        $mysqli = new \mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        if ($mysqli->connect_errno) {
            $this->log->write(__LINE__ . ' '. __METHOD__ . ' DB connect error: ' . $mysqli->connect_error);
            return $products;
        }
        $sql = "SELECT product_id, sku, virtual, downloadable, min_price, max_price, onsale, stock_quantity, stock_status, rating_count, average_rating, total_sales, tax_status, tax_class, global_unique_id FROM wp_wc_product_meta_lookup";
        $where = [];
        $params = [];
        foreach ($filters as $column => $value) {
            // Only allow valid columns
            if (preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
                $where[] = "$column = ?";
                $params[] = $value;
            }
        }
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $stmt = $mysqli->prepare($sql);
        if ($stmt && $params) {
            // Dynamically bind params
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $mysqli->query($sql);
        }
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            $result->free();
        } else {
            $this->log->write(__LINE__ . ' '. __METHOD__ . ' SQL error: ' . $mysqli->error);
        }
        $mysqli->close();
        $this->log->write(__LINE__ . ' '. __METHOD__ . ' - fetched ' . count($products) . ' products');
        return $products;
    }

    /**
     * Returns an iterator for the products, with optional filtering.
     * @param array $filters Optional associative array of filters (column => value)
     * @return ProductIterator
     */
    public function getProductIterator(array $filters = []) {
        $products = $this->fetchProducts($filters);
        return new ProductIterator($products);
    }
}
