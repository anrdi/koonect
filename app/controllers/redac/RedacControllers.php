<?php
declare(strict_types=1);

namespace Koonect\Controllers\Redac;

use Koonect\Core\{Request, Response, Session, View, Database};
use Koonect\Helpers\Sanitizer;
use Koonect\Services\CacheService;

// ═══════════════════════════════════════════════════════════════════
// REDAC AUTH CONTROLLER
// ═══════════════════════════════════════════════════════════════════
class AuthController
{
    public function form(Request $request): void
    {
        if (Session::has('user')) {
            $user = Session::get('user');
            if (in_array($user['role'], ['admin','director','chief_editor','journalist','proofreader','moderator'])) {
                Response::redirect(REDAC_URL . '/');
            }
        }
        View::render('redac/login', [], '');
    }

    public function login(Request $request): void
    {
        $email    = \Koonect\Helpers\Sanitizer::email($request->post('email', ''));
        $password = $request->post('password', '');
        $userModel = new \Koonect\Models\User();

        if (!$email) {
            Session::flash('error', 'Email invalide.');
            Response::redirect(REDAC_URL . '/login');
        }

        $user = $userModel->findByEmail((string)$email);
        $allowedRoles = ['admin','director','chief_editor','journalist','proofreader','moderator'];

        if (!$user || !in_array($user['role'], $allowedRoles) || !$userModel->verifyPassword($password, $user['password_hash'])) {
            \Koonect\Core\Logger::security('Tentative connexion rédaction échouée', ['email' => $email, 'ip' => $request->ip()]);
            Session::flash('error', 'Accès refusé.');
            Response::redirect(REDAC_URL . '/login');
        }

        session_regenerate_id(true);
        Session::set('user', [
            'id'           => $user['id'],
            'email'        => $user['email'],
            'username'     => $user['username'],
            'display_name' => $user['display_name'],
            'role'         => $user['role'],
        ]);
        $userModel->updateLastLogin((int)$user['id']);
        Response::redirect(REDAC_URL . '/');
    }

    public function logout(Request $request): void
    {
        Session::destroy();
        Response::redirect(REDAC_URL . '/login');
    }
}

// ═══════════════════════════════════════════════════════════════════
// REDAC DASHBOARD
// ═══════════════════════════════════════════════════════════════════
class DashboardController
{
    public function index(Request $request): void
    {
        $db   = Database::getInstance();
        $user = Session::get('user');
        $role = $user['role'];

        // Stats selon le rôle
        $isAdmin = in_array($role, ['admin','director','chief_editor']);

        $statsQuery = $isAdmin
            ? 'SELECT status, COUNT(*) AS cnt FROM articles WHERE deleted_at IS NULL GROUP BY status'
            : 'SELECT status, COUNT(*) AS cnt FROM articles WHERE author_id = ? AND deleted_at IS NULL GROUP BY status';
        $statsParams = $isAdmin ? [] : [(int)$user['id']];
        $rawStats = $db->fetchAll($statsQuery, $statsParams);

        $stats = array_fill_keys(['draft','review','validation','published','archived'], 0);
        foreach ($rawStats as $r) $stats[$r['status']] = (int)$r['cnt'];

        // Articles en attente de relecture/validation
        $pending = $db->fetchAll(
            'SELECT a.id, a.title, a.status, a.updated_at, u.display_name AS author_name
             FROM articles a LEFT JOIN users u ON a.author_id = u.id
             WHERE a.status IN ("review","validation") AND a.deleted_at IS NULL
             ORDER BY a.updated_at ASC LIMIT 10'
        );

        // Nombre total de commentaires actifs
        $totalComments = (int)($db->fetch(
            'SELECT COUNT(*) AS cnt FROM comments WHERE deleted_at IS NULL'
        )['cnt'] ?? 0);

        View::render('redac/dashboard', [
            'pageTitle'       => 'Tableau de bord',
            'stats'           => $stats,
            'pending'         => $pending,
            'totalComments'   => $totalComments,
        ], 'redac');
    }
}

// ═══════════════════════════════════════════════════════════════════
// CATEGORY CONTROLLER (redac)
// ═══════════════════════════════════════════════════════════════════
class CategoryController
{
    public function index(Request $request): void
    {
        $categories = (new \Koonect\Models\Category())->all();
        View::render('redac/categories', ['categories' => $categories, 'pageTitle' => 'Catégories'], 'redac');
    }

