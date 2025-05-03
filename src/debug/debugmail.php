<?php
session_start();
require_once APP_ROOT . '/src/config/database.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Handle actions
$message = '';
$messageType = '';

// Delete user
if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $result = $stmt->execute([$_POST['user_id']]);
        
        if ($result) {
            $message = "User deleted successfully";
            $messageType = "success";
        } else {
            $message = "Failed to delete user";
            $messageType = "error";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
    }
}

// Reset verification
if (isset($_POST['reset_verification']) && isset($_POST['user_id'])) {
    try {
        // Generate new token
        $token = bin2hex(random_bytes(32));
        
        $stmt = $conn->prepare("UPDATE users SET verified = 0, verification_token = ? WHERE id = ?");
        $result = $stmt->execute([$token, $_POST['user_id']]);
        
        if ($result) {
            $message = "Verification reset successfully";
            $messageType = "success";
        } else {
            $message = "Failed to reset verification";
            $messageType = "error";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
    }
}

// Verify user
if (isset($_POST['verify_user']) && isset($_POST['user_id'])) {
    try {
        $stmt = $conn->prepare("UPDATE users SET verified = 1, verification_token = NULL WHERE id = ?");
        $result = $stmt->execute([$_POST['user_id']]);
        
        if ($result) {
            $message = "User verified successfully";
            $messageType = "success";
        } else {
            $message = "Failed to verify user";
            $messageType = "error";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
    }
}

// Get all users
try {
    $stmt = $conn->prepare("SELECT id, username, email, verified, verification_token, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "Error fetching users: " . $e->getMessage();
    $messageType = "error";
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Mail - Insta-Lite</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .token-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .token-cell:hover {
            white-space: normal;
            word-break: break-all;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6 text-center">Debug Mail - User Management</h1>
        
        <?php if ($message): ?>
            <div class="mb-4 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-xl font-semibold">Users</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verification Token</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['id']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['verified'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo $user['verified'] ? 'Verified' : 'Not Verified'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 token-cell">
                                    <?php if ($user['verification_token']): ?>
                                        <a href="/verify?token=<?php echo htmlspecialchars($user['verification_token']); ?>" class="text-blue-600 hover:text-blue-900" title="Click to verify">
                                            <?php echo htmlspecialchars($user['verification_token']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400">None</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['created_at']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <div class="flex space-x-2">
                                        <?php if (!$user['verified'] && $user['verification_token']): ?>
                                            <a href="/verify?token=<?php echo htmlspecialchars($user['verification_token']); ?>" target="_blank" class="text-green-600 hover:text-green-900">Verify</a>
                                        <?php else: ?>
                                            <form method="post" class="inline">
                                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                                <button type="submit" name="reset_verification" class="text-yellow-600 hover:text-yellow-900">Reset</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                            <button type="submit" name="delete_user" class="text-red-600 hover:text-red-900">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="mt-8 text-center">
            <a href="/" class="text-blue-600 hover:text-blue-900">Back to Home</a>
            <a href="/debug/session" class="text-blue-600 hover:text-blue-900 ml-4">Session Debug</a>
        </div>
    </div>
</body>
</html>
