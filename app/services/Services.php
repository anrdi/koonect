<?php
declare(strict_types=1);

namespace Koonect\Services;

// ═══════════════════════════════════════════════════════════════════
// CACHE SERVICE (file-based)
// ═══════════════════════════════════════════════════════════════════
class CacheService
{
    private static ?string $basePath = null;

    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        $file = self::path($key);

        $data = self::read($file);
        if ($data !== null && ($data['expires'] ?? 0) > time()) {
            return $data['value'];
        }

        $value = $callback();
        self::put($key, $value, $ttl);
        return $value;
    }

    public static function put(string $key, mixed $value, int $ttl = CACHE_TTL): void
    {
        $file = self::path($key);
        $dir  = dirname($file);
        if (!self::ensureDirectory($dir)) {
            return;
        }

        @file_put_contents($file, serialize([
            'key'     => $key,
            'expires' => time() + $ttl,
            'value'   => $value,
        ]), LOCK_EX);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $file = self::path($key);
        $data = self::read($file);
        if ($data === null) return $default;

        if (($data['expires'] ?? 0) <= time()) {
            @unlink($file);
            return $default;
        }
        return $data['value'] ?? $default;
    }

    public static function forget(string $key): void
    {
        @unlink(self::path($key));
    }

    public static function flush(string $prefix = ''): void
    {
        $dir = self::pagesPath();
        if (!is_dir($dir)) return;

        $safePrefix = $prefix !== '' ? self::safeFilenamePart($prefix) : '';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if (!$item->isFile() || $item->getExtension() !== 'cache') {
                continue;
            }

            if ($safePrefix === '' || str_starts_with($item->getFilename(), $safePrefix)) {
                @unlink($item->getPathname());
            }
        }
    }

    private static function path(string $key): string
    {
        $hash = md5($key);
        return self::pagesPath() . '/' . substr($hash, 0, 2) . '/' . self::safeFilenamePart($key) . '-' . $hash . '.cache';
    }

    private static function pagesPath(): string
    {
        return self::basePath() . '/pages';
    }

    private static function basePath(): string
    {
        if (self::$basePath !== null) {
            return self::$basePath;
        }

        $candidates = [
            rtrim(CACHE_PATH, '/\\'),
            rtrim(sys_get_temp_dir(), '/\\') . '/koonect-cache',
        ];

        foreach ($candidates as $candidate) {
            if (self::ensureDirectory($candidate . '/pages')) {
                self::$basePath = $candidate;
                return self::$basePath;
            }
        }

        self::$basePath = $candidates[0];
        return self::$basePath;
    }

    private static function ensureDirectory(string $dir): bool
    {
        if (is_dir($dir)) {
            return is_writable($dir);
        }

        return @mkdir($dir, 0755, true) || is_dir($dir);
    }

    private static function read(string $file): ?array
    {
        if (!is_file($file) || !is_readable($file)) {
            return null;
        }

        $raw = @file_get_contents($file);
        if ($raw === false || $raw === '') {
            return null;
        }

        $data = @unserialize($raw, ['allowed_classes' => false]);
        if (!is_array($data) || !isset($data['expires']) || !array_key_exists('value', $data)) {
            return null;
        }

        return $data;
    }

    private static function safeFilenamePart(string $value): string
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '_', $value);
        return $safe !== '' ? $safe : 'cache';
    }
}

// ═══════════════════════════════════════════════════════════════════
// IMAGE SERVICE — compression + WebP + thumbnails
// ═══════════════════════════════════════════════════════════════════
class ImageService
{
    private const THUMB_WIDTH  = 400;
    private const THUMB_HEIGHT = 267;
    private const QUALITY_JPEG = 82;
    private const QUALITY_WEBP = 80;
    private const QUALITY_PNG  = 8;

    public static function process(string $sourcePath, string $destDir): array
    {
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);

        $info     = getimagesize($sourcePath);
        if (!$info) throw new \RuntimeException('Fichier image invalide.');

