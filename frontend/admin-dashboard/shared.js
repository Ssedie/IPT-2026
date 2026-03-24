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

  initGlobalSearch(); // ← wired here, runs on every page automatically
}

function logout() {
  localStorage.removeItem('user');
  window.location.href = '../login.html';
}

// ─── Central fetch helper ──────────────────────────────────────────────────
async function apiFetch(endpoint, method = 'GET', body = null) {
  const base = '../';
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

// ─── Search Page Config ────────────────────────────────────────────────────
// Controls what each page searches, how results are labelled, and where
// clicking a result navigates. Add/remove entries as your API grows.
const SEARCH_PAGE_CONFIG = {
  users: {
    type:           'table',
    searchEndpoint: 'admin-dashboard/api/users.php',
    searchFields:   ['name', 'email', 'role'],
    resultLabel:    u => u.name,
    resultSub:      u => u.email,
    resultIcon:     '👤',
    resultHref:     u => `users.html?highlight=${u.id}`,
  },
  orders: {
    type:           'table',
    searchEndpoint: 'admin-dashboard/api/orders.php',
    searchFields:   ['buyer_name', 'id'],
    resultLabel:    o => `#ORD-${o.id}`,
    resultSub:      o => o.buyer_name,
    resultIcon:     '📦',
    resultHref:     o => `orders.html?highlight=${o.id}`,
  },
  listings: {
    type:           'table',
    searchEndpoint: 'admin-dashboard/api/listings.php',
    searchFields:   ['product_name', 'farmer_name', 'category'],
    resultLabel:    l => l.product_name,
    resultSub:      l => `by ${l.farmer_name}`,
    resultIcon:     '📋',
    resultHref:     l => `listings.html?highlight=${l.id}`,
  },
  applications: {
    type:           'table',
    searchEndpoint: 'admin-dashboard/api/applications.php',
    searchFields:   ['full_name', 'barangay', 'city'],
    resultLabel:    a => a.full_name,
    resultSub:      a => `${a.barangay || ''} ${a.city || ''}`.trim(),
    resultIcon:     '🌱',
    resultHref:     a => `farmer_applications.html?highlight=${a.id}`,
  },
  transactions: {
    type:           'table',
    searchEndpoint: 'admin-dashboard/api/transactions.php',
    searchFields:   ['reference_no', 'buyer_name', 'seller_name'],
    resultLabel:    t => t.reference_no || `TXN-${t.id}`,
    resultSub:      t => `${t.buyer_name} → ${t.seller_name}`,
    resultIcon:     '💰',
    resultHref:     t => `transactions.html?highlight=${t.id}`,
  },
  disputes: {
    type:           'table',
    searchEndpoint: 'admin-dashboard/api/disputes.php',
    searchFields:   ['reason', 'buyer_name', 'seller_name'],
    resultLabel:    d => d.reason,
    resultSub:      d => `${d.buyer_name} vs. ${d.seller_name}`,
    resultIcon:     '⚖️',
    resultHref:     d => `disputes.html?highlight=${d.id}`,
  },
  audit: {
    type:           'table',
    searchEndpoint: 'admin-dashboard/api/audit_log.php',
    searchFields:   ['action', 'admin_name', 'target'],
    resultLabel:    a => a.action,
    resultSub:      a => `by ${a.admin_name}`,
    resultIcon:     '📝',
    resultHref:     a => `audit_log.html?highlight=${a.id}`,
  },
  // analytics has no rows — search just navigates to the page
  analytics: {
    type:           'section',
    searchEndpoint: null,
    resultLabel:    () => 'Analytics & Reports',
    resultSub:      () => 'View full analytics dashboard',
    resultIcon:     '📈',
    resultHref:     () => 'analytics_and_reports.html',
  },
};

const SEARCH_CATEGORY_LABELS = {
  users:        'Users',
  orders:       'Orders',
  listings:     'Listings',
  applications: 'Farmer Applications',
  transactions: 'Transactions',
  disputes:     'Disputes',
  audit:        'Audit Log',
  analytics:    'Analytics',
};

// ─── Global Search ─────────────────────────────────────────────────────────
function initGlobalSearch() {
  const searchBox     = document.querySelector('.search-box input');
  const searchWrapper = document.querySelector('.search-box');
  if (!searchBox || !searchWrapper) return;

  // Inject styles once
  if (!document.getElementById('_search-styles')) {
    const s = document.createElement('style');
    s.id = '_search-styles';
    s.textContent = `
      #_search-dropdown {
        position: absolute;
        top: calc(100% + 10px);
        left: 0; right: 0;
        background: #fff;
        border: 1.5px solid #e0e0e0;
        border-radius: 14px;
        box-shadow: 0 12px 40px rgba(0,0,0,0.13);
        z-index: 99999;
        max-height: 400px;
        overflow-y: auto;
        display: none;
        animation: _sdropIn 0.18s cubic-bezier(.4,0,.2,1);
        scrollbar-width: thin;
      }
      @keyframes _sdropIn {
        from { opacity:0; transform:translateY(-6px); }
        to   { opacity:1; transform:translateY(0);    }
      }
      .s-cat-header {
        padding: 8px 16px 3px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .09em;
        color: #aaa;
        text-transform: uppercase;
        border-top: 1px solid #f0f0f0;
      }
      .s-cat-header:first-child { border-top: none; }
      .s-result-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        cursor: pointer;
        border-radius: 8px;
        margin: 2px 6px;
        transition: background 0.13s;
      }
      .s-result-item:hover,
      .s-result-item.active { background: #e8f5e9; }
      .s-result-icon {
        font-size: 18px;
        flex-shrink: 0;
        width: 32px; height: 32px;
        display: flex; align-items: center; justify-content: center;
        background: #f5f5f5;
        border-radius: 8px;
      }
      .s-result-label { font-weight: 500; font-size: 13.5px; line-height: 1.3; }
      .s-result-sub   { font-size: 11.5px; color: #888; margin-top: 1px; }
      .s-footer {
        padding: 10px 16px;
        border-top: 1px solid #f0f0f0;
        font-size: 11.5px;
        color: #aaa;
        text-align: center;
      }
      .s-empty, .s-loading {
        padding: 24px 16px;
        text-align: center;
        color: #bbb;
        font-size: 13px;
      }
      mark.sh {
        background: #fff8c5;
        border-radius: 3px;
        padding: 0 2px;
        font-style: normal;
      }

      /* ── Row / card highlight after navigation ── */
      @keyframes _highlightPulse {
        0%   { box-shadow: 0 0 0 0   rgba(232,160,32,0.55); }
        50%  { box-shadow: 0 0 0 8px rgba(232,160,32,0);    }
        100% { box-shadow: 0 0 0 0   rgba(232,160,32,0);    }
      }
      .search-hl {
        background:  #fff8c5 !important;
        outline:     2px solid #e8a020 !important;
        border-radius: 6px;
        animation:   _highlightPulse 1s ease 0.3s 2;
        transition:  background 0.6s ease 3s, outline 0.6s ease 3s;
      }
      .search-hl.hl-fade {
        background:  transparent !important;
        outline:     2px solid transparent !important;
      }
    `;
    document.head.appendChild(s);
  }

  // Build dropdown container
  const dropdown = document.createElement('div');
  dropdown.id = '_search-dropdown';
  searchWrapper.style.position = 'relative';
  searchWrapper.appendChild(dropdown);

  let debounceTimer = null;

  // ── Input handler
  searchBox.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    const q = searchBox.value.trim();
    if (q.length < 2) { close(); return; }
    debounceTimer = setTimeout(() => runSearch(q), 280);
  });

  // ── Re-show on focus if query still valid
  searchBox.addEventListener('focus', () => {
    if (searchBox.value.trim().length >= 2) dropdown.style.display = 'block';
  });

  // ── Close on outside click
  document.addEventListener('click', e => {
    if (!searchWrapper.contains(e.target)) close();
  });

  // ── Keyboard navigation
  searchBox.addEventListener('keydown', e => {
    const items = [...dropdown.querySelectorAll('.s-result-item')];
    const idx   = items.findIndex(i => i.classList.contains('active'));

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      const next = idx < items.length - 1 ? idx + 1 : 0;
      items.forEach(i => i.classList.remove('active'));
      items[next]?.classList.add('active');
      items[next]?.scrollIntoView({ block: 'nearest' });

    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      const prev = idx > 0 ? idx - 1 : items.length - 1;
      items.forEach(i => i.classList.remove('active'));
      items[prev]?.classList.add('active');
      items[prev]?.scrollIntoView({ block: 'nearest' });

    } else if (e.key === 'Enter') {
      dropdown.querySelector('.s-result-item.active')?.click();

    } else if (e.key === 'Escape') {
      close(); searchBox.blur();
    }
  });

  function close() { dropdown.style.display = 'none'; }

  // ── Run search across all configured endpoints
  async function runSearch(q) {
    dropdown.innerHTML = `<div class="s-loading">🔍 Searching…</div>`;
    dropdown.style.display = 'block';

    const ql      = q.toLowerCase();
    const grouped = {};

    const numericQuery = ql.replace(/[^0-9]/g, '');

    const fetches = Object.entries(SEARCH_PAGE_CONFIG)
      .filter(([, cfg]) => cfg.searchEndpoint)
      .map(async ([key, cfg]) => {
        try {
          const data    = await apiFetch(cfg.searchEndpoint);
          const matches = data
            .filter(record =>
              cfg.searchFields.some(field =>{
                const val = String(record[field] ?? '').toLowerCase();
                
                // 1. Check for standard text match (e.g. "Ana Lopez")
                if (val.includes(ql)) return true;
                
                // 2. Check for numeric ID match (e.g. user typed "#ORD-39", we match "39")
                if (numericQuery.length > 0 && val === numericQuery) return true;

                return false;
              })
            )
            .slice(0, 4);
          if (matches.length) grouped[key] = { cfg, matches };
        } catch (_) { /* skip failed endpoints silently */ }
      });

    await Promise.allSettled(fetches);
    renderDropdown(grouped, q);
  }

  function renderDropdown(grouped, q) {
    const keys = Object.keys(grouped);
    if (!keys.length) {
      dropdown.innerHTML = `<div class="s-empty">😕 No results for "<strong>${esc(q)}</strong>"</div>`;
      return;
    }

    let html  = '';
    let total = 0;

    for (const key of keys) {
      const { cfg, matches } = grouped[key];
      html += `<div class="s-cat-header">${SEARCH_CATEGORY_LABELS[key] || key}</div>`;
      matches.forEach(record => {
        total++;
        html += `
          <div class="s-result-item" onclick="location.href='${cfg.resultHref(record)}'">
            <div class="s-result-icon">${cfg.resultIcon}</div>
            <div>
              <div class="s-result-label">${hl(esc(cfg.resultLabel(record) || ''), q)}</div>
              <div class="s-result-sub">${esc(cfg.resultSub(record) || '')}</div>
            </div>
          </div>`;
      });
    }

    html += `<div class="s-footer">
      ⌨️ Arrow keys · Enter to open · Esc to close &nbsp;·&nbsp;
      <strong>${total}</strong> result${total !== 1 ? 's' : ''}
    </div>`;

    dropdown.innerHTML = html;
    dropdown.style.display = 'block';
  }

  // Highlight matching text
  function hl(text, q) {
    const safe = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return text.replace(new RegExp(`(${safe})`, 'gi'), '<mark class="sh">$1</mark>');
  }

  // Escape HTML to prevent XSS in result labels
  function esc(str) {
    return String(str)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
}

