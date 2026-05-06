<?php
$currentRoute = $_GET['r'] ?? '';
$isAdmin      = str_starts_with($currentRoute, 'admin') || str_starts_with($currentRoute, 'adminDashboard')
             || str_starts_with($currentRoute, 'adminProduct') || str_starts_with($currentRoute, 'adminOrder')
             || str_starts_with($currentRoute, 'adminUser');
?>
</main>

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
  <script src="<?= BASE_URL ?>/assets/js/google.js"></script>
  <script src="<?= BASE_URL ?>/assets/js/ui.js"></script>
<?php endif; ?>

</body>
</html>
