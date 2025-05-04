<?php
// Get current page to highlight active link
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<nav class="bg-white shadow-sm fixed top-0 left-0 right-0 z-50 border-b border-gray-100">
    <div class="max-w-md mx-auto px-4">
        <div class="flex justify-between items-center h-14">
            <!-- Logo -->
            <div class="flex-shrink-0">
                <?php require_once __DIR__ . '/logo.php'; ?>
            </div>
            
            <!-- Right side icons -->
            <div class="flex items-center space-x-3">
                <!-- Create Post icon -->
                <a href="/create-post" class="<?php echo $current_page === 'create-post' ? 'text-pink-500' : 'text-gray-600'; ?> hover:text-pink-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </a>
                
                <!-- User Profile icon -->
                <a href="/profile" class="<?php echo $current_page === 'profile' ? 'ring-2 ring-pink-500' : ''; ?> rounded-full overflow-hidden w-7 h-7 flex items-center justify-center bg-gray-200">
                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo isset($_SESSION['username']) ? urlencode($_SESSION['username']) : 'user'; ?>" 
                         alt="Profile" 
                         class="w-full h-full object-cover">
                </a>
        </div>
    </div>
    
    <!-- Bottom Mobile Navigation Bar (fixed at bottom) -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 shadow-sm z-50">
        <div class="max-w-md mx-auto">
            <div class="flex justify-around items-center px-2 py-3">
                <!-- Home/Timeline -->
                <a href="/timeline" class="p-2">
                    <?php if ($current_page === 'timeline' || $current_page === 'index'): ?>
                        <!-- Filled icon when active -->
                        <svg class="w-7 h-7 text-pink-500" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 4h16v12H5.17L4 17.17V4zm4 4h8v2H8V8zm0 4h8v2H8v-2z"></path>
                        </svg>
                    <?php else: ?>
                        <!-- Outline icon when inactive -->
                        <svg class="w-7 h-7 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                        </svg>
                    <?php endif; ?>
                </a>
                
                <!-- Create Post -->
                <a href="/create-post" class="p-2">
                    <?php if ($current_page === 'create-post'): ?>
                        <!-- Filled icon when active -->
                        <svg class="w-7 h-7 text-pink-500" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"></path>
                        </svg>
                    <?php else: ?>
                        <!-- Outline icon when inactive -->
                        <svg class="w-7 h-7 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    <?php endif; ?>
                </a>
                
                <!-- Profile -->
                <a href="/profile" class="p-2">
                    <?php if ($current_page === 'profile'): ?>
                        <!-- Custom active state for profile -->
                        <div class="w-7 h-7 rounded-full overflow-hidden ring-2 ring-pink-500">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo isset($_SESSION['username']) ? urlencode($_SESSION['username']) : 'user'; ?>" 
                                 alt="Profile" 
                                 class="w-full h-full object-cover">
                        </div>
                    <?php else: ?>
                        <!-- Normal profile picture when inactive -->
                        <div class="w-7 h-7 rounded-full overflow-hidden">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo isset($_SESSION['username']) ? urlencode($_SESSION['username']) : 'user'; ?>" 
                                 alt="Profile" 
                                 class="w-full h-full object-cover">
                        </div>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>
    
    <!-- We've removed the hamburger menu since we now have a bottom navigation bar -->
</nav>

