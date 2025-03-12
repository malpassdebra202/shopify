<?php
session_start();
header('Content-Type: application/json');

$sessionsFile = 'sessions.json';
$sessionId = $_GET['session'] ?? '';

error_log("Checking redirect for session: " . $sessionId);

if (file_exists($sessionsFile)) {
    $sessions = json_decode(file_get_contents($sessionsFile), true);
    error_log("Current sessions: " . print_r($sessions, true));
    
    if (isset($sessions[$sessionId])) {
        $redirect = $sessions[$sessionId]['redirect'] ?? null;
        error_log("Found redirect value: " . $redirect);
        
        if ($redirect) {
            $sessions[$sessionId]['redirect'] = null;
            file_put_contents($sessionsFile, json_encode($sessions), LOCK_EX);
            error_log("Cleared redirect flag for session: " . $sessionId);
        }
        
        echo json_encode(['redirect' => $redirect]);
        exit;
    }
    error_log("Session not found: " . $sessionId);
}

echo json_encode(['redirect' => null]); 