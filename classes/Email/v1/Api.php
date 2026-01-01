<?php
namespace Classes\Email\v1;

/**
 * SMTP Email API Class
 * Base layer for sending emails via SMTP protocol
 * 
 * @author Georgy Polyan <acidlord@yandex.ru>
 */
class Api
{
    private $log;
    private $host;
    private $port;
    private $username;
    private $password;
    private $encryption;
    private $timeout;
    private $socket;
    private $lastError;

    /**
     * Constructor
     * @param string $accountName Account identifier for retrieving credentials from settings
     * @param array $config Optional configuration overrides
     */
    public function __construct($accountName, array $config = [])
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Settings.php');
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
        
		$logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
		$logName .= '.log';
        $this->log = new \Classes\Common\Log($logName);
        
        // Retrieve SMTP credentials from settings
        $usernameClass = new \Classes\Common\Settings('email_username_' . $accountName);
        $passwordClass = new \Classes\Common\Settings('email_password_' . $accountName);
        $hostClass = new \Classes\Common\Settings('email_host_' . $accountName);
        $portClass = new \Classes\Common\Settings('email_port_' . $accountName);
        
        $this->username = $usernameClass->getValue();
        $this->password = $passwordClass->getValue();
        $this->host = $hostClass->getValue();
        $this->port = (int)($portClass->getValue());
        
        // Get host and port from settings, with config overrides and defaults
        // Auto-detect encryption: port 465 uses SSL, port 587 uses TLS
        if (!isset($config['encryption'])) {
            $this->encryption = ($this->port == 465) ? 'ssl' : 'tls';
        } else {
            $this->encryption = $config['encryption'];
        }
        $this->timeout = $config['timeout'] ?? 30;
        