    public function store(Request $request): void
    {
        $data = [
            'name'             => Sanitizer::clean($request->post('name', '')),
            'description'      => Sanitizer::clean($request->post('description', '')),
            'parent_id'        => $request->post('parent_id') ? (int)$request->post('parent_id') : null,
            'meta_title'       => Sanitizer::clean($request->post('meta_title', '')),
            'meta_description' => Sanitizer::clean($request->post('meta_description', '')),
            'position'         => (int)$request->post('position', 0),
        ];
        if (strlen($data['name']) < 2) {
            Session::flash('error', 'Nom de catégorie trop court.');
            Response::redirect(REDAC_URL . '/categories');
        }
        (new \Koonect\Models\Category())->create($data);
        CacheService::flush();
        Session::flash('success', 'Catégorie créée.');
        Response::redirect(REDAC_URL . '/categories');
    }

    public function update(Request $request): void
    {
        $id   = (int)$request->param('id');
        $data = [
            'name'             => Sanitizer::clean($request->post('name', '')),
            'description'      => Sanitizer::clean($request->post('description', '')),
            'parent_id'        => $request->post('parent_id') ? (int)$request->post('parent_id') : null,
            'meta_title'       => Sanitizer::clean($request->post('meta_title', '')),
            'meta_description' => Sanitizer::clean($request->post('meta_description', '')),
            'position'         => (int)$request->post('position', 0),
        ];
        (new \Koonect\Models\Category())->update($id, $data);
        CacheService::flush();
        Session::flash('success', 'Catégorie mise à jour.');
        Response::redirect(REDAC_URL . '/categories');
    }

    public function destroy(Request $request): void
    {
        $id = (int)$request->param('id');
        Database::getInstance()->execute('DELETE FROM categories WHERE id = ?', [$id]);
        CacheService::flush();
        Session::flash('success', 'Catégorie supprimée.');
        Response::redirect(REDAC_URL . '/categories');
    }
}

// ═══════════════════════════════════════════════════════════════════
// TAG CONTROLLER (redac)
// ═══════════════════════════════════════════════════════════════════
class TagController
{
    public function index(Request $request): void
    {
        $tags = (new \Koonect\Models\Tag())->all();
        View::render('redac/tags', ['tags' => $tags, 'pageTitle' => 'Tags'], 'redac');
    }

    public function store(Request $request): void
    {
        $name = Sanitizer::clean($request->post('name', ''));
        if (strlen($name) < 2) {
            if ($request->isAjax()) Response::json(['error' => 'Nom trop court.'], 422);
            Session::flash('error', 'Nom de tag trop court.');
            Response::redirect(REDAC_URL . '/tags');
        }
        $tag = (new \Koonect\Models\Tag())->findOrCreate($name);
        if ($request->isAjax()) Response::json(['id' => $tag['id'], 'name' => $tag['name'], 'slug' => $tag['slug']]);
        Session::flash('success', 'Tag créé.');
        Response::redirect(REDAC_URL . '/tags');
    }
}

// ═══════════════════════════════════════════════════════════════════
// COMMENT CONTROLLER (redac)
// ═══════════════════════════════════════════════════════════════════
class CommentController
{
    public function index(Request $request): void
    {
        $comments = (new \Koonect\Models\Comment())->getRecent();
        View::render('redac/comments', ['comments' => $comments, 'pageTitle' => 'Modération des commentaires'], 'redac');
    }

    public function approve(Request $request): void
    {
        $id = (int)$request->param('id');
        (new \Koonect\Models\Comment())->approve($id);
        if ($request->isAjax()) Response::json(['success' => true]);
        Session::flash('success', 'Commentaire approuvé.');
        Response::redirect(REDAC_URL . '/commentaires');
    }

    public function reject(Request $request): void
    {
        $id = (int)$request->param('id');
        (new \Koonect\Models\Comment())->reject($id);
        if ($request->isAjax()) Response::json(['success' => true]);
        Session::flash('success', 'Commentaire rejeté.');
        Response::redirect(REDAC_URL . '/commentaires');
    }
}

// ═══════════════════════════════════════════════════════════════════
// USER CONTROLLER (redac)
// ═══════════════════════════════════════════════════════════════════
class UserController
{
    public function index(Request $request): void
    {
        $user = Session::get('user');
        if (!in_array($user['role'], ['admin', 'director'])) Response::forbidden();

        $users = Database::getInstance()->fetchAll(
            'SELECT id, email, username, display_name, role, status, created_at, last_login_at
             FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC'
        );
        View::render('redac/users/index', ['users' => $users, 'pageTitle' => 'Utilisateurs'], 'redac');
    }

