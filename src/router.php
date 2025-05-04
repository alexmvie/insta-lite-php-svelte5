<?php
/**
 * Simple Router for Insta-Lite
 * 
 * This file routes requests to the appropriate files in the src directory.
 */

// Map of routes to their corresponding files
$routes = [
    '/' => '/src/pages/timeline.php',  // Redirect home to timeline
    '/timeline' => '/src/pages/timeline.php',
    '/profile' => '/src/pages/profile.php',
    '/create-post' => '/src/pages/create-post.php',
    '/login' => '/src/pages/login.php',
    '/register' => '/src/pages/register.php',
    '/verify' => '/src/pages/verify.php',
    '/logout' => '/src/auth/logout.php',
    '/verification-pending' => '/src/pages/verification-pending.php',
    
    // Debug routes
    '/debug/mail' => '/src/debug/debugmail.php',
    '/debug/session' => '/src/debug/session-test.php',
    '/debug/verification' => '/src/debug/test-verification.php',
    '/debug/generate-token' => '/src/debug/generate-token.php',
    '/debug/db' => '/src/debug/test-db.php',
    '/debug/session-info' => '/src/debug/session-info.php',
];

// Get the requested URI
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove trailing slash if it exists
$request_uri = rtrim($request_uri, '/');

// Default to home page if empty
if (empty($request_uri)) {
    $request_uri = '/';
}

// Check if the route exists
if (isset($routes[$request_uri])) {
    // Include the corresponding file
    require_once APP_ROOT . $routes[$request_uri];
    exit;
}

// Check if it's a direct file access with .php extension
$php_request = $request_uri . '.php';
if (isset($routes[$php_request])) {
    require_once APP_ROOT . $routes[$php_request];
    exit;
}

// Handle 404 - Page not found
header('HTTP/1.0 404 Not Found');
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-96 text-center">
            <h1 class="text-2xl font-bold mb-6">404 - Page Not Found</h1>
            <p class="mb-4">The page you are looking for does not exist.</p>
            <a href="/" class="text-blue-600 hover:text-blue-800">Go to Home</a>
        </div>
    </div>
</body>
</html>';
exit;