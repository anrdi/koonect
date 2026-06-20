<?php
declare(strict_types=1);

namespace Koonect\Controllers;

use Koonect\Core\{Request, Response, View, Database};
use Koonect\Models\{Article, Category as CategoryModel, Tag as TagModel, Newsletter as NewsletterModel};
use Koonect\Helpers\{Paginator, Sanitizer};
use Koonect\Services\{CacheService, MailService};

// ═══════════════════════════════════════════════════════════════════
// SITEMAP
// ═══════════════════════════════════════════════════════════════════
class SitemapController
{
    public function index(Request $request): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        $baseUrl = APP_URL;
        
        $db = Database::getInstance();
        
        // Date de dernière mise à jour réelle des articles
        $latestArticle = $db->fetch('SELECT MAX(updated_at) AS lastmod FROM articles WHERE status="published" AND deleted_at IS NULL');
        $artLastmod = $latestArticle['lastmod'] ? date('c', strtotime($latestArticle['lastmod'])) : date('c');

        // Date de dernière mise à jour des pages
        $latestPage = $db->fetch('SELECT MAX(updated_at) AS lastmod FROM pages WHERE status="published"');
        $pageLastmod = $latestPage['lastmod'] ? date('c', strtotime($latestPage['lastmod'])) : date('c');

