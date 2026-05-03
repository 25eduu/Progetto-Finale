document.addEventListener('DOMContentLoaded', () => {

  // ── OTP boxes ──────────────────────────────────────────────────────────────
  const form = document.querySelector('.js-otp-form');
  if (form) {
    const boxes       = Array.from(form.querySelectorAll('.otp-box'));
    const hiddenInput = form.querySelector('#otp_code');

    function updateHiddenInput() {
      hiddenInput.value = boxes.map(b => b.value).join('');
    }

    boxes.forEach((box, index) => {
      box.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 1);
        updateHiddenInput();
        if (this.value && index < boxes.length - 1) boxes[index + 1].focus();
      });

      box.addEventListener('keydown', function (e) {
        if (e.key === 'Backspace' && !this.value && index > 0)          boxes[index - 1].focus();
        if (e.key === 'ArrowLeft'  && index > 0)                        boxes[index - 1].focus();
        if (e.key === 'ArrowRight' && index < boxes.length - 1)         boxes[index + 1].focus();
      });

      box.addEventListener('paste', function (e) {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData)
          .getData('text').replace(/\D/g, '').slice(0, 6);
        if (!pasted) return;
        pasted.split('').forEach((char, i) => { if (boxes[i]) boxes[i].value = char; });
        updateHiddenInput();
        boxes[Math.min(pasted.length, boxes.length - 1)].focus();
      });
    });

    form.addEventListener('submit', (e) => {
      updateHiddenInput();
      if (hiddenInput.value.length !== 6) {
        e.preventDefault();
        alert('Inserisci tutte e 6 le cifre del codice.');
      }
    });

    boxes[0]?.focus();
  }

  // ── Checkout payment options ────────────────────────────────────────────────
  const paymentMethodInput = document.getElementById('paymentMethodInput');
  const options            = document.querySelectorAll('.payment-option');
  const panels             = document.querySelectorAll('.payment-panel');

  if (paymentMethodInput && options.length > 0) {
    const total  = parseFloat(document.getElementById('checkoutTotalValue')?.value  || '0') || 0;
    const wallet = parseFloat(document.getElementById('checkoutWalletValue')?.value || '0') || 0;

    const walletSummary  = document.getElementById('checkoutWalletSummary');
    const walletUsed     = document.getElementById('checkoutWalletUsed');
    const cardRemaining  = document.getElementById('checkoutCardRemaining');
    const displayedTotal = document.getElementById('checkoutDisplayedTotal');
    const submitBtn      = document.getElementById('checkoutSubmitBtn');

    const formatEuro = (value) =>
      '€ ' + value.toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function updateSummary(method) {
      if (!walletSummary || !submitBtn) return;

      walletSummary.classList.add('d-none');
      if (displayedTotal) displayedTotal.textContent = formatEuro(total);

      if (method === 'wallet') {
        walletSummary.classList.remove('d-none');
        walletUsed.textContent    = '- ' + formatEuro(total);
        cardRemaining.textContent = formatEuro(0);
        submitBtn.textContent     = 'Conferma ordine';
        return;
      }

      if (method === 'mixed') {
        const used      = Math.min(wallet, total);
        const remaining = Math.max(0, total - used);
        walletSummary.classList.remove('d-none');
        walletUsed.textContent    = '- ' + formatEuro(used);
        cardRemaining.textContent = formatEuro(remaining);
        submitBtn.textContent     = 'Paga il residuo con carta';
        return;
      }

      if (method === 'paypal') {
        submitBtn.textContent = 'Continua con PayPal';
        return;
      }

      submitBtn.textContent = 'Vai al pagamento sicuro';
    }

    function activateMethod(method) {
      options.forEach(o => o.classList.toggle('active', o.dataset.method === method));
      panels.forEach(p  => p.classList.toggle('active',  p.dataset.panel  === method));
      paymentMethodInput.value = method;
      updateSummary(method);
    }

    options.forEach(o => o.addEventListener('click', () => activateMethod(o.dataset.method)));

    activateMethod(paymentMethodInput.value || 'card');
  }

});
