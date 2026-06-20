<?php
declare(strict_types=1);

use Koonect\Controllers\Portal\{AuthController, ProfileController, DashboardController, GdprController};
use Koonect\Middleware\{CsrfMiddleware, AuthMiddleware, RateLimitMiddleware};

/** @var \Koonect\Core\Router $router */

// ── Robots.txt ────────────────────────────────────────────────────
$router->get('/robots.txt', [\Koonect\Controllers\SitemapController::class, 'robots']);

// ── Authentification ──────────────────────────────────────────────
$router->get('/connexion',                   [AuthController::class, 'loginForm']);
$router->post('/connexion',                  [AuthController::class, 'login'],    [CsrfMiddleware::class, RateLimitMiddleware::class]);
$router->get('/inscription',                 [AuthController::class, 'registerForm']);
$router->post('/inscription',                [AuthController::class, 'register'], [CsrfMiddleware::class, RateLimitMiddleware::class]);
$router->get('/deconnexion',                 [AuthController::class, 'logout']);
$router->get('/verifier-email',              [AuthController::class, 'verifyEmail']);
$router->get('/mot-de-passe-oublie',         [AuthController::class, 'forgotForm']);
$router->post('/mot-de-passe-oublie',        [AuthController::class, 'forgotSend'], [CsrfMiddleware::class, RateLimitMiddleware::class]);
$router->get('/reinitialiser-mot-de-passe',  [AuthController::class, 'resetForm']);
$router->post('/reinitialiser-mot-de-passe', [AuthController::class, 'resetPassword'], [CsrfMiddleware::class]);
$router->get('/2fa',                         [AuthController::class, 'twoFactorForm']);
$router->post('/2fa',                        [AuthController::class, 'twoFactorVerify'], [CsrfMiddleware::class, RateLimitMiddleware::class]);

// ── Espace abonné (authentifié) ───────────────────────────────────
$router->group('', function ($router) {
    $router->get('/',                [DashboardController::class, 'index']);
    $router->get('/profil',          [ProfileController::class, 'show']);
    $router->post('/profil',         [ProfileController::class, 'update']);
    $router->get('/favoris',         [DashboardController::class, 'favorites']);
    $router->post('/favoris/:id',    [DashboardController::class, 'toggleFavorite']);
    $router->get('/historique',      [DashboardController::class, 'history']);
    $router->get('/commentaires',    [DashboardController::class, 'comments']);
    $router->get('/newsletter',      [DashboardController::class, 'newsletterSettings']);
    $router->post('/newsletter',     [DashboardController::class, 'updateNewsletter']);
    $router->get('/2fa/configurer',  [AuthController::class, 'setup2faForm']);
    $router->post('/2fa/configurer', [AuthController::class, 'setup2fa']);
    $router->get('/2fa/desactiver',  [AuthController::class, 'disable2fa']);

    // RGPD
    $router->get('/donnees',          [GdprController::class, 'index']);
    $router->get('/donnees/exporter', [GdprController::class, 'export']);
    $router->post('/donnees/supprimer', [GdprController::class, 'deleteAccount']);
    $router->get('/cookies',          [GdprController::class, 'cookies']);
    $router->post('/cookies',         [GdprController::class, 'updateConsent']);

}, [AuthMiddleware::class, CsrfMiddleware::class]);
