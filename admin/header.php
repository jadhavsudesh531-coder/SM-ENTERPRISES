<?php
session_start();
if(!isset($_SESSION['is_login'])){
    header('location:login.php');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SM ENTERPRISES</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <script src="../js/bootstrap.bundle.min.js"></script>
    <!-- App styles -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/user.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; }</style>
  
</head>  
<body class="admin-page" style="margin:0;padding:80px 0 0 0;">
    <!-- Main Navbar with Logo and Title -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="position: fixed; top: 0; width: 100%; z-index: 1030; background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3); padding: 12px 0;">
      <div class="container-fluid">
        <a class="navbar-brand" href="index.php" style="display: flex; align-items: center; gap: 0.8rem;">
          <img src="../productimg/logo.png" alt="SM Enterprises Admin" width="50" height="40" class="d-inline-block" style="filter: drop-shadow(0 2px 8px rgba(0,0,0,0.3));">
          <span style="font-size: 1.3rem; font-weight: 700; letter-spacing: 0.5px;">SM ENTERPRISES</span>
        </a>
        <div class="ms-auto d-flex align-items-center gap-2">
          <!-- Notification Icon -->
          <div class="nav-item dropdown">
            <button class="nav-link text-white btn btn-link p-0 me-2 position-relative" id="notificationBell" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: inherit; border: none; background: none; font-size: 1.3rem; padding: 8px 12px !important; border-radius: 8px; transition: all 0.3s ease;">
              <i class="bi bi-bell-fill"></i>
              <span id="notificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none; font-size: 0.65rem;">0</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" aria-labelledby="notificationBell" id="notificationDropdown" style="min-width: 320px; max-height: 400px; overflow-y: auto; border-radius: 12px; margin-top: 8px;">
              <li class="dropdown-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); color: white; padding: 12px 16px; margin: -8px -8px 8px -8px; border-radius: 12px 12px 0 0;">
                <span><strong><i class="bi bi-bell me-2"></i>Notifications</strong></span>
                <button class="btn btn-sm btn-link text-decoration-none p-0" onclick="clearAllNotifications()" style="font-size: 0.8rem; color: white; opacity: 0.9;">Clear All</button>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li id="notificationList" class="px-2">
                <p class="text-muted text-center py-3 mb-0" style="font-size: 0.9rem;">No new notifications</p>
              </li>
            </ul>
          </div>
          <!-- Admin User Menu -->
          <div class="nav-item dropdown">
            <button class="nav-link dropdown-toggle text-white btn btn-link px-3 py-2" id="adminUser" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: inherit; border: none; background: none; border-radius: 8px; transition: all 0.3s ease; font-weight: 500;">
              <i class="bi bi-person-circle me-1" style="font-size: 1.2rem;"></i> <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" aria-labelledby="adminUser" style="border-radius: 12px; margin-top: 8px; min-width: 240px;">
              <li class="dropdown-header" style="background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); color: white; padding: 12px 16px; margin: -8px -8px 8px -8px; border-radius: 12px 12px 0 0;">
                <i class="bi bi-shield-check me-2"></i>Admin Panel
              </li>
              <li><a class="dropdown-item py-2" href="index.php"><i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard</a></li>
              <li><a class="dropdown-item py-2" href="orders.php"><i class="bi bi-cart-check me-2 text-info"></i>Orders</a></li>
              <li><a class="dropdown-item py-2" href="delivery_agents.php"><i class="bi bi-truck me-2 text-success"></i>Delivery Agents</a></li>
              <li><a class="dropdown-item py-2" href="revenue.php"><i class="bi bi-graph-up-arrow me-2 text-warning"></i>Revenue</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger py-2" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </div>
        </div>
      </div>
    </nav>

    <!-- Vertical Sidebar Navigation for Admin -->
    <div class="col-sm-2 sidebar" style="position: fixed; left: 0; top: 80px; height: calc(100vh - 80px); overflow-y: auto; width: 16.666%; background: white; box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);">
      <ul class="nav flex-column sidebar-nav" style="padding: 20px 0;">
        <li class="nav-item">
          <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'index.php') !== false && strpos($_SERVER['PHP_SELF'], 'product') === false) ? 'active' : ''; ?>" aria-current="page" href="index.php" style="color: #dc2626; padding: 12px 20px; transition: all 0.3s ease; border-left: 4px solid transparent; font-weight: 500;">
            <i class="bi bi-speedometer2" style="margin-right: 10px;"></i>Dashboard  
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'product') !== false) ? 'active' : ''; ?>" href="view_product.php" style="color: #dc2626; padding: 12px 20px; transition: all 0.3s ease; border-left: 4px solid transparent; font-weight: 500;">
            <i class="bi bi-box-seam" style="margin-right: 10px;"></i>Products
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'orders.php') !== false) ? 'active' : ''; ?>" href="orders.php" style="color: #dc2626; padding: 12px 20px; transition: all 0.3s ease; border-left: 4px solid transparent; font-weight: 500;">
            <i class="bi bi-cart-check" style="margin-right: 10px;"></i>Orders
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'delivery_agents.php') !== false) ? 'active' : ''; ?>" href="delivery_agents.php" style="color: #dc2626; padding: 12px 20px; transition: all 0.3s ease; border-left: 4px solid transparent; font-weight: 500;">
            <i class="bi bi-truck" style="margin-right: 10px;"></i>Delivery Agents
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'revenue.php') !== false) ? 'active' : ''; ?>" href="revenue.php" style="color: #dc2626; padding: 12px 20px; transition: all 0.3s ease; border-left: 4px solid transparent; font-weight: 500;">
            <i class="bi bi-graph-up-arrow" style="margin-right: 10px;"></i>Revenue
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'customization') !== false) ? 'active' : ''; ?>" href="customization_request.php" style="color: #dc2626; padding: 12px 20px; transition: all 0.3s ease; border-left: 4px solid transparent; font-weight: 500;">
            <i class="bi bi-gear-wide-connected" style="margin-right: 10px;"></i>Customization
          </a>
        </li>
      </ul>
    </div>

