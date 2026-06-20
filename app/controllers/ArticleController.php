<?php
declare(strict_types=1);

namespace Koonect\Controllers;

use Koonect\Core\{Request, Response, View};
use Koonect\Helpers\{Seo, Paginator, Csrf};
use Koonect\Models\{Article, Category, Tag};
use Koonect\Services\CacheService;

class ArticleController
{
    private Article $articleModel;

    public function __construct()
    {
        $this->articleModel = new Article();
    }

    public function home(Request $request): void
    {
        $featured  = CacheService::remember('home_featured', 300, fn() => $this->articleModel->getFeatured());
        $breaking  = CacheService::remember('home_breaking', 120, fn() => $this->articleModel->getBreaking());
        $latest    = CacheService::remember('home_latest',   300, fn() => $this->articleModel->getPublished(12, 0));
        $mostRead  = CacheService::remember('home_mostread', 600, fn() => $this->articleModel->getMostRead(5));
        $categories = CacheService::remember('categories_all', 3600, fn() => (new Category())->all());

        View::render('home/index', [
            'featured'   => $featured,
            'breaking'   => $breaking,
            'latest'     => $latest,
            'mostRead'   => $mostRead,
            'categories' => $categories,
            'seo'        => [
                'seo_title'       => APP_NAME . ' — Journal en ligne — L\'actualité en continu',
                'seo_description' => 'Toute l\'actualité en temps réel sur ' . APP_NAME . '. Politique, économie, société, culture, technologie. Information vérifiée et contextualisée.',
            ],
        ]);
    }

    public function show(Request $request): void
    {
        $slug    = $request->param('slug');
        $article = $this->articleModel->findBySlug($slug);

        if (!$article) {
            // Vérifier les redirections 301
            $db       = \Koonect\Core\Database::getInstance();
            $redirect = $db->fetch('SELECT to_url FROM redirects WHERE from_url = ? LIMIT 1', ['/article/' . $slug]);
            if ($redirect) {
                Response::redirect($redirect['to_url'], 301);
            }
            Response::notFound();
        }

        // Incrémenter les vues (rate-limited par session)
        $viewKey = 'viewed_' . $article['id'];
        if (!\Koonect\Core\Session::has($viewKey)) {
            $this->articleModel->incrementViews($article['id']);
            \Koonect\Core\Session::set($viewKey, true);
        }

        $tags     = $this->articleModel->getTags($article['id']);
        $gallery  = $this->articleModel->getGallery($article['id']);
        $related  = $this->articleModel->getRelated($article['id'], (int)$article['category_id']);
        $comments = (new \Koonect\Models\Comment())->getApproved($article['id']);

        // Image OG
        $ogImage = $article['featured_image_webp']
            ? APP_URL . '/' . ltrim($article['featured_image_webp'], '/')
            : APP_URL . '/assets/img/og-default.jpg';

        $seo = [
            'seo_title'       => $article['seo_title']       ?: $article['title'] . ' — ' . APP_NAME,
            'seo_description' => $article['seo_description']  ?: $article['chapo'],
            'og_image'        => $ogImage,
            'og_type'         => 'article',
            'canonical_url'   => APP_URL . '/article/' . $article['slug'],
        ];

        // Schema.org
        $author     = ['display_name' => $article['author_name'], 'username' => $article['author_username']];
        $schemaJson = Seo::schemaArticle($article, $author, $ogImage);

        // BreadcrumbList JSON-LD
        $breadcrumbItems = [
            ['name' => 'Accueil', 'url' => APP_URL],
        ];
        if ($article['category_name']) {
            $breadcrumbItems[] = ['name' => $article['category_name'], 'url' => APP_URL . '/categorie/' . $article['category_slug']];
        }
        $breadcrumbItems[] = ['name' => $article['title']];
        $schemaJson .= "\n" . Seo::schemaBreadcrumb($breadcrumbItems);

        // Historique lecture (si connecté via SSO)
        $user = \Koonect\Core\Session::get('user');
        if ($user) {
            $db = \Koonect\Core\Database::getInstance();
            $db->execute(
                'INSERT INTO reading_history (user_id, article_id, read_at) VALUES (?, ?, NOW())
                 ON DUPLICATE KEY UPDATE read_at = NOW()',
                [$user['id'], $article['id']]
            );
        }

        View::render('article/show', [
            'article'    => $article,
            'tags'       => $tags,
            'gallery'    => $gallery,
            'related'    => $related,
            'comments'   => $comments,
            'seo'        => $seo,
            'schemaJson' => $schemaJson,
            'csrfToken'  => Csrf::getToken(),
        ]);
    }

