<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$statusLabels = ['pending'=>'En attente','approved'=>'Approuvé','rejected'=>'Rejeté','spam'=>'Spam'];
$statusColors = ['pending'=>'#f59e0b','approved'=>'#16a34a','rejected'=>'#dc2626','spam'=>'#6b7280'];
?>
<div style="max-width:800px;">
  <div style="border-bottom:2px solid #1A1A1A;padding-bottom:12px;margin-bottom:24px;">
    <h1 style="font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:700;">Mes commentaires</h1>
  </div>

  <?php if (empty($comments)): ?>
    <div style="padding:48px;text-align:center;color:#6b7280;background:#f9fafb;border-radius:8px;">
      <p>Vous n'avez pas encore posté de commentaire.</p>
    </div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:12px;">
    <?php foreach ($comments as $c): ?>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;gap:12px;flex-wrap:wrap;">
        <div>
          <a href="<?= APP_URL ?>/article/<?= $e($c['article_slug']) ?>"
             style="font-weight:600;font-size:.88rem;color:#1A1A1A;">
            <?= $e($c['article_title']) ?> →
          </a>
          <div style="font-family:'Inter',sans-serif;font-size:.72rem;color:#9ca3af;margin-top:2px;">
            <time datetime="<?= $e($c['created_at']) ?>"><?= date('d/m/Y à H\hi', strtotime($c['created_at'])) ?></time>
          </div>
        </div>
        <span style="padding:3px 10px;border-radius:2px;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;background:<?= $e($statusColors[$c['status']] ?? '#6b7280') ?>22;color:<?= $e($statusColors[$c['status']] ?? '#6b7280') ?>;">
          <?= $e($statusLabels[$c['status']] ?? $c['status']) ?>
        </span>
      </div>
      <p style="font-size:.9rem;color:#374151;line-height:1.6;"><?= nl2br($e($c['content'])) ?></p>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
