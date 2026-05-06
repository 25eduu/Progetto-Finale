<?php
$stats        = $stats        ?? [];
$recentOrders = $recentOrders ?? [];
$revenueChart = $revenueChart ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="display-6 fw-bold mb-0">Pannello Admin</h1>
    <p class="text-muted">Gestisci ordini, prodotti e utenti.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= BASE_URL ?>/index.php?r=adminProduct/index" class="btn btn-outline-dark rounded-pill px-4">Prodotti</a>
    <a href="<?= BASE_URL ?>/index.php?r=adminOrder/index"   class="btn btn-outline-dark rounded-pill px-4">Ordini</a>
    <a href="<?= BASE_URL ?>/index.php?r=adminUser/index"    class="btn btn-dark rounded-pill px-4">Utenti</a>
  </div>
</div>

<?php if (!empty($flash['success'])): ?>
  <div class="alert alert-success rounded-3 mb-4"><?= htmlspecialchars($flash['success']) ?></div>
<?php endif; ?>
<?php if (!empty($flash['error'])): ?>
  <div class="alert alert-danger rounded-3 mb-4"><?= htmlspecialchars($flash['error']) ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['label' => 'Utenti registrati', 'value' => number_format((int)($stats['total_users']  ?? 0)), 'color' => 'primary'],
    ['label' => 'Ordini totali',     'value' => number_format((int)($stats['total_orders'] ?? 0)), 'color' => 'secondary'],
    ['label' => 'Revenue (pagati)',  'value' => '€ ' . number_format((float)($stats['revenue'] ?? 0), 2, ',', '.'), 'color' => 'success'],
    ['label' => 'Prodotti esauriti', 'value' => number_format((int)($stats['out_of_stock'] ?? 0)), 'color' => 'danger'],
    ['label' => 'Ordini oggi',       'value' => number_format((int)($stats['orders_today'] ?? 0)), 'color' => 'warning'],
  ];
  foreach ($cards as $card): ?>
    <div class="col-6 col-lg">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body p-3">
          <div class="text-muted small mb-1"><?= $card['label'] ?></div>
          <div class="fs-4 fw-bold text-<?= $card['color'] ?>"><?= $card['value'] ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body p-4">
        <h2 class="h5 mb-3">Revenue ultimi 7 giorni</h2>
        <canvas id="revenueChart" height="120"></canvas>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body p-4">
        <h2 class="h5 mb-3">Ultimi ordini</h2>
        <?php if (empty($recentOrders)): ?>
          <p class="text-muted small">Nessun ordine ancora.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead class="table-light">
                <tr><th>#</th><th>Cliente</th><th>Stato</th><th class="text-end">Totale</th></tr>
              </thead>
              <tbody>
                <?php foreach ($recentOrders as $o):
                  $badge = match($o['status']) {
                    'paid' => 'success', 'shipped' => 'info', 'completed' => 'primary',
                    'cancelled' => 'danger', 'pending_payment' => 'warning', default => 'secondary',
                  }; ?>
                  <tr>
                    <td class="fw-semibold"><?= (int)$o['id'] ?></td>
                    <td>
                      <div class="fw-medium small"><?= htmlspecialchars($o['customer_name']) ?></div>
                      <div class="text-muted" style="font-size:.75rem"><?= date('d/m H:i', strtotime($o['created_at'])) ?></div>
                    </td>
                    <td><span class="badge text-bg-<?= $badge ?> rounded-pill"><?= htmlspecialchars($o['status']) ?></span></td>
                    <td class="text-end fw-semibold">€ <?= number_format((float)$o['total_amount'], 2, ',', '.') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="mt-3">
            <a href="<?= BASE_URL ?>/index.php?r=adminOrder/index" class="btn btn-outline-dark btn-sm rounded-pill">Vedi tutti</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>window.REVENUE_DATA = <?= json_encode($revenueChart) ?>;</script>
