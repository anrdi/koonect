<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$commentCount = count($comments ?? []);
?>

<div class="rdash-page-header">
  <div>
    <h1 class="rdash-page-title">Modération des commentaires</h1>
    <p style="color:#6b7280;font-size:.9rem;margin-top:4px;">Modérez les commentaires publiés sur le site.</p>
  </div>
  <div class="rdash-kpi"><?= number_format($commentCount, 0, ',', ' ') ?></div>
</div>

<?php if (empty($comments)): ?>
  <div class="rdash-empty">Aucun commentaire disponible pour le moment.</div>
<?php else: ?>
  <div class="rdash-comment-list">
    <?php foreach ($comments as $comment): ?>
      <?php
        $excerpt = mb_strimwidth(trim((string)($comment['content'] ?? '')), 0, 500, '…', 'UTF-8');
      ?>
      <article class="rdash-comment-card">
        <div class="rdash-comment-head">
          <div>
            <a href="<?= APP_URL ?>/article/<?= $e($comment['article_slug']) ?>" target="_blank" class="rdash-comment-article">
              <?= $e($comment['article_title']) ?>
            </a>
            <div class="rdash-comment-meta">
              Par <?= $e($comment['author_name'] ?? 'Utilisateur') ?> ·
              <?= date('d/m/Y à H\hi', strtotime($comment['created_at'])) ?>
              <?php if (!empty($comment['parent_id'])): ?>
                · réponse à un commentaire
              <?php endif; ?>
            </div>
          </div>
          <?php if ($comment['status'] === 'approved'): ?>
            <span class="rdash-comment-status rdash-comment-status--approved">En ligne</span>
          <?php elseif ($comment['status'] === 'rejected'): ?>
            <span class="rdash-comment-status rdash-comment-status--rejected">Rejeté</span>
          <?php else: ?>
            <span class="rdash-comment-status">En attente</span>
          <?php endif; ?>
        </div>

        <div class="rdash-comment-content"><?= nl2br($e($excerpt)) ?></div>

        <div class="rdash-comment-actions">
          <?php if ($comment['status'] !== 'approved'): ?>
            <form method="post" action="<?= REDAC_URL ?>/commentaires/<?= (int)$comment['id'] ?>/approuver">
              <?= \Koonect\Helpers\Csrf::field() ?>
              <button type="submit" class="btn btn--primary btn--sm">Approuver</button>
            </form>
          <?php endif; ?>
          <?php if ($comment['status'] !== 'rejected'): ?>
            <form method="post" action="<?= REDAC_URL ?>/commentaires/<?= (int)$comment['id'] ?>/rejeter">
              <?= \Koonect\Helpers\Csrf::field() ?>
              <button type="submit" class="btn btn--outline btn--sm" onclick="return confirm('Rejeter ce commentaire ?')">Rejeter</button>
            </form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<style nonce="<?= $e($cspNonce) ?>">
.rdash-page-header { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:20px; }
.rdash-page-title { font-size:1.3rem; font-weight:700; color:#1A1A1A; }
.rdash-kpi { min-width:64px; padding:10px 14px; border-radius:8px; background:#fff; border:1px solid #e5e7eb; font-family:'Playfair Display',serif; font-size:1.5rem; font-weight:900; color:#C8102E; text-align:center; }
.rdash-comment-list { display:flex; flex-direction:column; gap:12px; }
.rdash-comment-card { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:16px; }
.rdash-comment-head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:12px; }
.rdash-comment-article { display:inline-block; font-weight:700; color:#1A1A1A; text-decoration:none; margin-bottom:4px; }
.rdash-comment-article:hover { color:#C8102E; }
.rdash-comment-meta { font-size:.75rem; color:#9ca3af; font-family:'Inter',sans-serif; }
.rdash-comment-status { display:inline-block; padding:4px 10px; border-radius:2px; background:#fff7ed; color:#9a3412; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; }
.rdash-comment-status--approved { background:#f0fdf4; color:#16a34a; }
.rdash-comment-status--rejected { background:#fef2f2; color:#dc2626; }
.rdash-comment-content { font-size:.92rem; line-height:1.7; color:#374151; background:#f9fafb; border:1px solid #eef2f7; border-radius:6px; padding:14px; white-space:normal; }
.rdash-comment-actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
.rdash-comment-actions form { display:inline-flex; }
.rdash-empty { padding:40px; text-align:center; color:#9ca3af; font-size:.92rem; background:#fff; border:1px solid #e5e7eb; border-radius:8px; }
@media (max-width: 640px) {
  .rdash-page-header { flex-direction:column; }
  .rdash-comment-head { flex-direction:column; }
}
</style>
