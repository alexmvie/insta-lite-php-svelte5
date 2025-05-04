<?php
require __DIR__ . '/../../vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Middleware\ErrorMiddleware;

$app = AppFactory::create();

// Add routing middleware
$app->addRoutingMiddleware();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add CORS middleware
$app->add(function (Request $request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Handle preflight OPTIONS requests
$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response;
});


// Example route
$app->get('/api/hello', function (Request $request, Response $response, $args) {
    $data = ['message' => 'Hello from Slim REST API!'];
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

// Posts endpoints
$app->get('/api/posts', [\App\Controller\PostController::class, 'getAll']);
$app->get('/api/posts/{id}', [\App\Controller\PostController::class, 'getOne']);
$app->post('/api/posts', [\App\Controller\PostController::class, 'create']);
$app->put('/api/posts/{id}', [\App\Controller\PostController::class, 'update']);
$app->delete('/api/posts/{id}', [\App\Controller\PostController::class, 'delete']);

// Comments endpoints
$app->get('/api/comments', [\App\Controller\CommentController::class, 'getAll']);
$app->get('/api/comments/{id}', [\App\Controller\CommentController::class, 'getOne']);
$app->post('/api/comments/create', [\App\Controller\CommentController::class, 'createComment']);
$app->put('/api/comments/{id}', [\App\Controller\CommentController::class, 'update']);
$app->delete('/api/comments/{id}', [\App\Controller\CommentController::class, 'delete']);

// Likes endpoints
$app->get('/api/likes', [\App\Controller\LikeController::class, 'getAll']);
$app->post('/api/likes', [\App\Controller\LikeController::class, 'create']);
$app->delete('/api/likes', [\App\Controller\LikeController::class, 'delete']);

// Auth endpoints
$app->post('/api/auth/login', [\App\Controller\AuthController::class, 'login']);
$app->post('/api/auth/logout', [\App\Controller\AuthController::class, 'logout']);
$app->post('/api/auth/register', [\App\Controller\UserController::class, 'register']);
$app->get('/api/auth/me', [\App\Controller\UserController::class, 'me']);
$app->get('/api/auth/verify', [\App\Controller\VerificationController::class, 'verify']);
$app->post('/api/auth/verify', [\App\Controller\VerificationController::class, 'verifyApi']);

// --- ADMIN ENDPOINTS ---
// Users
$app->get('/api/admin/users', [\App\Controller\AdminController::class, 'listUsers']);
$app->post('/api/admin/users', [\App\Controller\AdminController::class, 'createUser']);
$app->put('/api/admin/users/{id}', [\App\Controller\AdminController::class, 'updateUser']);
$app->delete('/api/admin/users/{id}', [\App\Controller\AdminController::class, 'deleteUser']);
$app->post('/api/admin/users/{id}/verify', [\App\Controller\AdminController::class, 'verifyUser']);
$app->post('/api/admin/users/{id}/reset-verification', [\App\Controller\AdminController::class, 'resetVerification']);
$app->post('/api/admin/users/{id}/reset-password', [\App\Controller\AdminController::class, 'resetPassword']);
// Posts
$app->get('/api/admin/posts', [\App\Controller\AdminController::class, 'listPosts']);
$app->put('/api/admin/posts/{id}', [\App\Controller\AdminController::class, 'updatePost']);
$app->delete('/api/admin/posts/{id}', [\App\Controller\AdminController::class, 'deletePost']);
// Comments
$app->get('/api/admin/comments', [\App\Controller\AdminController::class, 'listComments']);
$app->put('/api/admin/comments/{id}', [\App\Controller\AdminController::class, 'updateComment']);
$app->delete('/api/admin/comments/{id}', [\App\Controller\AdminController::class, 'deleteComment']);
// Likes
$app->get('/api/admin/likes', [\App\Controller\AdminController::class, 'listLikes']);
$app->delete('/api/admin/likes/{id}', [\App\Controller\AdminController::class, 'deleteLike']);

$app->run();
