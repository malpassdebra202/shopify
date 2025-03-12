<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$sessionsFile = 'sessions.json';

if (isset($data['sessionId']) && isset($data['status'])) {
    if (file_exists($sessionsFile)) {
        $sessions = json_decode(file_get_contents($sessionsFile), true) ?? [];
        
        if (isset($sessions[$data['sessionId']])) {
            // Update the session status
            $sessions[$data['sessionId']]['status'] = $data['status'];
            $sessions[$data['sessionId']]['timestamp'] = time();
            
            // Save the updated sessions
            file_put_contents($sessionsFile, json_encode($sessions), LOCK_EX);
            
            echo json_encode(['success' => true]);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'error' => 'Failed to update session']); 