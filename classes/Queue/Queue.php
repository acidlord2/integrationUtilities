<?php

namespace Queue;

/**
 * Queue class for managing queue operations
 * 
 * @author Georgy Polyan <acidlord@yandex.ru>
 */
class Queue
{
    private $db;
    private $log;
    
    public function __construct()
    {
        $docroot = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 2);
        // Load configuration first (needed for DB constants)
        require_once($docroot . '/docker-config.php');
        require_once($docroot . '/classes/Common/Db.php');
        require_once($docroot . '/classes/Common/Log.php');
        
        $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($docroot, '', __FILE__)), " -");
        $logName .= '.log';
        $this->log = new \Classes\Common\Log($logName);
        
        $this->db = new \Classes\Common\Db();
    }
    
    /**
     * Safely escape string for SQL query
     * 
     * @param string $string The string to escape
     * @return string Escaped string
     */
    private function escapeString($string)
    {
        // Since we can't access the private connection, we'll use addslashes as a fallback
        // This is not as secure as mysqli_real_escape_string but better than nothing
        return addslashes($string);
    }
    
    /**
     * Create a new queue item
     * 
     * @param string $transactionId Binary transaction ID (16 bytes)
     * @param array $payload JSON payload data
     * @param int|null $timestamp Optional timestamp in milliseconds, defaults to current time
     * @return int|false The inserted ID or false on failure
     */
    public function create($transactionId, $payload, $timestamp = null)
    {
        try {
            if ($timestamp === null) {
                $timestamp = round(microtime(true) * 1000); // Convert to milliseconds
            }
            
            $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($payloadJson === false) {
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Failed to encode payload to JSON');
                return false;
            }
            
            // Use escapeString for safety
            $escapedTransactionId = $this->escapeString($transactionId);
            $escapedPayload = $this->escapeString($payloadJson);
            $sql = "INSERT INTO queue (timestamp, transaction_id, payload) VALUES ($timestamp, '$escapedTransactionId', '$escapedPayload')";
            $result = $this->db->execQuery($sql);
            if ($result) {
                $insertId = $this->db->getLastInsertId();
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Created queue item with ID: ' . $insertId);
                return $insertId;
            } else {
                // Log the specific MySQL error
                $mysqlError = $this->db->getLastError();
                $mysqlErrno = $this->db->getLastErrorNumber();
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - MySQL Error (' . $mysqlErrno . '): ' . $mysqlError);
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Failed SQL: ' . $sql);
                return false;
            }
        } catch (Exception $e) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Read a queue item by ID
     * 
     * @param int $id Queue item ID
     * @return array|false Queue item data or false if not found
     */
    public function read($id)
    {
        try {
            $sql = "SELECT id, timestamp, transaction_id, payload FROM queue WHERE id = " . (int)$id;
            $result = $this->db->execQueryArray($sql);
            
            if (!empty($result)) {
                $item = $result[0];
                $item['payload'] = json_decode($item['payload'], true);
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Read queue item ID: ' . $id);
                return $item;
            }
            
            return false;
        } catch (Exception $e) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a queue item
     * 
     * @param int $id Queue item ID
     * @param array $data Array of fields to update (timestamp, transaction_id, payload)
     * @return bool True on success, false on failure
     */
    public function update($id, $data)
    {
        try {
            $allowedFields = ['timestamp', 'transaction_id', 'payload'];
            $updateFields = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    if ($field === 'payload') {
                        $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        if ($value === false) {
                            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Failed to encode payload to JSON');
                            return false;
                        }
                    }
                    
                    if ($field === 'timestamp') {
                        $updateFields[] = $field . ' = ' . (int)$value;
                    } else {
                        $escapedValue = $this->escapeString($value);
                        $updateFields[] = $field . " = '" . $escapedValue . "'";
                    }
                }
            }
            
            if (empty($updateFields)) {
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - No valid fields to update');
                return false;
            }
            
            $sql = "UPDATE queue SET " . implode(', ', $updateFields) . " WHERE id = " . (int)$id;
            $result = $this->db->execQuery($sql);
            
            if ($result) {
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Updated queue item ID: ' . $id);
                return true;
            } else {
                // Log the specific MySQL error
                $mysqlError = $this->db->getLastError();
                $mysqlErrno = $this->db->getLastErrorNumber();
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - MySQL Error (' . $mysqlErrno . '): ' . $mysqlError);
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Failed SQL: ' . $sql);
                return false;
            }
        } catch (Exception $e) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a queue item
     * 
     * @param int $id Queue item ID
     * @return bool True on success, false on failure
     */
    public function delete($id)
    {
        try {
            $sql = "DELETE FROM queue WHERE id = " . (int)$id;
            $result = $this->db->execQuery($sql);
            
            if ($result) {
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Deleted queue item ID: ' . $id);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find queue items by transaction ID
     * 
     * @param string $transactionId Binary transaction ID (16 bytes)
     * @return array Array of queue items
     */
    public function findByTransactionId($transactionId)
    {
        try {
            $escapedTransactionId = $this->escapeString($transactionId);
            $sql = "SELECT id, timestamp, transaction_id, payload FROM queue WHERE transaction_id = '$escapedTransactionId' ORDER BY timestamp ASC";
            $result = $this->db->execQueryArray($sql);
            
            if (!empty($result)) {
                foreach ($result as &$item) {
                    $item['payload'] = json_decode($item['payload'], true);
                }
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Found ' . count($result) . ' items for transaction');
                return $result;
            }
            
            return [];
        } catch (Exception $e) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Exception: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get queue items within a timestamp range
     * 
     * @param int $fromTimestamp Start timestamp in milliseconds
     * @param int $toTimestamp End timestamp in milliseconds
     * @param int $limit Maximum number of items to return
     * @return array Array of queue items
     */
    public function getByTimeRange($fromTimestamp, $toTimestamp, $limit = 1000)
    {
        try {
            $sql = "SELECT id, timestamp, transaction_id, payload FROM queue 
                    WHERE timestamp >= " . (int)$fromTimestamp . " AND timestamp <= " . (int)$toTimestamp . " 
                    ORDER BY timestamp ASC LIMIT " . (int)$limit;
            $result = $this->db->execQueryArray($sql);
            
            if (!empty($result)) {
                foreach ($result as &$item) {
                    $item['payload'] = json_decode($item['payload'], true);
                }
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Found ' . count($result) . ' items in time range');
                return $result;
            }
            
            return [];
        } catch (Exception $e) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Exception: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get oldest queue items
     * 
     * @param int $limit Maximum number of items to return
     * @return array Array of queue items
     */
    public function getOldest($limit = 100)
    {
        try {
            $sql = "SELECT id, timestamp, transaction_id, payload FROM queue 
                    ORDER BY timestamp ASC LIMIT " . (int)$limit;
            $result = $this->db->execQueryArray($sql);
            
            if (!empty($result)) {
                foreach ($result as &$item) {
                    $item['payload'] = json_decode($item['payload'], true);
                }
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Retrieved ' . count($result) . ' oldest items');
                return $result;
            }
            
            return [];
        } catch (Exception $e) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Exception: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Count total items in queue
     * 
     * @param string|null $transactionId Optional transaction ID to count items for specific transaction
     * @return int Number of items in queue
     */
    public function count($transactionId = null)
    {
        try {
            if ($transactionId !== null) {
                $escapedTransactionId = $this->escapeString($transactionId);
                $sql = "SELECT COUNT(*) as count FROM queue WHERE transaction_id = '$escapedTransactionId'";
            } else {
                $sql = "SELECT COUNT(*) as count FROM queue";
            }
            
            $result = $this->db->execQueryArray($sql);
            
            if (!empty($result)) {
                return (int)$result[0]['count'];
            }
            
            return 0;
        } catch (Exception $e) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Exception: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Clear old queue items before specified timestamp
     * 
     * @param int $beforeTimestamp Clear items older than this timestamp in milliseconds
     * @return int Number of deleted items
     */
    public function clearOld($beforeTimestamp)
    {
        try {
            // First count how many items will be deleted
            $countSql = "SELECT COUNT(*) as count FROM queue WHERE timestamp < " . (int)$beforeTimestamp;
            $countResult = $this->db->execQueryArray($countSql);
            $itemCount = !empty($countResult) ? (int)$countResult[0]['count'] : 0;
            
            if ($itemCount > 0) {
                $sql = "DELETE FROM queue WHERE timestamp < " . (int)$beforeTimestamp;
                $result = $this->db->execQuery($sql);
                
                if ($result) {
                    $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Cleared ' . $itemCount . ' old items');
                    return $itemCount;
                }
            }
            
            return 0;
        } catch (Exception $e) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Exception: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Generate a UUID v4 for transaction ID
     * 
     * @return string 16-byte binary UUID
     */
    public static function generateTransactionId()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant bits
        return $data;
    }
    
    /**
     * Convert binary UUID to string representation
     * 
     * @param string $binary 16-byte binary UUID
     * @return string UUID string
     */
    public static function binaryUuidToString($binary)
    {
        $hex = bin2hex($binary);
        return substr($hex, 0, 8) . '-' . 
               substr($hex, 8, 4) . '-' . 
               substr($hex, 12, 4) . '-' . 
               substr($hex, 16, 4) . '-' . 
               substr($hex, 20, 12);
    }
    
    /**
     * Convert string UUID to binary representation
     * 
     * @param string $uuid UUID string
     * @return string 16-byte binary UUID
     */
    public static function stringUuidToBinary($uuid)
    {
        $hex = str_replace('-', '', $uuid);
        return hex2bin($hex);
    }
}