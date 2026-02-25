<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if(!isset($_SESSION['is_login'])){
    header('location:login.php');
}

$displayName = $_SESSION['username'] ?? $_SESSION['uname'] ?? 'User';

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
    <style>
      body { font-family: 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; }
    </style>

</head>  
<body class="user-page" style="margin:0;padding:80px 0 0 0;">
    <!-- navbar start -->
     <nav class="navbar navbar-expand-lg navbar-dark" style="position: fixed; top: 0; width: 100%; z-index: 1030; background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);">
  <div class="container-fluid">
    <a class="navbar-brand" href="view_product.php" style="display: flex; align-items: center; gap: 0.8rem;">
    <img src="../productimg/logo.png" alt="SM Enterprises Logo" width="50" height="40" class="d-inline-block" style="filter: drop-shadow(0 2px 8px rgba(0,0,0,0.3));">
    <span style="font-size: 1.3rem; font-weight: 700; letter-spacing: 0.5px;">SM ENTERPRISES</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
      
</ul>

      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <button id="darkModeToggle" class="nav-link text-white btn btn-link p-0 me-3" type="button" style="color: inherit; border: none; background: none;">
            <i class="bi bi-moon-fill"></i>
          </button>
        </li>
        <!-- Notification Icon -->
        <li class="nav-item dropdown">
          <button class="nav-link text-white btn btn-link p-0 me-3 position-relative" id="notificationBell" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: inherit; border: none; background: none; font-size: 1.3rem;">
            <i class="bi bi-bell-fill"></i>
            <span id="notificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none; font-size: 0.65rem;">0</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationBell" id="notificationDropdown" style="min-width: 320px; max-height: 400px; overflow-y: auto;">
            <li class="dropdown-header d-flex justify-content-between align-items-center">
              <span><strong>Notifications</strong></span>
              <button class="btn btn-sm btn-link text-decoration-none p-0" onclick="clearAllNotifications()" style="font-size: 0.8rem;">Clear All</button>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li id="notificationList" class="px-2">
              <p class="text-muted text-center py-3 mb-0" style="font-size: 0.9rem;">No new notifications</p>
            </li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <button class="nav-link dropdown-toggle text-white btn btn-link p-0" id="userMenu" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: inherit;">
            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($displayName);?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
            <li><a class="dropdown-item" href="myorder.php"><i class="bi bi-card-list me-2"></i>My Orders</a></li>
            <li><a class="dropdown-item" href="customization.php"><i class="bi bi-patch-check me-2"></i>Customization</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
          </ul>
        </li>
      </ul>
   
    </div>
  </div>
</nav>
<!-- navbar End -->
<script>
document.addEventListener('DOMContentLoaded', function(){
  var btn = document.getElementById('userMenu');
  if (btn) {
    // ensure Bootstrap dropdown instance exists and is usable
    try { bootstrap.Dropdown.getOrCreateInstance(btn); } catch (e) { /* ignore */ }
  }
});

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

// Dark Mode Toggle
document.addEventListener('DOMContentLoaded', function() {
  const darkModeToggle = document.getElementById('darkModeToggle');
  const html = document.documentElement;
  
  // Load saved dark mode preference
  const savedDarkMode = localStorage.getItem('darkMode');
  if (savedDarkMode === 'true') {
    html.setAttribute('data-bs-theme', 'dark');
    html.classList.add('dark-mode');
    darkModeToggle.innerHTML = '<i class="bi bi-sun-fill"></i>';
  } else {
    html.setAttribute('data-bs-theme', 'light');
    html.classList.remove('dark-mode');
    darkModeToggle.innerHTML = '<i class="bi bi-moon-fill"></i>';
  }
  
  // Toggle dark mode on button click
  darkModeToggle.addEventListener('click', function() {
    const isDarkMode = html.classList.contains('dark-mode');
    if (isDarkMode) {
      html.classList.remove('dark-mode');
      html.setAttribute('data-bs-theme', 'light');
      darkModeToggle.innerHTML = '<i class="bi bi-moon-fill"></i>';
      localStorage.setItem('darkMode', 'false');
    } else {
      html.classList.add('dark-mode');
      html.setAttribute('data-bs-theme', 'dark');
      darkModeToggle.innerHTML = '<i class="bi bi-sun-fill"></i>';
      localStorage.setItem('darkMode', 'true');
    }
  });
});
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

  var revealTargets = document.querySelectorAll('.card, .search-container, .table-responsive, .list-group, .profile-card');
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
  <div class="container-fluid">
    <div class="row mt-0">
      <div class="col-sm-2 sidebar" style="position: fixed; left: 0; top: 80px; height: calc(100vh - 80px); overflow-y: auto; width: 16.666%; background: white; box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);">
        <ul class="nav flex-column sidebar-nav" style="padding: 20px 0;">
          <li class="nav-item">
            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'index.php') !== false) ? 'active' : ''; ?>" aria-current="page" href="index.php" style="color: #dc2626; padding: 12px 20px; transition: all 0.3s ease; border-left: 4px solid transparent; font-weight: 500;">
              <i class="bi bi-grid-fill" style="margin-right: 10px;"></i>Dashboard  
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'Categories.php') !== false) ? 'active' : ''; ?>" href="Categories.php" style="color: #dc2626; padding: 12px 20px; transition: all 0.3s ease; border-left: 4px solid transparent; font-weight: 500;">
              <i class="bi bi-tags-fill" style="margin-right: 10px;"></i>Categories
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'customization.php') !== false) ? 'active' : ''; ?>" href="customization.php" style="color: #dc2626; padding: 12px 20px; transition: all 0.3s ease; border-left: 4px solid transparent; font-weight: 500;">
              <i class="bi bi-palette-fill" style="margin-right: 10px;"></i>Customization
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'profile.php') !== false) ? 'active' : ''; ?>" href="profile.php" style="color: #dc2626; padding: 12px 20px; transition: all 0.3s ease; border-left: 4px solid transparent; font-weight: 500;">
              <i class="bi bi-person-fill" style="margin-right: 10px;"></i>My Profile
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'myorder.php') !== false) ? 'active' : ''; ?>" href="myorder.php" style="color: #dc2626; padding: 12px 20px; transition: all 0.3s ease; border-left: 4px solid transparent; font-weight: 500;">
              <i class="bi bi-bag-check-fill" style="margin-right: 10px;"></i>My Orders
            </a>
          </li>
        </ul>
      </div>
      <div class="col-sm-10" style="margin-left: 16.666%;">

<?php
// Display error messages
if (isset($_SESSION['error_msg'])) {
    echo '<div class="alert alert-dismissible fade show" role="alert" style="position: fixed; top: 90px; right: 20px; z-index: 9999; max-width: 400px; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: white; border: none; border-radius: 12px; box-shadow: 0 8px 24px rgba(220, 38, 38, 0.3);">';
    echo '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
    echo htmlspecialchars($_SESSION['error_msg']);
    echo '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['error_msg']);
}

// Display success messages
if (isset($_SESSION['success_msg'])) {
    echo '<div class="alert alert-dismissible fade show" role="alert" style="position: fixed; top: 90px; right: 20px; z-index: 9999; max-width: 400px; background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; border: none; border-radius: 12px; box-shadow: 0 8px 24px rgba(5, 150, 105, 0.3);">';
    echo '<i class="bi bi-check-circle-fill me-2"></i>';
    echo htmlspecialchars($_SESSION['success_msg']);
    echo '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['success_msg']);
}
?>

<script>
// Auto-dismiss alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>
