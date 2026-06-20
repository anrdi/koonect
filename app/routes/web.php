<?php
declare(strict_types=1);

use Koonect\Controllers\{ArticleController, CategoryController, TagController,
                         SearchController, SitemapController, NewsletterController, PageController};
use Koonect\Middleware\{CsrfMiddleware, RateLimitMiddleware};

/** @var \Koonect\Core\Router $router */

// ── Pages publiques ───────────────────────────────────────────────

$router->get('/', [ArticleController::class, 'home']);
$router->get('/article/:slug', [ArticleController::class, 'show']);
$router->get('/categorie/:slug', [CategoryController::class, 'show']);
$router->get('/tag/:slug', [TagController::class, 'show']);
$router->get('/auteur/:username', [ArticleController::class, 'author']);
$router->get('/recherche', [SearchController::class, 'index']);
$router->get('/dossiers', [ArticleController::class, 'dossiers']);
$router->get('/dossier/:slug', [ArticleController::class, 'dossier']);

// ── Redirections 301 (slug changé) ────────────────────────────────
$router->get('/article/:slug', [ArticleController::class, 'checkRedirect']);

// ── Newsletter ────────────────────────────────────────────────────
$router->post('/newsletter/inscription',
    [NewsletterController::class, 'subscribe'],
    [CsrfMiddleware::class, RateLimitMiddleware::class]
);
$router->get('/newsletter/confirmer', [NewsletterController::class, 'confirm']);
$router->get('/newsletter/desabonnement', [NewsletterController::class, 'unsubscribe']);

// ── Pages statiques ───────────────────────────────────────────────
$router->get('/mentions-legales',       [PageController::class, 'mentionsLegales']);
$router->get('/cgu',                    [PageController::class, 'cgu']);
$router->get('/politique-de-cookies',   [PageController::class, 'cookies']);
$router->get('/rgpd',                   [PageController::class, 'rgpd']);
$router->get('/contact',               [PageController::class, 'contact']);
$router->post('/contact',              [PageController::class, 'contactSend'], [CsrfMiddleware::class, RateLimitMiddleware::class]);

// ── SEO ───────────────────────────────────────────────────────────
$router->get('/sitemap.xml',            [SitemapController::class, 'index']);
$router->get('/sitemap-articles.xml',   [SitemapController::class, 'articles']);
$router->get('/sitemap-categories.xml', [SitemapController::class, 'categories']);
$router->get('/sitemap-tags.xml',       [SitemapController::class, 'tags']);
$router->get('/sitemap-dossiers.xml',   [SitemapController::class, 'dossiers']);
$router->get('/sitemap-pages.xml',      [SitemapController::class, 'pages']);
$router->get('/sitemap-authors.xml',    [SitemapController::class, 'authors']);
$router->get('/sitemap-news.xml',       [SitemapController::class, 'news']);
$router->get('/robots.txt',             [SitemapController::class, 'robots']);
$router->get('/rss.xml',                [SitemapController::class, 'rss']);

// Pages statiques dynamiques (doit être après les routes spécifiques pour éviter de les intercepter)
$router->get('/:slug',                  [PageController::class, 'show']);

// ── API JSON (lecture, sans auth) ─────────────────────────────────
$router->get('/api/breaking-news',  [ArticleController::class, 'apiBreaking']);
$router->get('/api/most-read',      [ArticleController::class, 'apiMostRead']);
$router->get('/api/search',         [SearchController::class, 'apiSearch']);

// ── Commentaires (authentifié côté portail via SSO) ───────────────
$router->post('/api/commentaire', [ArticleController::class, 'postComment'], [CsrfMiddleware::class]);
