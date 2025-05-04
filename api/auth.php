<?php
class Auth {
    public static function check() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }

    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        require_once APP_ROOT . '/src/config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        try {
            $stmt = $conn->prepare("SELECT id, username, profile_picture, bio FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Auth Error: " . $e->getMessage());
            return null;
        }
    }

    public static function login($user_id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $user_id;
        $_SESSION['last_activity'] = time();
        self::extendSession();
        
        // Ensure session is saved
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Unset all session variables
        $_SESSION = array();
        
        // If it's desired to kill the session, also delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Finally, destroy the session
        session_destroy();
    }

    private static function extendSession() {
        $_SESSION['last_activity'] = time();
    }

    public static function checkSessionTimeout($timeout = 1800) { // 30 minutes
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > $timeout)) {
            self::logout();
            header('Location: /login');
            exit;
        }
    }
}
