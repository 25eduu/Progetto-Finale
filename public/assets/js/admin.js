/**
 * admin.js — script pannello amministrativo
 * Caricato solo nelle view admin (vedi layouts/header.php)
 */
function initRevenueChart(rawData) {
  const ctx = document.getElementById('revenueChart');
  if (!ctx) return;

  const today  = new Date();
  const labels = [];
  const values = [];

  for (let i = 6; i >= 0; i--) {
    const d = new Date(today);
    d.setDate(d.getDate() - i);
    const key = d.toISOString().slice(0, 10);
    labels.push(d.toLocaleDateString('it-IT', { weekday: 'short', day: 'numeric' }));
    const found = rawData.find(r => r.day === key);
    values.push(found ? parseFloat(found.total) : 0);
  }

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Revenue (€)',
        data: values,
        backgroundColor: 'rgba(13,110,253,.15)',
        borderColor:     'rgba(13,110,253,.8)',
        borderWidth: 2,
        borderRadius: 6,
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { callback: v => '€ ' + v.toLocaleString('it-IT') }
        }
      }
    }
  });
}
