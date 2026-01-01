<?php
/**
 * Email Template: CCD77 - Pickup with Cash Payment
 * Template for order pickup notification with cash payment
 * 
 * @param string $orderNumber Order number
 * @param float $orderAmount Order amount in rubles
 * @return array Array with 'body' and 'html' keys
 */

function getPickupOnlineEmailTemplate($orderNumber, $orderAmount) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/docker-config.php');
    $formattedAmount = number_format($orderAmount, 2, ',', ' ');
    
    // Get config values
    $address = CCD77_ADDRESS;
    $workingHours = CCD77_WORKING_HOURS;
    $phone = CCD77_PHONE;
    $email = CCD77_EMAIL;
    
    // Plain text body
    $body = "Ваш заказ готов к самовывозу!\n\n";
    $body .= "Номер заказа: {$orderNumber}\n\n";
    $body .= "Адрес самовывоза:\n";
    $body .= "{$address}\n\n";
    $body .= "Часы работы:\n";
    $body .= "{$workingHours}\n\n";
    $body .= "ЗАКАЗ ОПЛАЧЕН ОНЛАЙН\n";
    $body .= "Сумма заказа: {$formattedAmount} ₽\n\n";
    $body .= "Важно: При получении заказа, пожалуйста, проверьте комплектность и целостность товара. ";
    $body .= "Если у вас возникнут вопросы, наши специалисты всегда готовы помочь!\n\n";
    $body .= "Контакты:\n";
    $body .= "Телефон: {$phone}\n";
    $body .= "Email: {$email}\n\n";
    $body .= "С уважением,\n";
    $body .= "Команда CCD77.ru";
    
    // HTML body
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ #{$orderNumber} - CCD77.ru</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            background-color: #ffffff;
            padding: 30px 20px;
            text-align: center;
            border-bottom: 3px solid #4caf50;
        }
        .logo-text {
            color: #2e7d32;
            font-size: 32px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 2px;
        }
        .logo-text span {
            color: #4caf50;
        }
        .logo-subtitle {
            color: #4caf50;
            font-size: 12px;
            margin-top: 10px;
            letter-spacing: 1px;
        }
        .content {
            padding: 40px 30px;
        }
        .order-title {
            color: #2e7d32;
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 20px 0;
            text-align: center;
        }
        .order-number {
            color: #4caf50;
            font-size: 28px;
            font-weight: bold;
            text-align: center;
            margin: 0 0 30px 0;
        }
        .info-block {
            background-color: #f1f8f4;
            border-left: 4px solid #4caf50;
            padding: 20px;
            margin: 20px 0;
        }
        .info-title {
            color: #2e7d32;
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 10px 0;
        }
        .info-text {
            color: #1b5e20;
            font-size: 15px;
            margin: 5px 0;
            line-height: 1.8;
        }
        .payment-block {
            background-color: #e8f5e9;
            border: 2px solid #4caf50;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        .payment-title {
            color: #2e7d32;
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 10px 0;
        }
        .payment-amount {
            color: #1b5e20;
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        .payment-text {
            color: #2e7d32;
            font-size: 14px;
            margin: 10px 0 0 0;
        }
        .note {
            background-color: #fff9c4;
            border-left: 4px solid #fbc02d;
            padding: 15px;
            margin: 20px 0;
        }
        .note-text {
            color: #f57f17;
            font-size: 14px;
            margin: 0;
        }
        .footer {
            background-color: #1b5e20;
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
            font-size: 14px;
        }
        .footer a {
            color: #81c784;
            text-decoration: none;
        }
        .footer-divider {
            border: 0;
            border-top: 1px solid #2e7d32;
            margin: 20px 0;
        }
        .contact-info {
            margin: 15px 0;
        }
        .icon {
            display: inline-block;
            margin-right: 8px;
        }
        @media only screen and (max-width: 600px) {
            .content {
                padding: 30px 20px;
            }
            .order-title {
                font-size: 20px;
            }
            .order-number {
                font-size: 24px;
            }
            .payment-amount {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1 class="logo-text">CCD<span>77</span>.RU</h1>
            <div class="logo-subtitle">Корейская косметика и БАДы</div>
        </div>
        
        <!-- Content -->
        <div class="content">
            <h1 class="order-title">Ваш заказ готов к самовывозу!</h1>
            <div class="order-number">№ {$orderNumber}</div>
            
            <!-- Address Block -->
            <div class="info-block">
                <div class="info-title">📍 Адрес самовывоза:</div>
                <div class="info-text">{$address}</div>
            </div>
            
            <!-- Working Hours Block -->
            <div class="info-block">
                <div class="info-title">🕐 Часы работы:</div>
                <div class="info-text">{$workingHours}</div>
            </div>
            
            <!-- Payment Block -->
            <div class="payment-block">
                <div class="payment-title">💰 Заказ оплачен онлайн</div>
                <div class="payment-amount">{$formattedAmount} ₽</div>
            </div>
            
            <!-- Note -->
            <div class="note">
                <p class="note-text">
                    <strong>Важно:</strong> При получении заказа, пожалуйста, проверьте комплектность 
                    и целостность товара. Если у вас возникнут вопросы, наши специалисты всегда готовы помочь!
                </p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="contact-info">
                <strong>Контакты:</strong><br>
                📞 <a href="tel:{$phone}">{$phone}</a><br>
                ✉️ <a href="mailto:{$email}">{$email}</a>
            </div>
            
            <hr class="footer-divider">
            
            <div>
                <a href="https://ccd77.ru">ccd77.ru</a> - Корейская косметика и БАДы<br>
                © 2026 CCD77. Все права защищены.
            </div>
        </div>
    </div>
</body>
</html>
HTML;

    return [
        'body' => $body,
        'html' => $html
    ];
}
?>
