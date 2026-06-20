<?php
declare(strict_types=1);

namespace Koonect\Helpers;

// ═══════════════════════════════════════════════════════════════════
// SLUG
// ═══════════════════════════════════════════════════════════════════
class Slug
{
    public static function generate(string $text): string
    {
        if (function_exists('transliterator_transliterate')) {
            $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        } else {
            $text = mb_strtolower($text, 'UTF-8');
            $replacements = [
                'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae',
                'ç' => 'c',
                'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
                'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
                'ñ' => 'n',
                'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
                'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
                'ý' => 'y', 'ÿ' => 'y',
                'œ' => 'oe'
            ];
            $text = strtr($text, $replacements);
        }
        $text = preg_replace('/[^a-z0-9\s-]/', '', (string)$text);
        $text = preg_replace('/[\s-]+/', '-', trim((string)$text));
        return trim((string)$text, '-');
    }

    /**
     * Génère un slug unique en vérifiant l'unicité en base.
     */
    public static function unique(string $text, string $table, string $column = 'slug', ?int $excludeId = null): string
    {
        $db   = \Koonect\Core\Database::getInstance();
        $base = self::generate($text);
        $slug = $base;
        $i    = 1;

        while (true) {
            $sql    = "SELECT id FROM `$table` WHERE `$column` = ?";
            $params = [$slug];
            if ($excludeId !== null) {
                $sql    .= ' AND id != ?';
                $params[] = $excludeId;
            }
            $exists = $db->fetch($sql, $params);
            if (!$exists) break;
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}

// ═══════════════════════════════════════════════════════════════════
// SANITIZER
// ═══════════════════════════════════════════════════════════════════
class Sanitizer
{
    /**
     * Nettoie une chaîne pour affichage sécurisé.
     */
    public static function clean(string $value): string
    {
        return htmlspecialchars(trim(strip_tags($value)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize le contenu HTML riche de l'éditeur.
     * Autorise uniquement les balises éditoriales sûres.
     */
    public static function richHtml(string $html): string
    {
        $allowed = [
            'p', 'br', 'strong', 'em', 'u', 's', 'del',
            'h2', 'h3', 'h4', 'h5', 'h6',
            'ul', 'ol', 'li',
            'blockquote', 'cite',
            'a', 'img',
            'table', 'thead', 'tbody', 'tr', 'th', 'td',
            'figure', 'figcaption',
            'iframe',
            'hr', 'sup', 'sub',
            'div', 'span',
        ];

        $allowedAttrs = [
            'href', 'src', 'alt', 'title', 'class', 'id',
            'target', 'rel', 'width', 'height',
            'data-*', 'loading',
            'allowfullscreen', 'frameborder',
        ];

        // Supprimer les attributs événementiels (on*)
        $html = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/', '', (string)$html);
        // Supprimer javascript: dans les href/src
        $html = preg_replace('/(href|src)\s*=\s*["\']javascript:[^"\']*["\']/', '', (string)$html);

        return $html;
    }

    public static function email(string $email): string|false
    {
        return filter_var(trim($email), FILTER_VALIDATE_EMAIL) ? strtolower(trim($email)) : false;
    }

    public static function int(mixed $value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    public static function url(string $url): string|false
    {
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : false;
    }
}

// ═══════════════════════════════════════════════════════════════════
// SEO HELPER
// ═══════════════════════════════════════════════════════════════════
class Seo
{
    public static function metaTags(array $data): string
    {
        $e   = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $out = '';

        $title       = $data['seo_title']       ?? $data['title']       ?? APP_NAME;
        $description = $data['seo_description'] ?? $data['description'] ?? '';
        $image       = $data['og_image']        ?? '';
        $type        = $data['og_type']         ?? 'website';
        $robots      = $data['robots']          ?? '';
        $siteName    = APP_NAME;

        // Nettoyage de l'URL canonique
        if (!isset($data['canonical_url'])) {
            $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            $query = [];
            parse_str($_SERVER['QUERY_STRING'] ?? '', $query);
            $allowedParams = ['page'];
            $filteredQuery = array_intersect_key($query, array_flip($allowedParams));
            // Ne pas inclure ?page=1 dans la canonical (c'est la page par défaut)
            if (isset($filteredQuery['page']) && (int)$filteredQuery['page'] <= 1) {
                unset($filteredQuery['page']);
            }
            $queryString = !empty($filteredQuery) ? '?' . http_build_query($filteredQuery) : '';
            $url = APP_URL . $uriPath . $queryString;
        } else {
            $url = $data['canonical_url'];
        }

        // Basic
        $out .= "<title>{$e($title)}</title>\n";
        $out .= "<meta name=\"description\" content=\"{$e($description)}\">\n";
        $out .= "<link rel=\"canonical\" href=\"{$e($url)}\">\n";
        if ($robots) {
            $out .= "<meta name=\"robots\" content=\"{$e($robots)}\">\n";
        }

        // OpenGraph
        $out .= "<meta property=\"og:type\" content=\"{$e($type)}\">\n";
        $out .= "<meta property=\"og:title\" content=\"{$e($title)}\">\n";
        $out .= "<meta property=\"og:description\" content=\"{$e($description)}\">\n";
        $out .= "<meta property=\"og:url\" content=\"{$e($url)}\">\n";
        $out .= "<meta property=\"og:site_name\" content=\"{$e($siteName)}\">\n";
        $out .= "<meta property=\"og:locale\" content=\"fr_FR\">\n";
        if ($image) {
            $out .= "<meta property=\"og:image\" content=\"{$e($image)}\">\n";
            $out .= "<meta property=\"og:image:width\" content=\"1200\">\n";
            $out .= "<meta property=\"og:image:height\" content=\"630\">\n";
        }

        // Twitter Cards
        $out .= "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
        $out .= "<meta name=\"twitter:title\" content=\"{$e($title)}\">\n";
        $out .= "<meta name=\"twitter:description\" content=\"{$e($description)}\">\n";
        if ($image) $out .= "<meta name=\"twitter:image\" content=\"{$e($image)}\">\n";

        // Pagination rel links
        if (isset($data['paginator']) && $data['paginator'] instanceof Paginator) {
            $out .= $data['paginator']->relLinks($data['pagination_base_url'] ?? '');
        }

        return $out;
    }

    public static function schemaArticle(array $article, array $author, string $imageUrl): string
    {
        $schema = [
            '@context'         => 'https://schema.org',
            '@type'            => 'NewsArticle',
            'headline'         => $article['title'],
            'description'      => $article['seo_description'] ?? $article['chapo'] ?? '',
            'image'            => [$imageUrl],
            'datePublished'    => $article['published_at'] ?? $article['created_at'],
            'dateModified'     => $article['updated_at'],
            'author'           => [
                '@type' => 'Person',
                'name'  => $author['display_name'] ?? $author['username'],
                'url'   => APP_URL . '/auteur/' . ($author['username'] ?? ''),
            ],
            'publisher'        => [
                '@type' => 'Organization',
                'name'  => APP_NAME,
                'logo'  => [
                    '@type' => 'ImageObject',
                    'url'   => APP_URL . '/assets/img/logo.png',
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => APP_URL . '/article/' . $article['slug'],
            ],
        ];

        // Ajouter wordCount si le contenu est disponible
        if (!empty($article['content'])) {
            $schema['wordCount'] = str_word_count(strip_tags($article['content']));
        }

        return '<script type="application/ld+json">'
            . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . '</script>';
    }

    public static function schemaOrganizationAndWebSite(): string
    {
        $orgSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'NewsMediaOrganization',
            '@id' => APP_URL . '/#organization',
            'name' => APP_NAME,
            'url' => APP_URL,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => APP_URL . '/assets/img/logo.png',
            ],
            'sameAs' => [
                'https://twitter.com/koonect',
                'https://www.facebook.com/koonect',
                'https://www.linkedin.com/company/koonect'
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
                'url' => APP_URL . '/contact',
            ],
        ];

        $webSiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => APP_URL . '/#website',
            'name' => APP_NAME,
            'url' => APP_URL,
            'publisher' => ['@id' => APP_URL . '/#organization'],
            'inLanguage' => 'fr-FR',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => APP_URL . '/recherche?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string'
            ]
        ];

        return '<script type="application/ld+json">'
            . json_encode($orgSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . '</script>' . "\n"
            . '<script type="application/ld+json">'
            . json_encode($webSiteSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . '</script>';
    }

    /**
     * Génère le JSON-LD BreadcrumbList.
     * @param array $items [[name, url], [name, url], [name]] — dernier sans url = page courante
     */
    public static function schemaBreadcrumb(array $items): string
    {
        $listItems = [];
        foreach ($items as $i => $item) {
            $entry = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $item['name'],
            ];
            if (isset($item['url'])) {
                $entry['item'] = $item['url'];
            }
            $listItems[] = $entry;
        }

        $schema = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $listItems,
        ];

        return '<script type="application/ld+json">'
            . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . '</script>';
    }

    /**
     * Génère le JSON-LD ItemList pour les pages de listing (catégorie, tag, etc.).
     */
    public static function schemaItemList(array $articles, string $listName): string
    {
        $items = [];
        foreach ($articles as $i => $art) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'url'      => APP_URL . '/article/' . $art['slug'],
                'name'     => $art['title'],
            ];
        }

        $schema = [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'name'            => $listName,
            'numberOfItems'   => count($items),
            'itemListElement' => $items,
        ];

        return '<script type="application/ld+json">'
            . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . '</script>';
    }

    /**
     * Génère le JSON-LD Person pour les pages auteur (E-E-A-T).
     */
    public static function schemaPerson(array $author, int $articleCount): string
    {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Person',
            'name'        => $author['display_name'],
            'url'         => APP_URL . '/auteur/' . $author['username'],
            'worksFor'    => ['@id' => APP_URL . '/#organization'],
        ];

        if (!empty($author['avatar'])) {
            $avatarUrl = preg_match('#^https?://#', $author['avatar'])
                ? $author['avatar']
                : APP_URL . '/' . ltrim($author['avatar'], '/');
            $schema['image'] = $avatarUrl;
        }

        $roleName = match($author['role'] ?? '') {
            'admin'        => 'Administrateur',
            'director'     => 'Directeur de la publication',
            'chief_editor' => 'Rédacteur en chef',
            'journalist'   => 'Journaliste',
            'proofreader'  => 'Correcteur',
            default        => 'Rédacteur',
        };
        $schema['jobTitle'] = $roleName;
        $schema['description'] = $roleName . ' chez ' . APP_NAME . ' — ' . $articleCount . ' article' . ($articleCount > 1 ? 's' : '') . ' publié' . ($articleCount > 1 ? 's' : '');

        return '<script type="application/ld+json">'
            . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . '</script>';
    }
}

