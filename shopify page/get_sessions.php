<?php
session_start();
header('Content-Type: application/json');

// In a real application, you'd use a database
// For demonstration, we'll use a file
$sessionsFile = 'sessions.json';

if (file_exists($sessionsFile)) {
    $sessions = json_decode(file_get_contents($sessionsFile), true);
} else {
    $sessions = [];
}

// Clean up old sessions (older than 10 minutes)
$sessions = array_filter($sessions, function($session) {
    return time() - $session['timestamp'] < 600;
});

file_put_contents($sessionsFile, json_encode($sessions));

echo json_encode(array_values($sessions)); 