<?php
/**
 * Queue Service - Processes queue messages with API calls
 * 
 * Features:
 * - Single instance protection (prevents multiple services running)
 * - Processes queue messages in order by ID
 * - Supports multiple APIs (MS, Sportmaster)
 * - Time limit protection (180 seconds)
 * - Comprehensive logging
 * 
 * Usage:
 * php queue-service.php
 * 
 * @author Integration Helper
 */

// Prevent script timeout
set_time_limit(300);

// Load required classes
$docRoot = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 1);
require_once($docRoot . '/classes/Queue/Queue.php');
require_once($docRoot . '/classes/Common/Log.php');

class QueueService
{
    private $queue;
    private $log;
    private $startTime;
    private $maxExecutionTime = 180; // 3 minutes
    private $lockFile;
    private $supportedApis = ['ms', 'sportmaster'];
    
    public function __construct()
    {
        $this->startTime = time();
        $this->lockFile = $_SERVER['DOCUMENT_ROOT'] . '/queue-service.lock';
        
        // Initialize logging
        $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
        $logName .= '.log';
        $this->log = new \Classes\Common\Log($logName);
        
        // Initialize queue
        $this->queue = new \Queue\Queue();
        
        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Queue Service initialized');
    }
    
    /**
     * Check if another instance is already running
     * 
     * @return bool True if already running, false otherwise
     */
    private function isAlreadyRunning()
    {
        if (!file_exists($this->lockFile)) {
            return false;
        }
        
        $lockData = file_get_contents($this->lockFile);
        $lockInfo = json_decode($lockData, true);
        
        if (!$lockInfo || !isset($lockInfo['pid'])) {
            // Invalid lock file, remove it
            unlink($this->lockFile);
            return false;
        }
        
        // Check if process is still running (Windows compatible)
        $pid = $lockInfo['pid'];
        
        // For Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = shell_exec("tasklist /FI \"PID eq $pid\" 2>NUL");
            if (strpos($output, (string)$pid) !== false) {
                return true;
            }
        } else {
            // For Unix/Linux
            if (posix_kill($pid, 0)) {
                return true;
            }
        }
        
