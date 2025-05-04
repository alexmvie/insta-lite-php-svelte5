<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController extends BaseController {

    public function register(Request $request, Response $response, $args) {
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        $data = $request->getParsedBody();
        if (!isset($data['username'], $data['password'])) {
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Missing username or password']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Generate verification token
        $verification_token = bin2hex(random_bytes(32));
        $stmt = $conn->prepare('INSERT INTO users (username, password_hash, email, verified, verification_token) VALUES (?, ?, ?, 0, ?)');
        $stmt->execute([
            $data['username'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['email'] ?? '',
            $verification_token
        ]);
        // Send verification email
        require_once __DIR__ . '/../../../email/email.php';
        $email_sender = new \Email();
        $email_sender->sendVerificationEmail($data['email'], $verification_token);
        $response->getBody()->write(json_encode(['status' => 'success', 'message' => 'Check your email to verify your account']));
        return $response->withHeader('Content-Type', 'application/json');
    }
    /**
     * Get the current authenticated user's information
     * @param Request $request The request object
     * @param Response $response The response object
     * @param array $args Route arguments
     * @return Response The response with user data
     */
    public function me(Request $request, Response $response, $args) {
        $jwt = $this->requireAuth($request, $response);
        if (!$jwt) {
            $response->getBody()->write(json_encode([
                'error' => 'Unauthorized',
                'message' => 'Missing or invalid token.'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        // Get the authorization token from the request header
        $authHeader = $request->getHeaderLine('Authorization');
        $token = '';
        
        // Extract the token from the Authorization header
        if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }
        
        if (empty($token)) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Missing or invalid authentication token'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        // Connect to the database
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        
        try {
            // Find the user by ID from JWT
            $user_id = $jwt->sub ?? null;
            if (!$user_id) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'Invalid token payload: missing user_id'
                ]));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }
            $stmt = $conn->prepare('SELECT id, username, email, verified, is_admin, created_at FROM users WHERE id = :id');
            $stmt->bindParam(':id', $user_id, \PDO::PARAM_INT);
            $stmt->execute();
            
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$user) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'User not found or invalid token'
                ]));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }
            
            // Convert is_admin to boolean for JSON
            $user['is_admin'] = (bool)$user['is_admin'];
            $user['verified'] = (bool)$user['verified'];
            
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'user' => $user
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Error retrieving user data: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
