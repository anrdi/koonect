<?php
/* home/static-page.php */
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
?>
<div class="container" style="padding-top:40px;padding-bottom:80px;max-width:860px;">
  <?php if ($page): ?>
    <h1 style="font-family:'Playfair Display',serif;font-size:2rem;font-weight:900;margin-bottom:32px;padding-bottom:16px;border-bottom:3px solid #1A1A1A;">
      <?= $e($page['title']) ?>
    </h1>
    <div class="rich-content" style="font-family:'Source Serif 4',Georgia,serif;font-size:1.05rem;line-height:1.85;">
      <?= $page['content'] ?>
    </div>
  <?php else: ?>
    <p style="color:#6b7280;">Cette page est en cours de rédaction.</p>
  <?php endif; ?>
</div>
