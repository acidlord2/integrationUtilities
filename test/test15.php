<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Email/v1/EmailApi.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Email/v1/Email.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Email/Templates/ccd77-pickup-cash.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/docker-config.php');

echo "<h1>Email Send Test - Account: ccd77</h1>";

try {
    // Initialize EmailApi with account name 'ccd77'
    $emailApi = new \Classes\Email\v1\EmailApi('ccd77');
    
    echo "<h2>SMTP Configuration:</h2>";
    echo "<pre>";
    print_r($emailApi->getConfig());
    echo "</pre>";
    
    // Generate random test data
    $orderNumber = 'CCD-' . rand(10000, 99999);
    $orderAmount = rand(1000, 10000) + (rand(0, 99) / 100);
    
    // Generate email body and HTML using template
    $emailTemplate = getPickupCashEmailTemplate($orderNumber, $orderAmount);
    
    // Create test email
    $email = new \Classes\Email\v1\Email([
        'from' => 'no-reply@ccd77.ru',
        'to' => ['georgy.polyan@gmail.com'],
        'subject' => 'Заказ ' . $orderNumber . ' готов к самовывозу - CCD77.ru',
        'body' => $emailTemplate['body'],
        'htmlBody' => $emailTemplate['html']
    ]);
    
    echo "<h2>Email Details:</h2>";
    echo "<pre>";
    echo "From: " . $email->getFrom() . "\n";
    echo "To: " . implode(', ', $email->getTo()) . "\n";
    echo "Subject: " . $email->getSubject() . "\n";
    echo "Body: " . $email->getBody() . "\n";
    echo "</pre>";
    
    echo "<h2>Sending Email...</h2>";
    
    // Send email
    $result = $emailApi->sendEmail($email);
    
    echo "<h2>Result:</h2>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if ($result['success']) {
        echo "<p style='color: green; font-weight: bold;'>✓ Email sent successfully!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>✗ Failed to send email: " . $result['message'] . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>Exception: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p>Test completed at: " . date('Y-m-d H:i:s') . "</p>";
