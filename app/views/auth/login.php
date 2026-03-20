<h1 class="display-6 fw-semibold mb-4">Accedi</h1>

<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-body">
        <form method="post" action="<?= BASE_URL ?>/index.php?r=auth/login">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>

          <button type="submit" class="btn btn-dark w-100">Accedi</button>
        </form>

        <div class="text-center mt-3">
          <a href="<?= BASE_URL ?>/index.php?r=auth/register">Non hai un account? Registrati</a>
        </div>

        <hr class="my-4">

        <div id="googleLoginBtn"></div>
      </div>
    </div>
  </div>
</div>