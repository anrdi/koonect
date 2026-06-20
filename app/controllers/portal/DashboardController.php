<?php
declare(strict_types=1);

namespace Koonect\Controllers\Portal;

use Koonect\Core\{Request, Response, Session, View, Database};
use Koonect\Helpers\Sanitizer;

// ═══════════════════════════════════════════════════════════════════
// DASHBOARD CONTROLLER
// ═══════════════════════════════════════════════════════════════════
class DashboardController
{
    public function index(Request $request): void
    {
        View::render('portal/dashboard', [
            'seo' => ['seo_title' => 'Mon espace — ' . APP_NAME],
        ], 'portal');
    }

    public function favorites(Request $request): void
    {
        $user     = Session::get('user');
        $db       = Database::getInstance();
        $page     = max(1, (int)$request->get('page', 1));
        $offset   = ($page - 1) * 20;

        $total = (int)($db->fetch(
            'SELECT COUNT(*) AS cnt FROM favorites WHERE user_id = ?', [(int)$user['id']]
        )['cnt'] ?? 0);

        $favorites = $db->fetchAll(
            'SELECT a.id, a.title, a.chapo, a.slug, a.published_at,
                    c.name AS category_name, c.slug AS category_slug,
                    m.thumb_path AS featured_image_thumb, f.created_at AS favorited_at
             FROM favorites f
             INNER JOIN articles a ON f.article_id = a.id AND a.status = "published" AND a.deleted_at IS NULL
             LEFT JOIN categories c ON a.category_id = c.id
             LEFT JOIN media m ON a.featured_image_id = m.id
             WHERE f.user_id = ?
             ORDER BY f.created_at DESC
             LIMIT 20 OFFSET ?',
            [(int)$user['id'], $offset]
        );

        $paginator = new \Koonect\Helpers\Paginator($total, 20, $page);

        View::render('portal/favorites', [
            'favorites' => $favorites,
            'paginator' => $paginator,
            'total'     => $total,
            'seo'       => ['seo_title' => 'Mes favoris — ' . APP_NAME],
        ], 'portal');
    }

    public function toggleFavorite(Request $request): void
    {
        $user      = Session::get('user');
        $articleId = (int)$request->param('id');
        $db        = Database::getInstance();

        $existing = $db->fetch(
            'SELECT id FROM favorites WHERE user_id = ? AND article_id = ?',
            [(int)$user['id'], $articleId]
        );

        if ($existing) {
            $db->execute('DELETE FROM favorites WHERE user_id = ? AND article_id = ?', [(int)$user['id'], $articleId]);
            Response::json(['action' => 'removed', 'message' => 'Retiré des favoris.']);
        } else {
            $db->execute(
                'INSERT INTO favorites (user_id, article_id, created_at) VALUES (?, ?, NOW())',
                [(int)$user['id'], $articleId]
            );
            Response::json(['action' => 'added', 'message' => 'Ajouté aux favoris.']);
        }
    }

    public function history(Request $request): void
    {
        $user   = Session::get('user');
        $db     = Database::getInstance();
        $page   = max(1, (int)$request->get('page', 1));
        $offset = ($page - 1) * 20;

        $total = (int)($db->fetch(
            'SELECT COUNT(*) AS cnt FROM reading_history WHERE user_id = ?', [(int)$user['id']]
        )['cnt'] ?? 0);

        $history = $db->fetchAll(
            'SELECT a.id, a.title, a.chapo, a.slug, a.published_at,
                    c.name AS category_name, c.slug AS category_slug,
                    m.thumb_path AS featured_image_thumb, rh.read_at
             FROM reading_history rh
             INNER JOIN articles a ON rh.article_id = a.id AND a.status = "published" AND a.deleted_at IS NULL
             LEFT JOIN categories c ON a.category_id = c.id
             LEFT JOIN media m ON a.featured_image_id = m.id
             WHERE rh.user_id = ?
             ORDER BY rh.read_at DESC
             LIMIT 20 OFFSET ?',
            [(int)$user['id'], $offset]
        );

        $paginator = new \Koonect\Helpers\Paginator($total, 20, $page);

        View::render('portal/history', [
            'history'   => $history,
            'paginator' => $paginator,
            'total'     => $total,
            'seo'       => ['seo_title' => 'Historique de lecture — ' . APP_NAME],
        ], 'portal');
    }

    public function comments(Request $request): void
    {
        $user = Session::get('user');
        $db   = Database::getInstance();

        $comments = $db->fetchAll(
            'SELECT c.id, c.content, c.status, c.created_at, a.title AS article_title, a.slug AS article_slug
             FROM comments c
             INNER JOIN articles a ON c.article_id = a.id
             WHERE c.user_id = ? AND c.deleted_at IS NULL
             ORDER BY c.created_at DESC LIMIT 50',
            [(int)$user['id']]
        );

        View::render('portal/comments', [
            'comments' => $comments,
            'seo'      => ['seo_title' => 'Mes commentaires — ' . APP_NAME],
        ], 'portal');
    }

