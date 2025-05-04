<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Util\EnvironmentUtil;

class LikeController {
    public function getAll(Request $request, Response $response, $args) {
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        
        // Get query parameters
        $queryParams = $request->getQueryParams();
        $post_id = isset($queryParams['post_id']) ? (int)$queryParams['post_id'] : null;
        
        // Build the query based on filters
        $query = "
            SELECT 
                l.*,
                u.username, 
                u.profile_picture
            FROM likes l
            JOIN users u ON l.user_id = u.id
        ";
        
        // Add WHERE clause if filtering by post
        if ($post_id) {
            $query .= " WHERE l.post_id = :post_id ";
        }
        
        $query .= " ORDER BY l.created_at DESC";
        
        // Prepare and execute the query
        $stmt = $conn->prepare($query);
        
        if ($post_id) {
            $stmt->bindParam(':post_id', $post_id, \PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $likes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Process likes to ensure proper URLs for profile pictures
        foreach ($likes as &$like) {
            // Convert profile picture paths to full URLs
            $like['profile_picture'] = EnvironmentUtil::getAssetUrl($like['profile_picture']);
        }
        
        $response->getBody()->write(json_encode($likes));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function create(Request $request, Response $response, $args) {
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $data = $request->getParsedBody();
        $stmt = $conn->prepare('INSERT INTO likes (post_id, user_id, created_at) VALUES (?, ?, NOW())');
        $stmt->execute([
            $data['post_id'] ?? null,
            $data['user_id'] ?? null
        ]);
        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, $args) {
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $data = $request->getParsedBody();
        $stmt = $conn->prepare('DELETE FROM likes WHERE post_id = ? AND user_id = ?');
        $stmt->execute([
            $data['post_id'] ?? null,
            $data['user_id'] ?? null
        ]);
        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
