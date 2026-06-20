<?php
declare(strict_types=1);

namespace Koonect\Core;

// ═══════════════════════════════════════════════════════════════════
// REQUEST
// ═══════════════════════════════════════════════════════════════════
class Request
{
    private array $params = [];

    public function getMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function getUri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return rtrim((string)$uri, '/') ?: '/';
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    public function file(string $key): array|null
    {
        return $_FILES[$key] ?? null;
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    public function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    public function ip(): string
    {
        // Respecte les proxies de confiance configurés
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $h) {
            if (!empty($_SERVER[$h])) {
                $ip = trim(explode(',', $_SERVER[$h])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}

// ═══════════════════════════════════════════════════════════════════
// RESPONSE
// ═══════════════════════════════════════════════════════════════════
class Response
{
    public static function setStatusCode(int $code): void
    {
        http_response_code($code);
    }

    public static function redirect(string $url, int $code = 302): never
    {
        header("Location: $url", true, $code);
        exit;
    }

    public static function json(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    public static function notFound(): never
    {
        self::setStatusCode(404);
        View::render('errors/404');
        exit;
    }

    public static function forbidden(): never
    {
        self::setStatusCode(403);
        View::render('errors/403');
        exit;
    }
}

// ═══════════════════════════════════════════════════════════════════
// SESSION
// ═══════════════════════════════════════════════════════════════════
class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;

        $secure   = APP_ENV === 'production';
        $lifetime = SESSION_LIFETIME;

        // Assurer que le garbage collector de PHP conserve la session
        ini_set('session.gc_maxlifetime', (string)$lifetime);
        ini_set('session.cookie_lifetime', (string)$lifetime);

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => SESSION_DOMAIN,
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_name('KOONECT_SESSION');
        session_start();

        // Régénération périodique de l'ID de session
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
        } elseif (time() - $_SESSION['_created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['_created'] = time();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flush(): void
    {
        $_SESSION = [];
    }

    public static function destroy(): void
    {
        self::flush();
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}

// ═══════════════════════════════════════════════════════════════════
// VIEW
// ═══════════════════════════════════════════════════════════════════
class View
{
    private static array $sharedData = [];

    public static function share(string $key, mixed $value): void
    {
        self::$sharedData[$key] = $value;
    }

    public static function render(string $template, array $data = [], string $layout = 'main'): void
    {
        $data = array_merge(self::$sharedData, $data);

        // Rendre le template
        $content = self::include(VIEWS_PATH . '/' . $template . '.php', $data);

        // Rendre avec le layout
        $layoutFile = VIEWS_PATH . '/layouts/' . $layout . '.php';
        if (file_exists($layoutFile)) {
            $data['content'] = $content;
            echo self::include($layoutFile, $data);
        } else {
            echo $content;
        }
    }

    public static function renderPartial(string $partial, array $data = []): string
    {
        return self::include(VIEWS_PATH . '/partials/' . $partial . '.php', $data);
    }

    private static function include(string $path, array $data = []): string
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Vue introuvable : $path");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $path;
        return ob_get_clean() ?: '';
    }

    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

// ═══════════════════════════════════════════════════════════════════
// LOGGER
// ═══════════════════════════════════════════════════════════════════
class Logger
{
    private const LEVELS = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    public static function security(string $message, array $context = []): void
    {
        self::write('security', $message, $context, 'security.log');
    }

    private static function write(string $level, string $message, array $context, string $file = 'app.log'): void
    {
        $configLevel = LOG_LEVEL;
        if (isset(self::LEVELS[$level]) && isset(self::LEVELS[$configLevel])) {
            if (self::LEVELS[$level] < self::LEVELS[$configLevel]) return;
        }

        $logFile = LOG_PATH . '/' . $file;
        $date    = date('Y-m-d H:i:s');
        $ctx     = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        $line    = "[$date] [$level] $message$ctx" . PHP_EOL;

        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
