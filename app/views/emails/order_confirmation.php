<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Inter, Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
    .wrap { max-width: 480px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,.08); }
    .header { background: #042C53; padding: 32px; text-align: center; }
    .header h1 { color: #fff; margin: 0; font-size: 22px; }
    .body { padding: 32px; }
    .body p { color: #444; line-height: 1.6; margin: 0 0 16px; }
    .box { background: #f0f4f8; border-radius: 8px; padding: 20px 24px; margin: 24px 0; }
    .row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #e2e8f0; }
    .row:last-child { border-bottom: none; font-weight: 700; color: #042C53; }
    .footer { padding: 16px 32px; background: #f8f8f8; text-align: center; color: #999; font-size: 12px; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="header"><h1>✅ TechShop</h1></div>
    <div class="body">
      <p>Ciao <strong><?= htmlspecialchars($customerName) ?></strong>,</p>
      <p>Il tuo ordine è stato confermato con successo. Grazie per aver scelto TechShop!</p>
      <div class="box">
        <div class="row"><span>Numero ordine</span><span>#<?= (int)$orderId ?></span></div>
        <div class="row"><span>Totale pagato</span><span>€ <?= number_format($total, 2, ',', '.') ?></span></div>
      </div>
      <p>Puoi seguire lo stato del tuo ordine dalla tua dashboard.</p>
    </div>
    <div class="footer">TechShop &mdash; Non rispondere a questa email.</div>
  </div>
</body>
</html>
