<?php
declare(strict_types=1);

namespace Koonect\Models;

use Koonect\Core\Database;
use Koonect\Helpers\Slug;

// ═══════════════════════════════════════════════════════════════════
// USER MODEL
// ═══════════════════════════════════════════════════════════════════
class User
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetch(
            'SELECT id, email, username, display_name, avatar, role, status,
                    email_verified_at, two_factor_enabled, last_login_at, created_at
             FROM users WHERE id = ? AND deleted_at IS NULL',
            [$id]
        );
    }

    public function findByEmail(string $email): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1',
            [strtolower(trim($email))]
        );
    }

    public function create(array $data): int
    {
        $hash = password_hash($data['password'], PASSWORD_ARGON2ID, [
            'memory_cost' => ARGON2_MEMORY,
            'time_cost'   => ARGON2_TIME,
            'threads'     => ARGON2_THREADS,
        ]);

        $id = $this->db->insert(
            'INSERT INTO users (email, password_hash, username, display_name, role, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, "inactive", NOW(), NOW())',
            [
                strtolower(trim($data['email'])),
                $hash,
                $data['username'],
                $data['display_name'] ?? $data['username'],
                $data['role']         ?? 'subscriber',
            ]
        );
        return (int)$id;
    }

    public function verifyPassword(string $plaintext, string $hash): bool
    {
        return password_verify($plaintext, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
            'memory_cost' => ARGON2_MEMORY,
            'time_cost'   => ARGON2_TIME,
            'threads'     => ARGON2_THREADS,
        ]);
    }

    public function updateLastLogin(int $id): void
    {
        $this->db->execute('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
    }

    public function verifyEmail(int $id): void
    {
        $this->db->execute(
            'UPDATE users SET email_verified_at = NOW(), status = "active" WHERE id = ?', [$id]
        );
    }

    public function updatePassword(int $id, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => ARGON2_MEMORY,
            'time_cost'   => ARGON2_TIME,
            'threads'     => ARGON2_THREADS,
        ]);
        $this->db->execute(
            'UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?', [$hash, $id]
        );
    }

    public function createToken(int $userId, string $type, int $expiresInSeconds = 86400): string
    {
        $token     = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $this->db->execute(
            'INSERT INTO tokens (user_id, type, token_hash, expires_at, created_at)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NOW())',
            [$userId, $type, $tokenHash, $expiresInSeconds]
        );
        return $token;
    }

    public function verifyToken(string $token, string $type): array|false
    {
        $tokenHash = hash('sha256', $token);
        $row = $this->db->fetch(
            'SELECT * FROM tokens WHERE token_hash = ? AND type = ? AND used_at IS NULL AND expires_at > NOW()',
            [$tokenHash, $type]
        );
        if (!$row) return false;

        // Marquer comme utilisé
        $this->db->execute('UPDATE tokens SET used_at = NOW() WHERE id = ?', [$row['id']]);
        return $row;
    }

    public function enable2FA(int $userId, string $secret): void
    {
        $this->db->execute(
            'UPDATE users SET two_factor_secret = ?, two_factor_enabled = 1, updated_at = NOW() WHERE id = ?',
            [$secret, $userId]
        );
    }

    public function softDelete(int $id): void
    {
        $this->db->execute(
            'UPDATE users SET deleted_at = NOW(), status = "deleted", updated_at = NOW() WHERE id = ?', [$id]
        );
    }

    public function anonymize(int $id): void
    {
        $anon = 'anonyme_' . substr(md5((string)$id), 0, 8);
        $this->db->execute(
            'UPDATE users SET
             email = ?, username = ?, display_name = "Utilisateur supprimé",
             password_hash = "", avatar = NULL, two_factor_secret = NULL,
             anonymized_at = NOW(), updated_at = NOW()
             WHERE id = ?',
            [$anon . '@supprime.local', $anon, $id]
        );
    }
}

