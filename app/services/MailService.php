<?php
declare(strict_types=1);

namespace Koonect\Services;

use Koonect\Core\Logger;

// ═══════════════════════════════════════════════════════════════════
// MAIL SERVICE — SMTP via PHPMailer
// ═══════════════════════════════════════════════════════════════════
class MailService
{
    /**
     * Envoie un email transactionnel via SMTP.
     */
    public static function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody = '',
        array $customHeaders = []
    ): bool {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->AuthType   = 'LOGIN';
            $mail->addReplyTo('contact@koonect.fr', SMTP_FROM_NAME);
            if (SMTP_PORT === 465) {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 5;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ]
            ];

            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($toEmail, $toName);

            // Appliquer les en-têtes personnalisés
            foreach ($customHeaders as $name => $value) {
                $mail->addCustomHeader($name, $value);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = self::wrapTemplate($htmlBody, $subject);
            $mail->AltBody = $textBody ?: strip_tags($htmlBody);

            $mail->send();

            Logger::info('Email envoyé', ['to' => $toEmail, 'subject' => $subject]);
            return true;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            Logger::error('Erreur SMTP', ['error' => $e->getMessage(), 'to' => $toEmail]);
            return false;
        }
    }

    /**
     * Template HTML email de base (compatible webmail).
     */
    private static function wrapTemplate(string $content, string $subject): string
    {
        $name    = htmlspecialchars(APP_NAME, ENT_QUOTES);
        $url     = htmlspecialchars(APP_URL, ENT_QUOTES);
        $year    = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$subject}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Georgia,serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:40px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:4px;overflow:hidden;">
      <tr>
        <td style="background:#1A1A1A;padding:24px 40px;">
          <a href="{$url}" style="color:#C8102E;font-size:24px;font-weight:bold;text-decoration:none;">{$name}</a>
        </td>
      </tr>
      <tr>
        <td style="padding:40px;color:#1A1A1A;font-size:16px;line-height:1.7;">
          {$content}
        </td>
      </tr>
      <tr>
        <td style="background:#f9f9f9;padding:20px 40px;font-size:13px;color:#999;border-top:1px solid #eee;">
          © {$year} {$name} · <a href="{$url}/mentions-legales" style="color:#999;">Mentions légales</a>
          · <a href="{$url}/rgpd" style="color:#999;">RGPD</a>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }

    // ── Emails transactionnels ────────────────────────────────────

    public static function sendEmailVerification(string $toEmail, string $toName, string $token): bool
    {
        $link = PORTAL_URL . '/verifier-email?token=' . urlencode($token);
        $html = <<<HTML
<h2 style="font-size:22px;margin:0 0 20px;">Confirmez votre adresse email</h2>
<p>Bonjour {$toName},</p>
<p>Merci de vous être inscrit sur <strong>Koonect</strong>. Pour finaliser votre inscription, veuillez confirmer votre adresse email en cliquant sur le bouton ci-dessous.</p>
<p style="text-align:center;margin:32px 0;">
  <a href="{$link}" style="background:#C8102E;color:#fff;padding:14px 32px;border-radius:3px;text-decoration:none;font-size:16px;">Confirmer mon email</a>
</p>
<p style="font-size:14px;color:#666;">Ce lien expire dans 24 heures. Si vous n'avez pas créé de compte, ignorez cet email.</p>
HTML;
        return self::send($toEmail, $toName, 'Confirmez votre adresse email — ' . APP_NAME, $html);
    }

    public static function sendPasswordReset(string $toEmail, string $toName, string $token): bool
    {
        $link = PORTAL_URL . '/reinitialiser-mot-de-passe?token=' . urlencode($token);
        $html = <<<HTML
<h2 style="font-size:22px;margin:0 0 20px;">Réinitialisation de mot de passe</h2>
<p>Bonjour {$toName},</p>
<p>Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe.</p>
<p style="text-align:center;margin:32px 0;">
  <a href="{$link}" style="background:#0A3D6B;color:#fff;padding:14px 32px;border-radius:3px;text-decoration:none;font-size:16px;">Réinitialiser mon mot de passe</a>
</p>
<p style="font-size:14px;color:#666;">Ce lien expire dans 1 heure. Si vous n'avez pas fait cette demande, ignorez cet email.</p>
HTML;
        return self::send($toEmail, $toName, 'Réinitialisation de votre mot de passe — ' . APP_NAME, $html);
    }

    public static function sendNewsletterConfirmation(string $toEmail, string $token): bool
    {
        $link = APP_URL . '/newsletter/confirmer?token=' . urlencode($token);
        $unsubLink = APP_URL . '/newsletter/desabonnement?token=' . urlencode($token);
        $html = <<<HTML
<h2 style="font-size:22px;margin:0 0 20px;">Confirmez votre inscription à la newsletter</h2>
<p>Vous avez demandé à recevoir la newsletter de <strong>Koonect</strong>.</p>
<p>Pour confirmer votre inscription, cliquez sur le bouton ci-dessous :</p>
<p style="text-align:center;margin:32px 0;">
  <a href="{$link}" style="background:#C8102E;color:#fff;padding:14px 32px;border-radius:3px;text-decoration:none;font-size:16px;">Confirmer mon inscription</a>
</p>
<p style="font-size:13px;color:#999;margin-top:32px;">
  Si vous ne souhaitez plus recevoir ces emails : <a href="{$unsubLink}" style="color:#999;">Se désabonner</a>
</p>
HTML;
        $headers = [
            'List-Unsubscribe' => '<' . $unsubLink . '>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ];
        return self::send($toEmail, 'Abonné', 'Confirmez votre inscription — ' . APP_NAME, $html, '', $headers);
    }
}

