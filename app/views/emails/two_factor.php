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
    .code { font-size: 42px; font-weight: 700; letter-spacing: 10px; color: #042C53; text-align: center; padding: 24px; background: #f0f4f8; border-radius: 8px; margin: 24px 0; }
    .footer { padding: 16px 32px; background: #f8f8f8; text-align: center; color: #999; font-size: 12px; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="header"><h1>🔐 TechShop</h1></div>
    <div class="body">
      <p>Ciao <strong><?= htmlspecialchars($fullName) ?></strong>,</p>
      <p>Usa il codice qui sotto per completare la verifica del tuo accesso:</p>
      <div class="code"><?= htmlspecialchars($code) ?></div>
      <p>Il codice è valido per <strong>10 minuti</strong>. Se non hai richiesto tu questo accesso, ignora questa email.</p>
    </div>
    <div class="footer">TechShop &mdash; Non rispondere a questa email.</div>
  </div>
</body>
</html>
