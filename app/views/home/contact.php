<?php
/* home/contact.php */
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
?>
<div class="container" style="padding-top:40px;padding-bottom:80px;">
  <div style="max-width:640px;">
    <h1 style="font-family:'Playfair Display',serif;font-size:2rem;font-weight:900;margin-bottom:8px;">Contact</h1>
    <p style="color:#6b7280;margin-bottom:32px;">Une question, une suggestion ou un signalement ? Écrivez-nous.</p>

    <form method="post" action="<?= APP_URL ?>/contact" novalidate style="display:flex;flex-direction:column;gap:16px;">
      <?= \Koonect\Helpers\Csrf::field() ?>

      <div class="form-group">
        <label for="name" style="font-family:'Inter',sans-serif;font-size:.82rem;font-weight:600;display:block;margin-bottom:6px;">Votre nom</label>
        <input type="text" id="name" name="name" required placeholder="Jean Dupont"
               style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:4px;font-family:'Inter',sans-serif;font-size:.9rem;outline:none;">
      </div>

      <div class="form-group">
        <label for="email" style="font-family:'Inter',sans-serif;font-size:.82rem;font-weight:600;display:block;margin-bottom:6px;">Votre email</label>
        <input type="email" id="email" name="email" required placeholder="vous@exemple.fr"
               style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:4px;font-family:'Inter',sans-serif;font-size:.9rem;outline:none;">
      </div>

      <div class="form-group">
        <label for="subject" style="font-family:'Inter',sans-serif;font-size:.82rem;font-weight:600;display:block;margin-bottom:6px;">Sujet</label>
        <input type="text" id="subject" name="subject" placeholder="Question générale…"
               style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:4px;font-family:'Inter',sans-serif;font-size:.9rem;outline:none;">
      </div>

      <div class="form-group">
        <label for="message" style="font-family:'Inter',sans-serif;font-size:.82rem;font-weight:600;display:block;margin-bottom:6px;">Message <span style="color:#C8102E">*</span></label>
        <textarea id="message" name="message" required rows="6" minlength="20" maxlength="2000"
                  placeholder="Votre message…"
                  style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:4px;font-family:'Inter',sans-serif;font-size:.9rem;outline:none;resize:vertical;line-height:1.6;"></textarea>
      </div>

      <div>
        <button type="submit" class="btn btn--primary">Envoyer le message</button>
      </div>
    </form>
  </div>
</div>