    public function author(Request $request): void
    {
        $username = $request->param('username');
        $db       = \Koonect\Core\Database::getInstance();
        $author   = $db->fetch(
            'SELECT id, display_name, username, avatar, role FROM users WHERE username = ? AND deleted_at IS NULL LIMIT 1',
            [$username]
        );

        if (!$author) Response::notFound();

        $page     = max(1, (int)$request->get('page', 1));
        $total    = (int)($db->fetch(
            'SELECT COUNT(*) AS cnt FROM articles WHERE author_id = ? AND status = "published" AND deleted_at IS NULL',
            [$author['id']]
        )['cnt'] ?? 0);

        $paginator = new Paginator($total, ARTICLES_PER_PAGE, $page);
        $articles  = $this->articleModel->getPublished(ARTICLES_PER_PAGE, $paginator->offset, null, null, (int)$author['id']);

        $seoDescription = $author['display_name'] . ', '
            . (match($author['role']) {
                'admin'        => 'administrateur',
                'director'     => 'directeur de la publication',
                'chief_editor' => 'rédacteur en chef',
                'journalist'   => 'journaliste',
                'proofreader'  => 'correcteur',
                default        => 'rédacteur',
            })
            . ' chez ' . APP_NAME . '. ' . $total . ' article'
            . ($total > 1 ? 's' : '') . ' publié' . ($total > 1 ? 's' : '') . '.';

        // BreadcrumbList JSON-LD
        $breadcrumbJson = Seo::schemaBreadcrumb([
            ['name' => 'Accueil', 'url' => APP_URL],
            ['name' => $author['display_name']],
        ]);

        // Person JSON-LD (E-E-A-T)
        $personJson = Seo::schemaPerson($author, $total);

        View::render('author/show', [
            'author'    => $author,
            'articles'  => $articles,
            'paginator' => $paginator,
            'total'     => $total,
            'schemaJson' => $breadcrumbJson . "\n" . $personJson,
            'seo'       => [
                'seo_title'       => $author['display_name'] . ' — Journaliste — ' . APP_NAME,
                'seo_description' => $seoDescription,
                'paginator'       => $paginator,
            ],
        ]);
    }

    public function dossiers(Request $request): void
    {
        $db      = \Koonect\Core\Database::getInstance();
        $dossiers = $db->fetchAll(
            'SELECT d.*,
                    m.path AS cover_image_path,
                    m.webp_path AS cover_image_webp,
                    m.thumb_path AS cover_image_thumb,
                    m.alt_text AS cover_image_alt,
                    COALESCE(c.article_count, 0) AS article_count
             FROM dossiers d
             LEFT JOIN media m ON m.id = d.cover_image_id
             LEFT JOIN (
                 SELECT dossier_id, COUNT(*) AS article_count
                 FROM article_dossiers
                 GROUP BY dossier_id
             ) c ON c.dossier_id = d.id
             ORDER BY d.created_at DESC'
        );
        // BreadcrumbList JSON-LD
        $breadcrumbJson = Seo::schemaBreadcrumb([
            ['name' => 'Accueil', 'url' => APP_URL],
            ['name' => 'Dossiers'],
        ]);

        View::render('article/dossiers', [
            'dossiers' => $dossiers,
            'schemaJson' => $breadcrumbJson,
            'seo'      => [
                'seo_title'       => 'Dossiers thématiques et enquêtes — ' . APP_NAME,
                'seo_description' => 'Explorez les dossiers thématiques et les enquêtes approfondies de ' . APP_NAME . '. Analyses, décryptages et reportages de fond.',
            ],
        ]);
    }

    public function dossier(Request $request): void
    {
        $slug    = $request->param('slug');
        $db      = \Koonect\Core\Database::getInstance();
        $dossier = $db->fetch(
            'SELECT d.*,
                    m.path AS cover_image_path,
                    m.webp_path AS cover_image_webp,
                    m.thumb_path AS cover_image_thumb,
                    m.alt_text AS cover_image_alt,
                    COALESCE(c.article_count, 0) AS article_count
             FROM dossiers d
             LEFT JOIN media m ON m.id = d.cover_image_id
             LEFT JOIN (
                 SELECT dossier_id, COUNT(*) AS article_count
                 FROM article_dossiers
                 GROUP BY dossier_id
             ) c ON c.dossier_id = d.id
             WHERE d.slug = ? LIMIT 1',
            [$slug]
        );
        if (!$dossier) Response::notFound();

        $seoTitle = $dossier['meta_title'] ?: $dossier['title'] . ' — Dossier — ' . APP_NAME;
        $seoDescription = $dossier['meta_description']
            ?: ($dossier['description']
                ?: 'Dossier thématique ' . $dossier['title'] . ' sur ' . APP_NAME);

        $articles = $db->fetchAll(
            'SELECT a.id, a.title, a.chapo, a.slug, a.published_at,
                    m.thumb_path AS featured_image_thumb
             FROM articles a
             INNER JOIN article_dossiers ad ON a.id = ad.article_id AND ad.dossier_id = ?
             LEFT JOIN media m ON a.featured_image_id = m.id
             WHERE a.status = "published" AND a.deleted_at IS NULL
             ORDER BY a.published_at DESC',
            [$dossier['id']]
        );

        View::render('article/dossier', [
            'dossier'  => $dossier,
            'articles' => $articles,
            'seo'      => [
                'seo_title'       => $seoTitle,
                'seo_description' => $seoDescription,
            ],
        ]);
    }

    public function postComment(Request $request): void
    {
        $user = \Koonect\Core\Session::get('user');
        if (!$user) {
            Response::json(['error' => 'Connexion requise.'], 401);
        }

        $articleId = (int)$request->post('article_id');
        $content   = trim((string)$request->post('content', ''));
        $parentId  = $request->post('parent_id') ? (int)$request->post('parent_id') : null;

        if (strlen($content) < 10 || strlen($content) > 2000) {
            Response::json(['error' => 'Le commentaire doit contenir entre 10 et 2000 caractères.'], 422);
        }

        $commentModel = new \Koonect\Models\Comment();
        $id = $commentModel->create($articleId, (int)$user['id'], $content, $parentId, $request->ip());

        Response::json(['success' => true, 'id' => $id, 'message' => 'Commentaire publié avec succès.']);
    }

    public function apiBreaking(Request $request): void
    {
        $breaking = CacheService::remember('api_breaking', 120, fn() => $this->articleModel->getBreaking());
        Response::json(['data' => $breaking]);
    }

    public function apiMostRead(Request $request): void
    {
        $data = CacheService::remember('api_mostread', 600, fn() => $this->articleModel->getMostRead(5));
        Response::json(['data' => $data]);
    }
}
