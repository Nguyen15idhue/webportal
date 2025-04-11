<?php

namespace App\Core;

class Router {
    protected array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    // Load routes from a file (e.g., routes.php)
    public static function load(string $file): self {
        $router = new static; // Create instance of the current class
        // Use require_once to prevent accidental re-inclusion issues
        require_once $file;
        return $router;
    }

    // Define a GET route
    public function get(string $uri, string $controllerAction): void {
        $this->routes['GET'][$this->prepareUri($uri)] = $controllerAction;
    }

    // Define a POST route
    public function post(string $uri, string $controllerAction): void {
        $this->routes['POST'][$this->prepareUri($uri)] = $controllerAction;
    }

    // Prepare URI for matching (remove trailing slashes, add leading slash)
    protected function prepareUri(string $uri): string {
        // Ensure leading slash, remove trailing slash unless it's just "/"
        $uri = trim($uri, '/');
        return '/' . ($uri === '' ? '' : $uri);
    }

    // Direct the request to the appropriate controller action
    public function direct(string $uri, string $requestMethod): mixed {
        // Get only the path part, ignore query string
        $uriPath = parse_url($uri, PHP_URL_PATH);
        if ($uriPath === false || $uriPath === null) {
             $uriPath = '/'; // Default to root if parse fails
        }
        $uri = $this->prepareUri($uriPath);
        $requestMethod = strtoupper($requestMethod);

        // Check if the request method exists in routes
        if (!isset($this->routes[$requestMethod])) {
             return $this->handleNotFound("Method Not Allowed for this route.");
        }

        // 1. Simple direct match first
        if (array_key_exists($uri, $this->routes[$requestMethod])) {
            list($controller, $action) = explode('@', $this->routes[$requestMethod][$uri]);
            return $this->callAction($controller, $action); // No params for direct match
        }

        // 2. Parameter Matching (e.g., /users/{id})
        foreach ($this->routes[$requestMethod] as $routeUri => $controllerAction) {
            // Convert route URI placeholders like {id} to regex capture groups
            // Ensure it correctly handles segments - only match non-slash characters
             $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routeUri);
             // Add start/end delimiters and ensure slashes are literal
             $pattern = '#^' . str_replace('/', '\/', $pattern) . '$#';

             if (preg_match($pattern, $uri, $matches)) {
                 // Extract named parameters from the matches
                 $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                 // **FIXED LINE:** Explode controller/action and pass separately
                 list($controller, $action) = explode('@', $controllerAction);
                 return $this->callAction($controller, $action, $params);
             }
        }

        // No route matched
        return $this->handleNotFound();
    }

    // Call the controller action
    protected function callAction(string $controller, string $action, array $params = []): mixed {
        // Ensure controller name includes the namespace path if not already prefixed
        if (!str_starts_with($controller, 'App\\Controllers\\')) {
             // Determine if it's Admin, User, or other controller based on convention
             if (str_contains($controller, '\\')) { // Already has sub-namespace (e.g., User\DashboardController)
                  $controller = "App\\Controllers\\{$controller}";
             } else { // Assume top-level controller (e.g., AuthController)
                  $controller = "App\\Controllers\\{$controller}";
             }
        }


        if (!class_exists($controller)) {
             error_log("Controller class not found: {$controller}");
             return $this->handleNotFound("Controller not found.");
        }

        $controllerInstance = new $controller();

        if (!method_exists($controllerInstance, $action)) {
            error_log("Action method not found: {$controller}@{$action}");
            return $this->handleNotFound("Action not found.");
        }

        try {
            // Call the action method, unpacking only the *values* of the parameters
            // The keys ('id') are used by the method signature type hinting/naming
            return $controllerInstance->$action(...array_values($params));
        } catch (\ArgumentCountError $e) {
            // Log specific error if argument count mismatches
             error_log("Argument count error calling {$controller}@{$action}: " . $e->getMessage());
             return $this->handleNotFound("Invalid parameters for route.");
        } catch (\TypeError $e) {
             error_log("Type error calling {$controller}@{$action}: " . $e->getMessage());
              return $this->handleNotFound("Invalid parameter type for route.");
        }
         catch (\Exception $e) {
             error_log("Error executing action {$controller}@{$action}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
             // In production, show a generic error page
             http_response_code(500);
             // You might want a dedicated error view here
             echo "<h1>500 Internal Server Error</h1>";
             echo "<p>An unexpected error occurred. Please try again later.</p>";
             exit;
        }
    }

     // Handle 404 Not Found
     protected function handleNotFound(string $message = "Page Not Found."): void {
         http_response_code(404);
         // You can create a dedicated 404 view
         // view('errors.404', ['message' => $message]); // Assuming an error view exists
         echo "<h1>404 Not Found</h1><p>{$message}</p>";
         exit;
     }
}