        $now = date('c');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        echo "  <sitemap><loc>{$baseUrl}/sitemap-articles.xml</loc><lastmod>{$artLastmod}</lastmod></sitemap>\n";
        echo "  <sitemap><loc>{$baseUrl}/sitemap-categories.xml</loc><lastmod>{$now}</lastmod></sitemap>\n";
        echo "  <sitemap><loc>{$baseUrl}/sitemap-tags.xml</loc><lastmod>{$now}</lastmod></sitemap>\n";
        echo "  <sitemap><loc>{$baseUrl}/sitemap-dossiers.xml</loc><lastmod>{$now}</lastmod></sitemap>\n";
        echo "  <sitemap><loc>{$baseUrl}/sitemap-pages.xml</loc><lastmod>{$pageLastmod}</lastmod></sitemap>\n";
        echo "  <sitemap><loc>{$baseUrl}/sitemap-authors.xml</loc><lastmod>{$now}</lastmod></sitemap>\n";
        echo "  <sitemap><loc>{$baseUrl}/sitemap-news.xml</loc><lastmod>{$now}</lastmod></sitemap>\n";
        echo '</sitemapindex>';
        exit;
    }

    /**
     * Sitemap Google News — articles publiés dans les 48 dernières heures.
     */
    public function news(Request $request): void
    {
        $db = Database::getInstance();
        $articles = CacheService::remember('sitemap_news', 900, function () use ($db) {
            return $db->fetchAll(
                "SELECT a.title, a.slug, a.published_at, c.name AS category_name
                 FROM articles a
                 LEFT JOIN categories c ON a.category_id = c.id
                 WHERE a.status = 'published' AND a.deleted_at IS NULL
                 AND a.published_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                 ORDER BY a.published_at DESC"
            );
        });

        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=900');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        echo '        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";

        foreach ($articles as $a) {
            $loc = htmlspecialchars(APP_URL . '/article/' . $a['slug'], ENT_XML1);
            $pubDate = date('c', strtotime($a['published_at']));
            $title = htmlspecialchars($a['title'], ENT_XML1);
            $keywords = htmlspecialchars($a['category_name'] ?? '', ENT_XML1);
            echo "  <url>\n";
            echo "    <loc>{$loc}</loc>\n";
            echo "    <news:news>\n";
            echo "      <news:publication>\n";
            echo "        <news:name>" . htmlspecialchars(APP_NAME, ENT_XML1) . "</news:name>\n";
            echo "        <news:language>fr</news:language>\n";
            echo "      </news:publication>\n";
            echo "      <news:publication_date>{$pubDate}</news:publication_date>\n";
            echo "      <news:title>{$title}</news:title>\n";
            if ($keywords) {
                echo "      <news:keywords>{$keywords}</news:keywords>\n";
            }
            echo "    </news:news>\n";
            echo "  </url>\n";
        }
        echo '</urlset>';
        exit;
    }

    public function articles(Request $request): void
    {
        $articles = CacheService::remember('sitemap_articles', 3600, function () {
            return (new Article())->getAllForSitemap();
        });

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Homepage
        echo "  <url><loc>" . APP_URL . "/</loc><changefreq>hourly</changefreq><priority>1.0</priority></url>\n";

        foreach ($articles as $a) {
            $loc     = htmlspecialchars(APP_URL . '/article/' . $a['slug'], ENT_XML1);
            $lastmod = date('c', strtotime($a['updated_at']));
            echo "  <url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>\n";
        }
        echo '</urlset>';
        exit;
    }

    public function categories(Request $request): void
    {
        $categories = (new CategoryModel())->all();
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($categories as $c) {
            $loc = htmlspecialchars(APP_URL . '/categorie/' . $c['slug'], ENT_XML1);
            echo "  <url><loc>{$loc}</loc><changefreq>daily</changefreq><priority>0.7</priority></url>\n";
        }
        echo '</urlset>';
        exit;
    }

    public function tags(Request $request): void
    {
        $db = Database::getInstance();
        $tags = CacheService::remember('sitemap_tags', 3600, function () use ($db) {
            return $db->fetchAll(
                "SELECT DISTINCT t.slug FROM tags t
                 INNER JOIN article_tags at2 ON t.id = at2.tag_id
                 INNER JOIN articles a ON a.id = at2.article_id
                 WHERE a.status = 'published' AND a.deleted_at IS NULL"
            );
        });

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($tags as $t) {
            $loc = htmlspecialchars(APP_URL . '/tag/' . $t['slug'], ENT_XML1);
            echo "  <url><loc>{$loc}</loc><changefreq>weekly</changefreq><priority>0.6</priority></url>\n";
        }
        echo '</urlset>';
        exit;
    }

    public function dossiers(Request $request): void
    {
        $db = Database::getInstance();
        $dossiers = CacheService::remember('sitemap_dossiers', 3600, function () use ($db) {
            return $db->fetchAll(
                "SELECT DISTINCT d.slug FROM dossiers d
                 INNER JOIN article_dossiers ad ON d.id = ad.dossier_id
                 INNER JOIN articles a ON a.id = ad.article_id
                 WHERE a.status = 'published' AND a.deleted_at IS NULL"
            );
        });

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($dossiers as $d) {
            $loc = htmlspecialchars(APP_URL . '/dossier/' . $d['slug'], ENT_XML1);
            echo "  <url><loc>{$loc}</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>\n";
        }
        echo '</urlset>';
        exit;
    }

    public function pages(Request $request): void
    {
        $db = Database::getInstance();
        $pages = CacheService::remember('sitemap_pages', 3600, function () use ($db) {
            return $db->fetchAll(
                "SELECT slug, updated_at FROM pages WHERE status = 'published'"
            );
        });

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Pages statiques fixes
        $contactLoc = htmlspecialchars(APP_URL . '/contact', ENT_XML1);
        $dossiersLoc = htmlspecialchars(APP_URL . '/dossiers', ENT_XML1);
        echo "  <url><loc>{$contactLoc}</loc><changefreq>monthly</changefreq><priority>0.5</priority></url>\n";
        echo "  <url><loc>{$dossiersLoc}</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>\n";

        foreach ($pages as $p) {
            $loc = htmlspecialchars(APP_URL . '/' . $p['slug'], ENT_XML1);
            $lastmod = date('c', strtotime($p['updated_at']));
            echo "  <url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><changefreq>monthly</changefreq><priority>0.5</priority></url>\n";
        }
        echo '</urlset>';
        exit;
    }

    public function authors(Request $request): void
    {
        $db = Database::getInstance();
        $authors = CacheService::remember('sitemap_authors', 3600, function () use ($db) {
            return $db->fetchAll(
                "SELECT DISTINCT u.username, MAX(a.updated_at) AS lastmod
                 FROM users u
                 INNER JOIN articles a ON u.id = a.author_id
                 WHERE a.status = 'published' AND a.deleted_at IS NULL
                 GROUP BY u.id"
            );
        });

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($authors as $auth) {
            $loc = htmlspecialchars(APP_URL . '/auteur/' . $auth['username'], ENT_XML1);
            $lastmod = date('c', strtotime($auth['lastmod']));
            echo "  <url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><changefreq>weekly</changefreq><priority>0.5</priority></url>\n";
        }
        echo '</urlset>';
        exit;
    }

    public function robots(Request $request): void
    {
        header('Content-Type: text/plain');
        header('Cache-Control: public, max-age=86400');
        $host = $_SERVER['HTTP_HOST'] ?? 'koonect.fr';

        // Bloquer l'indexation sur les sous-domaines privés
        if (str_starts_with($host, 'portail.') || str_starts_with($host, 'espace-redactionnel-')) {
            echo "User-agent: *\n";
            echo "Disallow: /\n";
            exit;
        }
        // Tous les autres bots
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "\n";
        echo "Allow: /assets/\n";
        echo "Disallow: /api/\n";
        echo "Disallow: /recherche\n";
        echo "Disallow: /newsletter/\n";
        echo "Disallow: /app/\n";
        echo "\n";
        echo "Crawl-delay: 2\n";
        echo "\n";

        // Déclarer individuellement tous les sitemaps pour maximiser leur visibilité et indexation
        echo "Sitemap: " . APP_URL . "/sitemap.xml\n";
        echo "Sitemap: " . APP_URL . "/sitemap-articles.xml\n";
        echo "Sitemap: " . APP_URL . "/sitemap-categories.xml\n";
        echo "Sitemap: " . APP_URL . "/sitemap-tags.xml\n";
        echo "Sitemap: " . APP_URL . "/sitemap-dossiers.xml\n";
        echo "Sitemap: " . APP_URL . "/sitemap-pages.xml\n";
        echo "Sitemap: " . APP_URL . "/sitemap-authors.xml\n";
        echo "Sitemap: " . APP_URL . "/sitemap-news.xml\n";
        exit;
    }

    public function rss(Request $request): void
    {
        $articles = CacheService::remember('rss_feed', 600, function () {
            return (new Article())->getPublished(20, 0);
        });

        header('Content-Type: application/rss+xml; charset=utf-8');
        header('Cache-Control: public, max-age=600');
        echo '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
        echo '<rss version="2.0"' . "\n";
        echo '  xmlns:atom="http://www.w3.org/2005/Atom"' . "\n";
        echo '  xmlns:content="http://purl.org/rss/1.0/modules/content/"' . "\n";
        echo '  xmlns:dc="http://purl.org/dc/elements/1.1/"' . "\n";
        echo '  xmlns:media="http://search.yahoo.com/mrss/">' . "\n";
        echo '  <channel>' . "\n";
        echo '    <title>' . htmlspecialchars(APP_NAME, ENT_XML1) . '</title>' . "\n";
        echo '    <link>' . APP_URL . '</link>' . "\n";
        echo '    <description>Journal en ligne — L\'actualité en continu, vérifiée et contextualisée</description>' . "\n";
        echo '    <language>fr</language>' . "\n";
        echo '    <copyright>© ' . date('Y') . ' ' . htmlspecialchars(APP_NAME, ENT_XML1) . '. Tous droits réservés.</copyright>' . "\n";
        echo '    <lastBuildDate>' . date('r') . '</lastBuildDate>' . "\n";
        echo '    <atom:link href="' . APP_URL . '/rss.xml" rel="self" type="application/rss+xml" />' . "\n";
        echo '    <image>' . "\n";
        echo '      <url>' . APP_URL . '/assets/img/logo.png</url>' . "\n";
        echo '      <title>' . htmlspecialchars(APP_NAME, ENT_XML1) . '</title>' . "\n";
        echo '      <link>' . APP_URL . '</link>' . "\n";
        echo '    </image>' . "\n";

        foreach ($articles as $a) {
            $link = htmlspecialchars(APP_URL . '/article/' . $a['slug'], ENT_XML1);
            $pubDate = date('r', strtotime($a['published_at']));
            $chapo = $a['chapo'] ?? '';
            $authorName = htmlspecialchars($a['author_name'] ?? '', ENT_XML1);
            $categoryName = htmlspecialchars($a['category_name'] ?? '', ENT_XML1);

            // Préparer la balise HTML img pour Discord et les lecteurs RSS
            $imgHtml = '';
            $imgSrc = '';
            if (!empty($a['featured_image_webp'])) {
                $rawImgSrc = $a['featured_image_webp'];
                if (!preg_match('#^https?://#i', $rawImgSrc)) {
                    $rawImgSrc = APP_URL . '/' . ltrim($rawImgSrc, '/');
                }
                $imgSrc = htmlspecialchars($rawImgSrc, ENT_XML1);
                $imgHtml = '<img src="' . htmlspecialchars($rawImgSrc, ENT_QUOTES) . '" alt="' . htmlspecialchars($a['featured_image_alt'] ?? '', ENT_QUOTES) . '" /><br />';
            }

            echo '    <item>' . "\n";
            echo '      <title><![CDATA[' . $a['title'] . ']]></title>' . "\n";
            echo '      <link>' . $link . '</link>' . "\n";
            echo '      <guid isPermaLink="true">' . $link . '</guid>' . "\n";
            echo '      <pubDate>' . $pubDate . '</pubDate>' . "\n";
            echo '      <description><![CDATA[' . $imgHtml . $chapo . ']]></description>' . "\n";

            if ($authorName) {
                echo '      <dc:creator>' . $authorName . '</dc:creator>' . "\n";
            }
            if ($categoryName) {
                echo '      <category>' . $categoryName . '</category>' . "\n";
            }

            // Image featured comme enclosure media et thumbnail
            if ($imgSrc) {
                $imgLength = (int)($a['featured_image_size'] ?? 0);
                echo '      <media:content url="' . $imgSrc . '" medium="image" type="image/webp" />' . "\n";
                echo '      <media:thumbnail url="' . $imgSrc . '" />' . "\n";
                echo '      <enclosure url="' . $imgSrc . '" length="' . $imgLength . '" type="image/webp" />' . "\n";
            }

            echo '      <content:encoded><![CDATA['
                . $imgHtml
                . ($a['chapo'] ? '<p><strong>' . htmlspecialchars($a['chapo'], ENT_QUOTES) . '</strong></p>' : '')
                . ($a['subtitle'] ? '<h2>' . htmlspecialchars($a['subtitle'], ENT_QUOTES) . '</h2>' : '')
                . ']]></content:encoded>' . "\n";
            echo '    </item>' . "\n";
        }

        echo '  </channel>' . "\n";
        echo '</rss>';
        exit;
    }
}

