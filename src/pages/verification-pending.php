<?php
session_start();
require_once APP_ROOT . '/api/auth.php';

// If user is already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insta-Lite - Verify Email</title>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-96">
            <h1 class="text-2xl font-bold mb-6 text-center">Check Your Email</h1>
            
            <div class="text-center">
                <p class="mb-4">We've sent you an email with a verification link. Please check your inbox and click the link to verify your account.</p>
                <p class="text-sm text-gray-600">Didn't receive the email? Check your spam folder or <a href="/register" class="text-indigo-600 hover:text-indigo-500">try registering again</a>.</p>
            </div>
        </div>
    </div>
</body>
</html>
