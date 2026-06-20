<?php
declare(strict_types=1);

namespace Koonect\Controllers\Redac;

use Koonect\Core\{Request, Response, Session, View};
use Koonect\Models\Media as MediaModel;
use Koonect\Services\ImageService;

class MediaController
{
    private MediaModel $model;

    public function __construct()
    {
        $this->model = new MediaModel();
    }

    public function index(Request $request): void
    {
        $page     = max(1, (int)$request->get('page', 1));
        $search   = trim((string)$request->get('q', ''));
        $folderId = $request->get('folder') ? (int)$request->get('folder') : null;
        $offset   = ($page - 1) * 40;

        $medias  = $this->model->getAll(40, $offset, $folderId, $search);

        if ($request->isAjax()) {
            Response::json(['medias' => $medias]);
        }

        $folders = \Koonect\Core\Database::getInstance()->fetchAll('SELECT * FROM media_folders ORDER BY name');

        View::render('redac/media/library', [
            'medias'   => $medias,
            'folders'  => $folders,
            'page'     => $page,
            'search'   => $search,
            'folderId' => $folderId,
        ], 'redac');
    }

    public function upload(Request $request): void
    {
        $user = Session::get('user');

        if (empty($_FILES['files'])) {
            Response::json(['error' => 'Aucun fichier reçu.'], 400);
        }

        $files    = $this->normalizeFilesArray($_FILES['files']);
        $uploaded = [];
        $errors   = [];
        $folderId = $request->post('folder_id') ? (int)$request->post('folder_id') : null;

        foreach ($files as $file) {
            try {
                ImageService::validateUpload($file);
                $tempOrigPath = $this->createTempOriginalPath();

                if (!move_uploaded_file($file['tmp_name'], $tempOrigPath)) {
                    throw new \RuntimeException('Déplacement du fichier échoué.');
                }

                $uploaded[] = $this->storeImageFromTempOriginal($tempOrigPath, $file['name'], (int)$user['id'], $folderId);
            } catch (\Throwable $e) {
                $errors[] = $file['name'] . ' : ' . $e->getMessage();
                if (!empty($tempOrigPath) && is_file($tempOrigPath)) {
                    @unlink($tempOrigPath);
                }
            }
        }

        Response::json([
            'success'  => $uploaded,
            'errors'   => $errors,
        ]);
    }

    public function importFromUrl(Request $request): void
    {
        $user      = Session::get('user');
        $url       = trim((string)$request->post('url', ''));
        $folderId  = $request->post('folder_id') ? (int)$request->post('folder_id') : null;

        if ($url === '') {
            Response::json(['error' => 'URL manquante.'], 422);
        }

        try {
            $media = $this->addRemoteImageLink($url, (int)$user['id'], $folderId);
            Response::json([
                'success' => [$media],
                'errors'  => [],
            ]);
        } catch (\Throwable $e) {
            Response::json([
                'success' => [],
                'errors'  => [$e->getMessage()],
            ], 422);
        }
    }

