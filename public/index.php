<?php
/**
 * Insta-Lite - Main Entry Point
 * 
 * This file serves as the main entry point for the application.
 * It includes the router which handles all requests.
 */

// Define the application root directory
define('APP_ROOT', dirname(__DIR__));

// Include the router
require_once APP_ROOT . '/src/router.php';