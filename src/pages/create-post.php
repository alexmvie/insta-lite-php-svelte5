<?php
// Check if APP_ROOT is defined
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(dirname(__DIR__)));
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Get the database connection
$db = (new Database())->getConnection();

// Handle post creation
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = $_POST['caption'] ?? '';
    $image_url = ''; // In a real app, we would handle file uploads here
    
    // For demo purposes, we'll use a fun placeholder image
    if (isset($_POST['use_placeholder']) && $_POST['use_placeholder'] === 'yes') {
        // Array of fun placeholder images (webp format when possible)
        $placeholder_images = [
            'https://source.unsplash.com/random/800x600?nature&.webp',
            'https://source.unsplash.com/random/800x600?food&.webp',
            'https://source.unsplash.com/random/800x600?travel&.webp',
            'https://source.unsplash.com/random/800x600?animals&.webp',
            'https://source.unsplash.com/random/800x600?city&.webp'
        ];
        
        // Select a random image
        $image_url = $placeholder_images[array_rand($placeholder_images)];
    }
    
    try {
        // Check if image_url column exists in posts table
        $columnExists = false;
        try {
            $check = $db->query("SHOW COLUMNS FROM posts LIKE 'image_url'");
            $columnExists = ($check->rowCount() > 0);
        } catch (Exception $e) {
            // Column doesn't exist or other error
        }
        
        if ($columnExists) {
            // If image_url column exists, use it
            $stmt = $db->prepare("
                INSERT INTO posts (user_id, caption, image_url, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $caption,
                $image_url
            ]);
        } else {
            // If image_url column doesn't exist, don't include it
            $stmt = $db->prepare("
                INSERT INTO posts (user_id, caption, created_at) 
                VALUES (?, ?, NOW())
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $caption
            ]);
        }
        
        $success = true;
    } catch (PDOException $e) {
        $error = "Error creating post: " . $e->getMessage();
    }
}

// Get user data
$stmt = $db->prepare("SELECT username, profile_picture FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insta-Lite - Create Post</title>
    <script src="https://unpkg.com/htmx.org@1.9.16"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php require_once __DIR__ . '/../components/navbar.php'; ?>
    
    <div class="min-h-screen pt-16 pb-16 md:pb-0">
        <div class="max-w-md mx-auto px-4 py-4">
            <div class="bg-white shadow rounded-lg p-6">
                <h1 class="text-2xl font-bold mb-6">Create New Post</h1>
                
                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
                        <p>Post created successfully!</p>
                        <div class="mt-2">
                            <a href="/timeline" class="text-green-700 underline">View in Timeline</a>
                            <span class="mx-2">|</span>
                            <a href="/profile" class="text-green-700 underline">View in Profile</a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="/create-post" enctype="multipart/form-data">
                    <div class="mb-6">
                        <label for="caption" class="block text-gray-700 text-sm font-bold mb-2">
                            Caption
                        </label>
                        <textarea 
                            id="caption" 
                            name="caption" 
                            rows="4" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            placeholder="What's on your mind?"
                            required
                        ></textarea>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Image
                        </label>
                        <div class="flex items-center">
                            <input 
                                type="file" 
                                id="image" 
                                name="image" 
                                accept="image/*"
                                class="hidden"
                                disabled
                            >
                            <label 
                                for="image" 
                                class="cursor-pointer bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            >
                                Choose File
                            </label>
                            <span class="ml-3 text-gray-500 text-sm">
                                (Image upload not implemented in this demo)
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="use_placeholder" 
                                value="yes" 
                                class="form-checkbox h-5 w-5 text-blue-600"
                            >
                            <span class="ml-2 text-gray-700">Use placeholder image for demo</span>
                        </label>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <button 
                            type="submit" 
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                        >
                            Create Post
                        </button>
                        <a 
                            href="/timeline" 
                            class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
