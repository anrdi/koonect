<?php
/** @var array $article @var array $tags @var array $gallery @var array $related @var array $comments */
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$imgUrl = fn($p) => $p ? (preg_match('#^https?://|^data:#i', $p) ? $p : APP_URL . '/' . ltrim($p, '/')) : '';
$currentUser = \Koonect\Core\Session::get('user');
?>

<div class="article-page">

  <!-- BREADCRUMB -->
  <nav class="breadcrumb container" aria-label="Fil d'ariane">
    <ol>
      <li><a href="<?= APP_URL ?>">Accueil</a></li>
      <?php if ($article['category_name']): ?>
        <li><a href="<?= APP_URL ?>/categorie/<?= $e($article['category_slug']) ?>"><?= $e($article['category_name']) ?></a></li>
      <?php endif; ?>
      <li aria-current="page"><?= $e(mb_strimwidth($article['title'], 0, 60, '…')) ?></li>
    </ol>
  </nav>

  <!-- EN-TÊTE ARTICLE -->
  <header class="article-header container">
    <?php if ($article['category_name']): ?>
      <a href="<?= APP_URL ?>/categorie/<?= $e($article['category_slug']) ?>" class="article-category article-category--lg"><?= $e($article['category_name']) ?></a>
    <?php endif; ?>
    <h1 class="article-title"><?= $e($article['title']) ?></h1>
    <?php if ($article['subtitle']): ?>
      <p class="article-subtitle"><?= $e($article['subtitle']) ?></p>
    <?php endif; ?>
    <div class="article-byline">
      <?php if ($article['author_avatar']): ?>
        <img src="<?= $e($imgUrl($article['author_avatar'])) ?>" alt="<?= $e($article['author_name']) ?>" class="author-avatar" width="40" height="40" loading="lazy">
      <?php endif; ?>
      <div class="byline-info">
        <a href="<?= APP_URL ?>/auteur/<?= $e($article['author_username']) ?>" class="byline-name">Par <?= $e($article['author_name']) ?></a>
        <div class="byline-meta">
          <time datetime="<?= $e($article['published_at'] ?? '') ?>">
            <?= $article['published_at'] ? date('d/m/Y à H\hi', strtotime($article['published_at'])) : 'Récemment' ?>
          </time>
          <?php if (!empty($article['updated_at']) && $article['updated_at'] !== $article['published_at']): ?>
            · <span>Mis à jour le <?= date('d/m/Y', strtotime($article['updated_at'])) ?></span>
          <?php endif; ?>
          <?php if ($article['reading_time']): ?>
            · <span><?= (int)$article['reading_time'] ?> min de lecture</span>
          <?php endif; ?>
        </div>
      </div>
      <!-- Partage réseaux sociaux -->
      <?php $shortUrl = 'https://clic.koonect.fr/' . ($article['short_code'] ?: $article['id']); ?>
      <div class="article-share" aria-label="Partager cet article">
        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($shortUrl) ?>&text=<?= urlencode($article['title']) ?>"
           class="share-btn share-btn--twitter" target="_blank" rel="noopener noreferrer" aria-label="Partager sur Twitter/X">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.742l7.776-8.902L1.254 2.25H8.08l4.261 5.633L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
        </a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($shortUrl) ?>"
           class="share-btn share-btn--linkedin" target="_blank" rel="noopener noreferrer" aria-label="Partager sur LinkedIn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
        </a>
        <button class="share-btn share-btn--copy" aria-label="Copier le lien" data-copy="<?= $e($shortUrl) ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
        </button>
      </div>
    </div>
  </header>

  <!-- IMAGE PRINCIPALE -->
  <?php if ($article['featured_image_webp'] || $article['featured_image_path']): ?>
  <figure class="article-hero-image">
    <picture>
      <source srcset="<?= $e($imgUrl($article['featured_image_webp'])) ?>" type="image/webp">
      <img src="<?= $e($imgUrl($article['featured_image_path'])) ?>"
           alt="<?= $e($article['featured_image_alt'] ?? $article['title']) ?>"
           width="1200" height="630" loading="eager">
    </picture>
    <?php if ($article['featured_image_caption'] || $article['featured_image_credit']): ?>
      <figcaption>
        <?php if ($article['featured_image_caption']): ?><span class="caption"><?= $e($article['featured_image_caption']) ?></span><?php endif; ?>
        <?php if ($article['featured_image_credit']): ?><span class="credit">© <?= $e($article['featured_image_credit']) ?></span><?php endif; ?>
      </figcaption>
    <?php endif; ?>
  </figure>
  <?php endif; ?>

  <!-- CONTENU -->
  <div class="article-body container">
    <div class="article-content-grid">

      <!-- Texte principal -->
      <div class="article-content">
        <!-- Chapô -->
        <?php if ($article['chapo']): ?>
          <div class="article-chapo"><?= $e($article['chapo']) ?></div>
        <?php endif; ?>

        <!-- Contenu riche -->
        <div class="article-text rich-content">
          <?= $article['content'] /* Sanitisé à l'enregistrement */ ?>
        </div>

        <!-- Galerie -->
        <?php if (!empty($gallery)): ?>
        <div class="article-gallery" aria-label="Galerie photos">
          <h2 class="gallery-title">Galerie photos</h2>
          <div class="gallery-grid">
            <?php foreach ($gallery as $i => $img): ?>
            <figure class="gallery-item">
              <button class="gallery-trigger" data-lightbox-index="<?= $i ?>" aria-label="Voir la photo <?= $i+1 ?>">
                <picture>
                  <source srcset="<?= $e($imgUrl($img['webp_path'])) ?>" type="image/webp">
                  <img src="<?= $e($imgUrl($img['thumb_path'])) ?>"
                       alt="<?= $e($img['alt_text'] ?? '') ?>"
                       width="300" height="200" loading="lazy">
                </picture>
              </button>
              <?php if ($img['gallery_caption'] || $img['caption']): ?>
                <figcaption><?= $e($img['gallery_caption'] ?: $img['caption']) ?></figcaption>
              <?php endif; ?>
            </figure>
            <?php endforeach; ?>
          </div>
        </div>
        <!-- Données lightbox (JSON pour JS) -->
        <script id="gallery-data" type="application/json">
          <?= json_encode(array_map(fn($img) => [
            'src'     => $imgUrl($img['webp_path'] ?: $img['path']),
            'alt'     => $img['alt_text'] ?? '',
            'caption' => $img['gallery_caption'] ?: $img['caption'],
            'credit'  => $img['credit'],
          ], $gallery), JSON_UNESCAPED_UNICODE) ?>
        </script>
        <?php endif; ?>

        <!-- Tags -->
        <?php if (!empty($tags)): ?>
        <div class="article-tags" aria-label="Mots-clés">
          <span class="tags-label">Tags :</span>
          <?php foreach ($tags as $tag): ?>
            <a href="<?= APP_URL ?>/tag/<?= $e($tag['slug']) ?>" class="tag-chip"><?= $e($tag['name']) ?></a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Sidebar article -->
      <aside class="article-sidebar" aria-label="Informations complémentaires">

        <!-- Sommaire automatique -->
        <div class="sidebar-block toc-block" aria-label="Sommaire">
          <h2 class="sidebar-title">Sommaire</h2>
          <nav id="article-toc" aria-label="Table des matières"><!-- Généré par JS --></nav>
        </div>

        <!-- Auteur -->
        <div class="sidebar-block author-block">
          <h2 class="sidebar-title">L'auteur</h2>
          <div class="author-card">
            <?php if ($article['author_avatar']): ?>
              <img src="<?= $e($imgUrl($article['author_avatar'])) ?>" alt="<?= $e($article['author_name']) ?>" class="author-card-avatar" width="64" height="64" loading="lazy">
            <?php endif; ?>
            <div>
              <a href="<?= APP_URL ?>/auteur/<?= $e($article['author_username']) ?>" class="author-card-name"><?= $e($article['author_name']) ?></a>
            </div>
          </div>
        </div>

        <!-- Partage (duplication sidebar) -->
        <div class="sidebar-block">
          <h2 class="sidebar-title">Partager</h2>
          <div class="share-sidebar">
            <a href="https://twitter.com/intent/tweet?url=<?= urlencode($shortUrl) ?>&text=<?= urlencode($article['title']) ?>"
               class="share-btn share-btn--twitter" target="_blank" rel="noopener noreferrer">Twitter / X</a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($shortUrl) ?>"
               class="share-btn share-btn--linkedin" target="_blank" rel="noopener noreferrer">LinkedIn</a>
          </div>
        </div>

      </aside>
    </div>
  </div>

  <!-- ARTICLES LIÉS -->
  <?php if (!empty($related)): ?>
  <section class="article-related container" aria-labelledby="related-heading">
    <h2 id="related-heading" class="section-title">À lire aussi</h2>
    <div class="related-grid">
      <?php foreach ($related as $r): ?>
      <article class="article-card article-card--related">
        <?php if ($r['featured_image_thumb']): ?>
          <a href="<?= APP_URL ?>/article/<?= $e($r['slug']) ?>" class="article-card-image" tabindex="-1" aria-hidden="true">
            <img src="<?= $e($imgUrl($r['featured_image_thumb'])) ?>" alt="<?= $e($r['alt'] ?? '') ?>" width="300" height="200" loading="lazy">
          </a>
        <?php endif; ?>
        <div class="article-card-body">
          <h3 class="article-card-title"><a href="<?= APP_URL ?>/article/<?= $e($r['slug']) ?>"><?= $e($r['title']) ?></a></h3>
          <time datetime="<?= $e($r['published_at'] ?? '') ?>" class="article-time">
            <?= $r['published_at'] ? date('d/m/Y', strtotime($r['published_at'])) : 'Récemment' ?>
          </time>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- COMMENTAIRES -->
  <section class="article-comments container" aria-labelledby="comments-heading">
    <h2 id="comments-heading" class="section-title">Commentaires (<?= count($comments) ?>)</h2>

    <?php if (!empty($comments)): ?>
    <div class="comments-list">
      <?php foreach ($comments as $comment): ?>
      <article class="comment" id="comment-<?= (int)$comment['id'] ?>">
        <header class="comment-header">
          <?php if ($comment['author_avatar']): ?>
            <img src="<?= $e($imgUrl($comment['author_avatar'])) ?>" alt="<?= $e($comment['author_name']) ?>" class="comment-avatar" width="40" height="40" loading="lazy">
          <?php else: ?>
            <div class="comment-avatar-placeholder" aria-hidden="true"><?= mb_strtoupper(mb_substr($comment['author_name'], 0, 1)) ?></div>
          <?php endif; ?>
          <div>
            <span class="comment-author"><?= $e($comment['author_name']) ?></span>
            <time datetime="<?= $e($comment['created_at']) ?>" class="comment-date"><?= date('d/m/Y à H\hi', strtotime($comment['created_at'])) ?></time>
          </div>
        </header>
        <div class="comment-content"><?= nl2br($e($comment['content'])) ?></div>
        <?php if ($currentUser): ?>
          <button class="comment-reply-btn" data-parent="<?= (int)$comment['id'] ?>" aria-label="Répondre à ce commentaire">Répondre</button>
        <?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <p class="comments-empty">Soyez le premier à commenter cet article.</p>
    <?php endif; ?>

    <!-- Formulaire de commentaire -->
    <?php if ($currentUser): ?>
    <div class="comment-form-wrap">
      <h3 class="comment-form-title">Laisser un commentaire</h3>
      <form id="comment-form" class="comment-form" data-article="<?= (int)$article['id'] ?>">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $e($csrfToken) ?>">
        <input type="hidden" name="article_id" value="<?= (int)$article['id'] ?>">
        <input type="hidden" name="parent_id" id="comment-parent-id" value="">
        <div id="reply-indicator" class="reply-indicator" hidden>
          Réponse à <span id="reply-to-name"></span> <button type="button" id="cancel-reply">✕</button>
        </div>
        <textarea name="content" id="comment-content" rows="4" minlength="10" maxlength="2000"
                  placeholder="Votre commentaire…" required aria-label="Votre commentaire"></textarea>
        <div class="comment-form-footer">
          <span class="char-count"><span id="char-count">0</span>/2000</span>
          <button type="submit" class="btn btn--primary">Publier</button>
        </div>
        <p class="comment-moderation-note">Les commentaires sont publiés immédiatement mais peuvent être modérés a posteriori.</p>
      </form>
    </div>
    <?php else: ?>
    <div class="comment-login-prompt">
      <p><a href="<?= PORTAL_URL ?>/connexion?redirect=<?= urlencode(APP_URL . '/article/' . $article['slug']) ?>">Connectez-vous</a> pour laisser un commentaire.</p>
    </div>
    <?php endif; ?>
  </section>

</div><!-- .article-page -->
