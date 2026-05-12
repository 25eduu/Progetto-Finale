<?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h1 class="display-6 fw-bold mb-0">Ordini</h1>
  </div>
  <div class="d-flex gap-2 flex-wrap align-items-center">
    <a href="<?= BASE_URL ?>/index.php?r=adminDashboard/index" class="btn btn-outline-secondary rounded-pill px-4">
      ← Dashboard
    </a>
    <?php
    $statuses = ['', 'pending_payment', 'paid', 'shipped', 'completed', 'cancelled'];
    $labels   = ['Tutti', 'In attesa', 'Pagati', 'Spediti', 'Completati', 'Annullati'];
    $current  = $_GET['status'] ?? '';
    foreach ($statuses as $i => $s): ?>
      <a href="<?= BASE_URL ?>/index.php?r=adminOrder/index<?= $s ? '&status=' . $s : '' ?>"
         class="btn btn-sm rounded-pill <?= $current === $s ? 'btn-dark' : 'btn-outline-secondary' ?>">
        <?= $labels[$i] ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<?php if (!empty($flash['success'])): ?>
  <div class="alert alert-success rounded-3 mb-4"><?= htmlspecialchars($flash['success']) ?></div>
<?php endif; ?>
<?php if (!empty($flash['error'])): ?>
  <div class="alert alert-danger rounded-3 mb-4"><?= htmlspecialchars($flash['error']) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-4">#</th><th>Cliente</th><th>Metodo</th><th>Stato</th>
            <th class="text-end">Totale</th><th class="text-end pe-4">Data</th><th class="text-center">Azione</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($orders)): ?>
            <tr><td colspan="7" class="text-center text-muted py-5">Nessun ordine trovato.</td></tr>
          <?php endif; ?>
          <?php foreach ($orders as $o):
            $badge = match($o['status']) {
              'paid' => 'success', 'shipped' => 'info', 'completed' => 'primary',
              'cancelled' => 'danger', 'pending_payment' => 'warning', default => 'secondary',
            }; ?>
            <tr>
              <td class="ps-4 fw-semibold">#<?= (int)$o['id'] ?></td>
              <td>
                <div class="fw-medium"><?= htmlspecialchars($o['customer_name']) ?></div>
                <div class="text-muted small"><?= htmlspecialchars($o['customer_email']) ?></div>
              </td>
              <td><span class="text-capitalize"><?= htmlspecialchars($o['payment_method']) ?></span></td>
              <td><span class="badge text-bg-<?= $badge ?> rounded-pill"><?= htmlspecialchars($o['status']) ?></span></td>
              <td class="text-end fw-semibold">€ <?= number_format((float)$o['total_amount'], 2, ',', '.') ?></td>
              <td class="text-end text-muted small pe-4"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
              <td class="text-center">
                <form method="post" action="<?= BASE_URL ?>/index.php?r=adminOrder/updateStatus" class="d-flex gap-1 justify-content-center">
                  <?= CsrfHelper::field() ?>
                  <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                  <select name="status" class="form-select form-select-sm rounded-3" style="width:130px">
                    <option value="paid"      <?= $o['status']==='paid'      ? 'selected':'' ?>>Pagato</option>
                    <option value="shipped"   <?= $o['status']==='shipped'   ? 'selected':'' ?>>Spedito</option>
                    <option value="completed" <?= $o['status']==='completed' ? 'selected':'' ?>>Completato</option>
                    <option value="cancelled" <?= $o['status']==='cancelled' ? 'selected':'' ?>>Annullato</option>
                  </select>
                  <button type="submit" class="btn btn-outline-dark btn-sm rounded-3">Aggiorna</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
