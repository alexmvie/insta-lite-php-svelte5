<?php
// Start session
session_start();

// Clear all session data for testing
if (isset($_GET['clear'])) {
    $_SESSION = array();
    session_destroy();
    header('Location: /debug/session');
    exit;
}

// Set test session data
if (isset($_GET['set'])) {
    $_SESSION['test_data'] = 'This is test session data set at ' . date('Y-m-d H:i:s');
    header('Location: /debug/session');
    exit;
}

// Display session info
$session_id = session_id();
$session_status = session_status();
$session_status_text = '';

switch ($session_status) {
    case PHP_SESSION_DISABLED:
        $session_status_text = 'Sessions are disabled';
        break;
    case PHP_SESSION_NONE:
        $session_status_text = 'Sessions are enabled but none exists';
        break;
    case PHP_SESSION_ACTIVE:
        $session_status_text = 'Sessions are enabled and one exists';
        break;
}

// Get PHP configuration for sessions
$session_save_path = ini_get('session.save_path');
$session_use_cookies = ini_get('session.use_cookies');
$session_use_only_cookies = ini_get('session.use_only_cookies');
$session_cookie_lifetime = ini_get('session.cookie_lifetime');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Test - Insta-Lite</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6 text-center">Session Test</h1>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="p-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-xl font-semibold">Session Information</h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <p><strong>Session ID:</strong> <?php echo $session_id ?: 'None'; ?></p>
                    <p><strong>Session Status:</strong> <?php echo $session_status_text; ?> (<?php echo $session_status; ?>)</p>
                </div>
                
                <div>
                    <h3 class="text-lg font-medium mb-2">Session Configuration</h3>
                    <p><strong>Save Path:</strong> <?php echo $session_save_path ?: 'Default'; ?></p>
                    <p><strong>Use Cookies:</strong> <?php echo $session_use_cookies ? 'Yes' : 'No'; ?></p>
                    <p><strong>Use Only Cookies:</strong> <?php echo $session_use_only_cookies ? 'Yes' : 'No'; ?></p>
                    <p><strong>Cookie Lifetime:</strong> <?php echo $session_cookie_lifetime; ?> seconds</p>
                </div>
                
                <div>
                    <h3 class="text-lg font-medium mb-2">Session Data</h3>
                    <?php if (!empty($_SESSION)): ?>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <pre class="whitespace-pre-wrap"><?php print_r($_SESSION); ?></pre>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500">No session data available</p>
                    <?php endif; ?>
                </div>
                
                <div class="flex space-x-4">
                    <a href="?set=1" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Set Test Data</a>
                    <a href="?clear=1" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Clear Session</a>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="p-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-xl font-semibold">Cookie Information</h2>
            </div>
            <div class="p-6">
                <?php if (!empty($_COOKIE)): ?>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <pre class="whitespace-pre-wrap"><?php print_r($_COOKIE); ?></pre>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">No cookies available</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-6 text-center">
            <a href="/debug/mail" class="text-blue-600 hover:text-blue-900 mr-4">Debug Mail</a>
            <a href="/login" class="text-blue-600 hover:text-blue-900 mr-4">Login Page</a>
            <a href="/" class="text-blue-600 hover:text-blue-900">Home</a>
        </div>
    </div>
</body>
</html>
