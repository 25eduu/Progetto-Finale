<?php
/**
 * views/home/index.php
 * Homepage – banner hero + griglia prodotti con quick-add AJAX
 * Compatibile con CartController::addAjax() esistente
 */
?>

<section class="ts-hero">
  <div class="ts-hero__inner">

    <div class="ts-hero__text">
      <p class="ts-hero__eyebrow">Nuovi arrivi 2025</p>
      <h1 class="ts-hero__title">
        Tecnologia<br>al <span class="ts-hero__accent">miglior prezzo</span>
      </h1>
      <p class="ts-hero__sub">
        Smartphone, laptop e accessori.<br>
        Spedizione gratuita sopra&nbsp;€49.
      </p>
      <div class="ts-hero__btns">
        <a href="<?= BASE_URL ?>/index.php?r=products/index" class="ts-btn ts-btn--primary">
          Scopri i prodotti
        </a>
        <a href="<?= BASE_URL ?>/index.php?r=products/index" class="ts-btn ts-btn--ghost">
          Vedi le offerte
        </a>
      </div>
    </div>

    <div class="ts-hero__stats">
      <div class="ts-hero__stat">
        <span class="ts-hero__stat-n">500+</span>
        <span class="ts-hero__stat-l">Prodotti</span>
      </div>
      <div class="ts-hero__stat">
        <span class="ts-hero__stat-n">4.9 ★</span>
        <span class="ts-hero__stat-l">Rating</span>
      </div>
      <div class="ts-hero__stat">
        <span class="ts-hero__stat-n">48 h</span>
        <span class="ts-hero__stat-l">Consegna</span>
      </div>
    </div>

  </div>
</section>

<!-- ═══════════════════════════════════════════
     GRIGLIA PRODOTTI
════════════════════════════════════════════ -->
<div class="ts-home py-4">

  <div class="d-flex align-items-baseline justify-content-between mb-3">
    <h2 class="ts-section-title">Novità</h2>
    <a href="<?= BASE_URL ?>/index.php?r=products/index" class="ts-link-all">
      Vedi tutti &rarr;
    </a>
  </div>

  <?php if (empty($products)): ?>
    <p class="text-muted">Nessun prodotto disponibile al momento.</p>
  <?php else: ?>

    <div class="ts-grid">
      <?php foreach ($products as $p): ?>

        <div class="ts-card" data-product-id="<?= (int)$p['id'] ?>">

          <!-- Immagine -->
          <a class="ts-card__img-wrap"
             href="<?= BASE_URL ?>/index.php?r=products/show&id=<?= (int)$p['id'] ?>">

            <?php if ((int)$p['stock'] === 0): ?>
              <span class="ts-badge ts-badge--out">Esaurito</span>
            <?php endif; ?>

            <img
              src="<?= BASE_URL ?>/assets/<?= htmlspecialchars($p['image_path'] ?? 'images/placeholder.png') ?>"
              alt="<?= htmlspecialchars($p['name']) ?>"
              loading="lazy"
              class="ts-card__img">
          </a>

          <!-- Quick-add (nascosto se esaurito) -->
          <?php if ((int)$p['stock'] > 0): ?>
            <button class="ts-card__quick-add js-quick-add"
                    data-product-id="<?= (int)$p['id'] ?>"
                    title="Aggiungi al carrello"
                    aria-label="Aggiungi <?= htmlspecialchars($p['name']) ?> al carrello">
              +
            </button>
          <?php endif; ?>

          <!-- Testo -->
          <div class="ts-card__body">
            <a class="ts-card__name"
               href="<?= BASE_URL ?>/index.php?r=products/show&id=<?= (int)$p['id'] ?>">
              <?= htmlspecialchars($p['name']) ?>
            </a>
            <div class="ts-card__price">
              € <?= number_format((float)$p['price'], 2, ',', '.') ?>
            </div>
          </div>

        </div>

      <?php endforeach; ?>
    </div>

  <?php endif; ?>

</div>

<!-- ═══════════════════════════════════════════
     TOAST FEEDBACK
════════════════════════════════════════════ -->
<div id="ts-toast" class="ts-toast" aria-live="polite"></div>