// ═══════════════════════════════════════════════════════════════════
// SEARCH
// ═══════════════════════════════════════════════════════════════════
class SearchController
{
    public function index(Request $request): void
    {
        $query = trim((string)$request->get('q', ''));
        $page  = max(1, (int)$request->get('page', 1));

        if (strlen($query) < 2) {
            View::render('search/results', ['articles' => [], 'query' => $query, 'paginator' => null]);
            return;
        }

        $model     = new Article();
        $db        = Database::getInstance();
        $q         = '%' . $query . '%';
        $total     = (int)($db->fetch(
            'SELECT COUNT(*) AS cnt FROM articles WHERE status="published" AND deleted_at IS NULL AND (title LIKE ? OR chapo LIKE ? OR content LIKE ?)',
            [$q, $q, $q]
        )['cnt'] ?? 0);

        $paginator = new Paginator($total, ARTICLES_PER_PAGE, $page);
        $articles  = $model->search($query, ARTICLES_PER_PAGE, $paginator->offset);

        View::render('search/results', [
            'articles'  => $articles,
            'query'     => $query,
            'paginator' => $paginator,
            'total'     => $total,
            'seo'       => [
                'seo_title' => 'Recherche : ' . htmlspecialchars($query) . ' — ' . APP_NAME,
                'robots'    => 'noindex, nofollow'
            ],
        ]);
    }

