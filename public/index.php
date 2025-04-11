<?php

declare(strict_types=1);

// Define project root directory
define('ROOT_DIR', dirname(__DIR__));

// 1. Load Composer Autoloader
require ROOT_DIR . '/vendor/autoload.php';

// 2. Load Configuration
require ROOT_DIR . '/config/config.php';

// 3. Load Core Functions
require ROOT_DIR . '/app/Core/functions.php';

// 4. Start Session (Must be done before any output)
App\Core\Auth::startSession();

// 5. Load and Dispatch Router
try {
    $router = App\Core\Router::load(ROOT_DIR . '/app/routes.php'); // Assuming routes are in app/routes.php
    $router->direct(
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD']
    );
} catch (Exception $e) {
    // Log the exception
    error_log("Routing Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());

    // Show a generic error page in production
    if (ini_get('display_errors') === '0' || ini_get('display_errors') === 'Off') {
        http_response_code(500);
        // You could load a specific error view here
        echo "<h1>500 Internal Server Error</h1><p>An error occurred processing your request.</p>";
    } else {
        // Show detailed error in development
        http_response_code(500);
        echo "<h1>Error</h1><pre>";
        echo "Message: " . $e->getMessage() . "\n\n";
        echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n\n";
        echo "Trace:\n" . $e->getTraceAsString();
        echo "</pre>";
    }
    exit;
}