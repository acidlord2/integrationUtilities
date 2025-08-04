<?php
namespace Classes\Ccd77\v2;

/**
 * Class ProductApi
 * Handles operations for CCD77 products, including fetching and caching.
 */
class ProductApi {
    private $log;
    private $db;

    /**
     * ProductApi constructor.
     * Initializes logging.
     */
    public function __construct() {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Db.php');
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');

        $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
        $logName .= '.log';
        $this->log = new \Classes\Common\Log($logName);

        $this->db = new \Classes\Common\Db(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE_CCD77);
    }

    /**
     * Fetches product data from the database using wp_wc_product_meta_lookup table, with optional filtering.
     * @param array $filters Optional associative array of filters (column => value)
     * @return array Array of product data
     */
    public function fetchProducts(array $filters = []) {
        $where = [];
        foreach ($filters as $column => $value) {
            if (preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
                // Escape value for SQL
                $escaped = is_numeric($value) ? $value : "'" . $this->db->connection->real_escape_string($value) . "'";
                $where[] = "$column = $escaped";
            }
        }
        $sql = "SELECT * FROM `wp_wc_product_meta_lookup`";
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $this->log->write(__LINE__ . ' '. __METHOD__ . ' - SQL query: ' . $sql);
        $products = $this->db->execQueryArray($sql);
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
