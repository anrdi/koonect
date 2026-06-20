<?php
declare(strict_types=1);

namespace Koonect\Controllers\Redac;

use Koonect\Core\{Request, Response, Session, View};
use Koonect\Models\{Article as ArticleModel, Category, Tag};
use Koonect\Services\CacheService;

class ArticleController
{
    private ArticleModel $model;

    private const ROLE_CAN_PUBLISH = ['admin', 'director'];
    private const ROLE_CAN_VALIDATE = ['admin', 'director', 'chief_editor'];

    public function __construct()
    {
        $this->model = new ArticleModel();
    }

    public function index(Request $request): void
    {
        $user   = Session::get('user');
        $db     = \Koonect\Core\Database::getInstance();
        $status = $request->get('status', '');
        $search = trim((string)$request->get('q', ''));

        $where  = 'a.deleted_at IS NULL';
        $params = [];

        // Journalistes ne voient que leurs articles
        if ($user['role'] === 'journalist') {
            $where   .= ' AND a.author_id = ?';
            $params[] = $user['id'];
        }
        if ($status) {
            $where   .= ' AND a.status = ?';
            $params[] = $status;
        }
        if ($search) {
            $where   .= ' AND a.title LIKE ?';
            $params[] = '%' . $search . '%';
        }

        $articles = $db->fetchAll(
            "SELECT a.id, a.title, a.status, a.published_at, a.created_at, a.views_count,
                    u.display_name AS author_name, c.name AS category_name
             FROM articles a
             LEFT JOIN users u ON a.author_id = u.id
             LEFT JOIN categories c ON a.category_id = c.id
             WHERE $where ORDER BY a.updated_at DESC LIMIT 50",
            $params
        );

        View::render('redac/article/list', [
            'articles' => $articles,
            'status'   => $status,
            'search'   => $search,
        ], 'redac');
    }

    public function create(Request $request): void
    {
        $categories = (new Category())->all();
        $tags       = (new Tag())->all();

        View::render('redac/article/create', [
            'categories' => $categories,
            'tags'       => $tags,
            'article'    => null,
        ], 'redac');
    }

    public function store(Request $request): void
    {
        $user = Session::get('user');
        $data = $this->extractFormData($request);
        $data['author_id'] = $user['id'];
        
        $action = $request->post('action', 'save');
        $status = $this->resolveStatus($action, 'draft', $user['role']);
        $data['status']    = $status;

        $id = $this->model->create($data);

        // Sync tags
        if (!empty($data['tag_ids'])) {
            $this->model->syncTags($id, $data['tag_ids']);
        }

        $this->model->logRevision($id, (int)$user['id'], '', $status, 'Création');
        CacheService::flush();

        if ($status === 'published') {
            Session::flash('success', 'Article créé et publié.');
        } else {
            Session::flash('success', 'Article créé en brouillon.');
        }
        Response::redirect(REDAC_URL . '/articles/' . $id . '/modifier');
    }

    public function edit(Request $request): void
    {
        $id      = (int)$request->param('id');
        $article = $this->model->findById($id);
        if (!$article) Response::notFound();

        $user = Session::get('user');
        // Un journaliste ne peut éditer que ses propres articles
        if ($user['role'] === 'journalist' && $article['author_id'] != $user['id']) {
            Response::forbidden();
        }

        $categories = (new Category())->all();
        $tags       = (new Tag())->all();
        $articleTags= $this->model->getTags($id);
        $revisions  = $this->model->getRevisions($id);

        View::render('redac/article/edit', [
            'article'     => $article,
            'categories'  => $categories,
            'tags'        => $tags,
            'articleTags' => array_column($articleTags, 'id'),
            'revisions'   => $revisions,
        ], 'redac');
    }

    public function update(Request $request): void
    {
        $id      = (int)$request->param('id');
        $article = $this->model->findById($id);
        if (!$article) Response::notFound();

        $user = Session::get('user');
        if ($user['role'] === 'journalist' && $article['author_id'] != $user['id']) {
            Response::forbidden();
        }

        $data       = $this->extractFormData($request);
        $oldStatus  = $article['status'];
        $newStatus  = $this->resolveStatus($request->post('action', 'save'), $oldStatus, $user['role']);
        $data['status'] = $newStatus;

        $this->model->update($id, $data);

        if (!empty($data['tag_ids'])) {
            $this->model->syncTags($id, $data['tag_ids']);
        }

        if ($oldStatus !== $newStatus) {
            $note = $request->post('revision_note', null);
            $this->model->logRevision($id, (int)$user['id'], $oldStatus, $newStatus, $note);
        }

        CacheService::flush();
        Session::flash('success', 'Article mis à jour.');
        Response::redirect(REDAC_URL . '/articles/' . $id . '/modifier');
    }