// ═══════════════════════════════════════════════════════════════════
// PAGINATOR
// ═══════════════════════════════════════════════════════════════════
class Paginator
{
    public int $total;
    public int $perPage;
    public int $currentPage;
    public int $lastPage;
    public int $offset;

    public function __construct(int $total, int $perPage, int $currentPage)
    {
        $this->total       = $total;
        $this->perPage     = $perPage;
        $this->currentPage = max(1, $currentPage);
        $this->lastPage    = (int) ceil($total / $perPage);
        $this->offset      = ($this->currentPage - 1) * $perPage;
    }

    public function hasPages(): bool
    {
        return $this->lastPage > 1;
    }

    /**
     * Génère l'URL paginée. Page 1 = URL de base sans paramètre.
     */
    private function pageUrl(string $baseUrl, int $page): string
    {
        if ($page <= 1) return $baseUrl;
        $separator = str_contains($baseUrl, '?') ? '&' : '?';
        return $baseUrl . $separator . 'page=' . $page;
    }

    public function links(string $baseUrl = ''): string
    {
        if (!$this->hasPages()) return '';

        $url = $baseUrl ?: strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        $out = '<nav class="pagination" aria-label="Pagination"><ul>';

        // Précédent
        if ($this->currentPage > 1) {
            $out .= '<li><a href="' . $this->pageUrl($url, $this->currentPage - 1) . '" aria-label="Page précédente">&laquo;</a></li>';
        }

        // Pages
        $range = range(max(1, $this->currentPage - 2), min($this->lastPage, $this->currentPage + 2));
        if (!in_array(1, $range)) {
            $out .= '<li><a href="' . $this->pageUrl($url, 1) . '">1</a></li>';
            if (!in_array(2, $range)) $out .= '<li><span>…</span></li>';
        }

        foreach ($range as $page) {
            if ($page === $this->currentPage) {
                $out .= '<li><span class="current" aria-current="page">' . $page . '</span></li>';
            } else {
                $out .= '<li><a href="' . $this->pageUrl($url, $page) . '">' . $page . '</a></li>';
            }
        }

        if (!in_array($this->lastPage, $range)) {
            if (!in_array($this->lastPage - 1, $range)) $out .= '<li><span>…</span></li>';
            $out .= '<li><a href="' . $this->pageUrl($url, $this->lastPage) . '">' . $this->lastPage . '</a></li>';
        }

        // Suivant
        if ($this->currentPage < $this->lastPage) {
            $out .= '<li><a href="' . $this->pageUrl($url, $this->currentPage + 1) . '" aria-label="Page suivante">&raquo;</a></li>';
        }

        $out .= '</ul></nav>';
        return $out;
    }

