<?php
declare(strict_types=1);

namespace Koonect\Middleware;

use Koonect\Core\{Request, Response, Session, Logger};
use Koonect\Core\Database;
use Koonect\Helpers\Csrf;

// ═══════════════════════════════════════════════════════════════════
// CSRF MIDDLEWARE
// ═══════════════════════════════════════════════════════════════════
class CsrfMiddleware
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function handle(Request $request, callable $next): void
    {
        if (!in_array($request->getMethod(), self::SAFE_METHODS)) {
            if (!Csrf::verifyRequest()) {
                Logger::security('CSRF token invalide', [
                    'ip'  => $request->ip(),
                    'uri' => $request->getUri(),
                ]);
                Response::setStatusCode(419);
                if ($request->isAjax()) {
                    Response::json(['error' => 'Token CSRF invalide.'], 419);
                }
                Session::flash('error', 'Session expirée. Veuillez réessayer.');
                Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
            }
        }
        $next();
    }
}

// ═══════════════════════════════════════════════════════════════════
// AUTH MIDDLEWARE
// ═══════════════════════════════════════════════════════════════════
class AuthMiddleware
{
    public function handle(Request $request, callable $next): void
    {
        if (!Session::has('user')) {
            Session::flash('error', 'Vous devez être connecté pour accéder à cette page.');
            Response::redirect(PORTAL_URL . '/connexion?redirect=' . urlencode($request->getUri()));
        }
        $next();
    }
}

// ═══════════════════════════════════════════════════════════════════
// ROLE MIDDLEWARE (usage: new RoleMiddleware(['admin','director']))
// ═══════════════════════════════════════════════════════════════════
class RoleMiddleware
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function handle(Request $request, callable $next): void
    {
        $user = Session::get('user');
        if (!$user || !in_array($user['role'], $this->allowedRoles, true)) {
            Logger::security('Accès refusé (rôle insuffisant)', [
                'user_id' => $user['id'] ?? null,
                'role'    => $user['role'] ?? null,
                'uri'     => $request->getUri(),
            ]);
            Response::forbidden();
        }
        $next();
    }
}

// ═══════════════════════════════════════════════════════════════════
// RATE LIMIT MIDDLEWARE
// ═══════════════════════════════════════════════════════════════════
class RateLimitMiddleware
{
    private int $maxAttempts;
    private int $windowSeconds;

    public function __construct(int $maxAttempts = 60, int $windowSeconds = 60)
    {
        $this->maxAttempts   = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
    }

    public function handle(Request $request, callable $next): void
    {
        $ip     = $request->ip();
        $action = $request->getUri();
        $db     = Database::getInstance();
        $now    = time();

        // Vérifier si bloqué
        $row = $db->fetch(
            'SELECT attempts, window_start, blocked_until FROM rate_limits WHERE ip_address = ? AND action = ? LIMIT 1',
            [$ip, $action]
        );

        if ($row) {
            if ($row['blocked_until'] && $now < (int)$row['blocked_until']) {
                $retry = (int)$row['blocked_until'] - $now;
                header('Retry-After: ' . $retry);
                Response::json(['error' => 'Trop de requêtes. Réessayez dans ' . $retry . ' secondes.'], 429);
            }

            $windowStart = (int)$row['window_start'];
            if ($now - $windowStart < $this->windowSeconds) {
                $attempts = (int)$row['attempts'] + 1;
                if ($attempts >= $this->maxAttempts) {
                    $blockedUntil = $now + LOCKOUT_DURATION;
                    $db->execute(
                        'UPDATE rate_limits SET attempts = ?, blocked_until = ? WHERE ip_address = ? AND action = ?',
                        [$attempts, $blockedUntil, $ip, $action]
                    );
                    Logger::security('Rate limit atteint', ['ip' => $ip, 'action' => $action]);
                    header('Retry-After: ' . LOCKOUT_DURATION);
                    Response::json(['error' => 'Trop de requêtes. Compte temporairement bloqué.'], 429);
                }
                $db->execute(
                    'UPDATE rate_limits SET attempts = ? WHERE ip_address = ? AND action = ?',
                    [$attempts, $ip, $action]
                );
            } else {
                // Réinitialiser la fenêtre
                $db->execute(
                    'UPDATE rate_limits SET attempts = 1, window_start = ?, blocked_until = NULL WHERE ip_address = ? AND action = ?',
                    [$now, $ip, $action]
                );
            }
        } else {
            $db->execute(
                'INSERT INTO rate_limits (ip_address, action, attempts, window_start) VALUES (?, ?, 1, ?)',
                [$ip, $action, $now]
            );
        }

        $next();
    }
}

// ═══════════════════════════════════════════════════════════════════
// REDAC AUTH MIDDLEWARE (rédacteurs uniquement)
// ═══════════════════════════════════════════════════════════════════
class RedacAuthMiddleware
{
    private const REDAC_ROLES = ['admin', 'director', 'chief_editor', 'journalist', 'proofreader', 'moderator'];

    public function handle(Request $request, callable $next): void
    {
        $user = Session::get('user');
        if (!$user || !in_array($user['role'], self::REDAC_ROLES, true)) {
            Response::redirect(REDAC_URL . '/login');
        }
        $next();
    }
}
