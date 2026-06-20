<?php
declare(strict_types=1);

namespace Koonect\Controllers\Portal;

use Koonect\Core\{Request, Response, Session, View, Database};
use Koonect\Models\User;

class GdprController
{
    public function index(Request $request): void
    {
        View::render('portal/gdpr', ['seo' => ['seo_title' => 'Mes données — ' . APP_NAME]], 'portal');
    }

    public function export(Request $request): void
    {
        $user   = Session::get('user');
        $db     = Database::getInstance();
        $userId = (int)$user['id'];

        // Collecter toutes les données utilisateur
        $userData = [
            'export_date' => date('c'),
            'profile'     => $db->fetch(
                'SELECT email, username, display_name, created_at, last_login_at, email_verified_at FROM users WHERE id = ?',
                [$userId]
            ),
            'reading_history' => $db->fetchAll(
                'SELECT a.title, a.slug, rh.read_at FROM reading_history rh
                 INNER JOIN articles a ON rh.article_id = a.id WHERE rh.user_id = ?
                 ORDER BY rh.read_at DESC',
                [$userId]
            ),
            'favorites' => $db->fetchAll(
                'SELECT a.title, a.slug, f.created_at FROM favorites f
                 INNER JOIN articles a ON f.article_id = a.id WHERE f.user_id = ?
                 ORDER BY f.created_at DESC',
                [$userId]
            ),
            'comments' => $db->fetchAll(
                'SELECT c.content, c.status, c.created_at, a.title AS article_title FROM comments c
                 INNER JOIN articles a ON c.article_id = a.id
                 WHERE c.user_id = ? AND c.deleted_at IS NULL ORDER BY c.created_at DESC',
                [$userId]
            ),
            'newsletter_subscriptions' => $db->fetchAll(
                'SELECT email, confirmed_at, unsubscribed_at, created_at FROM newsletter_subscribers WHERE user_id = ?',
                [$userId]
            ),
            'gdpr_consents' => $db->fetchAll(
                'SELECT type, granted, created_at FROM gdpr_consents WHERE user_id = ? ORDER BY created_at DESC',
                [$userId]
            ),
        ];

        // Log de la demande d'export
        $db->execute(
            'INSERT INTO gdpr_consents (user_id, type, granted, ip_address, created_at) VALUES (?, "data_export", 1, ?, NOW())',
            [$userId, $request->ip()]
        );

        $json = json_encode($userData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="koonect-mes-donnees-' . date('Y-m-d') . '.json"');
        header('Content-Length: ' . strlen($json));
        header('Cache-Control: no-cache, no-store');
        echo $json;
        exit;
    }

    public function deleteAccount(Request $request): void
    {
        $user      = Session::get('user');
        $db        = Database::getInstance();
        $userId    = (int)$user['id'];
        $email     = trim((string)$request->post('confirm_email', ''));
        $password  = $request->post('confirm_password', '');

        // Vérifications
        if (strtolower($email) !== strtolower($user['email'])) {
            Session::flash('error', 'L\'adresse email ne correspond pas à votre compte.');
            Response::redirect(PORTAL_URL . '/donnees');
        }

        $fullUser = $db->fetch('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$userId]);
        $userModel = new User();

        if (!$fullUser || !$userModel->verifyPassword($password, $fullUser['password_hash'])) {
            Session::flash('error', 'Mot de passe incorrect.');
            Response::redirect(PORTAL_URL . '/donnees');
        }

        // Soft delete
        $userModel->softDelete($userId);

        // Log RGPD
        $db->execute(
            'INSERT INTO gdpr_consents (user_id, type, granted, ip_address, user_agent, created_at) VALUES (?, "account_deletion", 1, ?, ?, NOW())',
            [$userId, $request->ip(), $request->userAgent()]
        );

        // Déconnecter
        \Koonect\Services\SsoService::clearToken();
        Session::destroy();

        // Rediriger vers le site principal avec message
        Session::start();
        Session::flash('info', 'Votre compte a été supprimé. Vos données seront anonymisées sous 30 jours.');
        Response::redirect(APP_URL);
    }

    public function cookies(Request $request): void
    {
        View::render('portal/gdpr', [], 'portal');
    }

    public function updateConsent(Request $request): void
    {
        $user      = Session::get('user');
        $db        = Database::getInstance();
        $analytics = (int)($request->post('analytics', 0) == '1');

        $db->execute(
            'INSERT INTO gdpr_consents (user_id, type, granted, ip_address, created_at) VALUES (?, "analytics", ?, ?, NOW())',
            [(int)$user['id'], $analytics, $request->ip()]
        );

        Session::flash('success', 'Vos préférences de cookies ont été mises à jour.');
        Response::redirect(PORTAL_URL . '/donnees');
    }
}
