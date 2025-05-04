<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use App\Util\EnvironmentUtil;

class AdminController extends BaseController {

    // --- USER MANAGEMENT ---
    public function listUsers(Request $request, Response $response, $args) {
        $jwt = $this->requireAdmin($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $users = $conn->query('SELECT id, username, email, verified, created_at, profile_picture FROM users')->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($users as &$user) {
            if (isset($user['profile_picture'])) {
                $user['profile_picture'] = \App\Util\EnvironmentUtil::getAssetUrl($user['profile_picture']);
            }
        }
        $response->getBody()->write(json_encode($users));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function createUser(Request $request, Response $response, $args) {
        $jwt = $this->requireAdmin($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        // Get JSON data from request body
        $json = $request->getBody()->getContents();
        $data = json_decode($json, true);
        
        // Validate required fields
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Validation Error',
                'message' => 'Username, email and password are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        
        // Check if username or email already exists
        $checkStmt = $conn->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
        $checkStmt->execute([$data['username'], $data['email']]);
        $exists = $checkStmt->fetchColumn();
        
        if ($exists > 0) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'error' => 'Duplicate Entry',
                'message' => 'Username or email already exists'
            ]));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }
        
        // Insert new user
        $stmt = $conn->prepare('INSERT INTO users (username, email, password_hash, verified) VALUES (?, ?, ?, ?)');
        $result = $stmt->execute([
            $data['username'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            isset($data['verified']) ? (int)$data['verified'] : 0
        ]);
        
        if (!$result) {
            $response->getBody()->write(json_encode([
                'error' => 'Database Error',
                'message' => 'Failed to create user'
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        
        $userId = $conn->lastInsertId();
        
        // Return the newly created user
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'User created successfully',
            'user' => [
                'id' => $userId,
                'username' => $data['username'],
                'email' => $data['email'],
                'verified' => isset($data['verified']) ? (int)$data['verified'] : 0
            ]
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function updateUser(Request $request, Response $response, $args) {
        $jwt = $this->requireAdmin($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $data = $request->getParsedBody();
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('UPDATE users SET username=?, email=?, verified=? WHERE id=?');
        $stmt->execute([
            $data['username'] ?? '',
            $data['email'] ?? '',
            $data['verified'] ?? 0,
            $args['id']
        ]);
        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function deleteUser(Request $request, Response $response, $args) {
        $jwt = $this->requireAdmin($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('DELETE FROM users WHERE id=?');
        $stmt->execute([$args['id']]);
        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function verifyUser(Request $request, Response $response, $args) {
        $jwt = $this->requireAdmin($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('UPDATE users SET verified=1, verification_token=NULL WHERE id=?');
        $stmt->execute([$args['id']]);
        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function resetVerification(Request $request, Response $response, $args) {
        $jwt = $this->requireAdmin($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $token = bin2hex(random_bytes(32));
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('UPDATE users SET verified=0, verification_token=? WHERE id=?');
        $stmt->execute([$token, $args['id']]);
        // Resend verification email
        $stmt = $conn->prepare('SELECT email FROM users WHERE id=?');
        $stmt->execute([$args['id']]);
        $email = $stmt->fetchColumn();
        require_once __DIR__ . '/../../../email/email.php';
        $email_sender = new \Email();
        $email_sender->sendVerificationEmail($email, $token);
        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function resetPassword(Request $request, Response $response, $args) {
        $jwt = $this->requireAdmin($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $data = $request->getParsedBody();
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('UPDATE users SET password_hash=? WHERE id=?');
        $stmt->execute([
            password_hash($data['password'] ?? '', PASSWORD_DEFAULT),
            $args['id']
        ]);
        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // --- POST MANAGEMENT ---
    public function listPosts(Request $request, Response $response, $args) {
        $jwt = $this->requireAdmin($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        require_once __DIR__ . '/../../config/database.php';
        require_once __DIR__ . '/../Util/EnvironmentUtil.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $posts = $conn->query('SELECT * FROM posts')->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($posts as &$post) {
            if (isset($post['image_path'])) {
                $post['image_path'] = \App\Util\EnvironmentUtil::getAssetUrl($post['image_path']);
            }
        }
        $response->getBody()->write(json_encode($posts));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function updatePost(Request $request, Response $response, $args) {
        $jwt = $this->requireAdmin($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $data = $request->getParsedBody();
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('UPDATE posts SET content=? WHERE id=?');
        $stmt->execute([
            $data['content'] ?? '',
            $args['id']
        ]);
        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function deletePost(Request $request, Response $response, $args) {
        $jwt = $this->requireAdmin($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('DELETE FROM posts WHERE id=?');
        $stmt->execute([$args['id']]);
        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // --- COMMENT MANAGEMENT ---
    public function listComments(Request $request, Response $response, $args) {
        $jwt = $this->requireAdmin($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        require_once __DIR__ . '/../../config/database.php';
        require_once __DIR__ . '/../Util/EnvironmentUtil.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $comments = $conn->query('SELECT * FROM comments')->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($comments as &$comment) {
            if (isset($comment['profile_picture'])) {
                $comment['profile_picture'] = \App\Util\EnvironmentUtil::getAssetUrl($comment['profile_picture']);
            }
        }
        $response->getBody()->write(json_encode($comments));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function updateComment(Request $request, Response $response, $args) {
        $jwt = $this->requireAdmin($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $data = $request->getParsedBody();
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('UPDATE comments SET content=? WHERE id=?');
        $stmt->execute([
            $data['content'] ?? '',
            $args['id']
        ]);
        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function deleteComment(Request $request, Response $response, $args) {
        $jwt = $this->requireAdmin($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('DELETE FROM comments WHERE id=?');
        $stmt->execute([$args['id']]);
        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // --- LIKE MANAGEMENT ---
    public function listLikes(Request $request, Response $response, $args) {
        $jwt = $this->requireAdmin($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        require_once __DIR__ . '/../../config/database.php';
        require_once __DIR__ . '/../Util/EnvironmentUtil.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $likes = $conn->query('SELECT * FROM likes')->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($likes as &$like) {
            if (isset($like['profile_picture'])) {
                $like['profile_picture'] = \App\Util\EnvironmentUtil::getAssetUrl($like['profile_picture']);
            }
        }
        $response->getBody()->write(json_encode($likes));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function deleteLike(Request $request, Response $response, $args) {
        $jwt = $this->requireAdmin($request, $response);
        if (!$jwt) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('DELETE FROM likes WHERE id=?');
        $stmt->execute([$args['id']]);
        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
