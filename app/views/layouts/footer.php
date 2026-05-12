<?php
$currentRoute = $_GET['r'] ?? '';
$isAdmin = str_starts_with($currentRoute, 'admin');
?>
</main>

<footer class="site-footer py-5 bg-white border-top">
  <div class="container">
    <div class="row gy-4">
      <div class="col-md-4">
        <a class="footer-brand d-inline-flex align-items-center mb-3 fw-bold fs-5" href="<?= BASE_URL ?>/index.php">TechShop</a>
        <p class="text-muted mb-3">Elettronica, accessori e offerte con spedizione rapida e assistenza dedicata.</p>
        <div class="footer-badges d-flex flex-wrap gap-2">
          <span class="footer-badge">Pagamento sicuro</span>
          <span class="footer-badge">Spedizione veloce</span>
          <span class="footer-badge">Supporto 24/7</span>
        </div>
      </div>
      <div class="col-md-2">
        <h5 class="footer-title mb-3">Link utili</h5>
        <ul class="list-unstyled footer-links mb-0">
          <li><a href="<?= BASE_URL ?>/index.php?r=products/index">Prodotti</a></li>
          <li><a href="<?= BASE_URL ?>/index.php?r=products/search">Cerca</a></li>
          <li><a href="<?= BASE_URL ?>/index.php?r=checkout/index">Checkout</a></li>
          <li><a href="<?= BASE_URL ?>/index.php?r=account/dashboard">Account</a></li>
        </ul>
      </div>
      <div class="col-md-3">
        <h5 class="footer-title mb-3">Contatti</h5>
        <p class="mb-2"><strong>Email:</strong> <a href="mailto:support@techshop.it">support@techshop.it</a></p>
        <p class="mb-2"><strong>Telefono:</strong> <a href="tel:+393272378806">+39 327 237 8806</a></p>
        <p class="mb-0"><strong>P.IVA:</strong> IT12345678901</p>
      </div>
      <div class="col-md-3">
        <h5 class="footer-title mb-3">Seguici</h5>
        <div class="footer-social-links d-flex flex-wrap gap-2">
          <a class="footer-social-link" href="https://www.facebook.com/techshop" target="_blank" rel="noopener noreferrer">
            <span aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
              </svg>
            </span>
            Facebook
          </a>
          <a class="footer-social-link" href="https://www.instagram.com/techshop" target="_blank" rel="noopener noreferrer">
            <span aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="4" y="4" width="16" height="16" rx="5" />
                <circle cx="12" cy="12" r="4" />
                <path d="M17.5 6.5h.01" />
              </svg>
            </span>
            Instagram
          </a>
          <a class="footer-social-link" href="https://www.tiktok.com/@techshop" target="_blank" rel="noopener noreferrer">
            <span aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 8a4 4 0 0 0 4 4h3" />
                <path d="M12 3v6a6 6 0 1 0 6 6" />
              </svg>
            </span>
            TikTok
          </a>
          <a class="footer-social-link footer-social-link--highlight" href="https://wa.me/393272378806?text=Salve%20TechShop" target="_blank" rel="noopener noreferrer">
            <span aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16.5 13.5a6 6 0 1 1-1.5-4.25L18 8.5V5.5a9 9 0 1 0 0 13.5l-2.5-2.5z" />
                <path d="M15.1 12.9c-.25-.1-1.45-.73-1.67-.81-.22-.08-.38-.12-.54.12-.16.25-.6.81-.74.98-.14.17-.28.19-.53.07-.25-.12-1.04-.39-1.98-1.23-.73-.65-1.22-1.45-1.36-1.7-.14-.25-.02-.38.11-.5.12-.12.25-.28.37-.43.12-.15.16-.25.25-.42.09-.17.05-.31-.02-.43-.07-.12-.54-1.31-.74-1.79-.2-.47-.4-.41-.55-.42-.14-.01-.33-.01-.5-.01s-.43.06-.65.31c-.22.25-.85.83-.85 2.03s.87 2.35.99 2.51c.12.17 1.7 2.6 4.12 3.64.58.25 1.03.4 1.38.51.58.18 1.11.15 1.53.09.47-.07 1.45-.59 1.65-1.16.2-.57.2-1.06.14-1.16-.06-.1-.22-.16-.47-.28z" />
              </svg>
            </span>
            WhatsApp
          </a>
        </div>
      </div>
    </div>
    <div class="footer-note text-center text-muted small mt-4">© 2026 TechShop. Tutti i diritti riservati.</div>
  </div>
</footer>

<script>
  window.BASE_URL         = "<?= BASE_URL ?>";
  window.GOOGLE_CLIENT_ID = "<?= htmlspecialchars(GOOGLE_CLIENT_ID, ENT_QUOTES) ?>";
</script>

<script src="https://accounts.google.com/gsi/client" async defer></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($isAdmin): ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      if (window.REVENUE_DATA) initRevenueChart(window.REVENUE_DATA);
    });
  </script>
<?php else: ?>
  <script src="<?= BASE_URL ?>/assets/js/cart.js"></script>
  <script src="<?= BASE_URL ?>/assets/js/search.js"></script>
  <script src="<?= BASE_URL ?>/assets/js/google.js"></script>
  <script src="<?= BASE_URL ?>/assets/js/ui.js"></script>
<?php endif; ?>

</body>
</html>
