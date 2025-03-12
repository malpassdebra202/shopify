<?php
session_start();
header('Content-Type: application/json');

// Return stored payment info if available
echo json_encode([
    'payment_info' => $_SESSION['payment_info'] ?? null
]); 