<?php
declare(strict_types=1);

namespace Koonect\Controllers\Portal;

use Koonect\Core\{Request, Response, Session, View, Logger};
use Koonect\Models\User;
use Koonect\Services\{MailService, TwoFactorService, SsoService};
use Koonect\Helpers\Sanitizer;

class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // ── Connexion ─────────────────────────────────────────────────

    public function loginForm(Request $request): void
    {
        if (Session::has('user')) Response::redirect(PORTAL_URL . '/');
        View::render('portal/login', [], 'portal');
    }

    public function login(Request $request): void
    {
        $email    = Sanitizer::email($request->post('email', ''));
        $password = $request->post('password', '');

        if (!$email || !$password) {
            Session::flash('error', 'Email ou mot de passe invalide.');
            Response::redirect(PORTAL_URL . '/connexion');
        }

        $user = $this->userModel->findByEmail((string)$email);

        if (!$user || !$this->userModel->verifyPassword($password, $user['password_hash'])) {
            Logger::security('Tentative connexion échouée', ['email' => $email, 'ip' => $request->ip()]);
            Session::flash('error', 'Email ou mot de passe incorrect.');
            Response::redirect(PORTAL_URL . '/connexion');
        }

        if ($user['status'] === 'inactive') {
            Session::flash('error', 'Votre compte n\'est pas encore activé. Vérifiez vos emails.');
            Response::redirect(PORTAL_URL . '/connexion');
        }

        if ($user['status'] === 'banned') {
            Session::flash('error', 'Votre compte a été suspendu.');
            Response::redirect(PORTAL_URL . '/connexion');
        }

        // Rehash si nécessaire
        if ($this->userModel->needsRehash($user['password_hash'])) {
            $this->userModel->updatePassword($user['id'], $password);
        }

        // 2FA activé
        if ($user['two_factor_enabled']) {
            Session::set('2fa_pending_user_id', $user['id']);
            Response::redirect(PORTAL_URL . '/2fa');
        }

        $this->finalizeLogin($user, $request);
    }

    public function twoFactorForm(Request $request): void
    {
        if (!Session::has('2fa_pending_user_id')) {
            Response::redirect(PORTAL_URL . '/connexion');
        }
        View::render('portal/2fa', [], 'portal');
    }

    public function twoFactorVerify(Request $request): void
    {
        $userId = Session::get('2fa_pending_user_id');
        if (!$userId) Response::redirect(PORTAL_URL . '/connexion');

        $code = trim((string)$request->post('code', ''));
        $user = $this->userModel->findById((int)$userId);

        if (!$user || !TwoFactorService::verifyCode($user['two_factor_secret'], $code)) {
            Logger::security('2FA code invalide', ['user_id' => $userId, 'ip' => $request->ip()]);
            Session::flash('error', 'Code d\'authentification invalide.');
            Response::redirect(PORTAL_URL . '/2fa');
        }

        Session::delete('2fa_pending_user_id');
        $this->finalizeLogin($user, $request);
    }

    private function finalizeLogin(array $user, Request $request): void
    {
        session_regenerate_id(true);

        $sessionUser = [
            'id'           => $user['id'],
            'email'        => $user['email'],
            'username'     => $user['username'],
            'display_name' => $user['display_name'],
            'avatar'       => $user['avatar'],
            'role'         => $user['role'],
        ];
        Session::set('user', $sessionUser);

        // SSO cookie cross-sous-domaines
        SsoService::setTokenCookie(SsoService::createToken((int)$user['id']));

        $this->userModel->updateLastLogin((int)$user['id']);
        Logger::info('Connexion réussie', ['user_id' => $user['id'], 'ip' => $request->ip()]);

        $redirect = $request->get('redirect', PORTAL_URL . '/');
        Response::redirect($redirect);
    }

    // ── Inscription ───────────────────────────────────────────────

    public function registerForm(Request $request): void
    {
        if (Session::has('user')) Response::redirect(PORTAL_URL . '/');
        View::render('portal/register', [], 'portal');
    }

    public function register(Request $request): void
    {
        $email    = Sanitizer::email($request->post('email', ''));
        $username = trim((string)$request->post('username', ''));
        $password = $request->post('password', '');
        $confirm  = $request->post('password_confirm', '');

        $errors = [];
        if (!$email)                        $errors[] = 'Email invalide.';
        if (strlen($username) < 3)          $errors[] = 'Nom d\'utilisateur trop court (min. 3 caractères).';
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) $errors[] = 'Nom d\'utilisateur invalide (lettres, chiffres, _ et - uniquement).';
        if (strlen($password) < 12)         $errors[] = 'Mot de passe trop court (min. 12 caractères).';
        if ($password !== $confirm)         $errors[] = 'Les mots de passe ne correspondent pas.';
        if (!$request->post('gdpr_consent'))$errors[] = 'Vous devez accepter la politique de confidentialité.';

        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            Response::redirect(PORTAL_URL . '/inscription');
        }

        // Vérifier unicité email et username
        $db = \Koonect\Core\Database::getInstance();
        if ($db->fetch('SELECT id FROM users WHERE email = ? LIMIT 1', [(string)$email])) {
            Session::flash('error', 'Cette adresse email est déjà utilisée.');
            Response::redirect(PORTAL_URL . '/inscription');
        }
        if ($db->fetch('SELECT id FROM users WHERE username = ? LIMIT 1', [$username])) {
            Session::flash('error', 'Ce nom d\'utilisateur est déjà pris.');
            Response::redirect(PORTAL_URL . '/inscription');
        }

        $userId = $this->userModel->create([
            'email'        => (string)$email,
            'username'     => $username,
            'display_name' => $username,
            'password'     => $password,
            'role'         => 'subscriber',
        ]);

        // Enregistrer le consentement RGPD
        $db->execute(
            'INSERT INTO gdpr_consents (user_id, type, granted, ip_address, user_agent, created_at)
             VALUES (?, "registration", 1, ?, ?, NOW())',
            [$userId, $request->ip(), $request->userAgent()]
        );

        // Envoyer email de vérification
        $token = $this->userModel->createToken($userId, 'email_verification', 86400);
        MailService::sendEmailVerification((string)$email, $username, $token);

        Session::flash('success', 'Compte créé ! Vérifiez vos emails pour activer votre compte.');
        Response::redirect(PORTAL_URL . '/connexion');
    }

    // ── Vérification email ────────────────────────────────────────

    public function verifyEmail(Request $request): void
    {
        $token = $request->get('token', '');
        $row   = $this->userModel->verifyToken($token, 'email_verification');

        if (!$row) {
            Session::flash('error', 'Lien de vérification invalide ou expiré.');
            Response::redirect(PORTAL_URL . '/connexion');
        }

        $this->userModel->verifyEmail((int)$row['user_id']);
        Session::flash('success', 'Votre adresse email est confirmée. Vous pouvez vous connecter.');
        Response::redirect(PORTAL_URL . '/connexion');
    }

    // ── Mot de passe oublié ───────────────────────────────────────

    public function forgotForm(Request $request): void
    {
        View::render('portal/forgot-password', [], 'portal');
    }

    public function forgotSend(Request $request): void
    {
        $email = Sanitizer::email($request->post('email', ''));
        // Message générique pour éviter l'énumération d'emails
        Session::flash('success', 'Si un compte existe avec cet email, vous recevrez un lien de réinitialisation.');

        if ($email) {
            $user = $this->userModel->findByEmail((string)$email);
            if ($user) {
                $token = $this->userModel->createToken((int)$user['id'], 'password_reset', 3600);
                MailService::sendPasswordReset((string)$email, $user['display_name'], $token);
            }
        }

        Response::redirect(PORTAL_URL . '/connexion');
    }

    public function resetForm(Request $request): void
    {
        $token = $request->get('token', '');
        View::render('portal/reset-password', ['token' => $token], 'portal');
    }

    public function resetPassword(Request $request): void
    {
        $token    = $request->post('token', '');
        $password = $request->post('password', '');
        $confirm  = $request->post('password_confirm', '');

        if (strlen($password) < 12 || $password !== $confirm) {
            Session::flash('error', 'Mot de passe invalide ou non correspondant (min. 12 caractères).');
            Response::redirect(PORTAL_URL . '/reinitialiser-mot-de-passe?token=' . urlencode($token));
        }

        $row = $this->userModel->verifyToken($token, 'password_reset');
        if (!$row) {
            Session::flash('error', 'Lien de réinitialisation invalide ou expiré.');
            Response::redirect(PORTAL_URL . '/mot-de-passe-oublie');
        }

        $this->userModel->updatePassword((int)$row['user_id'], $password);
        Session::flash('success', 'Mot de passe réinitialisé. Vous pouvez vous connecter.');
        Response::redirect(PORTAL_URL . '/connexion');
    }

    // ── 2FA Setup ─────────────────────────────────────────────────

    public function setup2faForm(Request $request): void
    {
        $user   = Session::get('user');
        $secret = TwoFactorService::generateSecret();
        Session::set('2fa_setup_secret', $secret);
        $qrUrl  = TwoFactorService::getQrCodeUrl($user['email'], $secret);
        View::render('portal/2fa-setup', ['qrUrl' => $qrUrl, 'secret' => $secret], 'portal');
    }

    public function setup2fa(Request $request): void
    {
        $secret = Session::get('2fa_setup_secret');
        $code   = trim((string)$request->post('code', ''));
        $user   = Session::get('user');

        if (!$secret || !TwoFactorService::verifyCode($secret, $code)) {
            Session::flash('error', 'Code invalide. Réessayez.');
            Response::redirect(PORTAL_URL . '/2fa/configurer');
        }

        $this->userModel->enable2FA((int)$user['id'], $secret);
        Session::delete('2fa_setup_secret');
        Session::flash('success', 'Authentification à deux facteurs activée.');
        Response::redirect(PORTAL_URL . '/profil');
    }

    public function disable2fa(Request $request): void
    {
        $user = Session::get('user');
        \Koonect\Core\Database::getInstance()->execute(
            'UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL WHERE id = ?',
            [(int)$user['id']]
        );
        Session::flash('success', '2FA désactivé.');
        Response::redirect(PORTAL_URL . '/profil');
    }

    // ── Déconnexion ───────────────────────────────────────────────

    public function logout(Request $request): void
    {
        SsoService::clearToken();
        Session::destroy();
        Response::redirect(APP_URL);
    }
}
