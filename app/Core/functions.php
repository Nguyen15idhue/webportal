<?php

use App\Core\Auth;

// Function to load a view within a layout
function view(string $viewName, array $data = [], string $layout = 'guest'): void {
    extract($data); // Make variables available in the view

    $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $viewName) . '.php';
    $layoutPath = __DIR__ . '/../Views/layouts/' . $layout . '.php';

    if (!file_exists($viewPath)) {
        trigger_error("View not found: " . $viewPath, E_USER_ERROR);
        return;
    }
    if (!file_exists($layoutPath)) {
        trigger_error("Layout not found: " . $layoutPath, E_USER_ERROR);
        return;
    }

    // Start output buffering to capture view content
    ob_start();
    require $viewPath;
    $content = ob_get_clean(); // Get the captured view content

    // Now load the layout, which will use the $content variable
    require $layoutPath;
}

// Redirect function
function redirect(string $url): void {
    // If the URL doesn't start with http, assume it's relative to BASE_URL
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = rtrim(BASE_URL, '/') . '/' . ltrim($url, '/');
    }
    header("Location: " . $url);
    exit();
}

// Get asset URL
function asset(string $path): string {
    return rtrim(BASE_URL, '/') . '/assets/' . ltrim($path, '/');
}

// Get old form input (useful after validation failure)
function old(string $key, $default = '') {
    // Requires session flashing mechanism, simplified here
    return $_SESSION['_old_input'][$key] ?? $_POST[$key] ?? $default;
}

// Basic CSRF token generation (improve with time-limited tokens)
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF input field
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

// Verify CSRF token
function verify_csrf_token(): bool {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

// Helper to check permission for the currently logged-in user/admin
function can(string $permission): bool {
    return Auth::hasPermission($permission);
}

// Generate URL relative to BASE_URL
function url(string $path): string {
     return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

// Sanitize output
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Format currency helper (server-side)
function format_currency(float $amount): string {
    return number_format($amount, 0, ',', '.') . ' đ';
}

// Format date helper (server-side)
function format_date(?string $dateString, string $format = 'd/m/Y'): string {
    if (!$dateString) return '---';
    try {
        $date = new DateTime($dateString);
        return $date->format($format);
    } catch (Exception $e) {
        return 'Ngày lỗi';
    }
}