// ─── Highlight + Auto-Scroll after navigation ──────────────────────────────
//
// Call this at the END of your data-loading function on each page,
// after tbody.innerHTML or list.innerHTML has been set.
//
// Table rows:  applySearchHighlight('your-tbody-id')
// Card lists:  applySearchHighlight('your-list-id', 'cards')
//
// Each <tr> or card <div> must have:  data-id="${record.id}"
//
function applySearchHighlight(containerId, type = 'table') {
  const highlightId = new URLSearchParams(window.location.search).get('highlight');
  if (!highlightId) return;

  const selector = type === 'table' ? 'tr[data-id]' : '[data-id]';

  function attempt(tries = 0) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const items = container.querySelectorAll(selector);

    // Rows not rendered yet — retry up to 2.5 s
    if (!items.length && tries < 25) {
      return setTimeout(() => attempt(tries + 1), 100);
    }

    const target = [...items].find(el => String(el.dataset.id) === String(highlightId));
    if (!target) return;

    // Scroll into view, accounting for fixed topbar
    const topbarH = document.querySelector('.topbar')?.offsetHeight ?? 72;
    const top = target.getBoundingClientRect().top + window.scrollY - topbarH - 24;
    window.scrollTo({ top, behavior: 'smooth' });

    // Apply highlight
    target.classList.add('search-hl');

    // Fade out after 3 s
    setTimeout(() => {
      target.classList.add('hl-fade');
      setTimeout(() => target.classList.remove('search-hl', 'hl-fade'), 700);
    }, 3000);
  }

  attempt();
}

// ─── Reusable UI helpers ───────────────────────────────────────────────────
function statusBadge(status) {
  const map = {
    active:'green', approved:'green', delivered:'green',
    completed:'green', resolved:'green',
    pending:'yellow', open:'yellow',
    shipped:'blue', in_review:'blue', review:'blue', payment:'blue', buyer:'blue',
    suspended:'red', rejected:'red', disputed:'red', urgent:'red', flagged:'red', refund:'red',
    removed:'gray', cancelled:'gray', fee:'gray',
    farmer:'green', admin:'purple', payout:'purple',
  };
  const color = map[status?.toLowerCase()] || 'gray';
  const label = status
    ? status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ')
    : '—';
  return `<span class="badge ${color}">${label}</span>`;
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleDateString('en-PH', {
    month: 'short', day: 'numeric', year: 'numeric',
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