<script>
// Notification System
const notificationSystem = {
  notifications: [],
  maxNotifications: 5,
  autoRemoveDelay: 5000, // 5 seconds
  
  addNotification(message, type = 'info') {
    const notification = {
      id: Date.now(),
      message: message,
      type: type, // info, success, warning, danger
      timestamp: new Date().toLocaleTimeString()
    };
    
    this.notifications.unshift(notification);
    if (this.notifications.length > this.maxNotifications) {
      this.notifications.pop();
    }
    
    this.updateUI();
    this.autoRemove(notification.id);
  },
  
  removeNotification(id) {
    this.notifications = this.notifications.filter(n => n.id !== id);
    this.updateUI();
  },
  
  autoRemove(id) {
    setTimeout(() => {
      this.removeNotification(id);
    }, this.autoRemoveDelay);
  },
  
  clearAll() {
    this.notifications = [];
    this.updateUI();
  },
  
  updateUI() {
    const badge = document.getElementById('notificationBadge');
    const list = document.getElementById('notificationList');
    const count = this.notifications.length;
    
    // Update badge
    if (count > 0) {
      badge.textContent = count;
      badge.style.display = 'inline-block';
    } else {
      badge.style.display = 'none';
    }
    
    // Update list
    if (count === 0) {
      list.innerHTML = '<p class="text-muted text-center py-3 mb-0" style="font-size: 0.9rem;">No new notifications</p>';
    } else {
      let html = '';
      this.notifications.forEach(notif => {
        const iconClass = {
          info: 'bi-info-circle-fill text-info',
          success: 'bi-check-circle-fill text-success',
          warning: 'bi-exclamation-triangle-fill text-warning',
          danger: 'bi-x-circle-fill text-danger'
        }[notif.type] || 'bi-info-circle-fill text-info';
        
        html += `
          <div class="notification-item p-2 mb-2" style="background: rgba(0,0,0,0.03); border-radius: 8px; border-left: 3px solid var(--bs-${notif.type}); cursor: pointer;" onclick="notificationSystem.removeNotification(${notif.id})">
            <div class="d-flex align-items-start">
              <i class="bi ${iconClass} me-2" style="font-size: 1.1rem;"></i>
              <div class="flex-grow-1">
                <p class="mb-1" style="font-size: 0.85rem;">${notif.message}</p>
                <small class="text-muted" style="font-size: 0.75rem;">${notif.timestamp}</small>
              </div>
              <button class="btn btn-sm btn-link p-0 ms-2" onclick="event.stopPropagation(); notificationSystem.removeNotification(${notif.id})" style="font-size: 1.2rem; line-height: 1;">
                <i class="bi bi-x"></i>
              </button>
            </div>
          </div>
        `;
      });
      list.innerHTML = html;
    }
  }
};

