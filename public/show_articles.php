<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
require ROOT_PATH . '/app/config/config.php';
require_once ROOT_PATH . '/app/core/Database.php';
require_once ROOT_PATH . '/app/core/Kernel.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $db = \Koonect\Core\Database::getInstance();
    $articles = $db->fetchAll('SELECT id, title, status, scheduled_at, published_at, is_featured, is_breaking FROM articles');
    
    foreach ($articles as $art) {
        $cleanTitle = preg_replace('/[^\x20-\x7E]/', '?', $art['title']);
        echo "ID: " . $art['id'] . "\n";
        echo "Title: " . $cleanTitle . "\n";
        echo "Status: " . $art['status'] . "\n";
        echo "Scheduled At: " . ($art['scheduled_at'] ?? 'NULL') . "\n";
        echo "Published At: " . ($art['published_at'] ?? 'NULL') . "\n";
        echo "Featured: " . $art['is_featured'] . "\n";
        echo "Breaking: " . $art['is_breaking'] . "\n";
        echo "========================================\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
