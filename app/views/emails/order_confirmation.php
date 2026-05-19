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
      <div class="box" style="background:#f0f4f8;padding:24px;">
        <h2 style="font-size:1rem;margin:0 0 12px;font-weight:700;color:#042C53">Riepilogo dell'ordine</h2>
        <?php if (!empty($items) && is_array($items)): ?>
          <?php foreach ($items as $item): ?>
            <?php $unitPrice = (float)($item['unit_price'] ?? $item['price'] ?? 0); ?>
            <div class="row">
              <span><?= htmlspecialchars($item['name'] ?? 'Prodotto') ?> × <?= (int)($item['quantity'] ?? 0) ?></span>
              <span>€ <?= number_format($unitPrice * (int)($item['quantity'] ?? 0), 2, ',', '.') ?></span>
            </div>
          <?php endforeach; ?>
          <div class="row" style="border-top:1px solid #dfe3e8;margin-top:12px;padding-top:12px;font-weight:700;color:#042C53;">
            <span>Totale ordine</span>
            <span>€ <?= number_format($total, 2, ',', '.') ?></span>
          </div>
        <?php else: ?>
          <div class="row">
            <span>Totale ordine</span>
            <span>€ <?= number_format($total, 2, ',', '.') ?></span>
          </div>
        <?php endif; ?>
      </div>

      <p style="margin-top:20px;color:#555;font-size:0.95rem;">
        Il tuo ordine è in elaborazione. Ti invieremo un'altra email quando sarà spedito.
      </p>
      <p style="font-size:0.85rem;color:#777;">
        Riferimento ordine: <strong>#<?= (int)$orderId ?></strong>
      </p>
    </div>
    <div class="footer">TechShop &mdash; Non rispondere a questa email.</div>
  </div>
</body>
</html>