    public function updateStatus(Request $request): void
    {
        $id        = (int)$request->param('id');
        $article   = $this->model->findById($id);
        if (!$article) Response::json(['error' => 'Article introuvable.'], 404);

        $user      = Session::get('user');
        $newStatus = $request->post('status', '');
        $note      = $request->post('note', '');

        $allowed = $this->getAllowedTransitions($article['status'], $user['role']);
        if (!in_array($newStatus, $allowed, true)) {
            Response::json(['error' => 'Transition de statut non autorisée.'], 403);
        }

        $publishedAt = null;
        if ($newStatus === 'published') {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $db = \Koonect\Core\Database::getInstance();
        $db->execute(
            'UPDATE articles SET status = ?, published_at = COALESCE(?, published_at), updated_at = NOW() WHERE id = ?',
            [$newStatus, $publishedAt, $id]
        );

        $this->model->logRevision($id, (int)$user['id'], $article['status'], $newStatus, $note ?: null);
        CacheService::flush();

        Response::json(['success' => true, 'new_status' => $newStatus]);
    }

    public function autosave(Request $request): void
    {
        $id      = (int)$request->param('id');
        $user    = Session::get('user');
        $content = $request->post('content', '');
        $title   = $request->post('title', '');

        $db = \Koonect\Core\Database::getInstance();
        $db->execute(
            'UPDATE articles SET content = ?, title = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL',
            [\Koonect\Helpers\Sanitizer::richHtml($content), $title, $id]
        );

        Response::json(['success' => true, 'saved_at' => date('H:i:s')]);
    }

    public function destroy(Request $request): void
    {
        $id   = (int)$request->param('id');
        $user = Session::get('user');

        if (!in_array($user['role'], ['admin', 'director', 'chief_editor'])) {
            Response::forbidden();
        }

        $this->model->softDelete($id);
        CacheService::flush();
        Session::flash('success', 'Article archivé.');
        Response::redirect(REDAC_URL . '/articles');
    }

    public function revisions(Request $request): void
    {
        $id        = (int)$request->param('id');
        $article   = $this->model->findById($id);
        $revisions = $this->model->getRevisions($id);

        View::render('redac/article/revisions', [
            'article'   => $article,
            'revisions' => $revisions,
        ], 'redac');
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function extractFormData(Request $request): array
    {
        $tagIds = $request->post('tag_ids', []);
        if (is_string($tagIds)) $tagIds = array_filter(array_map('intval', explode(',', $tagIds)));

        return [
            'title'           => trim((string)$request->post('title', '')),
            'subtitle'        => trim((string)$request->post('subtitle', '')),
            'chapo'           => trim((string)$request->post('chapo', '')),
            'content'         => $request->post('content', ''),
            'category_id'     => $request->post('category_id') ? (int)$request->post('category_id') : null,
            'featured_image_id'=> $request->post('featured_image_id') ? (int)$request->post('featured_image_id') : null,
            'is_breaking'     => (int)($request->post('is_breaking', 0) == '1'),
            'is_featured'     => (int)($request->post('is_featured', 0) == '1'),
            'is_premium'      => (int)($request->post('is_premium', 0) == '1'),
            'seo_title'       => trim((string)$request->post('seo_title', '')),
            'seo_description' => trim((string)$request->post('seo_description', '')),
            'og_image'        => trim((string)$request->post('og_image', '')),
            'scheduled_at'    => $request->post('scheduled_at') ?: null,
            'tag_ids'         => $tagIds,
        ];
    }

    private function resolveStatus(string $action, string $currentStatus, string $role): string
    {
        return match ($action) {
            'submit'   => 'review',
            'validate' => in_array($role, self::ROLE_CAN_VALIDATE) ? 'validation' : $currentStatus,
            'publish'  => in_array($role, self::ROLE_CAN_PUBLISH)  ? 'published'  : $currentStatus,
            'reject'   => 'draft',
            'archive'  => 'archived',
            default    => $currentStatus, // 'save' → pas de changement de statut
        };
    }

    private function getAllowedTransitions(string $currentStatus, string $role): array
    {
        $transitions = [
            'draft'      => ['review'],
            'review'     => ['validation', 'draft'],
            'validation' => ['published', 'draft'],
            'published'  => ['archived'],
            'archived'   => ['draft'],
        ];
        $allowed = $transitions[$currentStatus] ?? [];

        // Restriction par rôle
        if (!in_array($role, self::ROLE_CAN_PUBLISH)) {
            $allowed = array_diff($allowed, ['published']);
        }
        if (!in_array($role, self::ROLE_CAN_VALIDATE)) {
            $allowed = array_diff($allowed, ['validation']);
        }

        return array_values($allowed);
    }
}