    private function addRemoteImageLink(string $url, int $uploadedBy, ?int $folderId = null): array
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('URL invalide.');
        }

        $parts  = parse_url($url);
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \RuntimeException('Seuls les liens HTTP ou HTTPS sont autorisés.');
        }

        $originalName = $this->resolveOriginalNameFromUrl($url);

        $id = $this->model->create([
            'filename'      => $originalName,
            'original_name' => mb_strimwidth($originalName, 0, 255, '', 'UTF-8'),
            'path'          => $url,
            'webp_path'     => $url,
            'thumb_path'    => $url,
            'mime_type'     => 'image/jpeg',
            'size'          => 0,
            'width'         => 0,
            'height'        => 0,
            'uploaded_by'   => $uploadedBy,
            'folder_id'     => $folderId,
        ]);

        return [
            'id'        => $id,
            'path'      => $url,
            'webp_path' => $url,
            'thumb'     => $url,
            'width'     => 0,
            'height'    => 0,
            'name'      => $originalName,
        ];
    }


    public function update(Request $request): void
    {
        $id      = (int)$request->param('id');
        $altText = trim((string)$request->post('alt_text', ''));
        $caption = trim((string)$request->post('caption', ''));
        $credit  = trim((string)$request->post('credit', ''));

        \Koonect\Core\Database::getInstance()->execute(
            'UPDATE media SET alt_text = ?, caption = ?, credit = ? WHERE id = ?',
            [$altText, $caption, $credit, $id]
        );

        Response::json(['success' => true]);
    }

    public function destroy(Request $request): void
    {
        $user = Session::get('user');
        if (!in_array($user['role'], ['admin', 'director', 'chief_editor'])) {
            Response::json(['error' => 'Non autorisé.'], 403);
        }

        $id = (int)$request->param('id');
        if ($this->model->delete($id)) {
            Response::json(['success' => true]);
        } else {
            Response::json(['error' => 'Média introuvable.'], 404);
        }
    }

    public function folders(Request $request): void
    {
        $folders = \Koonect\Core\Database::getInstance()->fetchAll('SELECT * FROM media_folders ORDER BY name');
        Response::json(['folders' => $folders]);
    }

    public function createFolder(Request $request): void
    {
        $name     = trim((string)$request->post('name', ''));
        $parentId = $request->post('parent_id') ? (int)$request->post('parent_id') : null;

        if (strlen($name) < 2) {
            Response::json(['error' => 'Nom de dossier invalide.'], 422);
        }

        $id = \Koonect\Core\Database::getInstance()->insert(
            'INSERT INTO media_folders (name, parent_id, created_at) VALUES (?, ?, NOW())',
            [$name, $parentId]
        );

        Response::json(['success' => true, 'id' => $id, 'name' => $name]);
    }

    private function importRemoteImage(string $url, int $uploadedBy, ?int $folderId = null): array
    {
        $this->assertImportUrlAllowed($url);
        $tempOrigPath = $this->createTempOriginalPath();

        try {
            $this->downloadRemoteFile($url, $tempOrigPath);
            $originalName = $this->resolveOriginalNameFromUrl($url);
            return $this->storeImageFromTempOriginal($tempOrigPath, $originalName, $uploadedBy, $folderId);
        } catch (\Throwable $e) {
            if (is_file($tempOrigPath)) {
                @unlink($tempOrigPath);
            }
            throw $e;
        }
    }

    private function storeImageFromTempOriginal(string $tempOrigPath, string $originalName, int $uploadedBy, ?int $folderId = null): array
    {
        $size = @filesize($tempOrigPath);
        if ($size === false) {
            throw new \RuntimeException('Impossible de lire le fichier image.');
        }

        ImageService::validateUpload([
            'error'    => UPLOAD_ERR_OK,
            'size'     => $size,
            'tmp_name' => $tempOrigPath,
            'name'     => $originalName,
            'type'     => '',
        ]);

        $destDir = dirname($tempOrigPath);

        try {
            $result = ImageService::process($tempOrigPath, $destDir);

            $relativePath = $this->toPublicUploadPath($result['path']);
            $webpPath     = $this->toPublicUploadPath($result['webp_path']);
            $thumbPath    = $this->toPublicUploadPath($result['thumb_path']);

            $id = $this->model->create([
                'filename'      => basename($result['path']),
                'original_name' => mb_strimwidth($originalName, 0, 255, '', 'UTF-8'),
                'path'          => $relativePath,
                'webp_path'     => $webpPath,
                'thumb_path'    => $thumbPath,
                'mime_type'     => 'image/jpeg',
                'size'          => $result['size'],
                'width'         => $result['width'],
                'height'        => $result['height'],
                'uploaded_by'   => $uploadedBy,
                'folder_id'     => $folderId,
            ]);

            return [
                'id'        => $id,
                'path'      => APP_URL . '/' . $relativePath,
                'webp_path' => APP_URL . '/' . $webpPath,
                'thumb'     => APP_URL . '/' . $thumbPath,
                'width'     => $result['width'],
                'height'    => $result['height'],
                'name'      => $originalName,
            ];
        } finally {
            @unlink($tempOrigPath);
        }
    }

    private function createTempOriginalPath(): string
    {
        $date    = date('Y/m');
        $destDir = UPLOAD_PATH . '/articles/' . $date;
        $this->ensureDirectory($destDir);

        $filename = uniqid('media_', true) . '_' . time();
        return $destDir . '/' . $filename . '_orig';
    }

    private function ensureDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            if (!is_writable($dir)) {
                throw new \RuntimeException('Le dossier de destination n\'est pas accessible en écriture.');
            }
            return;
        }

        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Impossible de créer le dossier de destination.');
        }
    }

    private function toPublicUploadPath(string $absolutePath): string
    {
        $base = rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR;
        $path = str_replace($base, '', $absolutePath);
        $path = str_replace(['\\', '/'], '/', $path);
        return 'uploads/' . ltrim($path, '/');
    }

    private function resolveOriginalNameFromUrl(string $url): string
    {
        $path = (string)(parse_url($url, PHP_URL_PATH) ?? '');
        $name = rawurldecode(basename($path));

        if ($name === '' || $name === '/' || $name === '.') {
            $name = 'image-importee.jpg';
        }

        if (!preg_match('/\.[a-z0-9]{2,5}$/i', $name)) {
            $name .= '.jpg';
        }

        return $name;
    }

    private function assertImportUrlAllowed(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('URL invalide.');
        }

        $parts  = parse_url($url);
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host   = strtolower((string)($parts['host'] ?? ''));

        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \RuntimeException('Seuls les liens HTTP ou HTTPS sont autorisés.');
        }

        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.local')) {
            throw new \RuntimeException('Hôte non autorisé.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (!$this->isPublicIp($host)) {
                throw new \RuntimeException('Adresse IP non autorisée.');
            }
            return;
        }

        $ips = [];
        if (function_exists('dns_get_record')) {
            foreach ([DNS_A, DNS_AAAA] as $type) {
                $records = @dns_get_record($host, $type);
                if (!is_array($records)) {
                    continue;
                }

                foreach ($records as $record) {
                    if (!empty($record['ip'])) {
                        $ips[] = $record['ip'];
                    }
                    if (!empty($record['ipv6'])) {
                        $ips[] = $record['ipv6'];
                    }
                }
            }
        }

        if (empty($ips)) {
            $ipv4 = @gethostbyname($host);
            if ($ipv4 && $ipv4 !== $host) {
                $ips[] = $ipv4;
            }
        }

        if (empty($ips)) {
            throw new \RuntimeException('Impossible de résoudre l\'hôte distant.');
        }

        foreach (array_unique($ips) as $ip) {
            if (!$this->isPublicIp($ip)) {
                throw new \RuntimeException('Le lien pointe vers une adresse non autorisée.');
            }
        }
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    private function downloadRemoteFile(string $url, string $destinationPath): void
    {
        $context = stream_context_create([
            'http' => [
                'method'          => 'GET',
                'header'          => implode("\r\n", [
                    'User-Agent: Koonect Media Importer/1.0',
                    'Accept: image/*',
                    'Connection: close',
                ]) . "\r\n",
                'timeout'         => 15,
                'follow_location' => 0,
                'max_redirects'   => 0,
                'ignore_errors'    => true,
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
                'allow_self_signed'=> false,
            ],
        ]);

        $remote = @fopen($url, 'rb', false, $context);
        if (!$remote) {
            throw new \RuntimeException('Impossible de télécharger l\'image distante.');
        }

        $meta    = stream_get_meta_data($remote);
        $headers  = is_array($meta['wrapper_data'] ?? null) ? $meta['wrapper_data'] : [];
        $status   = $this->extractHttpStatus($headers);
        $length   = $this->extractHeaderValue($headers, 'Content-Length');

        if ($status !== 200) {
            fclose($remote);
            throw new \RuntimeException('Le serveur distant a répondu avec le code HTTP ' . $status . '.');
        }

        if ($length !== null && (int)$length > UPLOAD_MAX_SIZE) {
            fclose($remote);
            throw new \RuntimeException('Image distante trop volumineuse (max ' . (UPLOAD_MAX_SIZE / 1048576) . ' Mo).');
        }

        $dest = fopen($destinationPath, 'wb');
        if (!$dest) {
            fclose($remote);
            throw new \RuntimeException('Impossible de créer le fichier temporaire.');
        }

        $bytes = 0;
        while (!feof($remote)) {
            $chunk = fread($remote, 8192);
            if ($chunk === false) {
                fclose($remote);
                fclose($dest);
                @unlink($destinationPath);
                throw new \RuntimeException('Erreur lors du téléchargement de l\'image.');
            }

            $bytes += strlen($chunk);
            if ($bytes > UPLOAD_MAX_SIZE) {
                fclose($remote);
                fclose($dest);
                @unlink($destinationPath);
                throw new \RuntimeException('Image distante trop volumineuse (max ' . (UPLOAD_MAX_SIZE / 1048576) . ' Mo).');
            }

            fwrite($dest, $chunk);
        }

        fclose($remote);
        fclose($dest);
    }

    private function extractHttpStatus(array $headers): int
    {
        $status = 0;
        foreach ($headers as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#i', (string)$line, $matches)) {
                $status = (int)$matches[1];
            }
        }
        return $status ?: 500;
    }

    private function extractHeaderValue(array $headers, string $name): ?string
    {
        foreach ($headers as $line) {
            if (stripos((string)$line, $name . ':') === 0) {
                return trim(substr((string)$line, strlen($name) + 1));
            }
        }
        return null;
    }

    private function normalizeFilesArray(array $files): array
    {
        $result = [];
        if (is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                $result[] = [
                    'name'     => $files['name'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'size'     => $files['size'][$i],
                    'error'    => $files['error'][$i],
                    'type'     => $files['type'][$i],
                ];
            }
        } else {
            $result[] = $files;
        }
        return $result;
    }
}
