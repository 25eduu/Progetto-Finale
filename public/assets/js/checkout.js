/**
 * checkout.js
 * Gestisce la selezione del metodo di pagamento e la validazione
 * del form di checkout (dati personali + indirizzo).
 * Caricato solo nella view checkout/index.php tramite footer.php.
 */
document.addEventListener('DOMContentLoaded', () => {

  const methodInput    = document.getElementById('paymentMethodInput');
  if (!methodInput) return; // non siamo nella pagina checkout

  const totalVal       = parseFloat(document.getElementById('checkoutTotalValue').value)  || 0;
  const walletVal      = parseFloat(document.getElementById('checkoutWalletValue').value) || 0;
  const walletSummary  = document.getElementById('checkoutWalletSummary');
  const walletUsed     = document.getElementById('checkoutWalletUsed');
  const cardRemaining  = document.getElementById('checkoutCardRemaining');
  const displayedTotal = document.getElementById('checkoutDisplayedTotal');
  const submitBtn      = document.getElementById('checkoutSubmitBtn');
  const detail         = document.getElementById('co-method-detail');
  const methodLabels   = document.querySelectorAll('.co-method:not(.co-method--disabled)');

  const fmt = v => '€ ' + v.toLocaleString('it-IT', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

  const detailTexts = {
    card:   'Dopo aver confermato, verrai reindirizzato su <strong>Stripe</strong> per inserire i dati della carta in modo sicuro.',
    wallet: `Verranno scalati <strong>${fmt(totalVal)}</strong> dal saldo del tuo wallet. Nessun reindirizzamento necessario.`,
    mixed:  `Wallet: <strong>- ${fmt(Math.min(walletVal, totalVal))}</strong> · Carta: <strong>${fmt(Math.max(0, totalVal - walletVal))}</strong> via Stripe.`,
  };

  const submitLabels = {
    card:   'Vai al pagamento sicuro',
    wallet: 'Conferma ordine',
    mixed:  'Paga il residuo con carta',
    paypal: 'Continua con PayPal',
  };

  function activate(method) {
    methodInput.value = method;

    // Evidenzia label selezionata
    methodLabels.forEach(l => l.classList.toggle('active', l.dataset.method === method));

    // Wallet summary nel riepilogo
    walletSummary.classList.add('d-none');
    if (displayedTotal) displayedTotal.textContent = fmt(totalVal);

    if (method === 'wallet') {
      walletSummary.classList.remove('d-none');
      walletUsed.textContent    = '- ' + fmt(totalVal);
      cardRemaining.textContent = fmt(0);
    } else if (method === 'mixed') {
      const used = Math.min(walletVal, totalVal);
      walletSummary.classList.remove('d-none');
      walletUsed.textContent    = '- ' + fmt(used);
      cardRemaining.textContent = fmt(Math.max(0, totalVal - used));
    }

    // Testo pulsante submit
    submitBtn.textContent = submitLabels[method] || submitLabels.card;

    // Pannello descrittivo
    if (detailTexts[method]) {
      detail.innerHTML = detailTexts[method];
      detail.classList.add('visible');
    } else {
      detail.classList.remove('visible');
    }
  }

  methodLabels.forEach(label => {
    label.addEventListener('click', () => activate(label.dataset.method));
  });

  activate('card'); // default

  // ── Validazione client-side ───────────────────────────────────────────
  const requiredFields = ['co-name', 'co-email', 'co-address', 'co-city', 'co-zip'];

  document.getElementById('checkoutForm').addEventListener('submit', (e) => {
    let ok = true;

    requiredFields.forEach(id => {
      const el = document.getElementById(id);
      if (!el) return;
      const empty = !el.value.trim();
      el.classList.toggle('is-invalid', empty);
      el.classList.toggle('is-valid',   !empty);
      if (empty) ok = false;
    });

    if (!ok) {
      e.preventDefault();
      const first = document.querySelector('#checkoutForm .is-invalid');
      first?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });

  // Rimuove is-invalid al primo input
  requiredFields.forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', () => {
      if (el.value.trim()) {
        el.classList.remove('is-invalid');
        el.classList.add('is-valid');
      }
    });
  });

});
