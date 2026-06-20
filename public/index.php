<?php
/**
 * Koonect — Front Controller
 * Point d'entrée unique de toutes les requêtes HTTP.
 */

declare(strict_types=1);

// ── Vérification version PHP ─────────────────────────────────────
if (PHP_VERSION_ID < 80300) {
    http_response_code(500);
    die('PHP 8.3+ requis. Version actuelle : ' . PHP_VERSION);
}

// ── Définir le chemin racine ─────────────────────────────────────
define('ROOT_PATH', dirname(__DIR__));

// ── Autoloader (Composer ou custom) ──────────────────────────────
$composerAutoload = ROOT_PATH . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require $composerAutoload;
} else {
    // Autoloader PSR-4 de secours (sans Composer)
    spl_autoload_register(function (string $class): void {
        // Koonect\Core\App → app/core/App.php
        $prefix = 'Koonect\\';
        $base   = ROOT_PATH . '/app/';

        if (!str_starts_with($class, $prefix)) return;

        $relative = str_replace($prefix, '', $class);
        $file     = $base . str_replace('\\', '/', $relative) . '.php';

        // Chercher aussi dans les sous-dossiers regroupés
        $groupedFiles = [
            'Core\Request'   => 'core/Kernel.php',
            'Core\Response'  => 'core/Kernel.php',
            'Core\Session'   => 'core/Kernel.php',
            'Core\View'      => 'core/Kernel.php',
            'Core\Logger'    => 'core/Kernel.php',
            'Helpers\Csrf'   => 'helpers/Csrf.php',
            'Helpers\Slug'   => 'helpers/Helpers.php',
            'Helpers\Sanitizer' => 'helpers/Helpers.php',
            'Helpers\Seo'    => 'helpers/Helpers.php',
            'Helpers\Paginator' => 'helpers/Helpers.php',
            'Middleware\CsrfMiddleware'     => 'middleware/Middleware.php',
            'Middleware\AuthMiddleware'     => 'middleware/Middleware.php',
            'Middleware\RoleMiddleware'     => 'middleware/Middleware.php',
            'Middleware\RateLimitMiddleware'=> 'middleware/Middleware.php',
            'Middleware\RedacAuthMiddleware'=> 'middleware/Middleware.php',
            'Services\CacheService'        => 'services/Services.php',
            'Services\ImageService'        => 'services/Services.php',
            'Services\MailService'         => 'services/MailService.php',
            'Services\TwoFactorService'    => 'services/MailService.php',
            'Services\SsoService'          => 'services/MailService.php',
            'Models\User'       => 'models/Models.php',
            'Models\Category'   => 'models/Models.php',
            'Models\Tag'        => 'models/Models.php',
            'Models\Comment'    => 'models/Models.php',
            'Models\Media'      => 'models/Models.php',
            'Models\Newsletter' => 'models/Models.php',
            'Controllers\ArticleController' => 'controllers/ArticleController.php',
            'Controllers\CategoryController'=> 'controllers/Controllers.php',
            'Controllers\TagController'     => 'controllers/Controllers.php',
            'Controllers\SearchController'  => 'controllers/Controllers.php',
            'Controllers\SitemapController' => 'controllers/Controllers.php',
            'Controllers\NewsletterController' => 'controllers/Controllers.php',
            'Controllers\PageController'    => 'controllers/Controllers.php',
        ];

        $key = str_replace($prefix, '', $class);
        if (isset($groupedFiles[$key])) {
            $groupedPath = $base . $groupedFiles[$key];
            if (file_exists($groupedPath)) {
                require_once $groupedPath;
                return;
            }
        }

        if (file_exists($file)) {
            require_once $file;
        }
    });
}

// ── Charger la configuration ─────────────────────────────────────
require ROOT_PATH . '/app/config/config.php';

// ── Charger les classes core (non-autoloaded si pas de Composer) ──
require_once ROOT_PATH . '/app/core/Database.php';
require_once ROOT_PATH . '/app/core/Kernel.php';
require_once ROOT_PATH . '/app/core/Router.php';
require_once ROOT_PATH . '/app/core/App.php';
require_once ROOT_PATH . '/app/helpers/Csrf.php';
require_once ROOT_PATH . '/app/helpers/Helpers.php';
require_once ROOT_PATH . '/app/middleware/Middleware.php';
require_once ROOT_PATH . '/app/services/Services.php';
require_once ROOT_PATH . '/app/services/MailService.php';
require_once ROOT_PATH . '/app/models/Article.php';
require_once ROOT_PATH . '/app/models/Models.php';
require_once ROOT_PATH . '/app/controllers/ArticleController.php';
require_once ROOT_PATH . '/app/controllers/Controllers.php';
require_once ROOT_PATH . '/app/controllers/portal/AuthController.php';
require_once ROOT_PATH . '/app/controllers/portal/DashboardController.php';
require_once ROOT_PATH . '/app/controllers/portal/GdprController.php';
require_once ROOT_PATH . '/app/controllers/redac/ArticleController.php';
require_once ROOT_PATH . '/app/controllers/redac/MediaController.php';
require_once ROOT_PATH . '/app/controllers/redac/RedacControllers.php';

// ── Gestion des erreurs ──────────────────────────────────────────
if (APP_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', LOG_PATH . '/php_errors.log');

    set_exception_handler(function (Throwable $e): void {
        \Koonect\Core\Logger::error('Exception non capturée', [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ]);
        http_response_code(500);
        require \Koonect\Core\VIEWS_PATH . '/errors/500.php';
        exit;
    });
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// ── Timezone ─────────────────────────────────────────────────────
date_default_timezone_set('Europe/Paris');

// ── Lancer l'application ─────────────────────────────────────────
(new \Koonect\Core\App())->run();