    public function apiSearch(Request $request): void
    {
        $query = trim((string)$request->get('q', ''));
        if (strlen($query) < 2) {
            Response::json(['data' => []]);
        }

        $model   = new Article();
        $results = $model->search($query, 5, 0);
        $data    = array_map(fn($a) => [
            'title' => $a['title'],
            'url'   => APP_URL . '/article/' . $a['slug'],
            'thumb' => $a['featured_image_thumb'] ? APP_URL . '/' . $a['featured_image_thumb'] : null,
            'date'  => $a['published_at'],
        ], $results);

        Response::json(['data' => $data]);
    }
}

// ═══════════════════════════════════════════════════════════════════
// NEWSLETTER (public)
// ═══════════════════════════════════════════════════════════════════
class NewsletterController
{
    public function subscribe(Request $request): void
    {
        $email  = Sanitizer::email($request->post('email', ''));
        $userId = \Koonect\Core\Session::get('user')['id'] ?? null;

        if (!$email) {
            if ($request->isAjax()) Response::json(['error' => 'Email invalide.'], 422);
            \Koonect\Core\Session::flash('error', 'Adresse email invalide.');
            Response::redirect($request->post('redirect', APP_URL));
        }

        $model  = new NewsletterModel();
        $result = $model->subscribe((string)$email, $userId ? (int)$userId : null, $request->ip());

        if ($result === 'already_confirmed') {
            if ($request->isAjax()) Response::json(['message' => 'Vous êtes déjà inscrit.']);
            \Koonect\Core\Session::flash('info', 'Vous êtes déjà inscrit à notre newsletter.');
        } else {
            MailService::sendNewsletterConfirmation((string)$email, $result);
            if ($request->isAjax()) Response::json(['success' => true, 'message' => 'Vérifiez vos emails pour confirmer votre inscription.']);
            \Koonect\Core\Session::flash('success', 'Vérifiez vos emails pour confirmer votre inscription à la newsletter.');
        }

        Response::redirect($request->post('redirect', APP_URL));
    }

