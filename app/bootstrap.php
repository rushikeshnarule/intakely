<?php
/**
 * Intakely — Bootstrap
 * Loads config, sets up autoloading, initializes the container and routes.
 */

declare(strict_types=1);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', PHP_SAPI === 'cli' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/logs/php_errors.log');

// Base path
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', __DIR__);
define('STORAGE_PATH', BASE_PATH . '/storage');
define('CONFIG_PATH', BASE_PATH . '/config');

// Simple PSR-4 autoloader
spl_autoload_register(function (string $class): void {
    if (strncmp('App\\', $class, 4) === 0) {
        $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($file)) {
            require $file;
        }
        return;
    }

    if (strncmp('Tests\\', $class, 6) === 0) {
        $file = BASE_PATH . '/tests/' . str_replace('\\', '/', substr($class, 6)) . '.php';
        if (file_exists($file)) {
            require $file;
        }
        return;
    }
});

// Load environment (do not overwrite existing system/container env vars)
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove quotes
            if (preg_match('/^"(.*)"$/', $value, $m)) $value = $m[1];
            if (preg_match("/^'(.*)'$/", $value, $m)) $value = $m[1];
            
            // Only set if not already set in container environment
            if (getenv($key) === false && !array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

/**
 * Get an environment variable with optional default.
 */
function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

/**
 * Get a config value using dot notation.
 */
function config(string $key, mixed $default = null): mixed
{
    static $configs = [];

    $parts = explode('.', $key);
    $file = array_shift($parts);

    if (!isset($configs[$file])) {
        $path = CONFIG_PATH . "/$file.php";
        $configs[$file] = file_exists($path) ? require $path : [];
    }

    $value = $configs[$file];
    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }

    return $value;
}

/**
 * Escape HTML output.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Generate a URL.
 */
function url(string $path = ''): string
{
    $base = rtrim(env('APP_URL', 'http://localhost:8000'), '/');
    return $base . '/' . ltrim($path, '/');
}

/**
 * Generate a CSRF token field.
 */
function csrf_field(): string
{
    $token = $_SESSION['_csrf_token'] ?? '';
    return '<input type="hidden" name="_csrf_token" value="' . e($token) . '">';
}

/**
 * Get the CSRF token.
 */
function csrf_token(): string
{
    return $_SESSION['_csrf_token'] ?? '';
}

/**
 * Flash a session message.
 */
function flash(string $key, mixed $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

/**
 * Get a flashed value.
 */
function old_flash(string $key, mixed $default = null): mixed
{
    $value = $_SESSION['_flash'][$key] ?? $default;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

// Initialize container and register services
$container = App\Core\Container::getInstance();

// Load config
$appConfig = require CONFIG_PATH . '/app.php';
$container->instance('config.app', $appConfig);

// Database
$container->singleton(App\Core\Database::class, function () {
    return new App\Core\Database();
});

// Router
$container->singleton(App\Core\Router::class, function () use ($container) {
    $router = new App\Core\Router($container);
    require CONFIG_PATH . '/routes.php';
    return $router;
});

// Session start
if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    $secure = env('SESSION_SECURE', 'false') === 'true';
    session_set_cookie_params([
        'lifetime' => (int) env('SESSION_LIFETIME', '120') * 60,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Generate CSRF token if not present
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
}
