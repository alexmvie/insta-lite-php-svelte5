<?php
try {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
    header('Access-Control-Allow-Headers: Content-Type');

    require_once APP_ROOT . '/src/config/database.php';
    
    // Initialize database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }

    // Handle different HTTP methods
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get all tasks
            $stmt = $conn->prepare("SELECT * FROM tasks ORDER BY created_at DESC");
            $stmt->execute();
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $tasks]);
            break;

        case 'POST':
            // Add new task
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['title']) || empty($data['title'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Title is required']);
                break;
            }

            $stmt = $conn->prepare("INSERT INTO tasks (title, completed) VALUES (?, ?)");
            $stmt->execute([$data['title'], 0]);
            
            // Get the last inserted ID
            $lastId = $conn->lastInsertId();
            
            // Get the newly created task
            $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$lastId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $task]);
            break;

        case 'PUT':
            // Update task
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID is required']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['completed'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Completed status is required']);
                break;
            }

            $stmt = $conn->prepare("UPDATE tasks SET completed = ? WHERE id = ?");
            $stmt->execute([$data['completed'], $id]);
            
            // Get the updated task
            $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $task]);
            break;

        case 'DELETE':
            // Delete task
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID is required']);
                exit;
            }

            $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'details' => $e->getMessage()
    ]);
}
