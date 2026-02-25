<?php
session_start();
if (!isset($_SESSION['delivery_agent_id'])) {
    header('location:login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Portal - SM ENTERPRISES</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <script src="../js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/user.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="delivery-page" style="margin-top:74px;">
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php"><i class="bi bi-truck me-2"></i>Delivery Portal</a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3"><i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['delivery_agent_name'] ?? 'Agent'); ?></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<script>
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

    function onScroll() {
        var y = window.scrollY || window.pageYOffset || 0;
        if (nav) nav.classList.toggle('scrolled', y > 30);

        var doc = document.documentElement;
        var scrollTop = doc.scrollTop || document.body.scrollTop;
        var scrollHeight = doc.scrollHeight - doc.clientHeight;
        var pct = scrollHeight > 0 ? (scrollTop / scrollHeight) * 100 : 0;
        if (progressEl) progressEl.style.width = pct + '%';
        if (topBtnEl) topBtnEl.classList.toggle('show', y > 280);
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    var revealTargets = document.querySelectorAll('.card, .table-responsive, .list-group');
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
