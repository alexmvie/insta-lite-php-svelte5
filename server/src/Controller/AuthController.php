<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController {
    public function login(Request $request, Response $response, $args) {
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        // Get JSON data from request body
        $input = $request->getBody()->getContents();
        $data = json_decode($input, true) ?: [];
        
        // Debug incoming data
        
        
        
        if (!$conn) {
            
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Database connection error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        
        $stmt = $conn->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$data['username'] ?? '']);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Debug user lookup
        error_log('User found: ' . ($user ? 'Yes' : 'No'));
        if ($user) {
            error_log('Password verification: ' . (password_verify($data['password'] ?? '', $user['password_hash'] ?? '') ? 'Success' : 'Failed'));
        }
        
        if ($user && password_verify($data['password'] ?? '', $user['password_hash'] ?? '')) {
            if (isset($user['verified']) && !$user['verified']) {
                $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Account not verified. Please check your email.']));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }
            // JWT generation
            $jwtConfig = require __DIR__ . '/../../config/jwt.php';
            $secretKey = $jwtConfig['secret'];
            $algo = $jwtConfig['algo'];
            $issuedAt = time();
            $payload = [
                'iat' => $issuedAt,
                'sub' => $user['id'],
                'username' => $user['username'],
                'is_admin' => isset($user['is_admin']) ? (int)$user['is_admin'] : 0,
                'verified' => isset($user['verified']) ? (int)$user['verified'] : 0
            ];
            $jwt = JWT::encode($payload, $secretKey, 'HS256');
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'user' => $user,
                'token' => $jwt
            ]));
        } else {
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Invalid credentials']));
        }
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function logout(Request $request, Response $response, $args) {
        // For stateless API, just return success (token-based logout is client-side)
        $response->getBody()->write(json_encode(['status' => 'success', 'message' => 'Logged out']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
