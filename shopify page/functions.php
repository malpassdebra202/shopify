<?php

/**
 * Format price with currency symbol
 */
function formatPrice($price) {
    global $config;
    $currency = $config['store']['currency'] ?? 'USD';
    
    // Format price to 2 decimal places
    $formattedPrice = number_format((float)$price, 2, '.', '');
    
    // Add currency symbol based on currency code
    switch ($currency) {
        case 'EUR':
            return '€' . $formattedPrice;
        case 'GBP':
            return '£' . $formattedPrice;
        case 'USD':
        default:
            return '$' . $formattedPrice;
    }
}

/**
 * Get product total (before shipping)
 */
function getProductTotal() {
    global $config;
    return $config['product']['price'] ?? 0;
}

/**
 * Get order total (including shipping)
 */
function getOrderTotal() {
    global $config;
    $total = getProductTotal();
    
    // Add shipping cost if not free
    $shipping = $config['product']['shipping']['price'] ?? 'FREE';
    if ($shipping !== 'FREE') {
        $total += (float)$shipping;
    }
    
    return $total;
} 