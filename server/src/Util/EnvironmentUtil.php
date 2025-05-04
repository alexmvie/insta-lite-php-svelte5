<?php
namespace App\Util;

/**
 * Utility class to handle environment-specific configurations
 * Provides a single source of truth for determining environment and URL handling
 */
class EnvironmentUtil {
    /**
     * Determine if the application is running in a development environment
     * 
     * @return bool True if in development environment, false otherwise
     */
    public static function isDevelopment(): bool {
        // Check for localhost or development domain
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return (
            strpos($host, 'localhost') !== false || 
            strpos($host, '127.0.0.1') !== false ||
            strpos($host, '.local') !== false ||
            strpos($host, '.test') !== false
        );
    }
    
    /**
     * Get the base URL for the application
     * 
     * @return string The base URL including protocol and domain
     */
    public static function getBaseUrl(): string {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
        
        return $protocol . $host;
    }
    
    /**
     * Get the full URL for an asset path
     * 
     * @param string|null $path The asset path
     * @return string|null The full URL for the asset
     */
    public static function getAssetUrl(?string $path): ?string {
        if (empty($path)) {
            return null;
        }
        
        // If path already starts with http(s), it's already a full URL
        if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            return $path;
        }
        
        // Ensure path starts with a slash
        if (strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }
        
        return self::getBaseUrl() . $path;
    }
}
