<?php
declare(strict_types=1);

namespace Koonect\Helpers;

use Koonect\Core\Session;

class Csrf
{
    public static function getToken(): string
    {
        if (!Session::has(CSRF_TOKEN_NAME)) {
            Session::set(CSRF_TOKEN_NAME, bin2hex(random_bytes(32)));
        }
        return Session::get(CSRF_TOKEN_NAME);
    }

    public static function verify(string $token): bool
    {
        $stored = Session::get(CSRF_TOKEN_NAME, '');
        return hash_equals($stored, $token);
    }

    public static function field(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token, ENT_QUOTES) . '">';
    }

    public static function verifyRequest(): bool
    {
        $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return self::verify($token);
    }
}