        $this->log->write(__LINE__ . ' __construct - SMTP initialized for account: ' . $accountName);
        $this->log->write(__LINE__ . ' Host: ' . $this->host . ', Port: ' . $this->port);
    }

    /**
     * Send email via SMTP
     * @param \Classes\Email\v1\Email $email Email object
     * @return bool Success status
     */
    public function send($email)
    {
        try {
            $this->log->write(__LINE__ . ' send - Starting email send process');
            
            // Validate email object
            if (!$email instanceof \Classes\Email\v1\Email) {
                throw new \InvalidArgumentException('Email parameter must be an instance of Email class');
            }
            
            // Validate required fields
            if (empty($email->getFrom())) {
                throw new \InvalidArgumentException('From address is required');
            }
            
            if (empty($email->getTo())) {
                throw new \InvalidArgumentException('To address is required');
            }
            
            // Connect to SMTP server
            if (!$this->connect()) {
                return false;
            }
            
            // Send EHLO/HELO
            if (!$this->sendCommand('EHLO ' . $this->host, 250)) {
                return false;
            }
            
            // Start TLS if encryption is enabled
            if ($this->encryption === 'tls') {
                if (!$this->startTLS()) {
                    return false;
                }
            }
            
            // Authenticate
            if (!$this->authenticate()) {
                return false;
            }
            
            // Send MAIL FROM
            $from = $this->extractEmail($email->getFrom());
            if (!$this->sendCommand('MAIL FROM:<' . $from . '>', 250)) {
                return false;
            }
            
            // Send RCPT TO for all recipients
            $recipients = array_merge(
                $email->getTo(),
                $email->getCc(),
                $email->getBcc()
            );
            
            foreach ($recipients as $recipient) {
                $rcptEmail = $this->extractEmail($recipient);
                if (!$this->sendCommand('RCPT TO:<' . $rcptEmail . '>', 250)) {
                    $this->log->write(__LINE__ . ' Failed to add recipient: ' . $rcptEmail);
                    continue;
                }
            }
            
            // Send DATA command
            if (!$this->sendCommand('DATA', 354)) {
                return false;
            }
            
            // Send email headers and body
            $message = $this->buildMessage($email);
            if (!$this->sendData($message)) {
                return false;
            }
            
            // End data with .
            if (!$this->sendCommand('.', 250)) {
                return false;
            }
            
            // Send QUIT
            $this->sendCommand('QUIT', 221);
            
            // Close connection
            $this->disconnect();
            
            $this->log->write(__LINE__ . ' send - Email sent successfully');
            return true;
            
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->log->write(__LINE__ . ' send - Error: ' . $e->getMessage());
            $this->disconnect();
            return false;
        }
    }

    /**
     * Connect to SMTP server
     * @return bool Success status
     */
    private function connect()
    {
        $this->log->write(__LINE__ . ' connect - Connecting to ' . $this->host . ':' . $this->port . ' with encryption: ' . $this->encryption);
        
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        // Use ssl:// protocol for SSL (port 465), plain connection for TLS (port 587)
        $protocol = ($this->encryption === 'ssl') ? 'ssl://' : '';
        $this->socket = @stream_socket_client(
            $protocol . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$this->socket) {
            $this->lastError = "Failed to connect: $errstr ($errno)";
            $this->log->write(__LINE__ . ' ' . $this->lastError);
            return false;
        }
        
        stream_set_timeout($this->socket, $this->timeout);
        
        // Read greeting
        $response = $this->readResponse();
        $this->log->write(__LINE__ . ' Server greeting: ' . $response);
        
        if (!$this->checkResponseCode($response, 220)) {
            $this->lastError = 'Invalid server greeting: ' . $response;
            $this->log->write(__LINE__ . ' ' . $this->lastError);
            return false;
        }
        
        return true;
    }

    /**
     * Start TLS encryption
     * @return bool Success status
     */
    private function startTLS()
    {
        if (!$this->sendCommand('STARTTLS', 220)) {
            return false;
        }
        
        $crypto = stream_socket_enable_crypto(
            $this->socket,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );
        
        if (!$crypto) {
            $this->lastError = 'Failed to enable TLS encryption';
            $this->log->write(__LINE__ . ' ' . $this->lastError);
            return false;
        }
        
        // Send EHLO again after TLS
        return $this->sendCommand('EHLO ' . $this->host, 250);
    }

    /**
     * Authenticate with SMTP server
     * @return bool Success status
     */
    private function authenticate()
    {
        $this->log->write(__LINE__ . ' authenticate - Authenticating as: ' . $this->username);
        
        // Send AUTH LOGIN
        if (!$this->sendCommand('AUTH LOGIN', 334)) {
            return false;
        }
        
        // Send username
        if (!$this->sendCommand(base64_encode($this->username), 334)) {
            $this->lastError = 'Authentication failed: invalid username';
            return false;
        }
        
        // Send password
        if (!$this->sendCommand(base64_encode($this->password), 235)) {
            $this->lastError = 'Authentication failed: invalid password';
            return false;
        }
        
        $this->log->write(__LINE__ . ' authenticate - Authentication successful');
        return true;
    }

    /**
     * Build email message with headers
     * @param \Classes\Email\v1\Email $email
     * @return string Complete message
     */
    private function buildMessage($email)
    {
        $headers = [];
        
        // From header
        $headers[] = 'From: ' . $email->getFrom();
        
        // To header
        if (!empty($email->getTo())) {
            $headers[] = 'To: ' . implode(', ', $email->getTo());
        }
        
        // CC header
        if (!empty($email->getCc())) {
            $headers[] = 'Cc: ' . implode(', ', $email->getCc());
        }
        
        // Subject header
        $headers[] = 'Subject: =?UTF-8?B?' . base64_encode($email->getSubject()) . '?=';
        
        // Date header
        $headers[] = 'Date: ' . date('r');
        
        // MIME headers
        $boundary = '----=_Part_' . md5(uniqid());
        $headers[] = 'MIME-Version: 1.0';
        
        if ($email->getHtmlBody()) {
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=' . $email->getCharset();
            $headers[] = 'Content-Transfer-Encoding: 8bit';
        }
        
        // Additional headers
        foreach ($email->getHeaders() as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }
        
        // Build message body
        $message = implode("\r\n", $headers) . "\r\n\r\n";
        
        if ($email->getHtmlBody()) {
            // Multipart message
            $message .= '--' . $boundary . "\r\n";
            $message .= 'Content-Type: text/plain; charset=' . $email->getCharset() . "\r\n";
            $message .= 'Content-Transfer-Encoding: 8bit' . "\r\n\r\n";
            $message .= $email->getBody() . "\r\n\r\n";
            
            $message .= '--' . $boundary . "\r\n";
            $message .= 'Content-Type: text/html; charset=' . $email->getCharset() . "\r\n";
            $message .= 'Content-Transfer-Encoding: 8bit' . "\r\n\r\n";
            $message .= $email->getHtmlBody() . "\r\n\r\n";
            
            $message .= '--' . $boundary . '--';
        } else {
            // Plain text message
            $message .= $email->getBody();
        }
        
        return $message;
    }

    /**
     * Send SMTP command
     * @param string $command Command to send
     * @param int $expectedCode Expected response code
     * @return bool Success status
     */
    private function sendCommand($command, $expectedCode)
    {
        $this->log->write(__LINE__ . ' sendCommand - Sending: ' . $command);
        
        fwrite($this->socket, $command . "\r\n");
        $response = $this->readResponse();
        
        $this->log->write(__LINE__ . ' sendCommand - Response: ' . $response);
        
        return $this->checkResponseCode($response, $expectedCode);
    }

    /**
     * Send email data
     * @param string $data Email content
     * @return bool Success status
     */
    private function sendData($data)
    {
        // Replace single dots at the beginning of lines with double dots (RFC 5321)
        $data = preg_replace('/^\./m', '..', $data);
        
        fwrite($this->socket, $data . "\r\n");
        return true;
    }

    /**
     * Read response from SMTP server
     * @return string Server response
     */
    private function readResponse()
    {
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return trim($response);
    }

    /**
     * Check if response code matches expected code
     * @param string $response Server response
     * @param int $expectedCode Expected code
     * @return bool Match status
     */
    private function checkResponseCode($response, $expectedCode)
    {
        $code = (int)substr($response, 0, 3);
        return $code === $expectedCode;
    }

    /**
     * Extract email address from "Name <email@example.com>" format
     * @param string $email Email string
     * @return string Clean email address
     */
    private function extractEmail($email)
    {
        if (preg_match('/<(.+?)>/', $email, $matches)) {
            return $matches[1];
        }
        return $email;
    }

    /**
     * Disconnect from SMTP server
     */
    private function disconnect()
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
            $this->log->write(__LINE__ . ' disconnect - Connection closed');
        }
    }

    /**
     * Get last error message
     * @return string Error message
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Get SMTP configuration
     * @return array Configuration details
     */
    public function getConfig()
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'encryption' => $this->encryption,
            'timeout' => $this->timeout
        ];
    }
}
