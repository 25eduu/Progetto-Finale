document.addEventListener('DOMContentLoaded', () => {
  const miniCartContent = document.getElementById('miniCartContent');
  const cartBadge = document.getElementById('cartBadge');
  const miniCartCanvas = document.getElementById('miniCartCanvas');
  const toastElement = document.getElementById('ts-toast') || document.getElementById('toastArea');
  let toastTimer;

  function showToast(message) {
    if (!toastElement) return;
    clearTimeout(toastTimer);
    toastElement.textContent = message;
    toastElement.classList.add('visible');
    toastTimer = setTimeout(() => toastElement.classList.remove('visible'), 2400);
  }

  async function postForm(url, data) {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
      },
      body: new URLSearchParams(data)
    });

    const text = await response.text();
    let json;

    try {
      json = JSON.parse(text);
    } catch (err) {
      throw new Error('Risposta non valida dal server: ' + err.message + '\n' + text);
    }

    if (!response.ok) {
      throw new Error(json.message || 'Errore server');
    }

    return json;
  }

  async function loadMiniCart() {
    if (!miniCartContent) return;

    const response = await fetch(`${window.BASE_URL}/index.php?r=cart/sidebar`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    if (!response.ok) {
      console.error('Impossibile caricare il mini carrello.', response.status);
      return;
    }

    miniCartContent.innerHTML = await response.text();
  }

  window.loadMiniCart = loadMiniCart;

  function updateBadge(count) {
    if (!cartBadge) return;

    cartBadge.textContent = count;

    if (count > 0) {
      cartBadge.classList.remove('d-none');
    } else {
      cartBadge.classList.add('d-none');
    }
  }

  function refreshMiniCartFromResponse(data) {
    if (!data || !data.success) return;

    if (typeof data.cartCount === 'number') {
      updateBadge(data.cartCount);
    }

    if (miniCartContent) {
      if (data.miniCartHtml) {
        miniCartContent.innerHTML = data.miniCartHtml;
      } else {
        return loadMiniCart();
      }
    }
  }

  document.addEventListener('show.bs.offcanvas', async (event) => {
    if (event.target && event.target.id === 'miniCartCanvas') {
      await loadMiniCart();
    }
  });

  document.addEventListener('submit', async (event) => {
    const form = event.target;

    if (!form.classList || !form.classList.contains('js-add-to-cart-form')) {
      return;
    }

    event.preventDefault();

    let productId = form.querySelector('[name="product_id"]')?.value;
    if (!productId) {
      productId = form.dataset.productId ?? '';
    }

    if (!productId) {
      console.error('Cart add: product_id non trovato');
      return;
    }

    try {
      const data = await postForm(
        `${window.BASE_URL}/index.php?r=cart/addAjax`,
        { product_id: productId }
      );

      refreshMiniCartFromResponse(data);

      const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(miniCartCanvas);
      offcanvas.show();
      showToast(data.message || 'Prodotto aggiunto al carrello.');
    } catch (error) {
      console.error('Errore aggiunta al carrello:', error);
      showToast(error.message || 'Errore, riprova.');
    }
  });

  document.addEventListener('click', async (event) => {
    const increaseBtn = event.target.closest('.js-cart-increase');
    const decreaseBtn = event.target.closest('.js-cart-decrease');
    const removeBtn = event.target.closest('.js-cart-remove');
    const quickAddBtn = event.target.closest('.js-quick-add');
    const closeCartLink = event.target.closest('[data-close-cart="1"]');

    if (closeCartLink) {
      const offcanvas = bootstrap.Offcanvas.getInstance(miniCartCanvas);
      if (offcanvas) {
        offcanvas.hide();
      }
      return;
    }

    if (increaseBtn || decreaseBtn || removeBtn) {
      const productId = (increaseBtn || decreaseBtn || removeBtn).dataset.productId;
      const currentQty = parseInt((increaseBtn || decreaseBtn)?.dataset.quantity ?? '0', 10);
      const stock = parseInt(increaseBtn?.dataset.stock ?? '0', 10);
      let quantity = currentQty;
      let action = '';

      if (increaseBtn) {
        quantity = currentQty + 1;
        if (quantity > stock) return;
        action = 'update';
      }

      if (decreaseBtn) {
        quantity = currentQty - 1;
        action = 'update';
      }

      if (removeBtn) {
        action = 'remove';
      }

      try {
        const url = `${window.BASE_URL}/index.php?r=cart/${action}Ajax`;
        const data = await postForm(url, { product_id: productId, quantity });

        refreshMiniCartFromResponse(data);
      } catch (error) {
        console.error('Errore aggiornamento carrello:', error);
        showToast(error.message || 'Errore aggiornamento carrello.');
      }
      return;
    }

    if (quickAddBtn) {
      const productId = quickAddBtn.dataset.productId;
      if (!productId) return;

      const originalLabel = quickAddBtn.textContent;
      quickAddBtn.classList.add('loading');
      quickAddBtn.textContent = '…';

      try {
        const data = await postForm(`${window.BASE_URL}/index.php?r=cart/addAjax`, { product_id: productId });

        refreshMiniCartFromResponse(data);
        showToast(data.message || 'Aggiunto al carrello!');
      } catch (error) {
        console.error('Errore quick-add carrello:', error);
        showToast(error.message || 'Errore di rete, riprova.');
      } finally {
        quickAddBtn.textContent = originalLabel;
        quickAddBtn.classList.remove('loading');
      }
    }
  });
});
