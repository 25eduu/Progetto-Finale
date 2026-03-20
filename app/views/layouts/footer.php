<?php
$env = parse_ini_file(__DIR__ . '/../../../.env', false, INI_SCANNER_RAW);
$googleClientId = $env['GOOGLE_CLIENT_ID'] ?? '';
?>
</main>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
  window.BASE_URL = "<?= BASE_URL ?>";
  window.GOOGLE_CLIENT_ID = "<?= htmlspecialchars($googleClientId, ENT_QUOTES) ?>";
</script>
</body>
</html>