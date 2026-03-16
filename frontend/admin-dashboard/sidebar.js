// Inject sidebar into any page — call injectSidebar() before initAdmin()
function injectSidebar() {
  const html = `
  <aside class="sidebar">
    <div class="sidebar-logo"><div class="logo-icon">🌾</div><div class="logo-text"><span class="wordmark">FARMVILLE</span><span class="admin-badge">Admin Control Panel</span></div></div>
    <div class="sidebar-user"><div class="user-avatar">🏛️</div><div class="user-info"><h4 id="admin-name">Antonio Cruz</h4><p id="admin-email">Municipal Agri Officer</p><div class="admin-pill">⚡ Super Admin</div></div></div>
    <nav class="sidebar-nav">
      <div class="nav-section-label">Overview</div>
      <a href="index.html" class="nav-item" data-page="dashboard"><span class="icon">📊</span> Dashboard</a>
      <a href="analytics_and_reports.html" class="nav-item" data-page="analytics"><span class="icon">📈</span> Analytics &amp; Reports</a>
      <div class="nav-section-label" style="margin-top:8px">Moderation</div>
      <a href="farmer_applications.html" class="nav-item" data-page="applications"><span class="icon">✅</span> Farmer Applications <span class="nav-badge amber">4</span></a>
      <a href="listings.html" class="nav-item" data-page="listings"><span class="icon">📋</span> Listing Moderation <span class="nav-badge">2</span></a>
      <a href="disputes.html" class="nav-item" data-page="disputes"><span class="icon">⚖️</span> Disputes <span class="nav-badge">3</span></a>
      <div class="nav-section-label" style="margin-top:8px">Management</div>
      <a href="users.html" class="nav-item" data-page="users"><span class="icon">👥</span> User Management</a>
      <a href="orders.html" class="nav-item" data-page="orders"><span class="icon">📦</span> All Orders</a>
      <a href="transactions.html" class="nav-item" data-page="transactions"><span class="icon">💰</span> Transactions</a>
      <a href="price_monitoring.html" class="nav-item" data-page="prices"><span class="icon">📉</span> Price Monitoring</a>
      <div class="nav-section-label" style="margin-top:8px">System</div>
      <a href="audit_log.html" class="nav-item" data-page="audit"><span class="icon">📝</span> Audit Log</a>
      <a href="settings.html" class="nav-item" data-page="settings"><span class="icon">⚙️</span> Settings</a>
    </nav>
    <div class="sidebar-footer">
      <a href="#" class="nav-item"><span class="icon">🌐</span> Back to Site</a>
      <a href="#" class="nav-item" onclick="logout();return false;"><span class="icon">🚪</span> Logout</a>
    </div>
  </aside>`;
  document.body.insertAdjacentHTML('afterbegin', html);
}