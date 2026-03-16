// shared.js — place inside admin-dashboard/

function initAdmin(activeLabel) {
  const currentUser = JSON.parse(localStorage.getItem('user') || 'null');
  if (!currentUser) { window.location.href = '../login.html'; return; }
  if (currentUser.role !== 'admin') { window.location.href = '../login.html'; return; }

  const nameEl  = document.getElementById('admin-name');
  const emailEl = document.getElementById('admin-email');
  if (nameEl)  nameEl.textContent  = currentUser.full_name || 'Admin';
  if (emailEl) emailEl.textContent = currentUser.email     || '';

  document.querySelectorAll('.nav-item[data-page]').forEach(item => {
    item.classList.remove('active');
    if (item.dataset.page === activeLabel) item.classList.add('active');
  });
}

function logout() {
  localStorage.removeItem('user');
  window.location.href = '../login.html';
}

// ─── Central fetch helper ──────────────────────────────────────────────────
// Usage: apiFetch('api/users.php?role=farmer')
//        apiFetch('api/users.php', 'POST', { action:'suspend', user_id: 5 })
// The base path goes UP one level because admin-dashboard/ is inside frontend/
async function apiFetch(endpoint, method = 'GET', body = null) {
  const base = '../';          // frontend/ root where all .php files live
  const url  = base + endpoint;

  const opts = {
    method,
    headers: { 'Content-Type': 'application/json' },
  };
  if (body) opts.body = JSON.stringify(body);

  const res  = await fetch(url, opts);
  const data = await res.json();

  if (!res.ok) throw new Error(data.error || 'API error');
  return data;
}

// ─── Reusable UI helpers ───────────────────────────────────────────────────
function statusBadge(status) {
  const map = {
    active:    'green',
    approved:  'green',
    delivered: 'green',
    completed: 'green',
    resolved:  'green',
    pending:   'yellow',
    open:      'yellow',
    shipped:   'blue',
    in_review: 'blue',
    review:    'blue',
    suspended: 'red',
    rejected:  'red',
    disputed:  'red',
    urgent:    'red',
    flagged:   'red',
    removed:   'gray',
    cancelled: 'gray',
    farmer:    'green',
    buyer:     'blue',
    admin:     'purple',
    payment:   'blue',
    refund:    'red',
    payout:    'purple',
    fee:       'gray',
  };
  const color = map[status?.toLowerCase()] || 'gray';
  const label = status ? status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ') : '—';
  return `<span class="badge ${color}">${label}</span>`;
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleDateString('en-PH', {
    month: 'short', day: 'numeric', year: 'numeric'
  });
}

function formatCurrency(amount) {
  return '₱' + Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function showTableError(tbodyId, colSpan, message) {
  document.getElementById(tbodyId).innerHTML =
    `<tr><td colspan="${colSpan}" style="text-align:center;padding:32px;color:var(--text-light)">${message}</td></tr>`;
}

function showTableLoading(tbodyId, colSpan) {
  document.getElementById(tbodyId).innerHTML =
    `<tr><td colspan="${colSpan}" style="text-align:center;padding:32px;color:var(--text-light)">Loading...</td></tr>`;
}