<?php
require_once __DIR__ . '/../../helpers/security/CsrfHelper.php';
$fullName      = $user['full_name']      ?? 'Utente';
$email         = $user['email']          ?? '';
$walletBalance = (float)($user['wallet_balance'] ?? 0);
$totalOrders   = (int)($stats['total_orders']    ?? 0);
$totalSpent    = (float)($stats['total_spent']   ?? 0);
?>

<section class="py-4">
  <div class="container">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
      <div>
        <h1 class="display-6 fw-bold mb-1">La tua dashboard</h1>
        <p class="text-muted mb-0">Ciao <?= htmlspecialchars($fullName) ?>, qui trovi saldo, ordini e movimenti wallet.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <?php if (!empty($user['role']) && $user['role'] === 'admin'): ?>
          <a href="<?= BASE_URL ?>/index.php?r=adminDashboard/index" class="btn btn-warning rounded-pill px-4">📊 Area Admin</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/index.php?r=products/index"  class="btn btn-outline-dark rounded-pill px-4">Continua gli acquisti</a>
        <a href="<?= BASE_URL ?>/index.php?r=account/profile" class="btn btn-outline-dark rounded-pill px-4">Modifica profilo</a>
        <a href="<?= BASE_URL ?>/index.php?r=auth/logout"     class="btn btn-dark rounded-pill px-4">Logout</a>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body p-4">
            <div class="text-muted small mb-2">Saldo wallet</div>
            <div class="fs-2 fw-bold">€ <?= number_format($walletBalance, 2, ',', '.') ?></div>
            <div class="text-success small mt-2">Disponibile per i prossimi ordini</div>
            <div class="d-grid gap-2 mt-3">
              <a href="<?= BASE_URL ?>/index.php?r=wallet/recharge&amount=10" class="btn btn-sm btn-outline-success">+€ 10,00</a>
              <a href="<?= BASE_URL ?>/index.php?r=wallet/recharge&amount=25" class="btn btn-sm btn-outline-success">+€ 25,00</a>
              <a href="<?= BASE_URL ?>/index.php?r=wallet/recharge&amount=50" class="btn btn-sm btn-outline-success">+€ 50,00</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body p-4">
            <div class="text-muted small mb-2">Ordini effettuati</div>
            <div class="fs-2 fw-bold"><?= $totalOrders ?></div>
            <div class="text-muted small mt-2">Storico totale del tuo account</div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body p-4">
            <div class="text-muted small mb-2">Totale speso</div>
            <div class="fs-2 fw-bold">€ <?= number_format($totalSpent, 2, ',', '.') ?></div>
            <div class="text-muted small mt-2">Somma di tutti gli ordini</div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h2 class="h4 mb-0">Ultimi ordini</h2>
              <span class="text-muted small"><?= count($orders) ?> mostrati</span>
            </div>
            <?php if (empty($orders)): ?>
              <div class="alert alert-light border rounded-4 mb-0">Non hai ancora effettuato ordini.</div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table align-middle mb-0">
                  <thead>
                    <tr><th>Ordine</th><th>Data</th><th>Pagamento</th><th>Stato</th><th class="text-end">Totale</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($orders as $order): ?>
                      <tr>
                        <td>
                          <div class="fw-semibold">#<?= (int)$order['id'] ?></div>
                          <div class="text-muted small"><?= htmlspecialchars((string)$order['payment_status']) ?></div>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime((string)$order['created_at'])) ?></td>
                        <td>
                          <div class="text-capitalize"><?= htmlspecialchars((string)$order['payment_method']) ?></div>
                          <?php if ((float)$order['wallet_amount_paid'] > 0): ?>
                            <div class="small text-success">Wallet: € <?= number_format((float)$order['wallet_amount_paid'], 2, ',', '.') ?></div>
                          <?php endif; ?>
                          <?php if ((float)$order['stripe_amount_paid'] > 0): ?>
                            <div class="small text-muted">Carta: € <?= number_format((float)$order['stripe_amount_paid'], 2, ',', '.') ?></div>
                          <?php endif; ?>
                        </td>
                        <td>
                          <span class="badge text-bg-dark rounded-pill px-3 py-2 text-capitalize">
                            <?= htmlspecialchars((string)$order['status']) ?>
                          </span>
                        </td>
                        <td class="text-end fw-semibold">€ <?= number_format((float)$order['total_amount'], 2, ',', '.') ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-body p-4">
            <h2 class="h4 mb-3">Profilo</h2>
            <div class="mb-2">
              <div class="text-muted small">Nome</div>
              <div class="fw-semibold"><?= htmlspecialchars($fullName) ?></div>
            </div>
            <div class="mb-2">
              <div class="text-muted small">Email</div>
              <div class="fw-semibold"><?= htmlspecialchars($email) ?></div>
            </div>
            <div>
              <div class="text-muted small">Registrato dal</div>
              <div class="fw-semibold"><?= date('d/m/Y', strtotime((string)$user['created_at'])) ?></div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h2 class="h4 mb-0">Movimenti wallet</h2>
              <span class="text-muted small"><?= count($walletLogs) ?> recenti</span>
            </div>
            <?php if (empty($walletLogs)): ?>
              <div class="alert alert-light border rounded-4 mb-0">Nessun movimento wallet disponibile.</div>
            <?php else: ?>
              <div class="d-flex flex-column gap-3">
                <?php foreach ($walletLogs as $log):
                  $amount = (float)$log['amount']; ?>
                  <div class="border rounded-4 p-3">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                      <div>
                        <div class="fw-semibold"><?= htmlspecialchars((string)$log['description']) ?></div>
                        <div class="text-muted small"><?= date('d/m/Y H:i', strtotime((string)$log['created_at'])) ?></div>
                      </div>
                      <div class="fw-bold <?= $amount >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= $amount >= 0 ? '+' : '-' ?>€ <?= number_format(abs($amount), 2, ',', '.') ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
