<?php
// Handle verification
if (isset($_GET['token'])) {
    $verification_token = $_GET['token'];
    
    try {
        require_once APP_ROOT . '/src/config/database.php';
        $db = new Database();
        $conn = $db->getConnection();

        if (!$conn) {
            throw new Exception('Database connection failed');
        }

        // First check if token exists
        $stmt = $conn->prepare("SELECT id, username, verified FROM users WHERE verification_token = ?");
        $stmt->execute([$verification_token]);
        $user = $stmt->fetch();

        if ($user) {
            // Update user as verified
            $stmt = $conn->prepare("
                UPDATE users 
                SET verified = 1, verification_token = NULL 
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);

            // Show success message
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Verification Success</title>
                <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
            </head>
            <body class="bg-gray-100">
                <div class="min-h-screen flex items-center justify-center">
                    <div class="bg-white p-8 rounded-lg shadow-md w-96">
                        <h1 class="text-2xl font-bold mb-6 text-center">Verification Success</h1>
                        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                            Your account has been successfully verified! You can now log in.
                        </div>
                        <p class="text-center">
                            <a href="/login" class="text-blue-600 hover:text-blue-500">Go to login page</a>
                        </p>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        } else {
            // Show error message
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Verification Error</title>
                <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
            </head>
            <body class="bg-gray-100">
                <div class="min-h-screen flex items-center justify-center">
                    <div class="bg-white p-8 rounded-lg shadow-md w-96">
                        <h1 class="text-2xl font-bold mb-6 text-center">Verification Error</h1>
                        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                            Invalid verification link. Please try registering again.
                        </div>
                        <p class="text-center">
                            <a href="/register" class="text-indigo-600 hover:text-indigo-500">Try registering again</a>
                        </p>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    } catch(Exception $e) {
        error_log("Verification Error: " . $e->getMessage());
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Verification Error</title>
            <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        </head>
        <body class="bg-gray-100">
            <div class="min-h-screen flex items-center justify-center">
                <div class="bg-white p-8 rounded-lg shadow-md w-96">
                    <h1 class="text-2xl font-bold mb-6 text-center">Verification Error</h1>
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                        An error occurred during verification. Please try again.
                    </div>
                    <p class="text-center">
                        <a href="/register" class="text-indigo-600 hover:text-indigo-500">Try registering again</a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
?>
