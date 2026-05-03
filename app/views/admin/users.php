<?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="display-6 fw-bold mb-0">Utenti</h1>
  <span class="text-muted"><?= count($users) ?> utenti registrati</span>
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
            <th class="ps-4">#</th>
            <th>Utente</th>
            <th>Accesso</th>
            <th>Ruolo</th>
            <th class="text-end">Wallet</th>
            <th class="text-end pe-4">Registrato</th>
            <th class="text-center">Ricarica wallet</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td class="ps-4 text-muted small"><?= (int)$u['id'] ?></td>
              <td>
                <div class="fw-medium"><?= htmlspecialchars($u['full_name']) ?></div>
                <div class="text-muted small"><?= htmlspecialchars($u['email']) ?></div>
              </td>
              <td>
                <span class="badge rounded-pill <?= $u['auth_provider'] === 'google' ? 'text-bg-warning' : 'text-bg-secondary' ?>">
                  <?= htmlspecialchars($u['auth_provider']) ?>
                </span>
              </td>
              <td>
                <span class="badge rounded-pill <?= $u['role'] === 'admin' ? 'text-bg-dark' : 'text-bg-light border' ?>">
                  <?= htmlspecialchars($u['role']) ?>
                </span>
              </td>
              <td class="text-end fw-semibold text-success">
                € <?= number_format((float)$u['wallet_balance'], 2, ',', '.') ?>
              </td>
              <td class="text-end text-muted small pe-4">
                <?= date('d/m/Y', strtotime($u['created_at'])) ?>
              </td>
              <td class="text-center">
                <form method="post" action="<?= BASE_URL ?>/index.php?r=admin/addWallet"
                      class="d-flex gap-1 justify-content-center align-items-center">
                  <?= CsrfHelper::field() ?>
                  <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                  <div class="input-group input-group-sm" style="max-width:180px">
                    <span class="input-group-text">€</span>
                    <input type="number" name="amount" class="form-control" min="0.01" step="0.01" placeholder="0.00" required>
                    <button type="submit" class="btn btn-outline-success">+</button>
                  </div>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