    /**
     * Génère les balises <link rel="prev/next"> pour le <head>.
     */
    public function relLinks(string $baseUrl = ''): string
    {
        if (!$this->hasPages()) return '';

        $url = $baseUrl ?: strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        $out = '';

        if ($this->currentPage > 1) {
            $out .= '<link rel="prev" href="' . htmlspecialchars($this->pageUrl($url, $this->currentPage - 1)) . "\">\n";
        }
        if ($this->currentPage < $this->lastPage) {
            $out .= '<link rel="next" href="' . htmlspecialchars($this->pageUrl($url, $this->currentPage + 1)) . "\">\n";
        }

        return $out;
    }
}

// ═══════════════════════════════════════════════════════════════════
// DATE FORMATTER
// ═══════════════════════════════════════════════════════════════════
class DateFormatter
{
    public static function frenchLong(?\DateTimeInterface $date = null): string
    {
        $date ??= new \DateTimeImmutable('now');

        if (class_exists(\IntlDateFormatter::class)) {
            $formatter = new \IntlDateFormatter(
                'fr_FR',
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::NONE,
                $date->getTimezone()->getName(),
                \IntlDateFormatter::GREGORIAN,
                'EEEE d MMMM y'
            );

            if ($formatter !== false) {
                $formatted = $formatter->format($date);
                if ($formatted !== false) {
                    return (string) $formatted;
                }
            }
        }

        return self::fallbackFrenchLong($date);
    }

    private static function fallbackFrenchLong(\DateTimeInterface $date): string
    {
        $formatted = $date->format('l j F Y');

        $replacements = [
            'Monday'    => 'lundi',
            'Tuesday'   => 'mardi',
            'Wednesday' => 'mercredi',
            'Thursday'  => 'jeudi',
            'Friday'    => 'vendredi',
            'Saturday'  => 'samedi',
            'Sunday'    => 'dimanche',
            'January'   => 'janvier',
            'February'  => 'février',
            'March'     => 'mars',
            'April'     => 'avril',
            'May'       => 'mai',
            'June'      => 'juin',
            'July'      => 'juillet',
            'August'    => 'août',
            'September' => 'septembre',
            'October'   => 'octobre',
            'November'  => 'novembre',
            'December'  => 'décembre',
        ];

        return strtr($formatted, $replacements);
    }
}
