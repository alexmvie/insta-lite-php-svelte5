<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Util\EnvironmentUtil;

class PostController extends BaseController {

    public function getAll(Request $request, Response $response, $args) {
        $jwt = $this->requireAuth($request, $response);
        if (!$jwt) {
            return $response;
        }
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        
        // Get query parameters
        $queryParams = $request->getQueryParams();
        $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 10;
        $offset = isset($queryParams['offset']) ? (int)$queryParams['offset'] : 0;
        $user_id = isset($queryParams['user_id']) ? (int)$queryParams['user_id'] : null;
        $username = isset($queryParams['username']) ? $queryParams['username'] : null;
        
        // Get current user ID if available from session
        session_start();
        $current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        
        // Build the query based on filters
        $query = "
            SELECT 
                p.*, 
                u.username, 
                u.profile_picture,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count,
                (SELECT COUNT(*) > 0 FROM likes WHERE post_id = p.id AND user_id = :current_user_id) AS is_liked_by_current_user
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
        ";
        
        // Add WHERE clause if filtering by user
        if ($user_id) {
            $query .= " WHERE p.user_id = :user_id ";
        } elseif ($username) {
            $query .= " WHERE u.username = :username ";
        }
        
        // Add ORDER BY and LIMIT
        $query .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        
        // Prepare and execute the query
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':current_user_id', $current_user_id, \PDO::PARAM_INT);
        
        if ($user_id) {
            $stmt->bindParam(':user_id', $user_id, \PDO::PARAM_INT);
        } elseif ($username) {
            $stmt->bindParam(':username', $username, \PDO::PARAM_STR);
        }
        
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Process posts to ensure proper URLs for images
        foreach ($posts as &$post) {
            // Convert image paths to full URLs
            $post['image_path'] = EnvironmentUtil::getAssetUrl($post['image_path']);
            $post['profile_picture'] = EnvironmentUtil::getAssetUrl($post['profile_picture']);
        }
        
        // Check if there are more posts
        $has_more = false;
        if (count($posts) == $limit) {
            // Create a new query to check for more posts
            $check_query = "
                SELECT 1
                FROM posts p 
                JOIN users u ON p.user_id = u.id 
            ";
            
            // Add WHERE clause if filtering by user
            if ($user_id) {
                $check_query .= " AND p.user_id = :user_id ";
            } elseif ($username) {
                $check_query .= " AND u.username = :username ";
            }
            
            // Add ORDER BY and LIMIT with direct values (no parameter binding)
            $check_query .= " ORDER BY p.created_at DESC LIMIT 1 OFFSET " . ($offset + $limit);
            
            $check_stmt = $conn->prepare($check_query);
            
            if ($user_id) {
                $check_stmt->bindParam(':user_id', $user_id, \PDO::PARAM_INT);
            } elseif ($username) {
                $check_stmt->bindParam(':username', $username, \PDO::PARAM_STR);
            }
            
            $check_stmt->execute();
            $has_more = $check_stmt->rowCount() > 0;
        }
        
        // Format the response similar to the old project
        $response_data = [
            'success' => true,
            'posts' => $posts,
            'has_more' => $has_more
        ];
        
        $response->getBody()->write(json_encode($response_data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getOne(Request $request, Response $response, $args) {
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('SELECT * FROM posts WHERE id = ?');
        $stmt->execute([$args['id']]);
        $post = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($post) {
            $response->getBody()->write(json_encode($post));
        } else {
            $response->getBody()->write(json_encode(['error' => 'Not found']));
            return $response->withStatus(404);
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response, $args) {
        $jwt = $this->requireAuth($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $data = $request->getParsedBody();
        $stmt = $conn->prepare('INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, NOW())');
        $stmt->execute([
            $data['user_id'] ?? null,
            $data['content'] ?? ''
        ]);
        $response->getBody()->write(json_encode(['status' => 'success', 'id' => $conn->lastInsertId()]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response, $args) {
        $jwt = $this->requireAuth($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $data = $request->getParsedBody();
        $stmt = $conn->prepare('UPDATE posts SET content = ? WHERE id = ?');
        $stmt->execute([
            $data['content'] ?? '',
            $args['id']
        ]);
        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, $args) {
        $jwt = $this->requireAuth($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('DELETE FROM posts WHERE id = ?');
        $stmt->execute([$args['id']]);
        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