    public function confirm(Request $request): void
    {
        $token  = $request->get('token', '');
        $model  = new NewsletterModel();
        $result = $model->confirm($token);

        if ($result) {
            \Koonect\Core\Session::flash('success', 'Votre inscription à la newsletter est confirmée !');
        } else {
            \Koonect\Core\Session::flash('error', 'Lien de confirmation invalide ou déjà utilisé.');
        }
        Response::redirect(APP_URL);
    }

    public function unsubscribe(Request $request): void
    {
        $token  = $request->get('token', '');
        $model  = new NewsletterModel();
        $result = $model->unsubscribe($token);

        View::render('home/unsubscribe', [
            'success' => $result,
            'seo'     => ['seo_title' => 'Désabonnement newsletter — ' . APP_NAME],
        ]);
    }
}

// ═══════════════════════════════════════════════════════════════════
// CATEGORY (public)
// ═══════════════════════════════════════════════════════════════════
class CategoryController
{
    public function show(Request $request): void
    {
        $slug     = $request->param('slug');
        $model    = new CategoryModel();
        $category = $model->findBySlug($slug);
        if (!$category) Response::notFound();

        $page      = max(1, (int)$request->get('page', 1));
        $artModel  = new Article();
        $total     = $artModel->countPublished((int)$category['id']);
        $paginator = new Paginator($total, ARTICLES_PER_PAGE, $page);
        $articles  = $artModel->getPublished(ARTICLES_PER_PAGE, $paginator->offset, (int)$category['id']);

        $seoTitle = ($category['meta_title'] ?: $category['name'] . ' — Actualités et articles') . ' — ' . APP_NAME;
        $seoDesc  = $category['meta_description']
            ?: ($category['description']
                ?: 'Retrouvez tous les articles de la rubrique ' . $category['name'] . ' sur ' . APP_NAME . '.');

        // BreadcrumbList JSON-LD
        $breadcrumbJson = \Koonect\Helpers\Seo::schemaBreadcrumb([
            ['name' => 'Accueil', 'url' => APP_URL],
            ['name' => $category['name']],
        ]);

        // ItemList JSON-LD
        $itemListJson = !empty($articles)
            ? \Koonect\Helpers\Seo::schemaItemList($articles, $category['name'] . ' — ' . APP_NAME)
            : '';

        View::render('category/show', [
            'category'  => $category,
            'articles'  => $articles,
            'paginator' => $paginator,
            'total'     => $total,
            'schemaJson' => $breadcrumbJson . "\n" . $itemListJson,
            'seo'       => [
                'seo_title'       => $seoTitle,
                'seo_description' => $seoDesc,
                'og_image'        => $category['og_image'],
                'paginator'       => $paginator,
            ],
        ]);
    }
}

