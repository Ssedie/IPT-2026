// farmer-dashboard/app.js
const API = 'api';

function getUser() { return JSON.parse(localStorage.getItem('user') || 'null'); }
function farmerId() { return getUser()?.id; }

async function apiFetch(endpoint, options = {}) {
  try {
    const res = await fetch(`${API}/${endpoint}`, {
      headers: { 'Content-Type': 'application/json', ...(options.headers||{}) },
      ...options
    });
    return await res.json();
  } catch(e) { return { error: 'Network error' }; }
}

function showToast(msg, type = 'success') {
  let t = document.getElementById('toast');
  if (!t) {
    t = document.createElement('div'); t.id = 'toast';
    t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;padding:12px 22px;border-radius:12px;font-size:0.875rem;font-family:DM Sans,sans-serif;font-weight:600;box-shadow:0 4px 20px rgba(0,0,0,0.15);transition:opacity 0.3s;max-width:320px;';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.style.background = type==='success'?'#2d5a1b':type==='error'?'#d94f4f':'#e8a020';
  t.style.color = '#fff'; t.style.opacity = '1';
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.style.opacity='0', 3000);
}

function statusBadge(status) {
  const map = {
    pending:   ['badge-pending',   'Pending'],
    shipped:   ['badge-packed',    'Shipped'],
    delivered: ['badge-delivered', 'Delivered ✓'],
    cancelled: ['badge-cancelled', 'Cancelled'],
    disputed:  ['badge-disputed',  'Disputed'],
  };
  const [cls, label] = map[status] || ['badge-pending', status];
  return `<span class="badge ${cls}">${label}</span>`;
}

function formatDate(dt) {
  if (!dt) return '';
  return new Date(dt).toLocaleDateString('en-PH', { year:'numeric', month:'short', day:'numeric' });
}

function formatMoney(n) {
  return '₱' + parseFloat(n||0).toLocaleString('en-PH', { minimumFractionDigits:2 });
}

function timeAgo(dt) {
  if (!dt) return '';
  const diff = (Date.now() - new Date(dt)) / 1000;
  if (diff < 60)    return 'just now';
  if (diff < 3600)  return Math.floor(diff/60)  + 'm ago';
  if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
  return formatDate(dt);
}

function navTo(page) {
  if (window.parent && window.parent.navigateTo) {
    window.parent.navigateTo('farmer-dashboard/' + page);
  }
}

function getInitials(name) {
  return (name||'').split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase() || '?';
}