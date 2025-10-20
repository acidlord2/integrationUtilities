<?php
/**
 * Queue Service Monitor - Check service status and queue statistics
 */

// Set document root for CLI usage
if (!isset($_SERVER['DOCUMENT_ROOT']) || empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = '/var/www/html';
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Queue/Queue.php');

echo "=== Queue Service Monitor ===\n";
echo "Generated at: " . date('Y-m-d H:i:s') . "\n\n";

// Check if service is running
$lockFile = $_SERVER['DOCUMENT_ROOT'] . '/queue-service.lock';
$isRunning = false;
$lockInfo = null;

if (file_exists($lockFile)) {
    $lockData = file_get_contents($lockFile);
    $lockInfo = json_decode($lockData, true);
    
    if ($lockInfo && isset($lockInfo['pid'])) {
        // Check if process is still running
        $pid = $lockInfo['pid'];
        
        // For Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = shell_exec("tasklist /FI \"PID eq $pid\" 2>NUL");
            $isRunning = strpos($output, (string)$pid) !== false;
        } else {
            // For Unix/Linux
            $isRunning = posix_kill($pid, 0);
        }
    }
}

// Service Status
echo "🔍 Service Status:\n";
if ($isRunning && $lockInfo) {
    echo "  ✅ RUNNING (PID: {$lockInfo['pid']})\n";
    echo "  🕐 Started: {$lockInfo['started']}\n";
    $runningTime = time() - $lockInfo['timestamp'];
    echo "  ⏱️  Running for: " . gmdate("H:i:s", $runningTime) . "\n";
} else {
    echo "  ❌ NOT RUNNING\n";
    if (file_exists($lockFile)) {
        echo "  ⚠️  Stale lock file exists\n";
    }
}
echo "\n";

// Queue Statistics
try {
    $queue = new \Queue\Queue();
    
    echo "📊 Queue Statistics:\n";
    $totalMessages = $queue->count();
    echo "  📋 Total messages: $totalMessages\n";
    
    if ($totalMessages > 0) {
        // Get sample messages to analyze
        $messages = $queue->getOldest(10);
        
        // Count by API
        $apiCounts = [];
        $oldestTimestamp = null;
        $newestTimestamp = null;
        
        foreach ($messages as $message) {
            $api = $message['payload']['api'] ?? 'unknown';
            $apiCounts[$api] = ($apiCounts[$api] ?? 0) + 1;
            
            if ($oldestTimestamp === null || $message['timestamp'] < $oldestTimestamp) {
                $oldestTimestamp = $message['timestamp'];
            }
            if ($newestTimestamp === null || $message['timestamp'] > $newestTimestamp) {
                $newestTimestamp = $message['timestamp'];
            }
        }
        
        echo "  📊 Messages by API:\n";
        foreach ($apiCounts as $api => $count) {
            echo "    - $api: $count messages\n";
        }
        
        if ($oldestTimestamp) {
            echo "  🕐 Oldest message: " . date('Y-m-d H:i:s', $oldestTimestamp) . "\n";
        }
        
        echo "\n📋 Next messages to process:\n";
        $displayCount = min(5, count($messages));
        for ($i = 0; $i < $displayCount; $i++) {
            $msg = $messages[$i];
            $api = $msg['payload']['api'] ?? 'unknown';
            $desc = $msg['payload']['description'] ?? 'No description';
            $time = date('H:i:s', $msg['timestamp']);
            echo "  {$msg['id']}: [$api] $desc ($time)\n";
        }
        
        if ($totalMessages > 5) {
            echo "  ... and " . ($totalMessages - 5) . " more\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error getting queue statistics: " . $e->getMessage() . "\n";
}

echo "\n";

// Recent log entries (if available)
$logFile = $_SERVER['DOCUMENT_ROOT'] . '/logs/queue-service.log';
if (file_exists($logFile)) {
    echo "📜 Recent log entries:\n";
    $logLines = file($logFile);
    $recentLines = array_slice($logLines, -5);
    foreach ($recentLines as $line) {
        echo "  " . trim($line) . "\n";
    }
} else {
    echo "📜 No log file found\n";
}

echo "\n=== Monitor Complete ===\n";
?>