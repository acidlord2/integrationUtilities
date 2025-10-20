<?php
/**
 * Docker-compatible configuration for the application
 * This file will override database settings when running in Docker
 */

// Ensure timezone is set
date_default_timezone_set('Europe/Moscow');

// Try multiple ways to get environment variables (CLI vs web context)
function getDockerEnv($key) {
    // Try getenv() first
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }
    
    // Try $_ENV superglobal
    if (isset($_ENV[$key])) {
        return $_ENV[$key];
    }
    
    // Try $_SERVER superglobal (sometimes env vars are here in web context)
    if (isset($_SERVER[$key])) {
        return $_SERVER[$key];
    }
    
    return false;
}

// Use environment variables for database configuration in Docker first
$dockerHostname = getDockerEnv('DB_HOSTNAME');
if ($dockerHostname !== false) {
    define('DB_HOSTNAME', $dockerHostname);
    define('DB_USERNAME', getDockerEnv('DB_USERNAME'));
    define('DB_PASSWORD', getDockerEnv('DB_PASSWORD'));
    define('DB_DATABASE', getDockerEnv('DB_DATABASE'));
    
    // Load the rest of config.php but skip database constants
    $content = file_get_contents(__DIR__ . '/config.php');
    // Remove the database constant definitions to avoid conflicts
    $content = preg_replace('/define\s*\(\s*[\'"]DB_(HOSTNAME|USERNAME|PASSWORD|DATABASE)[\'"].*?\);/m', '', $content);
    eval('?>' . $content);
} else {
    // Not in Docker, use regular config
    include_once(__DIR__ . '/config.php');
}
?>