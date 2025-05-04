<?php
/**
 * Post Helper Functions
 * 
 * This file contains helper functions for post-related operations.
 */

/**
 * Check if a user has liked a post
 * 
 * @param int $user_id The user ID
 * @param int $post_id The post ID
 * @return bool True if the user has liked the post, false otherwise
 */
function checkIfUserLikedPost($user_id, $post_id) {
    // Get the database connection
    require_once __DIR__ . '/../config/database.php';
    $db = (new Database())->getConnection();
    
    // Check if user has liked the post
    $stmt = $db->prepare("SELECT id FROM likes WHERE user_id = :user_id AND post_id = :post_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
}
