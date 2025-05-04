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

        $parsedBody = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();

        // Validate image
        if (!isset($uploadedFiles['image']) || $uploadedFiles['image']->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode(['error' => 'Image is required and must be a valid file.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $image = $uploadedFiles['image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($image->getClientMediaType(), $allowedTypes)) {
            $response->getBody()->write(json_encode(['error' => 'Only JPEG, PNG, or WebP images are allowed.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        if ($image->getSize() > 5 * 1024 * 1024) {
            $response->getBody()->write(json_encode(['error' => 'Image file too large (max 5MB).']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Load image config
        $imageConfig = require __DIR__ . '/../../config/image.php';
        $userId = $jwt->sub ?? null;
        $timestamp = time();
        $fullresDir = __DIR__ . '/../../public/' . $imageConfig['fullres_dir'];
        $thumbDir = __DIR__ . '/../../public/' . $imageConfig['thumb_dir'];
        if (!is_dir($fullresDir)) {
            mkdir($fullresDir, 0777, true);
        }
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0777, true);
        }
        $filename = 'post_' . $userId . '_' . $timestamp . '.webp';
        $targetPath = $fullresDir . $filename;
        $relativePath = $imageConfig['fullres_dir'] . $filename;
        $thumbPath = $thumbDir . $filename;
        $thumbRelativePath = $imageConfig['thumb_dir'] . $filename;

        // Convert and save as webp
        $tmpPath = $image->getFilePath() ?? null;
        if (!$tmpPath) {
            $tmpPath = tempnam(sys_get_temp_dir(), 'upl');
            $image->moveTo($tmpPath);
        }
        $mime = $image->getClientMediaType();
        if ($mime === 'image/jpeg') {
            $src = imagecreatefromjpeg($tmpPath);
        } elseif ($mime === 'image/png') {
            $src = imagecreatefrompng($tmpPath);
        } elseif ($mime === 'image/webp') {
            $src = imagecreatefromwebp($tmpPath);
        } else {
            $response->getBody()->write(json_encode(['error' => 'Unsupported image type.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Fullres resize
        $width = imagesx($src);
        $height = imagesy($src);
        $maxWidth = $imageConfig['fullres_max_width'];
        if ($width > $maxWidth) {
            $ratio = $maxWidth / $width;
            $newWidth = $maxWidth;
            $newHeight = (int)($height * $ratio);
            $fullresImg = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($fullresImg, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        } else {
            $fullresImg = $src;
            $newWidth = $width;
            $newHeight = $height;
        }
        imagewebp($fullresImg, $targetPath, $imageConfig['webp_quality']);

        // Thumbnail resize (fit/crop to 724x384)
        $thumbW = $imageConfig['thumb_width'];
        $thumbH = $imageConfig['thumb_height'];
        $thumbImg = imagecreatetruecolor($thumbW, $thumbH);
        // Fill with white background (for pngs with transparency)
        $white = imagecolorallocate($thumbImg, 255,255,255);
        imagefill($thumbImg, 0, 0, $white);
        // Calculate crop/fit
        $srcRatio = $width / $height;
        $thumbRatio = $thumbW / $thumbH;
        if ($srcRatio > $thumbRatio) {
            // Source is wider
            $cropH = $height;
            $cropW = (int)($height * $thumbRatio);
            $srcX = (int)(($width - $cropW) / 2);
            $srcY = 0;
        } else {
            // Source is taller
            $cropW = $width;
            $cropH = (int)($width / $thumbRatio);
            $srcX = 0;
            $srcY = (int)(($height - $cropH) / 2);
        }
        imagecopyresampled($thumbImg, $src, 0, 0, $srcX, $srcY, $thumbW, $thumbH, $cropW, $cropH);
        imagewebp($thumbImg, $thumbPath, $imageConfig['webp_quality']);
        imagedestroy($src);
        imagedestroy($fullresImg);
        imagedestroy($thumbImg);
        if ($tmpPath && file_exists($tmpPath)) {
            @unlink($tmpPath);
        }

        // Insert post
        $caption = $parsedBody['caption'] ?? '';
        $location = $parsedBody['location'] ?? '';
        $stmt = $conn->prepare('INSERT INTO posts (user_id, caption, location, image_path, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([
            $userId,
            $caption,
            $location,
            $relativePath
        ]);
        $postId = $conn->lastInsertId();
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'id' => $postId,
            'image_path' => $relativePath
        ]));
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
