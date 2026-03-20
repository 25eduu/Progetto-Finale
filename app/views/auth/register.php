<h1 class="display-6 fw-semibold mb-4">Registrati</h1>

<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-body">
        <form method="post" action="<?= BASE_URL ?>/index.php?r=auth/register">
          <div class="mb-3">
            <label class="form-label">Nome completo</label>
            <input type="text" name="full_name" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required minlength="8">
          </div>

          <button type="submit" class="btn btn-dark w-100">Crea account</button>
        </form>

        <div class="text-center mt-3">
          <a href="<?= BASE_URL ?>/index.php?r=auth/login">Hai già un account? Accedi</a>
        </div>

        <hr class="my-4">

        <div id="googleRegisterBtn"></div>
      </div>
    </div>
  </div>
</div>