<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Util\EnvironmentUtil;

class CommentController extends BaseController {

    public function getAll(Request $request, Response $response, $args) {
        $jwt = $this->requireAuth($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        
        // Get query parameters
        $queryParams = $request->getQueryParams();
        $post_id = isset($queryParams['post_id']) ? (int)$queryParams['post_id'] : null;
        
        // Build the query based on filters
        $query = "
            SELECT 
                c.*,
                u.username, 
                u.profile_picture
            FROM comments c
            JOIN users u ON c.user_id = u.id
        ";
        
        // Add WHERE clause if filtering by post
        if ($post_id) {
            $query .= " WHERE c.post_id = :post_id ";
        }
        
        $query .= " ORDER BY c.created_at DESC";
        
        // Prepare and execute the query
        $stmt = $conn->prepare($query);
        
        if ($post_id) {
            $stmt->bindParam(':post_id', $post_id, \PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $comments = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Process comments to ensure proper URLs for profile pictures
        foreach ($comments as &$comment) {
            // Convert profile picture paths to full URLs
            $comment['profile_picture'] = EnvironmentUtil::getAssetUrl($comment['profile_picture']);
        }
        
        $response->getBody()->write(json_encode($comments));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function create(Request $request, Response $response, $args) {
        $jwt = $this->requireAuth($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        // This is the admin endpoint, not the user endpoint
        return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
    }

    public function createComment(Request $request, Response $response, $args) {
        $jwt = $this->requireAuth($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $data = $request->getParsedBody();

        // Validate required fields
        if (!isset($data['post_id']) || !isset($data['user_id']) || !isset($data['content'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Missing required fields: post_id, user_id, and content are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Ensure post_id and user_id are integers
        $post_id = (int)$data['post_id'];
        $user_id = (int)$data['user_id'];
        $content = (string)$data['content'];

        // Validate that post_id and user_id are not zero
        if ($post_id <= 0 || $user_id <= 0) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid post_id or user_id'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $conn->prepare('INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$post_id, $user_id, $content]);

        // Return the created comment with its ID
        $lastInsertId = $conn->lastInsertId();
        $stmt = $conn->prepare('SELECT * FROM comments WHERE id = ?');
        $stmt->execute([$lastInsertId]);
        $comment = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Add profile picture URL if available
        if ($comment && $comment['profile_picture']) {
            $comment['profile_picture'] = EnvironmentUtil::getAssetUrl($comment['profile_picture']);
        }

        $response->getBody()->write(json_encode($comment));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getOne(Request $request, Response $response, $args) {
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('SELECT * FROM comments WHERE id = ?');
        $stmt->execute([$args['id']]);
        $comment = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($comment) {
            $response->getBody()->write(json_encode($comment));
        } else {
            $response->getBody()->write(json_encode(['error' => 'Not found']));
            return $response->withStatus(404);
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response, $args) {
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $data = $request->getParsedBody();
        $stmt = $conn->prepare('UPDATE comments SET content = ? WHERE id = ?');
        $stmt->execute([
            $data['content'] ?? '',
            $args['id']
        ]);
        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, $args) {
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('DELETE FROM comments WHERE id = ?');
        $stmt->execute([$args['id']]);
        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