    public function newsletterSettings(Request $request): void
    {
        $user = Session::get('user');
        $db   = Database::getInstance();

        $subscription = $db->fetch(
            'SELECT * FROM newsletter_subscribers WHERE user_id = ? OR email = ? LIMIT 1',
            [(int)$user['id'], $user['email']]
        );

        View::render('portal/newsletter', [
            'subscription' => $subscription,
            'seo'          => ['seo_title' => 'Newsletter — ' . APP_NAME],
        ], 'portal');
    }

    public function updateNewsletter(Request $request): void
    {
        $user   = Session::get('user');
        $db     = Database::getInstance();
        $action = $request->post('action', '');

        if ($action === 'unsubscribe') {
            $db->execute(
                'UPDATE newsletter_subscribers SET unsubscribed_at = NOW() WHERE user_id = ? OR email = ?',
                [(int)$user['id'], $user['email']]
            );
            Session::flash('success', 'Vous avez été désabonné de la newsletter.');
        } elseif ($action === 'subscribe') {
            $model  = new \Koonect\Models\Newsletter();
            $token  = $model->subscribe($user['email'], (int)$user['id'], $request->ip());
            if ($token !== 'already_confirmed') {
                \Koonect\Services\MailService::sendNewsletterConfirmation($user['email'], $token);
                Session::flash('info', 'Un email de confirmation vous a été envoyé.');
            } else {
                Session::flash('info', 'Vous êtes déjà abonné à la newsletter.');
            }
        }

        Response::redirect(PORTAL_URL . '/newsletter');
    }
}

// ═══════════════════════════════════════════════════════════════════
// PROFILE CONTROLLER
// ═══════════════════════════════════════════════════════════════════
class ProfileController
{
    public function show(Request $request): void
    {
        $user   = Session::get('user');
        $db     = Database::getInstance();
        $full   = $db->fetch('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [(int)$user['id']]);

        View::render('portal/profile', [
            'profile' => $full,
            'seo'     => ['seo_title' => 'Mon profil — ' . APP_NAME],
        ], 'portal');
    }

    public function update(Request $request): void
    {
        $user        = Session::get('user');
        $db          = Database::getInstance();
        $userId      = (int)$user['id'];
        $displayName = Sanitizer::clean($request->post('display_name', ''));
        $bio         = Sanitizer::clean($request->post('bio', ''));

        $errors = [];
        if (strlen($displayName) < 2)  $errors[] = 'Nom affiché trop court.';
        if (strlen($displayName) > 120) $errors[] = 'Nom affiché trop long (max 120 caractères).';
        if (strlen($bio) > 500)         $errors[] = 'Bio trop longue (max 500 caractères).';

        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            Response::redirect(PORTAL_URL . '/profil');
        }

        // Mise à jour du profil
        $db->execute(
            'UPDATE users SET display_name = ?, updated_at = NOW() WHERE id = ?',
            [$displayName, $userId]
        );

        // Upsert profil abonné
        $db->execute(
            'INSERT INTO subscriber_profiles (user_id, bio, updated_at) VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE bio = ?, updated_at = NOW()',
            [$userId, $bio, $bio]
        );

        // Gérer l'upload avatar
        if (!empty($_FILES['avatar']['name'])) {
            try {
                \Koonect\Services\ImageService::validateUpload($_FILES['avatar']);
                $destDir = UPLOAD_PATH . '/authors';
                if (!is_dir($destDir)) mkdir($destDir, 0755, true);

                $tmp      = $_FILES['avatar']['tmp_name'];
                $filename = 'avatar_' . $userId . '_' . time();
                $result   = \Koonect\Services\ImageService::process($tmp, $destDir);

                $relativePath = 'uploads/authors/' . basename($result['webp_path']);
                $db->execute('UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?', [$relativePath, $userId]);
            } catch (\Exception $e) {
                Session::flash('error', 'Erreur avatar : ' . $e->getMessage());
                Response::redirect(PORTAL_URL . '/profil');
            }
        }

        // Changer le mot de passe
        $currentPw  = $request->post('current_password', '');
        $newPw      = $request->post('new_password', '');
        $confirmPw  = $request->post('new_password_confirm', '');

        if ($newPw) {
            $fullUser = $db->fetch('SELECT password_hash FROM users WHERE id = ?', [$userId]);
            $model    = new \Koonect\Models\User();

            if (!$model->verifyPassword($currentPw, $fullUser['password_hash'])) {
                Session::flash('error', 'Mot de passe actuel incorrect.');
                Response::redirect(PORTAL_URL . '/profil');
            }
            if (strlen($newPw) < 12) {
                Session::flash('error', 'Nouveau mot de passe trop court (min. 12 caractères).');
                Response::redirect(PORTAL_URL . '/profil');
            }
            if ($newPw !== $confirmPw) {
                Session::flash('error', 'Les nouveaux mots de passe ne correspondent pas.');
                Response::redirect(PORTAL_URL . '/profil');
            }

            $model->updatePassword($userId, $newPw);
        }

        // Mettre à jour la session
        $updated = $db->fetch('SELECT id, email, username, display_name, avatar, role FROM users WHERE id = ?', [$userId]);
        Session::set('user', $updated);

        Session::flash('success', 'Profil mis à jour avec succès.');
        Response::redirect(PORTAL_URL . '/profil');
    }
}
