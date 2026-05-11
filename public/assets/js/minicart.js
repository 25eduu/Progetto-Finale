/**
 * minicart.js
 * Gestisce il mini-cart offcanvas: apertura, aggiornamento badge,
 * aumento/diminuzione quantità e rimozione prodotti via AJAX.
 */
document.addEventListener('DOMContentLoaded', () => {
  const miniCartContent = document.getElementById('miniCartContent');
  const cartBadge       = document.getElementById('cartBadge');
  const miniCartCanvas  = document.getElementById('miniCartCanvas');

  // ── Helpers ──────────────────────────────────────────────────────────────

  async function postForm(url, data) {
    const response = await fetch(url, {
      method:  'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type':     'application/x-www-form-urlencoded;charset=UTF-8',
      },
      body: new URLSearchParams(data),
    });
    return response.json();
  }

  async function loadMiniCart() {
    if (!miniCartContent) return;
    const response = await fetch(`${window.BASE_URL}/index.php?r=cart/sidebar`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    miniCartContent.innerHTML = await response.text();
  }

  function updateBadge(count) {
    if (!cartBadge) return;
    cartBadge.textContent = count;
    cartBadge.classList.toggle('d-none', count <= 0);
  }

  function refreshFromResponse(data) {
    if (!data?.success) return;
    updateBadge(data.cartCount);
    if (miniCartContent && data.miniCartHtml) {
      miniCartContent.innerHTML = data.miniCartHtml;
    }
  }

  // ── Apertura offcanvas: carica contenuto aggiornato ──────────────────────

  document.addEventListener('show.bs.offcanvas', async (event) => {
    if (event.target?.id === 'miniCartCanvas') await loadMiniCart();
  });

  // ── Submit form "Aggiungi al carrello" (pagina prodotto) ─────────────────

  document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!form.classList.contains('js-add-to-cart-form')) return;

    event.preventDefault();
    const productId = form.querySelector('[name="product_id"]').value;

    const data = await postForm(
      `${window.BASE_URL}/index.php?r=cart/addAjax`,
      { product_id: productId }
    );

    refreshFromResponse(data);
    bootstrap.Offcanvas.getOrCreateInstance(miniCartCanvas).show();
  });

  // ── Click su bottoni dentro il mini-cart ─────────────────────────────────

  document.addEventListener('click', async (event) => {
    const increase  = event.target.closest('.js-cart-increase');
    const decrease  = event.target.closest('.js-cart-decrease');
    const remove    = event.target.closest('.js-cart-remove');
    const closeLink = event.target.closest('[data-close-cart="1"]');

    if (closeLink) {
      bootstrap.Offcanvas.getInstance(miniCartCanvas)?.hide();
      return;
    }

    if (increase) {
      const { productId, quantity, stock } = increase.dataset;
      const nextQty = parseInt(quantity, 10) + 1;
      if (nextQty > parseInt(stock, 10)) return;
      refreshFromResponse(await postForm(
        `${window.BASE_URL}/index.php?r=cart/updateAjax`,
        { product_id: productId, quantity: nextQty }
      ));
      return;
    }

    if (decrease) {
      const { productId, quantity } = decrease.dataset;
      refreshFromResponse(await postForm(
        `${window.BASE_URL}/index.php?r=cart/updateAjax`,
        { product_id: productId, quantity: parseInt(quantity, 10) - 1 }
      ));
      return;
    }

    if (remove) {
      refreshFromResponse(await postForm(
        `${window.BASE_URL}/index.php?r=cart/removeAjax`,
        { product_id: remove.dataset.productId }
      ));
    }
  });
});
