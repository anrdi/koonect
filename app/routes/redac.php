<?php
declare(strict_types=1);

use Koonect\Controllers\Redac\{DashboardController, ArticleController, MediaController,
                                UserController, SettingsController, AuthController};
use Koonect\Middleware\{CsrfMiddleware, RedacAuthMiddleware, RateLimitMiddleware};

/** @var \Koonect\Core\Router $router */

// ── Robots.txt ────────────────────────────────────────────────────
$router->get('/robots.txt', [\Koonect\Controllers\SitemapController::class, 'robots']);

// ── Login rédaction ───────────────────────────────────────────────
$router->get('/login',  [AuthController::class, 'form']);
$router->post('/login', [AuthController::class, 'login'],  [CsrfMiddleware::class, RateLimitMiddleware::class]);
$router->get('/logout', [AuthController::class, 'logout']);

// ── Espace rédaction (authentifié, rôle rédaction) ────────────────
$router->group('', function ($router) {

    $router->get('/', [DashboardController::class, 'index']);

    // Articles
    $router->get('/articles',                  [ArticleController::class, 'index']);
    $router->get('/articles/nouveau',          [ArticleController::class, 'create']);
    $router->post('/articles/nouveau',         [ArticleController::class, 'store']);
    $router->get('/articles/:id/modifier',     [ArticleController::class, 'edit']);
    $router->post('/articles/:id/modifier',    [ArticleController::class, 'update']);
    $router->post('/articles/:id/supprimer',   [ArticleController::class, 'destroy']);
    $router->post('/articles/:id/statut',      [ArticleController::class, 'updateStatus']);
    $router->get('/articles/:id/historique',   [ArticleController::class, 'revisions']);
    $router->post('/articles/:id/autosave',    [ArticleController::class, 'autosave']);

    // Médias
    $router->get('/medias',                    [MediaController::class, 'index']);
    $router->post('/medias/upload',            [MediaController::class, 'upload']);
    $router->post('/medias/import',            [MediaController::class, 'importFromUrl']);
    $router->post('/medias/:id/supprimer',     [MediaController::class, 'destroy']);
    $router->post('/medias/:id/modifier',      [MediaController::class, 'update']);
    $router->get('/medias/dossiers',           [MediaController::class, 'folders']);
    $router->post('/medias/dossiers',          [MediaController::class, 'createFolder']);

    // Catégories
    $router->get('/categories',                [\Koonect\Controllers\Redac\CategoryController::class, 'index']);
    $router->post('/categories',               [\Koonect\Controllers\Redac\CategoryController::class, 'store']);
    $router->post('/categories/:id',           [\Koonect\Controllers\Redac\CategoryController::class, 'update']);
    $router->post('/categories/:id/supprimer', [\Koonect\Controllers\Redac\CategoryController::class, 'destroy']);

    // Tags
    $router->get('/tags',                      [\Koonect\Controllers\Redac\TagController::class, 'index']);
    $router->post('/tags',                     [\Koonect\Controllers\Redac\TagController::class, 'store']);

    // Commentaires
    $router->get('/commentaires',              [\Koonect\Controllers\Redac\CommentController::class, 'index']);
    $router->post('/commentaires/:id/approuver', [\Koonect\Controllers\Redac\CommentController::class, 'approve']);
    $router->post('/commentaires/:id/rejeter', [\Koonect\Controllers\Redac\CommentController::class, 'reject']);

    // Utilisateurs (admin/directeur uniquement)
    $router->get('/utilisateurs',              [UserController::class, 'index']);
    $router->get('/utilisateurs/nouveau',      [UserController::class, 'create']);
    $router->post('/utilisateurs/nouveau',     [UserController::class, 'store']);
    $router->get('/utilisateurs/:id/modifier', [UserController::class, 'edit']);
    $router->post('/utilisateurs/:id/modifier',[UserController::class, 'update']);

    // Paramètres
    $router->get('/parametres',                [SettingsController::class, 'index']);
    $router->post('/parametres',               [SettingsController::class, 'save']);
    $router->get('/parametres/smtp',           [SettingsController::class, 'smtpTest']);
    $router->get('/parametres/seo',            [SettingsController::class, 'seo']);
    $router->post('/parametres/seo',           [SettingsController::class, 'saveSeo']);
    $router->get('/parametres/rgpd',           [SettingsController::class, 'rgpd']);

    // Newsletter admin
    $router->get('/newsletter',                [\Koonect\Controllers\Redac\NewsletterController::class, 'index']);
    $router->post('/newsletter/campagne',      [\Koonect\Controllers\Redac\NewsletterController::class, 'createCampaign']);
    $router->post('/newsletter/envoyer/:id',   [\Koonect\Controllers\Redac\NewsletterController::class, 'send']);

}, [RedacAuthMiddleware::class, CsrfMiddleware::class]);
