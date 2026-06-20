<?php
declare(strict_types=1);

namespace Koonect\Core;

/**
 * App — Point d'entrée du bootstrap.
 * Détermine le sous-domaine et charge les routes correspondantes.
 */
class App
{
    private Router $router;
    private Request $request;

    public function __construct()
    {
        $this->request = new Request();
        $this->router  = new Router();
    }

    public function run(): void
    {
        // Démarrer la session sécurisée
        Session::start();

        // Définir les headers de sécurité
        $this->setSecurityHeaders();

        // Partager des données globales aux vues
        $this->shareGlobalViewData();

        // Charger les routes selon le sous-domaine
        $this->loadRoutes();

        // Dispatcher la requête
        $this->router->dispatch($this->request);
    }

    private function loadRoutes(): void
    {
        // Route files expect a local $router variable in scope.
        $router = $this->router;
        $host = $_SERVER['HTTP_HOST'] ?? 'koonect.fr';

        if (str_starts_with($host, 'portail.')) {
            require APP_PATH . '/routes/portal.php';
        } elseif (str_starts_with($host, 'espace-redactionnel-')) {
            require APP_PATH . '/routes/redac.php';
        } else {
            require APP_PATH . '/routes/web.php';
        }
    }

    private function setSecurityHeaders(): void
    {
        if (headers_sent()) return;

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-" . $this->getCspNonce() . "' https://*.tabnav.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://*.tabnav.com",
            "font-src 'self' https://fonts.gstatic.com https://*.tabnav.com",
            "img-src 'self' data: https:",
            "media-src 'self'",
            "frame-src 'self' https://www.youtube.com https://vimeo.com",
            "connect-src 'self' https://*.tabnav.com",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "upgrade-insecure-requests",
        ]);

        header('Content-Security-Policy: ' . $csp);
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        if (APP_ENV === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }

    private function getCspNonce(): string
    {
        if (!Session::has('csp_nonce')) {
            Session::set('csp_nonce', base64_encode(random_bytes(16)));
        }
        return Session::get('csp_nonce');
    }

    private function shareGlobalViewData(): void
    {
        // Données globales disponibles dans toutes les vues
        View::share('appName',     APP_NAME);
        View::share('appUrl',      APP_URL);
        View::share('portalUrl',   PORTAL_URL);
        View::share('cspNonce',    $this->getCspNonce());
        View::share('flashSuccess',Session::getFlash('success'));
        View::share('flashError',  Session::getFlash('error'));
        View::share('flashInfo',   Session::getFlash('info'));
        View::share('currentUser', Session::get('user'));
        View::share('csrfToken',   \Koonect\Helpers\Csrf::getToken());

        // Paramètres site depuis la DB (cachés)
        View::share('siteSettings', \Koonect\Services\CacheService::remember('site_settings', 3600, function () {
            $db = Database::getInstance();
            $rows = $db->fetchAll('SELECT `key`, `value` FROM settings');
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row['key']] = $row['value'];
            }

            // Si nav_categories n'est pas configuré, charger les catégories de la DB
            if (empty($settings['nav_categories'])) {
                $settings['nav_categories'] = $db->fetchAll('SELECT id, name, slug FROM categories ORDER BY position, name');
            } elseif (is_string($settings['nav_categories'])) {
                $decoded = json_decode($settings['nav_categories'], true);
                if (is_array($decoded)) {
                    $settings['nav_categories'] = $decoded;
                } else {
                    $settings['nav_categories'] = $db->fetchAll('SELECT id, name, slug FROM categories ORDER BY position, name');
                }
            }

            return $settings;
        }));
    }
}