// ═══════════════════════════════════════════════════════════════════
// CATEGORY MODEL
// ═══════════════════════════════════════════════════════════════════
class Category
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(): array
    {
        return $this->db->fetchAll('SELECT * FROM categories ORDER BY position, name');
    }

    public function findBySlug(string $slug): array|false
    {
        return $this->db->fetch('SELECT * FROM categories WHERE slug = ? LIMIT 1', [$slug]);
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetch('SELECT * FROM categories WHERE id = ? LIMIT 1', [$id]);
    }

    public function create(array $data): int
    {
        $slug = Slug::unique($data['name'], 'categories');
        return (int)$this->db->insert(
            'INSERT INTO categories (name, slug, description, parent_id, meta_title, meta_description, og_image, position, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                $data['name'],
                $slug,
                $data['description']      ?? null,
                $data['parent_id']        ?? null,
                $data['meta_title']       ?? $data['name'],
                $data['meta_description'] ?? null,
                $data['og_image']         ?? null,
                $data['position']         ?? 0,
            ]
        );
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->execute(
            'UPDATE categories SET name=?, description=?, parent_id=?, meta_title=?, meta_description=?, og_image=?, position=? WHERE id=?',
            [
                $data['name'],
                $data['description']      ?? null,
                $data['parent_id']        ?? null,
                $data['meta_title']       ?? $data['name'],
                $data['meta_description'] ?? null,
                $data['og_image']         ?? null,
                $data['position']         ?? 0,
                $id,
            ]
        ) > 0;
    }
}

// ═══════════════════════════════════════════════════════════════════
// TAG MODEL
// ═══════════════════════════════════════════════════════════════════
class Tag
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(): array
    {
        return $this->db->fetchAll('SELECT * FROM tags ORDER BY name');
    }

    public function findBySlug(string $slug): array|false
    {
        return $this->db->fetch('SELECT * FROM tags WHERE slug = ? LIMIT 1', [$slug]);
    }

    public function findOrCreate(string $name): array
    {
        $slug = Slug::generate($name);
        $row  = $this->db->fetch('SELECT * FROM tags WHERE slug = ? LIMIT 1', [$slug]);
        if ($row) return $row;

        $id = $this->db->insert('INSERT INTO tags (name, slug, created_at) VALUES (?, ?, NOW())', [$name, $slug]);
        return ['id' => $id, 'name' => $name, 'slug' => $slug];
    }

    public function getPopular(int $limit = 20): array
    {
        return $this->db->fetchAll(
            'SELECT t.*, COUNT(at2.article_id) AS article_count
             FROM tags t
             INNER JOIN article_tags at2 ON t.id = at2.tag_id
             INNER JOIN articles a ON at2.article_id = a.id AND a.status = "published"
             GROUP BY t.id ORDER BY article_count DESC LIMIT ?',
            [$limit]
        );
    }
}

// ═══════════════════════════════════════════════════════════════════
// COMMENT MODEL
// ═══════════════════════════════════════════════════════════════════
class Comment
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getApproved(int $articleId): array
    {
        return $this->db->fetchAll(
            'SELECT c.*, u.display_name AS author_name, u.avatar AS author_avatar
             FROM comments c
             LEFT JOIN users u ON c.user_id = u.id
             WHERE c.article_id = ? AND c.status = "approved" AND c.deleted_at IS NULL
             ORDER BY c.created_at ASC',
            [$articleId]
        );
    }

    public function create(int $articleId, int $userId, string $content, ?int $parentId, string $ip): int
    {
        return (int)$this->db->insert(
            'INSERT INTO comments (article_id, user_id, parent_id, content, status, ip_address, created_at, updated_at)
             VALUES (?, ?, ?, ?, "approved", ?, NOW(), NOW())',
            [$articleId, $userId, $parentId, strip_tags($content), $ip]
        );
    }

    public function approve(int $id): void
    {
        $this->db->execute('UPDATE comments SET status = "approved", updated_at = NOW() WHERE id = ?', [$id]);
    }

    public function reject(int $id): void
    {
        $this->db->execute('UPDATE comments SET status = "rejected", updated_at = NOW() WHERE id = ?', [$id]);
    }

    public function getPending(int $limit = 25): array
    {
        return $this->db->fetchAll(
            'SELECT c.*, u.display_name, a.title AS article_title, a.slug AS article_slug
             FROM comments c
             LEFT JOIN users u ON c.user_id = u.id
             LEFT JOIN articles a ON c.article_id = a.id
             WHERE c.status = "pending" AND c.deleted_at IS NULL
             ORDER BY c.created_at ASC LIMIT ?',
            [$limit]
        );
    }

    public function getRecent(int $limit = 50): array
    {
        return $this->db->fetchAll(
            'SELECT c.*, u.display_name, a.title AS article_title, a.slug AS article_slug
             FROM comments c
             LEFT JOIN users u ON c.user_id = u.id
             LEFT JOIN articles a ON c.article_id = a.id
             WHERE c.deleted_at IS NULL
             ORDER BY c.created_at DESC LIMIT ?',
            [$limit]
        );
    }
}

