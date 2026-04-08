document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.js-otp-form');
  if (!form) return;

  const boxes = Array.from(form.querySelectorAll('.otp-box'));
  const hiddenInput = form.querySelector('#otp_code');

  function updateHiddenInput() {
    hiddenInput.value = boxes.map(box => box.value).join('');
  }

  boxes.forEach((box, index) => {
    box.addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '').slice(0, 1);
      updateHiddenInput();

      if (this.value && index < boxes.length - 1) {
        boxes[index + 1].focus();
      }
    });

    box.addEventListener('keydown', function (e) {
      if (e.key === 'Backspace' && !this.value && index > 0) {
        boxes[index - 1].focus();
      }

      if (e.key === 'ArrowLeft' && index > 0) {
        boxes[index - 1].focus();
      }

      if (e.key === 'ArrowRight' && index < boxes.length - 1) {
        boxes[index + 1].focus();
      }
    });

    box.addEventListener('paste', function (e) {
      e.preventDefault();

      const pasted = (e.clipboardData || window.clipboardData)
        .getData('text')
        .replace(/\D/g, '')
        .slice(0, 6);

      if (!pasted) return;

      pasted.split('').forEach((char, i) => {
        if (boxes[i]) boxes[i].value = char;
      });

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
});

document.addEventListener('DOMContentLoaded', () => {
  const paymentMethodInput = document.getElementById('paymentMethodInput');
  const paymentOptions = document.querySelectorAll('.payment-option');
  const paymentPanels = document.querySelectorAll('.payment-panel');

  const totalInput = document.getElementById('checkoutTotalValue');
  const walletInput = document.getElementById('checkoutWalletValue');
  const walletSummary = document.getElementById('checkoutWalletSummary');
  const walletUsed = document.getElementById('checkoutWalletUsed');
  const cardRemaining = document.getElementById('checkoutCardRemaining');
  const displayedTotal = document.getElementById('checkoutDisplayedTotal');

  if (!paymentMethodInput || paymentOptions.length === 0) return;

  const total = parseFloat(totalInput?.value || '0') || 0;
  const wallet = parseFloat(walletInput?.value || '0') || 0;

  function formatEuro(value) {
    return '€ ' + value.toLocaleString('it-IT', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  function updateSummary(method) {
    if (!walletSummary || !walletUsed || !cardRemaining || !displayedTotal) return;

    walletSummary.classList.add('d-none');
    displayedTotal.textContent = formatEuro(total);

    if (method === 'mixed') {
      const used = Math.min(wallet, total);
      const remaining = Math.max(total - used, 0);

      walletSummary.classList.remove('d-none');
      walletUsed.textContent = '- ' + formatEuro(used);
      cardRemaining.textContent = formatEuro(remaining);
    } else if (method === 'wallet') {
      walletSummary.classList.remove('d-none');
      walletUsed.textContent = '- ' + formatEuro(total);
      cardRemaining.textContent = formatEuro(0);
    }
  }

  function activatePaymentMethod(method) {
    paymentOptions.forEach((option) => {
      option.classList.toggle('active', option.dataset.method === method);
    });

    paymentPanels.forEach((panel) => {
      panel.classList.toggle('active', panel.dataset.panel === method);
    });

    paymentMethodInput.value = method;
    updateSummary(method);
  }

  paymentOptions.forEach((option) => {
    option.addEventListener('click', () => {
      activatePaymentMethod(option.dataset.method);
    });
  });

  activatePaymentMethod(paymentMethodInput.value || 'card');
});