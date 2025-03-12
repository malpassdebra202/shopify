<?php
// Load configuration
$config = require __DIR__ . '/config.php';
if (!is_array($config)) {
    $config = array(
        'store' => array(
            'name' => 'Store Name',
            'logo' => '/img/logo.jpg',
            'currency' => 'USD',
            'theme_color' => '#FFBABA',
            'footer_year' => date('Y'),
        ),
        'product' => array(
            'name' => 'Product Name',
            'price' => 0.00,
            'size' => '',
            'image' => '/img/product.jpg',
            'shipping' => array(
                'method' => 'Standard Shipping',
                'price' => 'FREE',
            ),
        ),
    );
}

// Load helper functions
require_once __DIR__ . '/functions.php'; 