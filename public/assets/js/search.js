/**
 * search.js
 * Ricerca live nella navbar: dropdown con risultati mentre si digita.
 * Debounce 300ms per non sovraccaricare il server.
 */
document.addEventListener('DOMContentLoaded', () => {
  const input    = document.getElementById('navSearchInput');
  const dropdown = document.getElementById('navSearchDropdown');
  const btn      = document.getElementById('navSearchBtn');

  if (!input || !dropdown || !btn) return;

  const BASE        = window.BASE_URL ?? '';
  let debounceTimer = null;
  let lastQuery     = '';

  // ── Debounce: attende 300ms dopo l'ultimo tasto ───────────────────────────

  input.addEventListener('input', () => {
    const query = input.value.trim();
    clearTimeout(debounceTimer);

    if (query.length < 2) { closeDropdown(); return; }
    if (query === lastQuery) return;

    debounceTimer = setTimeout(() => fetchResults(query), 300);
  });

  // ── Click sul bottone lente → vai alla pagina ricerca avanzata ────────────

  btn.addEventListener('click', () => {
    const q = input.value.trim();
    window.location.href = q.length >= 2
      ? `${BASE}/index.php?r=products/search`
      : `${BASE}/index.php?r=products/search`;
  });

  // Invio da tastiera
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      btn.click();
    }
    if (e.key === 'Escape') {
      closeDropdown();
      input.blur();
    }
  });

  // ── Fetch AJAX ────────────────────────────────────────────────────────────

  async function fetchResults(query) {
    lastQuery = query;
    try {
      const res  = await fetch(`${BASE}/index.php?r=products/searchAjax&q=${encodeURIComponent(query)}`);
      const data = await res.json();

      data.results.length === 0
        ? renderEmpty(query, data.search_url)
        : renderResults(data.results, query, data.search_url);
    } catch {
      closeDropdown();
    }
  }

  // ── Render ────────────────────────────────────────────────────────────────

  function renderResults(results, query, searchUrl) {
    const items = results.map(p => `
      <a href="${p.url}"
         class="d-flex align-items-center gap-3 px-3 py-2 text-decoration-none text-dark search-result-item">
        <img src="${BASE}/assets/${p.image_path}"
             alt="${escapeHtml(p.name)}"
             style="width:42px;height:42px;object-fit:contain;flex-shrink:0"
             class="rounded-2 bg-light p-1">
        <div class="flex-grow-1 overflow-hidden">
          <div class="fw-semibold text-truncate" style="font-size:.875rem">
            ${highlight(p.name, query)}
          </div>
          <div class="text-muted" style="font-size:.75rem">${escapeHtml(p.category_name)}</div>
        </div>
        <div class="text-end flex-shrink-0">
          <div class="fw-semibold" style="font-size:.875rem">€ ${p.price}</div>
          ${p.in_stock
            ? '<span class="badge text-bg-success" style="font-size:.65rem">Disponibile</span>'
            : '<span class="badge text-bg-danger" style="font-size:.65rem">Esaurito</span>'}
        </div>
      </a>
    `).join('<div class="mx-3" style="height:1px;background:#f1f5f9"></div>');

    const footer = `
      <div class="px-3 py-2 border-top">
        <a href="${searchUrl}"
           class="btn btn-sm btn-outline-dark w-100 rounded-pill" style="font-size:.8rem">
          Cerca "<strong>${escapeHtml(query)}</strong>" — ricerca avanzata →
        </a>
      </div>`;

    dropdown.innerHTML = items + footer;
    openDropdown();
  }

  function renderEmpty(query, searchUrl) {
    dropdown.innerHTML = `
      <div class="px-3 py-3 text-center text-muted" style="font-size:.875rem">
        Nessun risultato per "<strong>${escapeHtml(query)}</strong>"
      </div>
      <div class="px-3 pb-2">
        <a href="${searchUrl}"
           class="btn btn-sm btn-outline-dark w-100 rounded-pill" style="font-size:.8rem">
          Prova la ricerca avanzata →
        </a>
      </div>`;
    openDropdown();
  }

  // ── Utility ───────────────────────────────────────────────────────────────

  function highlight(text, query) {
    const safe  = escapeHtml(text);
    const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
    return safe.replace(regex, '<mark class="p-0 rounded-1" style="background:#fef08a">$1</mark>');
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function escapeRegex(str) {
    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  function openDropdown()  { dropdown.classList.remove('d-none'); }
  function closeDropdown() { dropdown.classList.add('d-none'); lastQuery = ''; }

  // Chiude cliccando fuori
  document.addEventListener('click', (e) => {
    if (!input.contains(e.target) && !dropdown.contains(e.target) && !btn.contains(e.target)) {
      closeDropdown();
    }
  });
});
