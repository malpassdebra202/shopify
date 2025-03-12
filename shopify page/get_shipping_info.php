<?php
session_start();
header('Content-Type: application/json');

// Return stored shipping info if available
echo json_encode([
    'shipping_info' => $_SESSION['shipping_info'] ?? null
]); 