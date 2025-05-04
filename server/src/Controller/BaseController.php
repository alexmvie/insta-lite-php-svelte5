<?php
namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;

use Psr\Http\Message\ResponseInterface as Response;

abstract class BaseController {
    /**
     * Check if the user is authenticated via JWT
     * @param Request $request
     * @return object|false Decoded JWT payload if valid, false otherwise
     */
    protected function checkAuth(Request $request) {
        $authHeader = $request->getHeaderLine('Authorization');
        $token = '';
        if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }
        if (empty($token)) {
            return false;
        }
        try {
            $jwtConfig = require __DIR__ . '/../../config/jwt.php';
            $secretKey = $jwtConfig['secret'];
            $algo = $jwtConfig['algo'];
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($secretKey, $algo));
            return $decoded;
        } catch (\Exception $e) {
            error_log('JWT authentication error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Require authentication: returns decoded JWT or sends 401 JSON response
     * @param Request $request
     * @param Response $response (PSR-7)
     * @return object|null
     */
    protected function requireAuth(Request $request, Response $response) {
        $jwt = $this->checkAuth($request);
        if (!$jwt) {
            $response->getBody()->write(json_encode([
                'error' => 'Unauthorized',
                'message' => 'Missing or invalid token.'
            ]));
            // Stop further execution in controller
            return null;
        }
        return $jwt;
    }

    /**
     * Require admin authentication: returns decoded JWT or sends 401 JSON response
     * @param Request $request
     * @param Response $response (PSR-7)
     * @return object|null
     */
    protected function requireAdmin(Request $request, Response $response) {
        $jwt = $this->checkAuth($request);
        if (!$jwt || !isset($jwt->is_admin) || !$jwt->is_admin) {
            $response->getBody()->write(json_encode([
                'error' => 'Unauthorized',
                'message' => 'Admin privileges required.'
            ]));
            return null;
        }
        return $jwt;
    }
}