    public function create(Request $request): void
    {
        $user = Session::get('user');
        if (!in_array($user['role'], ['admin', 'director'])) Response::forbidden();
        View::render('redac/users/create', ['pageTitle' => 'Nouvel utilisateur'], 'redac');
    }

    public function store(Request $request): void
    {
        $user = Session::get('user');
        if (!in_array($user['role'], ['admin', 'director'])) Response::forbidden();

        $email    = Sanitizer::email($request->post('email', ''));
        $username = Sanitizer::clean($request->post('username', ''));
        $name     = Sanitizer::clean($request->post('display_name', ''));
        $role     = $request->post('role', 'journalist');
        $password = $request->post('password', '');

        $allowedRoles = ['admin','director','chief_editor','journalist','proofreader','moderator','subscriber'];
        if (!$email || strlen($username) < 3 || strlen($password) < 12 || !in_array($role, $allowedRoles)) {
            Session::flash('error', 'Données invalides. Vérifiez tous les champs.');
            Response::redirect(REDAC_URL . '/utilisateurs/nouveau');
        }

        $model = new \Koonect\Models\User();
        $id = $model->create([
            'email'        => (string)$email,
            'username'     => $username,
            'display_name' => $name ?: $username,
            'password'     => $password,
            'role'         => $role,
        ]);
        $model->verifyEmail($id); // Activer directement les comptes créés par l'admin

        Session::flash('success', 'Utilisateur créé et activé.');
        Response::redirect(REDAC_URL . '/utilisateurs');
    }

    public function edit(Request $request): void
    {
        $id   = (int)$request->param('id');
        $user = Database::getInstance()->fetch('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$user) Response::notFound();
        View::render('redac/users/edit', ['editUser' => $user, 'pageTitle' => 'Modifier l\'utilisateur'], 'redac');
    }

    public function update(Request $request): void
    {
        $currentUser = Session::get('user');
        if (!in_array($currentUser['role'], ['admin', 'director'])) Response::forbidden();

        $id   = (int)$request->param('id');
        $db   = Database::getInstance();
        $role = $request->post('role', '');
        $status = $request->post('status', '');
        $name   = Sanitizer::clean($request->post('display_name', ''));

        $db->execute(
            'UPDATE users SET display_name = ?, role = ?, status = ?, updated_at = NOW() WHERE id = ?',
            [$name, $role, $status, $id]
        );

        Session::flash('success', 'Utilisateur mis à jour.');
        Response::redirect(REDAC_URL . '/utilisateurs');
    }
}

// ═══════════════════════════════════════════════════════════════════
// SETTINGS CONTROLLER (redac)
// ═══════════════════════════════════════════════════════════════════
class SettingsController
{
    public function index(Request $request): void
    {
        $user = Session::get('user');
        if (!in_array($user['role'], ['admin', 'director'])) Response::forbidden();

        $settings = Database::getInstance()->fetchAll('SELECT * FROM settings ORDER BY `group`, `key`');
        $grouped  = [];
        foreach ($settings as $s) {
            $grouped[$s['group']][] = $s;
        }

        View::render('redac/settings/index', ['grouped' => $grouped, 'pageTitle' => 'Paramètres'], 'redac');
    }

    public function save(Request $request): void
    {
        $user = Session::get('user');
        if (!in_array($user['role'], ['admin', 'director'])) Response::forbidden();

        $db   = Database::getInstance();
        $data = $request->post();

        foreach ($data as $key => $value) {
            if (in_array($key, ['csrf_token', '_method'])) continue;
            $db->execute(
                'UPDATE settings SET value = ?, updated_at = NOW() WHERE `key` = ?',
                [Sanitizer::clean((string)$value), $key]
            );
        }

        CacheService::forget('site_settings');
        Session::flash('success', 'Paramètres sauvegardés.');
        Response::redirect(REDAC_URL . '/parametres');
    }

    public function smtpTest(Request $request): void
    {
        $user   = Session::get('user');
        $result = \Koonect\Services\MailService::send(
            $user['email'], $user['display_name'],
            'Test SMTP — ' . APP_NAME,
            '<p>Le serveur SMTP est correctement configuré !</p>'
        );
        Session::flash($result ? 'success' : 'error', $result ? 'Email de test envoyé !' : 'Échec SMTP. Vérifiez la configuration.');
        Response::redirect(REDAC_URL . '/parametres');
    }

    public function seo(Request $request): void
    {
        $settings = Database::getInstance()->fetchAll("SELECT * FROM settings WHERE `group` = 'seo'");
        View::render('redac/settings/seo', ['settings' => $settings, 'pageTitle' => 'SEO'], 'redac');
    }