// ═══════════════════════════════════════════════════════════════════
// PAGE (public - pages statiques)
// ═══════════════════════════════════════════════════════════════════
class PageController
{
    public function mentionsLegales(Request $request): void
    {
        $this->renderPage('mentions-legales', 'Mentions légales');
    }
    public function cgu(Request $request): void
    {
        $this->renderPage('cgu', 'Conditions générales d\'utilisation');
    }
    public function cookies(Request $request): void
    {
        $this->renderPage('politique-de-cookies', 'Politique de cookies');
    }
    public function rgpd(Request $request): void
    {
        $this->renderPage('rgpd', 'Protection des données (RGPD)');
    }

    public function show(Request $request): void
    {
        $slug = $request->param('slug');
        $db   = Database::getInstance();
        $page = $db->fetch('SELECT * FROM pages WHERE slug = ? AND status = "published" LIMIT 1', [$slug]);
        
        if (!$page) {
            Response::notFound();
        }

        View::render('home/static-page', [
            'page' => $page,
            'seo'  => ['seo_title' => ($page['meta_title'] ?? $page['title']) . ' — ' . APP_NAME],
        ]);
    }

    public function contact(Request $request): void
    {
        View::render('home/contact', [
            'seo' => ['seo_title' => 'Contact — ' . APP_NAME],
        ]);
    }

    public function contactSend(Request $request): void
    {
        $name    = Sanitizer::clean($request->post('name', ''));
        $email   = Sanitizer::email($request->post('email', ''));
        $subject = Sanitizer::clean($request->post('subject', ''));
        $message = Sanitizer::clean($request->post('message', ''));

        if (!$email || strlen($message) < 20) {
            \Koonect\Core\Session::flash('error', 'Veuillez remplir tous les champs correctement.');
            Response::redirect(APP_URL . '/contact');
        }

        $html = "<p><strong>De :</strong> {$name} ({$email})</p><p><strong>Sujet :</strong> {$subject}</p><p>{$message}</p>";
        MailService::send(SMTP_FROM_EMAIL, APP_NAME . ' Contact', "Message de $name : $subject", $html);

        \Koonect\Core\Session::flash('success', 'Votre message a été envoyé. Nous vous répondrons dans les meilleurs délais.');
        Response::redirect(APP_URL . '/contact');
    }

    private function renderPage(string $slug, string $defaultTitle): void
    {
        $db   = Database::getInstance();
        $page = $db->fetch('SELECT * FROM pages WHERE slug = ? AND status = "published" LIMIT 1', [$slug]);
        View::render('home/static-page', [
            'page' => $page,
            'seo'  => ['seo_title' => ($page['meta_title'] ?? $defaultTitle) . ' — ' . APP_NAME],
        ]);
    }
}