        $mime     = $info['mime'];
        $image    = self::createFromMime($sourcePath, $mime);
        $filename = pathinfo($sourcePath, PATHINFO_FILENAME);

        // Image principale optimisée
        $mainPath = $destDir . '/' . $filename . '.jpg';
        self::saveAsJpeg($image, $mainPath, $info[0], $info[1]);

        // Version WebP
        $webpPath = $destDir . '/' . $filename . '.webp';
        self::saveAsWebp($image, $webpPath, $info[0], $info[1]);

        // Thumbnail
        $thumbPath = $destDir . '/' . $filename . '_thumb.webp';
        self::saveThumbnail($image, $thumbPath, $info[0], $info[1]);

        imagedestroy($image);

        return [
            'path'       => $mainPath,
            'webp_path'  => $webpPath,
            'thumb_path' => $thumbPath,
            'width'      => $info[0],
            'height'     => $info[1],
            'size'       => filesize($mainPath),
        ];
    }

    private static function createFromMime(string $path, string $mime): \GdImage
    {
        return match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png'  => imagecreatefrompng($path),
            'image/gif'  => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default      => throw new \RuntimeException("Type MIME non supporté : $mime"),
        };
    }

    private static function saveAsJpeg(\GdImage $img, string $dest, int $w, int $h): void
    {
        $resized = self::resize($img, $w, $h, 1920, 1080);
        imagejpeg($resized, $dest, self::QUALITY_JPEG);
        if ($resized !== $img) imagedestroy($resized);
    }

    private static function saveAsWebp(\GdImage $img, string $dest, int $w, int $h): void
    {
        $resized = self::resize($img, $w, $h, 1920, 1080);
        imagewebp($resized, $dest, self::QUALITY_WEBP);
        if ($resized !== $img) imagedestroy($resized);
    }

    private static function saveThumbnail(\GdImage $img, string $dest, int $w, int $h): void
    {
        $thumb = imagecreatetruecolor(self::THUMB_WIDTH, self::THUMB_HEIGHT);

        // Crop centré (cover)
        $srcRatio  = $w / $h;
        $destRatio = self::THUMB_WIDTH / self::THUMB_HEIGHT;

        if ($srcRatio > $destRatio) {
            $srcH  = $h;
            $srcW  = (int)($h * $destRatio);
            $srcX  = (int)(($w - $srcW) / 2);
            $srcY  = 0;
        } else {
            $srcW  = $w;
            $srcH  = (int)($w / $destRatio);
            $srcX  = 0;
            $srcY  = (int)(($h - $srcH) / 2);
        }

        imagecopyresampled($thumb, $img, 0, 0, $srcX, $srcY, self::THUMB_WIDTH, self::THUMB_HEIGHT, $srcW, $srcH);
        imagewebp($thumb, $dest, self::QUALITY_WEBP);
        imagedestroy($thumb);
    }

    private static function resize(\GdImage $img, int $srcW, int $srcH, int $maxW, int $maxH): \GdImage
    {
        if ($srcW <= $maxW && $srcH <= $maxH) return $img;

        $ratio  = min($maxW / $srcW, $maxH / $srcH);
        $newW   = (int)($srcW * $ratio);
        $newH   = (int)($srcH * $ratio);

        $resized = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
        return $resized;
    }

    public static function validateUpload(array $file): void
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Erreur lors de l\'upload (code ' . $file['error'] . ').');
        }
        if ($file['size'] > UPLOAD_MAX_SIZE) {
            throw new \RuntimeException('Fichier trop volumineux (max ' . (UPLOAD_MAX_SIZE / 1048576) . ' Mo).');
        }

        // Vérification MIME réelle (pas MIME spoofing)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
            throw new \RuntimeException("Type de fichier non autorisé : $mime");
        }

        // Vérifier que c'est vraiment une image
        if (!getimagesize($file['tmp_name'])) {
            throw new \RuntimeException('Le fichier n\'est pas une image valide.');
        }
    }
}
