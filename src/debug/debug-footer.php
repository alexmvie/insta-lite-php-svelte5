<?php
if (!defined('DEBUG_ENABLED') || !DEBUG_ENABLED) {
    return;
}

// Get all debug tools
$debug_tools = [
    'Test Verification' => '/test-verification.php',
    'Generate Token' => '/generate-token.php',
    'Database Info' => '/database-info.php',
    'Session Info' => '/session-info.php'
];

// Get current page
$current_page = $_SERVER['PHP_SELF'];
?>

<div class="fixed bottom-0 left-0 w-full bg-gray-800 text-white p-4">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <span class="text-sm">Debug Tools:</span>
                <?php foreach ($debug_tools as $name => $url): ?>
                    <a href="<?php echo htmlspecialchars($url); ?>" 
                       class="text-sm text-gray-300 hover:text-white <?php echo $current_page === $url ? 'font-bold' : ''; ?>">
                        <?php echo htmlspecialchars($name); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="flex items-center space-x-4">
                <span class="text-sm">Session:</span>
                <span class="text-sm text-gray-300">
                    <?php 
                    $session_info = [];
                    if (isset($_SESSION['user_id'])) {
                        $session_info[] = "user_id: {$_SESSION['user_id']}";
                    }
                    if (isset($_SESSION['verification_success'])) {
                        $session_info[] = "verification_success: {$_SESSION['verification_success']}";
                    }
                    echo htmlspecialchars(implode(', ', $session_info));
                    ?>
                </span>
            </div>
        </div>
    </div>
</div>

<style>
    /* Ensure debug footer doesn't overlap content */
    body {
        margin-bottom: 64px;
    }
</style>
