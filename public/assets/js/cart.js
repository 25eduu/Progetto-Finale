document.addEventListener('DOMContentLoaded', () => {
    const miniCartContent = document.getElementById('miniCartContent');
    const cartBadge = document.getElementById('cartBadge');
    const miniCartCanvas = document.getElementById('miniCartCanvas');
  
    async function postForm(url, data) {
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
        },
        body: new URLSearchParams(data)
      });
  
      return response.json();
    }
  
    async function loadMiniCart() {
      if (!miniCartContent) return;
  
      const response = await fetch(`${window.BASE_URL}/index.php?r=cart/sidebar`, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
  
      const html = await response.text();
      miniCartContent.innerHTML = html;
    }
  
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
  
      updateBadge(data.cartCount);
  
      if (miniCartContent && data.miniCartHtml) {
        miniCartContent.innerHTML = data.miniCartHtml;
      }
    }
  
    document.addEventListener('show.bs.offcanvas', async (event) => {
      if (event.target && event.target.id === 'miniCartCanvas') {
        await loadMiniCart();
      }
    });
  
    document.addEventListener('submit', async (event) => {
      const form = event.target;
  
      if (form.classList.contains('js-add-to-cart-form')) {
        event.preventDefault();
  
        const productId = form.querySelector('[name="product_id"]').value;
  
        const data = await postForm(
          `${window.BASE_URL}/index.php?r=cart/addAjax`,
          { product_id: productId }
        );
  
        refreshMiniCartFromResponse(data);
  
        const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(miniCartCanvas);
        offcanvas.show();
      }
    });
  
    document.addEventListener('click', async (event) => {
      const increaseBtn = event.target.closest('.js-cart-increase');
      const decreaseBtn = event.target.closest('.js-cart-decrease');
      const removeBtn = event.target.closest('.js-cart-remove');
      const closeCartLink = event.target.closest('[data-close-cart="1"]');
  
      if (closeCartLink) {
        const offcanvas = bootstrap.Offcanvas.getInstance(miniCartCanvas);
        if (offcanvas) {
          offcanvas.hide();
        }
        return;
      }
  
      if (increaseBtn) {
        const productId = increaseBtn.dataset.productId;
        const currentQty = parseInt(increaseBtn.dataset.quantity, 10);
        const stock = parseInt(increaseBtn.dataset.stock, 10);
  
        const nextQty = currentQty + 1;
        if (nextQty > stock) return;
  
        const data = await postForm(
          `${window.BASE_URL}/index.php?r=cart/updateAjax`,
          { product_id: productId, quantity: nextQty }
        );
  
        refreshMiniCartFromResponse(data);
        return;
      }
  
      if (decreaseBtn) {
        const productId = decreaseBtn.dataset.productId;
        const currentQty = parseInt(decreaseBtn.dataset.quantity, 10);
        const nextQty = currentQty - 1;
  
        const data = await postForm(
          `${window.BASE_URL}/index.php?r=cart/updateAjax`,
          { product_id: productId, quantity: nextQty }
        );
  
        refreshMiniCartFromResponse(data);
        return;
      }
  
      if (removeBtn) {
        const productId = removeBtn.dataset.productId;
  
        const data = await postForm(
          `${window.BASE_URL}/index.php?r=cart/removeAjax`,
          { product_id: productId }
        );
  
        refreshMiniCartFromResponse(data);
      }
    });
  });
(function () {
    const BASE = window.BASE_URL ?? '';
  
    /* ── Toast helper ── */
    const $toast = document.getElementById('ts-toast');
    let toastTimer;
    function showToast(msg) {
      clearTimeout(toastTimer);
      $toast.textContent = msg;
      $toast.classList.add('visible');
      toastTimer = setTimeout(() => $toast.classList.remove('visible'), 2400);
    }
  
    /* ── Quick-add click ── */
    document.addEventListener('click', function (e) {
      const btn = e.target.closest('.js-quick-add');
      if (!btn) return;
  
      const productId = btn.dataset.productId;
      if (!productId) return;
  
      /* Feedback visivo immediato */
      btn.classList.add('loading');
      const originalLabel = btn.textContent;
      btn.textContent = '…';
  
      const fd = new FormData();
      fd.append('product_id', productId);
  
      fetch(BASE + '/index.php?r=cart/addAjax', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          /* Aggiorna badge navbar */
          const badge = document.getElementById('cartBadge');
          if (badge) {
            badge.textContent = data.cartCount;
            badge.classList.remove('d-none');
          }
  
          /* Aggiorna contenuto mini-cart (offcanvas) */
          const miniContent = document.getElementById('miniCartContent');
          if (miniContent && data.miniCartHtml) {
            miniContent.innerHTML = data.miniCartHtml;
          }
  
          /* Feedback check momentaneo sul bottone */
          btn.textContent = '✓';
          setTimeout(() => {
            btn.textContent = originalLabel;
            btn.classList.remove('loading');
          }, 900);
  
          showToast('Aggiunto al carrello!');
        } else {
          btn.textContent = originalLabel;
          btn.classList.remove('loading');
          showToast(data.message ?? 'Errore, riprova.');
        }
      })
      .catch(() => {
        btn.textContent = originalLabel;
        btn.classList.remove('loading');
        showToast('Errore di rete, riprova.');
      });
    });
  })();