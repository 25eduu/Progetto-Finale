  /**
 * search.js
 * Ricerca live nella navbar: mostra un dropdown con i risultati
 * mentre l'utente digita, con debounce per non sovraccaricare il server.
 */
document.addEventListener('DOMContentLoaded', () => {
  const input    = document.getElementById('navSearchInput');
  const dropdown = document.getElementById('navSearchDropdown');
  const btn      = document.getElementById('navSearchBtn');

  if (!input || !dropdown) return;

  let debounceTimer = null;
  let lastQuery     = '';

  // ── Debounce: attende 300ms dopo l'ultimo tasto prima di cercare ──────────

  input.addEventListener('input', () => {
    const query = input.value.trim();

    clearTimeout(debounceTimer);

    if (query.length < 2) {
      closeDropdown();
      return;
    }

    if (query === lastQuery) return;

    debounceTimer = setTimeout(() => fetchResults(query), 300);
  });

  // ── Cerca via AJAX ────────────────────────────────────────────────────────

  async function fetchResults(query) {
    lastQuery = query;

    try {
      const res  = await fetch(`${window.BASE_URL}/index.php?r=products/searchAjax&q=${encodeURIComponent(query)}`);
      const data = await res.json();

      if (data.results.length === 0) {
        renderEmpty(query, data.search_url);
      } else {
        renderResults(data.results, query, data.search_url);
      }
    } catch {
      closeDropdown();
    }
  }

  // ── Render risultati ──────────────────────────────────────────────────────

  function renderResults(results, query, searchUrl) {
    const html = results.map(p => `
      <a href="${p.url}" class="d-flex align-items-center gap-3 px-3 py-2 text-decoration-none text-dark search-result-item">
        <img src="${window.BASE_URL}/assets/${p.image_path}"
             alt="${escapeHtml(p.name)}"
             style="width:44px;height:44px;object-fit:contain;flex-shrink:0"
             class="rounded-2 bg-light p-1">
        <div class="flex-grow-1 overflow-hidden">
          <div class="fw-semibold small text-truncate">${highlight(p.name, query)}</div>
          <div class="text-muted" style="font-size:.75rem">${escapeHtml(p.category_name)}</div>
        </div>
        <div class="text-end flex-shrink-0">
          <div class="fw-semibold small">€ ${p.price}</div>
          ${p.in_stock
            ? '<span class="badge text-bg-success" style="font-size:.65rem">Disponibile</span>'
            : '<span class="badge text-bg-danger" style="font-size:.65rem">Esaurito</span>'}
        </div>
      </a>
    `).join('<hr class="my-0 mx-3">');

    const footer = `
      <div class="px-3 py-2 border-top">
        <a href="${searchUrl}?q=${encodeURIComponent(query)}"
           class="btn btn-sm btn-outline-dark w-100 rounded-3">
          Vedi tutti i risultati per "<strong>${escapeHtml(query)}</strong>"
        </a>
      </div>
    `;

    dropdown.innerHTML = html + footer;
    openDropdown();
  }

  function renderEmpty(query, searchUrl) {
    dropdown.innerHTML = `
      <div class="px-3 py-3 text-center text-muted small">
        Nessun prodotto trovato per "<strong>${escapeHtml(query)}</strong>"
      </div>
      <div class="px-3 pb-2">
        <a href="${searchUrl}" class="btn btn-sm btn-outline-dark w-100 rounded-3">
          Ricerca avanzata
        </a>
      </div>
    `;
    openDropdown();
  }

  // ── Highlight parola cercata nel nome ─────────────────────────────────────

  function highlight(text, query) {
    const escaped = escapeHtml(text);
    const regex   = new RegExp(`(${escapeRegex(query)})`, 'gi');
    return escaped.replace(regex, '<mark class="p-0 bg-warning bg-opacity-50">$1</mark>');
  }

  function escapeHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function escapeRegex(str) {
    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  // ── Apri / chiudi dropdown ────────────────────────────────────────────────

  function openDropdown()  { dropdown.classList.remove('d-none'); }
  function closeDropdown() { dropdown.classList.add('d-none'); lastQuery = ''; }

  // Chiude cliccando fuori
  document.addEventListener('click', (e) => {
    if (!input.contains(e.target) && !dropdown.contains(e.target)) {
      closeDropdown();
    }
  });

  // Chiude premendo Escape
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { closeDropdown(); input.blur(); }
  });

  // Click sul pulsante lente → vai alla pagina ricerca avanzata
  btn.addEventListener('click', (e) => {
    const q = input.value.trim();
    if (q.length >= 2) {
      e.preventDefault();
      window.location.href = `${window.BASE_URL}/index.php?r=products/search`;
    }
  });

  // Hover sugli item del dropdown
  dropdown.addEventListener('mouseover', (e) => {
    const item = e.target.closest('.search-result-item');
    dropdown.querySelectorAll('.search-result-item').forEach(el => el.classList.remove('bg-light'));
    if (item) item.classList.add('bg-light');
  });
});
