<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class VerificationController {
    /**
     * Handle GET verification requests (from email links)
     * This redirects to the frontend verification page with the token and user_id
     */
    public function verify(Request $request, Response $response, $args) {
        $params = $request->getQueryParams();
        $token = $params['token'] ?? '';
        $userId = $params['user_id'] ?? null;
        
        // Redirect to the frontend verification page
        return $response
            ->withHeader('Location', "/verify?token={$token}&user_id={$userId}")
            ->withStatus(302);
    }
    
    /**
     * Handle POST verification requests (from the frontend verification page)
     * This is the API endpoint that the frontend calls to verify the token
     */
    public function verifyApi(Request $request, Response $response, $args) {
        require_once __DIR__ . '/../../config/database.php';
        $db = new \Database();
        $conn = $db->getConnection();
        
        // Get JSON data from request body
        $json = $request->getBody()->getContents();
        $data = json_decode($json, true);
        
        $token = $data['token'] ?? '';
        $userId = $data['user_id'] ?? null;
        
        if (!$token || !$userId) {
            $response->getBody()->write(json_encode([
                'status' => 'error', 
                'message' => 'Missing required parameters'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        try {
            // For simulation, we'll verify any token that starts with 'verify-'
            // In a real app, you'd check against a stored token in the database
            $decodedToken = base64_decode($token);
            if (strpos($decodedToken, 'verify-') === 0) {
                // Extract username from token (for simulation only)
                $parts = explode('-', $decodedToken);
                if (count($parts) >= 3) {
                    // Update user as verified
                    $stmt = $conn->prepare('UPDATE users SET verified = 1 WHERE id = ?');
                    $stmt->execute([$userId]);
                    
                    if ($stmt->rowCount() > 0) {
                        $response->getBody()->write(json_encode([
                            'status' => 'success', 
                            'message' => 'Your account has been successfully verified!'
                        ]));
                        return $response->withHeader('Content-Type', 'application/json');
                    } else {
                        $response->getBody()->write(json_encode([
                            'status' => 'error', 
                            'message' => 'User not found or already verified.'
                        ]));
                        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                    }
                }
            }
            
            // If we get here, the token is invalid
            $response->getBody()->write(json_encode([
                'status' => 'error', 
                'message' => 'Invalid verification token.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            // Handle any exceptions
            $response->getBody()->write(json_encode([
                'status' => 'error', 
                'message' => 'An error occurred during verification: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
