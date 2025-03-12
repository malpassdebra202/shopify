<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$sessionsFile = 'sessions.json';

error_log("Received redirect request: " . print_r($data, true));

if (file_exists($sessionsFile)) {
    $sessions = json_decode(file_get_contents($sessionsFile), true);
} else {
    $sessions = [];
}

if (isset($data['sessionId']) && isset($data['action'])) {
    if (isset($sessions[$data['sessionId']])) {
        // Update session status
        $sessions[$data['sessionId']]['status'] = $data['action'];
        $sessions[$data['sessionId']]['timestamp'] = time();
        
        error_log("Updating session: " . print_r($sessions[$data['sessionId']], true));
        
        // Write to file immediately
        if (file_put_contents($sessionsFile, json_encode($sessions), LOCK_EX)) {
            error_log("Successfully updated sessions file");
            echo json_encode(['success' => true]);
        } else {
            error_log("Failed to write to sessions file");
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update session']);
        }
    } else {
        error_log("Session not found: " . $data['sessionId']);
        http_response_code(404);
        echo json_encode(['error' => 'Session not found']);
    }
} else {
    error_log("Invalid request data");
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
} 