    public function saveSeo(Request $request): void
    {
        $this->save($request);
    }

    public function rgpd(Request $request): void
    {
        $db    = Database::getInstance();
        $stats = [
            'pending_deletions' => $db->fetch('SELECT COUNT(*) AS cnt FROM users WHERE deleted_at IS NOT NULL AND anonymized_at IS NULL')['cnt'] ?? 0,
            'nl_unconfirmed'    => $db->fetch('SELECT COUNT(*) AS cnt FROM newsletter_subscribers WHERE confirmed_at IS NULL')['cnt'] ?? 0,
            'recent_consents'   => $db->fetchAll('SELECT type, COUNT(*) AS cnt FROM gdpr_consents WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY type'),
        ];
        View::render('redac/settings/rgpd', ['stats' => $stats, 'pageTitle' => 'RGPD'], 'redac');
    }
}

// ═══════════════════════════════════════════════════════════════════
// NEWSLETTER CONTROLLER (redac)
// ═══════════════════════════════════════════════════════════════════
class NewsletterController
{
    public function index(Request $request): void
    {
        $db = Database::getInstance();
        $campaigns   = $db->fetchAll('SELECT * FROM newsletter_campaigns ORDER BY created_at DESC LIMIT 20');
        $subscribers = (int)($db->fetch('SELECT COUNT(*) AS cnt FROM newsletter_subscribers WHERE confirmed_at IS NOT NULL AND unsubscribed_at IS NULL')['cnt'] ?? 0);

        View::render('redac/newsletter', [
            'campaigns'   => $campaigns,
            'subscribers' => $subscribers,
            'pageTitle'   => 'Newsletter',
        ], 'redac');
    }

    public function createCampaign(Request $request): void
    {
        $db      = Database::getInstance();
        $subject = Sanitizer::clean($request->post('subject', ''));
        $content = Sanitizer::richHtml($request->post('content_html', ''));
        $listId  = $request->post('list_id') ? (int)$request->post('list_id') : null;

        if (strlen($subject) < 5) {
            Session::flash('error', 'Objet de la campagne trop court.');
            Response::redirect(REDAC_URL . '/newsletter');
        }

        $db->insert(
            'INSERT INTO newsletter_campaigns (list_id, subject, content_html, status, created_at) VALUES (?, ?, ?, "draft", NOW())',
            [$listId, $subject, $content]
        );

        Session::flash('success', 'Campagne créée en brouillon.');
        Response::redirect(REDAC_URL . '/newsletter');
    }

    public function send(Request $request): void
    {
        $id       = (int)$request->param('id');
        $db       = Database::getInstance();
        $campaign = $db->fetch('SELECT * FROM newsletter_campaigns WHERE id = ? AND status = "draft"', [$id]);

        if (!$campaign) {
            Session::flash('error', 'Campagne introuvable ou déjà envoyée.');
            Response::redirect(REDAC_URL . '/newsletter');
        }

        $subscribers = (new \Koonect\Models\Newsletter())->getConfirmedSubscribers();
        $sent = 0; $errors = 0;

        $db->execute('UPDATE newsletter_campaigns SET status = "sending" WHERE id = ?', [$id]);

        $unsubBaseUrl = APP_URL . '/newsletter/desabonnement?token=';

        foreach ($subscribers as $sub) {
            $unsubLink = $unsubBaseUrl . urlencode(hash('sha256', $sub['token_hash']));
            $html = $campaign['content_html']
                . '<p style="font-size:12px;color:#999;margin-top:32px;text-align:center;">Pour vous désabonner : <a href="' . $unsubLink . '">cliquez ici</a></p>';

            $headers = [
                'List-Unsubscribe' => '<' . $unsubLink . '>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ];

            $ok = \Koonect\Services\MailService::send($sub['email'], '', $campaign['subject'], $html, '', $headers);
            if ($ok) {
                $sent++;
                $db->execute(
                    'INSERT IGNORE INTO newsletter_sends (campaign_id, subscriber_id, sent_at) VALUES (?, ?, NOW())',
                    [$id, $sub['id']]
                );
            } else {
                $errors++;
            }
        }

        $db->execute(
            'UPDATE newsletter_campaigns SET status = "sent", sent_at = NOW(), stats_json = ? WHERE id = ?',
            [json_encode(['sent' => $sent, 'errors' => $errors]), $id]
        );

        Session::flash('success', "Campagne envoyée : $sent emails expédiés, $errors erreurs.");
        Response::redirect(REDAC_URL . '/newsletter');
    }
}
