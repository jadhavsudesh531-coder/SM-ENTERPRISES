<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SM ENTERPRISES | Premium Awards & Signage</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-dark: #121212;
            --accent-gold: #c5a059;
            --soft-gray: #f8f9fa;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #e9edf2;
            color: var(--primary-dark);
            overflow-x: hidden;
        }

        html {
            scroll-behavior: smooth;
        }

        .scroll-progress {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            width: 0;
            background: linear-gradient(90deg, #c5a059 0%, #f2dfb0 100%);
            z-index: 1060;
            transition: width 0.1s linear;
        }

        /* Navbar Enhancement */
        .navbar {
            backdrop-filter: blur(10px);
            background-color: rgba(0, 0, 0, 0.85) !important;
            transition: all 0.3s ease;
            padding: 15px 0;
        }

        .navbar.scrolled {
            background-color: rgba(0, 0, 0, 0.97) !important;
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.3);
            padding: 10px 0;
        }

        .navbar-brand {
            font-weight: 800;
            letter-spacing: 1px;
            color: var(--accent-gold) !important;
        }

        .nav-link {
            font-weight: 500;
            transition: 0.3s;
            position: relative;
        }

        .nav-link:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 50%;
            background-color: var(--accent-gold);
            transition: 0.3s;
        }

        .nav-link:hover:after {
            width: 100%;
            left: 0;
        }

        /* Hero Carousel */
        .carousel-item {
            height: 85vh;
            background: #000;
        }

        .carousel-item img {
            opacity: 0.7;
            transition: transform 5s ease;
        }

        .carousel-item.active img {
            transform: scale(1.1);
        }

        .carousel-caption {
            z-index: 10;
            bottom: 25%;
            text-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        .btn-premium {
            background-color: var(--accent-gold);
            color: white;
            border: none;
            padding: 12px 35px;
            border-radius: 50px;
            font-weight: 600;
            transition: 0.4s;
            position: relative;
            overflow: hidden;
        }

        .btn-premium:hover {
            background-color: #b38e4a;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(197, 160, 89, 0.3);
        }

        /* Category Cards */
        .category-card {
            border: none;
            border-radius: 20px;
            background: var(--soft-gray);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
        }

        .category-card:hover {
            transform: translateY(-10px);
            background: #eef1f5;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
        }

        .category-card img {
            height: 250px;
            object-fit: cover;
            transition: 0.5s;
        }

        .category-card:hover img {
            transform: scale(1.05);
        }

        /* Footer */
        footer {
            background-color: var(--primary-dark);
            border-top: 4px solid var(--accent-gold);
        }

        .social-icons a {
            font-size: 1.5rem;
            margin: 0 10px;
            color: #fff;
            transition: 0.3s;
        }

        .social-icons a:hover {
            color: var(--accent-gold);
            transform: scale(1.2);
        }

        /* Animations */
        [data-aos="fade-up"] {
            transition: 0.8s;
        }

        .reveal {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity 0.7s ease, transform 0.7s ease;
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .category-card {
            transform-style: preserve-3d;
            will-change: transform;
        }

        .hero-subtle {
            color: #f4dfae;
            border-bottom: 2px solid rgba(197, 160, 89, 0.55);
            padding-bottom: 2px;
        }

        .cursor-blink {
            display: inline-block;
            color: var(--accent-gold);
            animation: blink 1s steps(1) infinite;
            margin-left: 2px;
        }

        @keyframes blink {
            50% { opacity: 0; }
        }

        .scroll-top-btn {
            position: fixed;
            right: 20px;
            bottom: 20px;
            width: 46px;
            height: 46px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, #c5a059 0%, #b38e4a 100%);
            color: #fff;
            box-shadow: 0 10px 22px rgba(197, 160, 89, 0.35);
            z-index: 1060;
            opacity: 0;
            transform: translateY(10px);
            pointer-events: none;
            transition: all 0.25s ease;
        }

        .scroll-top-btn.show {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .ripple {
            position: absolute;
            border-radius: 50%;
            transform: scale(0);
            animation: rippleAnim 0.6s linear;
            background-color: rgba(255, 255, 255, 0.55);
            pointer-events: none;
        }

        @keyframes rippleAnim {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    </style>
</head>
<body>

<div id="scrollProgress" class="scroll-progress" aria-hidden="true"></div>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="productimg/logo.png" alt="Logo" width="45" height="40" class="me-2">
            SM ENTERPRISES
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navContent">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link px-3" href="user/login.php">USER LOGIN</a></li>
                <li class="nav-item"><a class="nav-link px-3 ms-lg-2" href="admin/login.php" style="color: var(--accent-gold);">ADMIN PORTAL</a></li>
            </ul>
        </div>
    </div>
</nav>

<div id="heroSlider" class="carousel slide carousel-fade" data-bs-ride="carousel">
    <div class="carousel-inner">
        <div class="carousel-item active">
            <img src="slide1.png" class="d-block w-100 h-100" alt="Acrylic Trophies">
            <div class="carousel-caption text-start">
                <h1 class="display-3 fw-bold mb-3">Excellence <span id="heroWord" class="hero-subtle">Crafted</span><span class="cursor-blink">|</span></h1>
                <p class="lead mb-4 col-md-8">Custom Acrylic Trophies designed to capture life's greatest achievements with precision and elegance.</p>
                <a href="user/view_product.php" class="btn btn-premium btn-lg">SHOP THE COLLECTION</a>
            </div>
        </div>
        <div class="carousel-item">
            <img src="slide2.png" class="d-block w-100 h-100" alt="Signages">
            <div class="carousel-caption text-start">
                <h1 class="display-3 fw-bold mb-3">Premium <span style="color:var(--accent-gold)">Signage.</span></h1>
                <p class="lead mb-4 col-md-8">Professional nameplates and office signages that define your brand's presence.</p>
                <a href="user/view_product.php" class="btn btn-premium btn-lg">EXPLORE DESIGNS</a>
            </div>
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroSlider" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroSlider" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>

<div class="container py-5" id="categoriesSection">
    <div class="text-center mb-5">
        <h6 class="text-uppercase fw-bold text-muted" style="letter-spacing: 3px;">Our Expertise</h6>
        <h2 class="display-5 fw-bold">Explore Categories</h2>
        <div style="width: 60px; height: 3px; background: var(--accent-gold); margin: 15px auto;"></div>
    </div>

    <div class="row g-4">
        <div class="col-md-4 reveal">
            <div class="card category-card p-0 shadow-sm interactive-card">
                <img src="slide1.png" alt="Trophies">
                <div class="card-body p-4">
                    <h4 class="fw-bold">Trophies</h4>
                    <p class="text-muted small">Elegant acrylic and crystal trophies for corporate and sporting events.</p>
                    <a href="#" class="text-decoration-none fw-bold" style="color:var(--accent-gold)">VIEW MORE <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4 reveal">
            <div class="card category-card p-0 shadow-sm interactive-card">
                <img src="slide2.png" alt="Nameplates">
                <div class="card-body p-4">
                    <h4 class="fw-bold">Signages</h4>
                    <p class="text-muted small">Modern nameplates and indoor/outdoor signage solutions.</p>
                    <a href="#" class="text-decoration-none fw-bold" style="color:var(--accent-gold)">VIEW MORE <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4 reveal">
            <div class="card category-card p-0 shadow-sm interactive-card">
                <img src="slide3.png" alt="Medals">
                <div class="card-body p-4">
                    <h4 class="fw-bold">Medals</h4>
                    <p class="text-muted small">High-quality medals available in gold, silver, and bronze finishes.</p>
                    <a href="#" class="text-decoration-none fw-bold" style="color:var(--accent-gold)">VIEW MORE <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="text-white pt-5 pb-4">
    <div class="container text-center text-md-start">
        <div class="row">
            <div class="col-md-4 col-lg-4 col-xl-4 mx-auto mb-4">
                <h5 class="text-uppercase fw-bold mb-4" style="color:var(--accent-gold)">SM ENTERPRISES</h5>
                <p class="text-white-50 small">Defining excellence in the award and signage industry since 2011. Every piece we craft tells a story of success.</p>
            </div>
            <div class="col-md-4 col-lg-4 col-xl-4 mx-auto mb-4">
                <h5 class="text-uppercase fw-bold mb-4">Quick Contact</h5>
                <p class="small text-white-50"><i class="bi bi-geo-alt-fill me-2"></i> Mumbai, India</p>
                <p class="small text-white-50"><i class="bi bi-envelope-fill me-2"></i> sm.trophy10@gmail.com</p>
            </div>
            <div class="col-md-4 col-lg-4 col-xl-4 mx-auto mb-md-0 mb-4 text-center">
                <h5 class="text-uppercase fw-bold mb-4">Connect With Us</h5>
                <div class="social-icons">
                    <a href="https://www.instagram.com/sm_trophys"><i class="bi bi-instagram"></i></a>
                    <a href="https://wa.me/919076484862"><i class="bi bi-whatsapp"></i></a>
                    <a href="mailto:sm.trophy10@gmail.com"><i class="bi bi-envelope"></i></a>
                </div>
            </div>
        </div>
        <hr class="mt-4 mb-4" style="background-color: rgba(255,255,255,0.1);">
        <div class="text-center text-white-50 small">
            © 2024 SM ENTERPRISES. All Rights Reserved.
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        const navbar = document.querySelector('.navbar');
        const progress = document.getElementById('scrollProgress');

        function onScroll() {
            const y = window.scrollY || window.pageYOffset;
            if (navbar) {
                navbar.classList.toggle('scrolled', y > 40);
            }

            const doc = document.documentElement;
            const scrollTop = doc.scrollTop || document.body.scrollTop;
            const scrollHeight = doc.scrollHeight - doc.clientHeight;
            const percent = scrollHeight > 0 ? (scrollTop / scrollHeight) * 100 : 0;
            if (progress) {
                progress.style.width = percent + '%';
            }

            const scrollTopBtn = document.getElementById('scrollTopBtn');
            if (scrollTopBtn) {
                scrollTopBtn.classList.toggle('show', y > 300);
            }
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        const revealEls = document.querySelectorAll('.reveal');
        if ('IntersectionObserver' in window && revealEls.length) {
            const revealObserver = new IntersectionObserver((entries, obs) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        obs.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12 });
            revealEls.forEach(el => revealObserver.observe(el));
        } else {
            revealEls.forEach(el => el.classList.add('visible'));
        }

        const words = ['Crafted', 'Personalized', 'Awarded'];
        const heroWord = document.getElementById('heroWord');
        let wordIdx = 0;
        if (heroWord) {
            setInterval(() => {
                wordIdx = (wordIdx + 1) % words.length;
                heroWord.style.opacity = '0';
                setTimeout(() => {
                    heroWord.textContent = words[wordIdx];
                    heroWord.style.opacity = '1';
                }, 180);
            }, 2400);
        }

        document.querySelectorAll('.interactive-card').forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const px = (e.clientX - rect.left) / rect.width;
                const py = (e.clientY - rect.top) / rect.height;
                const rotateY = (px - 0.5) * 10;
                const rotateX = (0.5 - py) * 10;
                card.style.transform = 'perspective(900px) rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg) translateY(-8px)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });

        document.querySelectorAll('.btn-premium').forEach(btn => {
            btn.addEventListener('click', function (e) {
                const circle = document.createElement('span');
                const diameter = Math.max(this.clientWidth, this.clientHeight);
                const radius = diameter / 2;
                circle.classList.add('ripple');
                circle.style.width = circle.style.height = diameter + 'px';
                circle.style.left = (e.clientX - this.getBoundingClientRect().left - radius) + 'px';
                circle.style.top = (e.clientY - this.getBoundingClientRect().top - radius) + 'px';
                const prev = this.querySelector('.ripple');
                if (prev) prev.remove();
                this.appendChild(circle);
            });
        });

        const scrollTopBtn = document.getElementById('scrollTopBtn');
        if (scrollTopBtn) {
            scrollTopBtn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    })();
</script>

<button id="scrollTopBtn" class="scroll-top-btn" type="button" aria-label="Scroll to top">
    <i class="bi bi-arrow-up"></i>
</button>
</body>
</html>