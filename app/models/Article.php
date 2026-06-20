<?php
declare(strict_types=1);

namespace Koonect\Models;

use Koonect\Core\Database;
use Koonect\Helpers\{Slug, Sanitizer};

class Article
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Lecture ───────────────────────────────────────────────────

    public function findBySlug(string $slug): array|false
    {
        return $this->db->fetch(
            'SELECT a.*, 
                    u.display_name AS author_name, u.username AS author_username, u.avatar AS author_avatar,
                    c.name AS category_name, c.slug AS category_slug,
                    m.webp_path AS featured_image_webp, m.path AS featured_image_path,
                    m.alt_text AS featured_image_alt, m.caption AS featured_image_caption,
                    m.credit AS featured_image_credit
             FROM articles a
             LEFT JOIN users u ON a.author_id = u.id
             LEFT JOIN categories c ON a.category_id = c.id
             LEFT JOIN media m ON a.featured_image_id = m.id
             WHERE a.slug = ? AND a.status = "published" AND a.deleted_at IS NULL',
            [$slug]
        );
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetch(
            'SELECT a.*,
                    u.display_name AS author_name, u.username AS author_username,
                    c.name AS category_name, c.slug AS category_slug
             FROM articles a
             LEFT JOIN users u ON a.author_id = u.id
             LEFT JOIN categories c ON a.category_id = c.id
             WHERE a.id = ? AND a.deleted_at IS NULL',
            [$id]
        );
    }

    public function getPublished(int $limit = 12, int $offset = 0, ?int $categoryId = null, ?int $tagId = null, ?int $authorId = null): array
    {
        $params = [];
        $join   = '';

        if ($tagId !== null) {
            $join    .= ' INNER JOIN article_tags at2 ON a.id = at2.article_id AND at2.tag_id = ?';
            $params[] = $tagId;
        }

        $where  = 'a.status = "published" AND a.deleted_at IS NULL AND a.published_at <= ?';
        $params[] = date('Y-m-d H:i:s');

        if ($categoryId !== null) {
            $where   .= ' AND a.category_id = ?';
            $params[] = $categoryId;
        }

        if ($authorId !== null) {
            $where   .= ' AND a.author_id = ?';
            $params[] = $authorId;
        }

        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll(
            "SELECT a.id, a.title, a.subtitle, a.chapo, a.slug, a.published_at, a.reading_time,
                    a.views_count, a.is_featured, a.is_breaking, a.is_premium,
                    u.display_name AS author_name, u.username AS author_username,
                    c.name AS category_name, c.slug AS category_slug,
                    m.webp_path AS featured_image_webp, m.thumb_path AS featured_image_thumb,
                    m.alt_text AS featured_image_alt, m.size AS featured_image_size
             FROM articles a
             LEFT JOIN users u ON a.author_id = u.id
             LEFT JOIN categories c ON a.category_id = c.id
             LEFT JOIN media m ON a.featured_image_id = m.id
             $join
             WHERE $where
             ORDER BY a.published_at DESC
             LIMIT ? OFFSET ?",
            $params
        );
    }

    public function countPublished(?int $categoryId = null, ?int $tagId = null): int
    {
        $params = [];
        $join   = '';

        if ($tagId !== null) {
            $join    = ' INNER JOIN article_tags at2 ON articles.id = at2.article_id AND at2.tag_id = ?';
            $params[] = $tagId;
        }

        $where  = 'status = "published" AND deleted_at IS NULL AND published_at <= ?';
        $params[] = date('Y-m-d H:i:s');

        if ($categoryId !== null) {
            $where   .= ' AND category_id = ?';
            $params[] = $categoryId;
        }

        $row = $this->db->fetch("SELECT COUNT(*) AS cnt FROM articles $join WHERE $where", $params);
        return (int)($row['cnt'] ?? 0);
    }

    public function getMostRead(int $limit = 5): array
    {
        return $this->db->fetchAll(
            'SELECT a.id, a.title, a.slug, a.views_count, a.published_at,
                    c.name AS category_name, c.slug AS category_slug,
                    m.thumb_path AS featured_image_thumb
             FROM articles a
             LEFT JOIN categories c ON a.category_id = c.id
             LEFT JOIN media m ON a.featured_image_id = m.id
             WHERE a.status = "published" AND a.deleted_at IS NULL AND a.published_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             ORDER BY a.views_count DESC
             LIMIT ?',
            [$limit]
        );
    }

    public function getFeatured(): array
    {
        return $this->db->fetchAll(
            'SELECT a.id, a.title, a.subtitle, a.chapo, a.slug, a.published_at, a.reading_time,
                    u.display_name AS author_name,
                    c.name AS category_name, c.slug AS category_slug,
                    m.webp_path AS featured_image_webp, m.path AS featured_image_path,
                    m.alt_text AS featured_image_alt
             FROM articles a
             LEFT JOIN users u ON a.author_id = u.id
             LEFT JOIN categories c ON a.category_id = c.id
             LEFT JOIN media m ON a.featured_image_id = m.id
             WHERE a.status = "published" AND a.is_featured = 1 AND a.deleted_at IS NULL
             ORDER BY a.published_at DESC
             LIMIT 5'
        );
    }

    public function getBreaking(): array
    {
        return $this->db->fetchAll(
            'SELECT id, title, slug FROM articles
             WHERE status = "published" AND is_breaking = 1 AND deleted_at IS NULL
             AND published_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             ORDER BY published_at DESC LIMIT 3'
        );
    }

    public function getRelated(int $articleId, int $categoryId, int $limit = 4): array
    {
        return $this->db->fetchAll(
            'SELECT a.id, a.title, a.slug, a.published_at,
                    m.thumb_path AS featured_image_thumb, m.alt_text AS featured_image_alt
             FROM articles a
             LEFT JOIN media m ON a.featured_image_id = m.id
             WHERE a.status = "published" AND a.deleted_at IS NULL
             AND a.category_id = ? AND a.id != ?
             ORDER BY a.published_at DESC LIMIT ?',
            [$categoryId, $articleId, $limit]
        );
    }

    public function search(string $query, int $limit = 12, int $offset = 0): array
    {
        $q = '%' . $query . '%';
        return $this->db->fetchAll(
            'SELECT a.id, a.title, a.chapo, a.slug, a.published_at,
                    c.name AS category_name, c.slug AS category_slug,
                    m.thumb_path AS featured_image_thumb
             FROM articles a
             LEFT JOIN categories c ON a.category_id = c.id
             LEFT JOIN media m ON a.featured_image_id = m.id
             WHERE a.status = "published" AND a.deleted_at IS NULL
             AND (a.title LIKE ? OR a.chapo LIKE ? OR a.content LIKE ?)
             ORDER BY a.published_at DESC LIMIT ? OFFSET ?',
            [$q, $q, $q, $limit, $offset]
        );
    }

    public function getTags(int $articleId): array
    {
        return $this->db->fetchAll(
            'SELECT t.id, t.name, t.slug FROM tags t
             INNER JOIN article_tags at2 ON t.id = at2.tag_id
             WHERE at2.article_id = ?
             ORDER BY t.name',
            [$articleId]
        );
    }

    public function getGallery(int $articleId): array
    {
        return $this->db->fetchAll(
            'SELECT m.*, ag.position, ag.caption AS gallery_caption
             FROM article_galleries ag
             INNER JOIN media m ON ag.media_id = m.id
             WHERE ag.article_id = ?
             ORDER BY ag.position ASC',
            [$articleId]
        );
    }

    // ── Écriture ──────────────────────────────────────────────────

    public function create(array $data): int
    {
        $slug = Slug::unique($data['title'], 'articles');
        $now  = date('Y-m-d H:i:s');

        $publishedAt = null;
        if (($data['status'] ?? 'draft') === 'published') {
            $publishedAt = $now;
        }

        $shortCode = self::generateShortCode();
        while ($this->db->fetch('SELECT id FROM articles WHERE short_code = ? LIMIT 1', [$shortCode])) {
            $shortCode = self::generateShortCode();
        }

        $id = $this->db->insert(
            'INSERT INTO articles
             (title, subtitle, chapo, content, slug, author_id, category_id, status,
              featured_image_id, reading_time, is_breaking, is_featured, is_premium,
              seo_title, seo_description, og_image, schema_type, scheduled_at, published_at, created_at, updated_at, short_code)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['title'],
                $data['subtitle']          ?? null,
                $data['chapo']             ?? null,
                Sanitizer::richHtml($data['content'] ?? ''),
                $slug,
                $data['author_id'],
                $data['category_id']       ?? null,
                $data['status']            ?? 'draft',
                $data['featured_image_id'] ?? null,
                $data['reading_time']      ?? self::estimateReadingTime($data['content'] ?? ''),
                (int)($data['is_breaking'] ?? 0),
                (int)($data['is_featured'] ?? 0),
                (int)($data['is_premium']  ?? 0),
                $data['seo_title']         ?? $data['title'],
                $data['seo_description']   ?? null,
                $data['og_image']          ?? null,
                $data['schema_type']       ?? 'NewsArticle',
                $data['scheduled_at']      ?? null,
                $publishedAt,
                $now,
                $now,
                $shortCode,
            ]
        );

        return (int)$id;
    }

    public function update(int $id, array $data): bool
    {
        $current = $this->findById($id);
        if (!$current) return false;

        // Regénérer slug si titre changé
        $slug = ($data['title'] !== $current['title'])
            ? Slug::unique($data['title'], 'articles', 'slug', $id)
            : $current['slug'];

        // Créer une redirection 301 si le slug a changé
        if ($slug !== $current['slug']) {
            $this->db->execute(
                'INSERT IGNORE INTO redirects (from_url, to_url, created_at) VALUES (?, ?, NOW())',
                ['/article/' . $current['slug'], '/article/' . $slug]
            );
        }

        $publishedAt = null;
        if ($data['status'] === 'published' && $current['status'] !== 'published') {
            $publishedAt = date('Y-m-d H:i:s');
        } else {
            $publishedAt = $current['published_at'];
        }

        $affected = $this->db->execute(
            'UPDATE articles SET
             title=?, subtitle=?, chapo=?, content=?, slug=?, category_id=?, status=?,
             featured_image_id=?, reading_time=?, is_breaking=?, is_featured=?, is_premium=?,
             seo_title=?, seo_description=?, og_image=?, schema_type=?, scheduled_at=?,
             published_at=?, updated_at=NOW()
             WHERE id=?',
            [
                $data['title'],
                $data['subtitle']          ?? null,
                $data['chapo']             ?? null,
                Sanitizer::richHtml($data['content'] ?? ''),
                $slug,
                $data['category_id']       ?? null,
                $data['status']            ?? $current['status'],
                $data['featured_image_id'] ?? null,
                $data['reading_time']      ?? self::estimateReadingTime($data['content'] ?? ''),
                (int)($data['is_breaking'] ?? 0),
                (int)($data['is_featured'] ?? 0),
                (int)($data['is_premium']  ?? 0),
                $data['seo_title']         ?? $data['title'],
                $data['seo_description']   ?? null,
                $data['og_image']          ?? null,
                $data['schema_type']       ?? 'NewsArticle',
                $data['scheduled_at']      ?? null,
                $publishedAt,
                $id,
            ]
        );

        return $affected > 0;
    }

    public function incrementViews(int $id): void
    {
        $this->db->execute('UPDATE articles SET views_count = views_count + 1 WHERE id = ?', [$id]);
    }

    public function syncTags(int $articleId, array $tagIds): void
    {
        $this->db->execute('DELETE FROM article_tags WHERE article_id = ?', [$articleId]);
        foreach ($tagIds as $tagId) {
            $this->db->execute(
                'INSERT IGNORE INTO article_tags (article_id, tag_id) VALUES (?, ?)',
                [$articleId, (int)$tagId]
            );
        }
    }

    public function softDelete(int $id): bool
    {
        return $this->db->execute(
            'UPDATE articles SET deleted_at = NOW(), status = "archived" WHERE id = ?', [$id]
        ) > 0;
    }

    // ── Workflow ──────────────────────────────────────────────────

    public function logRevision(int $articleId, int $editorId, string $fromStatus, string $toStatus, ?string $note = null): void
    {
        $this->db->execute(
            'INSERT INTO article_revisions (article_id, editor_id, status_from, status_to, note, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())',
            [$articleId, $editorId, $fromStatus, $toStatus, $note]
        );
    }

    public function getRevisions(int $articleId): array
    {
        return $this->db->fetchAll(
            'SELECT ar.*, u.display_name AS editor_name FROM article_revisions ar
             LEFT JOIN users u ON ar.editor_id = u.id
             WHERE ar.article_id = ? ORDER BY ar.created_at DESC',
            [$articleId]
        );
    }

    // ── Sitemap ───────────────────────────────────────────────────

    public function getAllForSitemap(): array
    {
        return $this->db->fetchAll(
            'SELECT slug, updated_at FROM articles
             WHERE status = "published" AND deleted_at IS NULL
             ORDER BY updated_at DESC'
        );
    }

    // ── Utilitaires ───────────────────────────────────────────────

    public static function estimateReadingTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        return max(1, (int)ceil($wordCount / 200)); // 200 mots/min
    }

    public static function generateShortCode(int $length = 6): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, $max)];
        }
        return $code;
    }
}