// ═══════════════════════════════════════════════════════════════════
// TWO FACTOR SERVICE — TOTP RFC 6238
// ═══════════════════════════════════════════════════════════════════
class TwoFactorService
{
    private const CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32

    public static function generateSecret(): string
    {
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= self::CHARS[random_int(0, 31)];
        }
        return $secret;
    }

    public static function getQrCodeUrl(string $email, string $secret): string
    {
        $label  = rawurlencode(TOTP_ISSUER . ':' . $email);
        $params = http_build_query([
            'secret'    => $secret,
            'issuer'    => TOTP_ISSUER,
            'algorithm' => 'SHA1',
            'digits'    => TOTP_DIGITS,
            'period'    => TOTP_PERIOD,
        ]);
        $otpauth = rawurlencode("otpauth://totp/{$label}?{$params}");
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={$otpauth}";
    }

    public static function verifyCode(string $secret, string $code): bool
    {
        $code = preg_replace('/\s/', '', $code);
        if (!preg_match('/^\d{6}$/', $code)) return false;

        $timestamp = (int)(time() / TOTP_PERIOD);

        // Tolérance ±1 période
        for ($i = -1; $i <= 1; $i++) {
            $expected = self::generateCode($secret, $timestamp + $i);
            if (hash_equals($expected, $code)) return true;
        }
        return false;
    }

    private static function generateCode(string $secret, int $timestamp): string
    {
        $key     = self::base32Decode($secret);
        $message = pack('N*', 0) . pack('N*', $timestamp);
        $hash    = hash_hmac('sha1', $message, $key, true);
        $offset  = ord($hash[19]) & 0xF;
        $otp     = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8)  |
            ((ord($hash[$offset + 3]) & 0xFF))
        ) % (10 ** TOTP_DIGITS);

        return str_pad((string)$otp, TOTP_DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Decode(string $input): string
    {
        $input   = strtoupper($input);
        $charMap = array_flip(str_split(self::CHARS));
        $binary  = '';

        foreach (str_split($input) as $char) {
            if (!isset($charMap[$char])) continue;
            $binary .= str_pad(decbin($charMap[$char]), 5, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($binary, 8) as $byte) {
            if (strlen($byte) === 8) {
                $output .= chr(bindec($byte));
            }
        }
        return $output;
    }
}

// ═══════════════════════════════════════════════════════════════════
// SSO SERVICE — cross-sous-domaines
// ═══════════════════════════════════════════════════════════════════
class SsoService
{
    private const COOKIE_NAME = 'KOONECT_SSO';

    public static function createToken(int $userId): string
    {
        $payload = json_encode(['uid' => $userId, 'ts' => time()]);
        $sig     = hash_hmac('sha256', (string)$payload, APP_KEY);
        return base64_encode($payload . '|' . $sig);
    }

    public static function setTokenCookie(string $token): void
    {
        setcookie(self::COOKIE_NAME, $token, [
            'expires'  => time() + SESSION_LIFETIME,
            'path'     => '/',
            'domain'   => SESSION_DOMAIN,
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function verify(): ?array
    {
        $raw = $_COOKIE[self::COOKIE_NAME] ?? null;
        if (!$raw) return null;

        $decoded = base64_decode($raw, true);
        if (!$decoded) return null;

        $parts = explode('|', $decoded, 2);
        if (count($parts) !== 2) return null;

        [$payload, $sig] = $parts;
        $expected = hash_hmac('sha256', $payload, APP_KEY);

        if (!hash_equals($expected, $sig)) {
            Logger::security('SSO token signature invalide');
            return null;
        }

        $data = json_decode($payload, true);
        if (!isset($data['uid'], $data['ts'])) return null;

        // Token expiré
        if (time() - $data['ts'] > SESSION_LIFETIME) return null;

        return $data;
    }

    public static function clearToken(): void
    {
        setcookie(self::COOKIE_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'domain'   => SESSION_DOMAIN,
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
