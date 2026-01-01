<?php
namespace Classes\Email\v1;

/**
 * Email API Class
 * High-level interface for sending emails
 * 
 * @author Georgy Polyan <acidlord@yandex.ru>
 */
class EmailApi
{
    private $log;
    private $smtpApi;
    private $accountName;

    /**
     * Constructor
     * @param string $accountName Account identifier for retrieving SMTP credentials
     * @param array $config Optional SMTP configuration overrides
     */
    public function __construct($accountName, array $config = [])
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Email/v1/Api.php');
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Email/v1/Email.php');
        
        $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
        $logName .= '.log';
        $this->log = new \Classes\Common\Log($logName);
        
        $this->accountName = $accountName;
        
        // Initialize SMTP API
        $this->smtpApi = new \Classes\Email\v1\Api($accountName, $config);
        
        $this->log->write(__LINE__ . ' __construct - EmailApi initialized for account: ' . $accountName);
    }

    /**
     * Send email
     * @param \Classes\Email\v1\Email $email Email object to send
     * @return array Result with success status and message
     */
    public function sendEmail($email)
    {
        try {
            $this->log->write(__LINE__ . ' sendEmail - Starting email send');
            
            // Validate email object
            if (!$email instanceof \Classes\Email\v1\Email) {
                throw new \InvalidArgumentException('Email parameter must be an instance of Email class');
            }
            
            // Log email details
            $this->log->write(__LINE__ . ' sendEmail - From: ' . $email->getFrom());
            $this->log->write(__LINE__ . ' sendEmail - To: ' . json_encode($email->getTo(), JSON_UNESCAPED_UNICODE));
            $this->log->write(__LINE__ . ' sendEmail - Subject: ' . $email->getSubject());
            
            // Send via SMTP API
            $result = $this->smtpApi->send($email);
            
            if ($result) {
                $this->log->write(__LINE__ . ' sendEmail - Email sent successfully');
                return [
                    'success' => true,
                    'message' => 'Email sent successfully',
                    'account' => $this->accountName
                ];
            } else {
                $error = $this->smtpApi->getLastError();
                $this->log->write(__LINE__ . ' sendEmail - Failed to send email: ' . $error);
                return [
                    'success' => false,
                    'message' => 'Failed to send email: ' . $error,
                    'account' => $this->accountName
                ];
            }
            
        } catch (\Exception $e) {
            $this->log->write(__LINE__ . ' sendEmail - Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'account' => $this->accountName
            ];
        }
    }

    /**
     * Send multiple emails
     * @param array $emails Array of Email objects
     * @return array Results for each email
     */
    public function sendBulk(array $emails)
    {
        $this->log->write(__LINE__ . ' sendBulk - Sending ' . count($emails) . ' emails');
        
        $results = [];
        foreach ($emails as $index => $email) {
            $this->log->write(__LINE__ . ' sendBulk - Processing email ' . ($index + 1) . ' of ' . count($emails));
            $results[] = $this->sendEmail($email);
        }
        
        $successCount = count(array_filter($results, function($r) { return $r['success']; }));
        $this->log->write(__LINE__ . ' sendBulk - Completed: ' . $successCount . ' successful, ' . (count($results) - $successCount) . ' failed');
        
        return [
            'success' => $successCount === count($results),
            'total' => count($results),
            'successful' => $successCount,
            'failed' => count($results) - $successCount,
            'results' => $results
        ];
    }

    /**
     * Get SMTP configuration
     * @return array Configuration details
     */
    public function getConfig()
    {
        return $this->smtpApi->getConfig();
    }

    /**
     * Get account name
     * @return string Account name
     */
    public function getAccountName()
    {
        return $this->accountName;
    }
}
