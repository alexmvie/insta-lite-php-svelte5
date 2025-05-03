<?php
// Start a new session or resume the existing one
session_start();

// Include the auth class
require_once APP_ROOT . '/src/auth/auth.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once APP_ROOT . '/src/config/database.php';
    $db = new Database();
    $conn = $db->getConnection();

    try {
        $stmt = $conn->prepare("SELECT id, username, password_hash, verified FROM users WHERE username = ?");
        $stmt->execute([$_POST['username']]);
        $user = $stmt->fetch();

        if ($user && password_verify($_POST['password'], $user['password_hash'])) {
            if ($user['verified']) {
                // Set session variables directly
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['last_activity'] = time();
                
                // Redirect to home page
                header('Location: /');
                exit;
            } else {
                $error = "Account not verified. Please check your email for verification link.";
            }
        } else {
            $error = "Invalid username or password.";
        }
    } catch(PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        $error = "An error occurred during login. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insta-Lite - Login</title>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-96">
            <h1 class="text-2xl font-bold mb-6 text-center">Insta-Lite</h1>

            <?php if (isset($error)): ?>
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" name="username" id="username" required 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" id="password" required 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <button type="submit" 
                        class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Login
                </button>

                <p class="mt-4 text-center text-sm text-gray-600">
                    Don't have an account?
                    <a href="/register" class="font-medium text-indigo-600 hover:text-indigo-500">Register</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>
