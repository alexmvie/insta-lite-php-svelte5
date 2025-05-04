<?php
/**
 * API Endpoint to render a post card from JSON data
 * 
 * This endpoint accepts a POST request with JSON data for a post
 * and returns the HTML for the post card.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_INACTIVE) {
    session_start();
}

// Get current user ID if logged in
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Get the raw POST data
$json = file_get_contents('php://input');
$post = json_decode($json, true);

if (!$post) {
    http_response_code(400);
    echo "Invalid JSON data";
    exit;
}

// Make sure we have all the required fields
if (!isset($post['id']) || !isset($post['user_id']) || !isset($post['username'])) {
    http_response_code(400);
    echo "Missing required fields";
    exit;
}

// Extract the post data into variables that the post-card component expects
extract($post);

// Capture the output of the post-card component
ob_start();
include __DIR__ . '/../components/post-card.php';
$post_html = ob_get_clean();

// Return the HTML
echo $post_html;