function clearAllNotifications() {
  notificationSystem.clearAll();
}

// Example: Add notification from PHP session messages
document.addEventListener('DOMContentLoaded', function() {
  <?php if (isset($_SESSION['notif_msg'])): ?>
    notificationSystem.addNotification('<?php echo addslashes($_SESSION['notif_msg']); ?>', '<?php echo $_SESSION['notif_type'] ?? 'info'; ?>');
    <?php unset($_SESSION['notif_msg'], $_SESSION['notif_type']); ?>
  <?php endif; ?>
});

document.addEventListener('DOMContentLoaded', function(){
  var btn = document.getElementById('adminUser');
  if (btn) {
    try { bootstrap.Dropdown.getOrCreateInstance(btn); } catch (e) { /* ignore */ }
  }
});
</script>
<script>
// Image hover preview (shows a larger floating preview when hovering small images)
(function(){
  if ('ontouchstart' in window) return; // skip on touch devices
  document.addEventListener('DOMContentLoaded', function(){
    var preview = document.createElement('div');
    preview.id = 'imgPreview';
    preview.style.position = 'fixed';
    preview.style.display = 'none';
    preview.style.zIndex = '3000';
    preview.style.pointerEvents = 'none';
    preview.style.background = '#ffffff';
    preview.style.border = '1px solid rgba(0,0,0,0.12)';
    preview.style.borderRadius = '10px';
    preview.style.boxShadow = '0 12px 28px rgba(0,0,0,0.22)';
    preview.style.padding = '6px';
    document.body.appendChild(preview);
    var previewImg = document.createElement('img');
    previewImg.style.maxWidth = '360px';
    previewImg.style.maxHeight = '360px';
    previewImg.style.display = 'block';
    previewImg.style.borderRadius = '8px';
    preview.appendChild(previewImg);
    var active = false;
    var showTimeout = null;

    document.addEventListener('mouseover', function(e){
      var t = e.target;
      if (!t || t.tagName !== 'IMG') return;
      if (t.classList.contains('no-preview')) return;
      if (/logo/i.test(t.src)) return; // don't preview logos
      var w = t.clientWidth || t.naturalWidth || 0;
      if (w > 300) return; // only for small images
      showTimeout = setTimeout(function(){
        previewImg.src = t.src;
        preview.style.display = 'block';
        active = true;
        positionPreview(e);
      }, 180);
    });

    document.addEventListener('mousemove', function(e){
      if (!active) return;
      positionPreview(e);
    });

    document.addEventListener('mouseout', function(e){
      var t = e.target;
      if (!t || t.tagName !== 'IMG') return;
      clearTimeout(showTimeout);
      preview.style.display = 'none';
      active = false;
    });

    function positionPreview(e){
      var padding = 12;
      var vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
      var vh = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0);
      var rect = preview.getBoundingClientRect();
      var x = e.clientX + 20;
      var y = e.clientY + 20;
      if (x + rect.width + padding > vw) x = e.clientX - rect.width - 20;
      if (y + rect.height + padding > vh) y = e.clientY - rect.height - 20;
      preview.style.left = Math.max(8, x) + 'px';
      preview.style.top = Math.max(8, y) + 'px';
    }
  });
})();
</script>
<script>
// Image click preview (opens full image in Bootstrap modal)
document.addEventListener('DOMContentLoaded', function(){
  // append modal markup
  var modalHtml = '\n<div class="modal fade" id="imgModal" tabindex="-1" aria-hidden="true">\n  <div class="modal-dialog modal-dialog-centered modal-lg">\n    <div class="modal-content bg-transparent border-0">\n      <div class="modal-body p-0">\n        <img src="" id="imgModalImg" class="img-fluid rounded" alt="">\n      </div>\n    </div>\n  </div>\n</div>\n';
  document.body.insertAdjacentHTML('beforeend', modalHtml);

  var imgModalEl = document.getElementById('imgModal');
  var imgModal = bootstrap.Modal.getOrCreateInstance(imgModalEl);
  var modalImg = document.getElementById('imgModalImg');

  document.addEventListener('click', function(e){
    var t = e.target;
    if (!t || t.tagName !== 'IMG') return;
    if (t.classList.contains('no-preview')) return;
    if (/logo/i.test(t.src)) return; // don't open logos
    e.preventDefault();
    modalImg.src = t.src;
    modalImg.alt = t.alt || '';
    imgModal.show();
  });

  imgModalEl.addEventListener('hidden.bs.modal', function(){
    modalImg.src = '';
  });
});
</script>
<script>
// Shared portal interactions
document.addEventListener('DOMContentLoaded', function(){
  if (!document.getElementById('globalScrollProgress')) {
    var progress = document.createElement('div');
    progress.id = 'globalScrollProgress';
    progress.className = 'scroll-progress-global';
    document.body.appendChild(progress);
  }

  if (!document.getElementById('globalScrollTopBtn')) {
    var topBtn = document.createElement('button');
    topBtn.id = 'globalScrollTopBtn';
    topBtn.className = 'scroll-top-global';
    topBtn.type = 'button';
    topBtn.setAttribute('aria-label', 'Scroll to top');
    topBtn.innerHTML = '<i class="bi bi-arrow-up"></i>';
    document.body.appendChild(topBtn);
    topBtn.addEventListener('click', function(){
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  var progressEl = document.getElementById('globalScrollProgress');
  var topBtnEl = document.getElementById('globalScrollTopBtn');
  var nav = document.querySelector('.navbar');

  function handleScrollEffects() {
    var y = window.scrollY || window.pageYOffset || 0;
    if (nav) nav.classList.toggle('scrolled', y > 30);

    var doc = document.documentElement;
    var scrollTop = doc.scrollTop || document.body.scrollTop;
    var scrollHeight = doc.scrollHeight - doc.clientHeight;
    var pct = scrollHeight > 0 ? (scrollTop / scrollHeight) * 100 : 0;
    if (progressEl) progressEl.style.width = pct + '%';
    if (topBtnEl) topBtnEl.classList.toggle('show', y > 280);
  }

  window.addEventListener('scroll', handleScrollEffects, { passive: true });
  handleScrollEffects();

  var revealTargets = document.querySelectorAll('.card, .table-container, .summary-card, .table-responsive');
  revealTargets.forEach(function(el){
    el.classList.add('reveal-in');
    el.classList.add('interactive-lift');
  });

  if ('IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function(entries, obs){
      entries.forEach(function(entry){
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });
    revealTargets.forEach(function(el){ observer.observe(el); });
  } else {
    revealTargets.forEach(function(el){ el.classList.add('visible'); });
  }

  var buttons = document.querySelectorAll('.btn');
  buttons.forEach(function(btn){
    btn.classList.add('btn-ripple');
    btn.addEventListener('click', function(e){
      var wave = document.createElement('span');
      var d = Math.max(btn.clientWidth, btn.clientHeight);
      var r = d / 2;
      wave.className = 'btn-ripple-wave';
      wave.style.width = d + 'px';
      wave.style.height = d + 'px';
      wave.style.left = (e.clientX - btn.getBoundingClientRect().left - r) + 'px';
      wave.style.top = (e.clientY - btn.getBoundingClientRect().top - r) + 'px';
      var old = btn.querySelector('.btn-ripple-wave');
      if (old) old.remove();
      btn.appendChild(wave);
    });
  });
});
</script>
 <!-- container start  -->
  <div class="container">
    <div class="row mt-2 justify-content-center">