// ═══════════════════════════════════════════════════════════════════
// MEDIA MODEL
// ═══════════════════════════════════════════════════════════════════
class Media
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(array $data): int
    {
        return (int)$this->db->insert(
            'INSERT INTO media (filename, original_name, path, webp_path, thumb_path, mime_type, size, width, height,
             alt_text, caption, credit, folder_id, uploaded_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                $data['filename'],
                $data['original_name'],
                $data['path'],
                $data['webp_path']   ?? null,
                $data['thumb_path']  ?? null,
                $data['mime_type'],
                $data['size'],
                $data['width']       ?? 0,
                $data['height']      ?? 0,
                $data['alt_text']    ?? null,
                $data['caption']     ?? null,
                $data['credit']      ?? null,
                $data['folder_id']   ?? null,
                $data['uploaded_by'],
            ]
        );
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetch('SELECT * FROM media WHERE id = ? LIMIT 1', [$id]);
    }

    public function getAll(int $limit = 50, int $offset = 0, ?int $folderId = null, string $search = ''): array
    {
        $where  = '1=1';
        $params = [];

        if ($folderId !== null) {
            $where   .= ' AND folder_id = ?';
            $params[] = $folderId;
        }
        if ($search) {
            $where   .= ' AND (original_name LIKE ? OR alt_text LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll(
            "SELECT * FROM media WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?",
            $params
        );
    }

    public function delete(int $id): bool
    {
        $media = $this->findById($id);
        if (!$media) return false;

        // Supprimer les fichiers physiques
        foreach (['path', 'webp_path', 'thumb_path'] as $field) {
            if (!empty($media[$field]) && file_exists(STORAGE_PATH . '/' . $media[$field])) {
                @unlink(STORAGE_PATH . '/' . $media[$field]);
            }
        }

        $this->db->execute('DELETE FROM media WHERE id = ?', [$id]);
        return true;
    }
}

// ═══════════════════════════════════════════════════════════════════
// NEWSLETTER MODEL
// ═══════════════════════════════════════════════════════════════════
class Newsletter
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function subscribe(string $email, ?int $userId = null, string $ip = ''): string
    {
        $email = strtolower(trim($email));
        $token = bin2hex(random_bytes(32));
        $hash  = hash('sha256', $token);

        // Vérifier si déjà inscrit
        $existing = $this->db->fetch(
            'SELECT * FROM newsletter_subscribers WHERE email = ? LIMIT 1', [$email]
        );

        if ($existing) {
            if ($existing['confirmed_at']) {
                return 'already_confirmed';
            }
            // Remettre un nouveau token
            $this->db->execute(
                'UPDATE newsletter_subscribers SET token_hash = ?, created_at = NOW() WHERE id = ?',
                [$hash, $existing['id']]
            );
        } else {
            $this->db->insert(
                'INSERT INTO newsletter_subscribers (email, user_id, token_hash, ip_address, created_at)
                 VALUES (?, ?, ?, ?, NOW())',
                [$email, $userId, $hash, $ip]
            );
        }

        return $token;
    }

    public function confirm(string $token): bool
    {
        $hash = hash('sha256', $token);
        $row  = $this->db->fetch(
            'SELECT * FROM newsletter_subscribers WHERE token_hash = ? AND confirmed_at IS NULL AND unsubscribed_at IS NULL LIMIT 1',
            [$hash]
        );
        if (!$row) return false;

        $this->db->execute(
            'UPDATE newsletter_subscribers SET confirmed_at = NOW() WHERE id = ?', [$row['id']]
        );
        return true;
    }

    public function unsubscribe(string $token): bool
    {
        // 1. Chercher par le hash du token (cas du token en clair)
        $hash = hash('sha256', $token);
        $row  = $this->db->fetch(
            'SELECT * FROM newsletter_subscribers WHERE token_hash = ? LIMIT 1', [$hash]
        );

        // 2. Chercher par correspondance directe (si le lien contient déjà le token_hash)
        if (!$row) {
            $row = $this->db->fetch(
                'SELECT * FROM newsletter_subscribers WHERE token_hash = ? LIMIT 1', [$token]
            );
        }

        // 3. Chercher par double hash (si le lien contient hash('sha256', token_hash))
        if (!$row) {
            $row = $this->db->fetch(
                'SELECT * FROM newsletter_subscribers WHERE SHA2(token_hash, 256) = ? LIMIT 1', [$token]
            );
        }

        if (!$row) return false;

        $this->db->execute(
            'UPDATE newsletter_subscribers SET unsubscribed_at = NOW() WHERE id = ?', [$row['id']]
        );
        return true;
    }

    public function getConfirmedSubscribers(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM newsletter_subscribers WHERE confirmed_at IS NOT NULL AND unsubscribed_at IS NULL'
        );
    }
}