        // Process not running, remove stale lock file
        unlink($this->lockFile);
        return false;
    }
    
    /**
     * Create lock file to prevent multiple instances
     */
    private function createLockFile()
    {
        $lockData = [
            'pid' => getmypid(),
            'started' => date('Y-m-d H:i:s'),
            'timestamp' => time()
        ];
        
        file_put_contents($this->lockFile, json_encode($lockData, JSON_PRETTY_PRINT));
        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Lock file created with PID: ' . getmypid());
    }
    
    /**
     * Remove lock file
     */
    private function removeLockFile()
    {
        if (file_exists($this->lockFile)) {
            unlink($this->lockFile);
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Lock file removed');
        }
    }
    
    /**
     * Check if execution time limit has been exceeded
     * 
     * @return bool True if time limit exceeded
     */
    private function isTimeExceeded()
    {
        return (time() - $this->startTime) >= $this->maxExecutionTime;
    }
    
    /**
     * Get remaining execution time in seconds
     * 
     * @return int Remaining seconds
     */
    private function getRemainingTime()
    {
        return $this->maxExecutionTime - (time() - $this->startTime);
    }
    
    /**
     * Process all queue messages
     */
    public function processQueue()
    {
        // Check if already running
        if ($this->isAlreadyRunning()) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Service already running, exiting');
            echo "Queue service is already running. Exiting.\n";
            return false;
        }
        
        // Create lock file
        $this->createLockFile();
        
        try {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Starting queue processing');
            echo "Queue Service started at " . date('Y-m-d H:i:s') . "\n";
            echo "Max execution time: {$this->maxExecutionTime} seconds\n\n";
            
            // Get all queue messages ordered by ID
            $messages = $this->getAllQueueMessages();
            
            if (empty($messages)) {
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - No queue messages to process');
                echo "No messages in queue.\n";
                return true;
            }
            
            $totalMessages = count($messages);
            $processedCount = 0;
            
            echo "Found $totalMessages messages to process\n\n";
            $this->log->write(__LINE__ . ' ' . __METHOD__ . " - Found $totalMessages messages to process");
            
            // Process each message
            foreach ($messages as $message) {
                // Check time limit before processing each message
                if ($this->isTimeExceeded()) {
                    $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Time limit exceeded, stopping processing');
                    echo "\nTime limit exceeded. Stopping processing.\n";
                    echo "Processed $processedCount out of $totalMessages messages.\n";
                    break;
                }
                
                $remainingTime = $this->getRemainingTime();
                echo "Processing message ID: {$message['id']} (Time remaining: {$remainingTime}s)\n";
                
                $success = $this->processMessage($message);
                $processedCount++;
                
                if ($success) {
                    // Remove processed message from queue
                    $this->queue->delete($message['id']);
                    echo "✅ Message {$message['id']} processed and removed from queue\n\n";
                } else {
                    echo "❌ Message {$message['id']} processing failed, kept in queue\n\n";
                }
            }
            
            echo "Queue processing completed. Processed: $processedCount messages\n";
            $this->log->write(__LINE__ . ' ' . __METHOD__ . " - Queue processing completed. Processed: $processedCount messages");
            
            return true;
            
        } catch (Exception $e) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Exception: ' . $e->getMessage());
            echo "Error: " . $e->getMessage() . "\n";
            return false;
        } finally {
            // Always remove lock file
            $this->removeLockFile();
        }
    }
    
    /**
     * Get all queue messages ordered by ID
     * 
     * @return array Array of queue messages
     */
    private function getAllQueueMessages()
    {
        try {
            // Get oldest messages first (ordered by timestamp, then by ID)
            $messages = $this->queue->getOldest(1000); // Limit to 1000 messages per run
            
            // Sort by ID to ensure proper order
            usort($messages, function($a, $b) {
                return $a['id'] <=> $b['id'];
            });
            
            return $messages;
        } catch (Exception $e) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Error getting queue messages: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Process a single queue message
     * 
     * @param array $message Queue message data
     * @return bool True if successful, false otherwise
     */
    private function processMessage($message)
    {
        try {
            $payload = $message['payload'];
            $messageId = $message['id'];
            
            // Validate required payload fields
            if (!$this->validatePayload($payload)) {
                $this->log->write(__LINE__ . ' ' . __METHOD__ . " - Invalid payload for message $messageId");
                return false;
            }
            
            $api = strtolower($payload['api']);
            $organization = $payload['organization'] ?? null;
            $url = $payload['url'];
            $body = $payload['body'] ?? null;
            $method = $payload['method'] ?? 'GET';
            
            echo "  API: $api\n";
            echo "  Organization: $organization\n";
            echo "  Method: $method\n";
            echo "  URL: $url\n";
            
            $this->log->write(__LINE__ . ' ' . __METHOD__ . " - Processing message $messageId: API=$api, URL=$url");
            
            // Route to appropriate API handler
            switch ($api) {
                case 'ms':
                    return $this->processMsApi($messageId, $organization, $url, $body, $method);
                
                case 'sportmaster':
                    return $this->processSportmasterApi($messageId, $organization, $url, $body, $method);
                
                default:
                    $this->log->write(__LINE__ . ' ' . __METHOD__ . " - Unsupported API: $api");
                    echo "  ❌ Unsupported API: $api\n";
                    return false;
            }
            
        } catch (Exception $e) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Error processing message: ' . $e->getMessage());
            echo "  ❌ Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Validate payload structure
     * 
     * @param array $payload Message payload
     * @return bool True if valid
     */
    private function validatePayload($payload)
    {
        if (!is_array($payload)) {
            return false;
        }
        
        // Required fields
        $required = ['api', 'url', 'method'];
        foreach ($required as $field) {
            if (!isset($payload[$field]) || empty($payload[$field])) {
                return false;
            }
        }
        
        // For post, patch, put methods, body should be mandatory
        $method = strtoupper($payload['method']);
        if (in_array($method, ['POST', 'PATCH', 'PUT']) && empty($payload['body'])) {
            return false;
        }

        // Check if API is supported
        if (!in_array(strtolower($payload['api']), $this->supportedApis)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Process MS (MoySklad) API call
     * 
     * @param int $messageId Message ID
     * @param string $organization Organization identifier
     * @param string $url API endpoint URL
     * @param mixed $body Request body
     * @param string $method HTTP method
     * @return bool Success status
     */
    private function processMsApi($messageId, $organization, $url, $body, $method = 'GET')
    {
        try {
            echo "  🔄 Calling MS API...\n";
            
            // Load MS API class
            $docRoot = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 1);
            $msApiFile = $docRoot . '/classes/MS/v2/Api.php';
            if (file_exists($msApiFile)) {
                require_once($msApiFile);
                
                // Initialize MS API
                $msApi = new \MS\v2\Api();
                
                // Use the appropriate method based on HTTP method
                $result = false;
                switch (strtoupper($method)) {
                    case 'GET':
                        $result = $msApi->getData($url);
                        break;
                    case 'POST':
                        $result = $msApi->postData($url, $body);
                        break;
                    case 'PUT':
                        $result = $msApi->putData($url, $body);
                        break;
                    case 'PATCH':
                        $result = $msApi->patchData($url, $body);
                        break;
                    case 'DELETE':
                        $result = $msApi->deleteData($url);
                        break;
                    default:
                        echo "  ❌ Unsupported HTTP method: $method\n";
                        return false;
                }
                
                if ($result !== false) {
                    echo "  ✅ MS API call successful\n";
                    $this->log->write(__LINE__ . ' ' . __METHOD__ . " - MS API call successful for message $messageId");
                    return true;
                } else {
                    echo "  ❌ MS API call failed\n";
                    return false;
                }
            } else {
                echo "  ❌ MS API class not found at: $msApiFile\n";
                return false;
            }
            
        } catch (Exception $e) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . " - MS API error for message $messageId: " . $e->getMessage());
            echo "  ❌ MS API error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Process Sportmaster API call
     * 
     * @param int $messageId Message ID
     * @param string $organization Organization identifier
     * @param string $url API endpoint URL
     * @param mixed $body Request body
     * @param string $method HTTP method
     * @return bool Success status
     */
    private function processSportmasterApi($messageId, $organization, $url, $body, $method = 'GET')
    {
        try {
            echo "  🔄 Calling Sportmaster API...\n";
            
            // Load Sportmaster API class
            $docRoot = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 1);
            $sportmasterApiFile = $docRoot . '/classes/Sportmaster/v2/Api.php';
            if (file_exists($sportmasterApiFile)) {
                require_once($sportmasterApiFile);
                
                // Initialize Sportmaster API with clientId (organization)
                // If no organization provided, use a default or throw error
                if (empty($organization)) {
                    echo "  ❌ Organization (clientId) is required for Sportmaster API\n";
                    return false;
                }
                
                $sportmasterApi = new \Classes\Sportmaster\v2\Api($organization);
                
                // Use the appropriate method based on HTTP method
                $result = false;
                switch (strtoupper($method)) {
                    case 'GET':
                        $result = $sportmasterApi->getData($url);
                        break;
                    case 'POST':
                        $result = $sportmasterApi->postData($url, $body);
                        break;
                    case 'PUT':
                        $result = $sportmasterApi->putData($url, $body);
                        break;
                    case 'PATCH':
                        $result = $sportmasterApi->patchData($url, $body);
                        break;
                    case 'DELETE':
                        $result = $sportmasterApi->deleteData($url);
                        break;
                    default:
                        echo "  ❌ Unsupported HTTP method for Sportmaster: $method\n";
                        return false;
                }
                
                if ($result !== false) {
                    echo "  ✅ Sportmaster API call successful\n";
                    $this->log->write(__LINE__ . ' ' . __METHOD__ . " - Sportmaster API call successful for message $messageId");
                    return true;
                } else {
                    echo "  ❌ Sportmaster API call failed\n";
                    return false;
                }
            } else {
                echo "  ❌ Sportmaster API class not found at: $sportmasterApiFile\n";
                return false;
            }
            
        } catch (Exception $e) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . " - Sportmaster API error for message $messageId: " . $e->getMessage());
            echo "  ❌ Sportmaster API error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Make HTTP API call using cURL
     * 
     * @param string $url API endpoint URL
     * @param string $method HTTP method
     * @param mixed $body Request body
     * @param array $headers HTTP headers
     * @return mixed Response data or false on failure
     */
    private function makeApiCall($url, $method = 'GET', $body = null, $headers = [])
    {
        $startTime = microtime(true);
        
        try {
            $curl = curl_init();
            
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_CUSTOMREQUEST => strtoupper($method),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Integration Helper Queue Service/1.0'
            ]);
            
            if ($body !== null && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
                if (is_array($body) || is_object($body)) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
                } else {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                }
            }
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($error) {
                $this->log->write(__LINE__ . ' ' . __METHOD__ . " - cURL error: $error");
                echo "  ❌ cURL error: $error\n";
                return false;
            }
            
            echo "  📊 HTTP $httpCode (${executionTime}ms)\n";
            
            if ($httpCode >= 200 && $httpCode < 300) {
                $this->log->write(__LINE__ . ' ' . __METHOD__ . " - API call successful: HTTP $httpCode, ${executionTime}ms");
                return $response;
            } else {
                $this->log->write(__LINE__ . ' ' . __METHOD__ . " - API call failed: HTTP $httpCode, ${executionTime}ms");
                echo "  ❌ HTTP Error $httpCode\n";
                return false;
            }
            
        } catch (Exception $e) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' - Exception in API call: ' . $e->getMessage());
            echo "  ❌ Exception: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    echo "=== Queue Service ===\n";
    echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";
    
    $service = new QueueService();
    $success = $service->processQueue();
    
    echo "\nService completed at: " . date('Y-m-d H:i:s') . "\n";
    exit($success ? 0 : 1);
} else {
    echo "This script must be run from command line.\n";
    exit(1);
}
?>