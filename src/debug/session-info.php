<?php
session_start();
require_once 'includes/auth.php';

// If user is not logged in, redirect to login
if (!Auth::isLoggedIn()) {
    header('Location: /login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Info - Insta-Lite</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-96">
            <h1 class="text-2xl font-bold mb-6 text-center">Session Information</h1>
            
            <div class="space-y-4">
                <?php if (empty($_SESSION)): ?>
                    <p class="text-center text-gray-600">No session data available</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($_SESSION as $key => $value): ?>
                            <div class="flex justify-between border-b border-gray-200 py-2">
                                <span class="font-medium text-gray-700"><?php echo htmlspecialchars($key); ?></span>
                                <span class="text-gray-600"><?php echo is_array($value) ? 'Array' : htmlspecialchars($value); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-6 text-center">
                <a href="/" class="text-indigo-600 hover:text-indigo-500">Back to Home</a>
            </div>
        </div>
    </div>

    <?php
    // Include debug footer
    define('DEBUG_ENABLED', true);
    require_once 'includes/debug-footer.php';
    ?>
</body>
</html>
