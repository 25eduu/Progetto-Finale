<?php
/**
 * views/home/index.php
 * Homepage – hero + categorie rapide + griglia prodotti con quick-add AJAX
 */
?>

<!-- ═══════════════════ HERO ════════════════════ -->
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
        <a href="<?= BASE_URL ?>/index.php?r=products/search" class="ts-btn ts-btn--ghost">
          Cerca nel catalogo
        </a>
      </div>
    </div>

    <div class="ts-hero__stats">
      <div class="ts-hero__stat">
        <span class="ts-hero__stat-n">500+</span>
        <span class="ts-hero__stat-l">Prodotti</span>
      </div>
      <div class="ts-hero__stat">
        <span class="ts-hero__stat-n">4.9★</span>
        <span class="ts-hero__stat-l">Rating</span>
      </div>
      <div class="ts-hero__stat">
        <span class="ts-hero__stat-n">48h</span>
        <span class="ts-hero__stat-l">Consegna</span>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ CATEGORIE RAPIDE ══════════════ -->
<div class="ts-home py-4">

  <?php
  $categoryIcons = [
    1 => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="3" width="10" height="18" rx="2" /><path d="M9 5h6" /><path d="M12 19.5h.01" /><path d="M8 13h8" /></svg>',
    2 => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18v10H3z" /><path d="M3 16h18" /><path d="M7 6V4h10v2" /></svg>',
    3 => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12a2 2 0 0 1 2 2v4H4V5a2 2 0 0 1 2-2z" /><path d="M4 9h16v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9z" /><path d="M8 13h8" /></svg>',
  ];
  ?>

  <div class="d-flex flex-wrap align-items-center gap-2 mb-5">
    <?php foreach ($homeCategories as $category): ?>
      <a href="<?= BASE_URL ?>/index.php?r=products/search&category_id=<?= (int)$category['id'] ?>"
         class="ts-category-chip">
        <span class="ts-category-chip__icon">
          <?= $categoryIcons[$category['id']] ?? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9" /></svg>' ?>
        </span>
        <?= htmlspecialchars($category['name']) ?>
      </a>
    <?php endforeach; ?>
    <a href="<?= BASE_URL ?>/index.php?r=products/search"
       class="btn btn-sm rounded-pill px-3"
       style="background:var(--bg);border:1.5px solid var(--border);color:var(--text-muted)">
      Cerca per nome →
    </a>
  </div>

  <section class="ts-home-features py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
      <h2 class="ts-section-title mb-0">Perché sceglierci</h2>
      <p class="text-muted small mb-0">Supporto, spedizione rapida e pagamenti certificati.</p>
    </div>
    <div class="ts-home-features__grid">
      <div class="ts-feature-card">
        <div class="ts-feature-card__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="6" y="4" width="12" height="16" rx="3" />
            <path d="M8 18h8" />
            <path d="M9 8h6" />
          </svg>
        </div>
        <h3>Spedizione veloce</h3>
        <p>Ordini elaborati entro 24h e consegna rapida su tutta Italia.</p>
      </div>
      <div class="ts-feature-card">
        <div class="ts-feature-card__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="6" width="18" height="12" rx="2" />
            <path d="M3 10h18" />
            <path d="M7 16h4" />
          </svg>
        </div>
        <h3>Assistenza dedicata</h3>
        <p>Supporto rapido via chat e email per ogni acquisto.</p>
      </div>
      <div class="ts-feature-card">
        <div class="ts-feature-card__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 8h16v10H4z" />
            <path d="M8 12h8" />
            <path d="M8 16h5" />
          </svg>
        </div>
        <h3>Pagamento sicuro</h3>
        <p>Checkout protetto con Stripe e connessione HTTPS.</p>
      </div>
    </div>
  </section>

  <!-- ═══════════════ GRIGLIA PRODOTTI ══════════════ -->
  <div class="d-flex align-items-baseline justify-content-between mb-3">
    <h2 class="ts-section-title">Ultimi arrivi</h2>
    <a href="<?= BASE_URL ?>/index.php?r=products/index" class="ts-link-all">
      Vedi tutti →
    </a>
  </div>

  <?php if (empty($products)): ?>
    <p class="text-muted">Nessun prodotto disponibile al momento.</p>
  <?php else: ?>
    <div class="ts-grid">
      <?php foreach ($products as $p): ?>
        <div class="ts-card" data-product-id="<?= (int)$p['id'] ?>">

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

          <?php if ((int)$p['stock'] > 0): ?>
            <button class="ts-card__quick-add js-quick-add"
                    data-product-id="<?= (int)$p['id'] ?>"
                    title="Aggiungi al carrello"
                    aria-label="Aggiungi <?= htmlspecialchars($p['name']) ?> al carrello">
              +
            </button>
          <?php endif; ?>

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

<!-- Toast feedback quick-add -->
<div id="ts-toast" class="ts-toast" aria-live="polite"></div>
