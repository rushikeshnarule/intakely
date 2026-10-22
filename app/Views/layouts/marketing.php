<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Apex Law LLP — Precision Advocacy for Modern Legal Challenges') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
    <style>
        /* ============================================================
           EXECUTIVE LEGAL LANDING PAGE STYLES (Matching Image Spec)
           ============================================================ */
        :root {
            --law-dark: #0D1117;
            --law-court-dark: #121820;
            --law-wood: #1E1712;
            --law-gold: #C5A059;
            --law-text-light: #F3F4F6;
            --law-bg-light: #FAF9F6;
            --law-border-hairline: rgba(0, 0, 0, 0.08);
            --law-font-display: 'Outfit', 'Inter', sans-serif;
            --law-font-serif: 'Playfair Display', Georgia, serif;
        }

        body {
            background-color: var(--law-bg-light);
            color: #111827;
            font-family: 'Inter', -apple-system, sans-serif;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        /* Nav Header Floating on Hero */
        .legal-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
            padding: 1.5rem 3rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .legal-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: #FFFFFF;
        }

        .legal-crest-box {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFFFFF;
        }

        .legal-nav-menu {
            display: flex;
            align-items: center;
            gap: 2.25rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .legal-nav-link {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            letter-spacing: 0.01em;
            transition: color 0.2s ease;
        }

        .legal-nav-link:hover {
            color: #FFFFFF;
        }

        .legal-consult-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 1.25rem;
            border-radius: 9999px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            color: #FFFFFF;
            font-size: 0.8125rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .legal-consult-btn:hover {
            background: #FFFFFF;
            color: #000000;
            border-color: #FFFFFF;
        }

        /* Mobile Hamburger & Drawer */
        .mobile-menu-toggle {
            display: none;
            background: transparent;
            border: none;
            color: #FFFFFF;
            cursor: pointer;
            padding: 0.5rem;
        }

        .mobile-drawer {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(13, 17, 23, 0.98);
            backdrop-filter: blur(20px);
            z-index: 100;
            display: flex;
            flex-direction: column;
            padding: 2rem;
            transform: translateY(-100%);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .mobile-drawer.open {
            transform: translateY(0);
        }

        .mobile-nav-links {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-top: 3rem;
        }

        .mobile-nav-link {
            color: #FFFFFF;
            font-size: 1.5rem;
            font-weight: 600;
            text-decoration: none;
        }

        @media (max-width: 900px) {
            .legal-header {
                padding: 1.25rem 1.5rem;
            }
            .legal-nav-menu, .legal-consult-btn {
                display: none;
            }
            .mobile-menu-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Top Header Navigation -->
    <header class="legal-header">
        <a href="/" class="legal-logo">
            <div class="legal-crest-box">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3v18M6 8l6-5 6 5M3 14l3-6 3 6a3 3 0 0 1-6 0zm12 0l3-6 3 6a3 3 0 0 1-6 0z"/>
                </svg>
            </div>
            <span style="font-weight:700;font-size:1.125rem;letter-spacing:-0.01em;">Apex Law LLP</span>
        </a>

        <nav>
            <ul class="legal-nav-menu">
                <li><a href="#hero" class="legal-nav-link">Home</a></li>
                <li><a href="#about" class="legal-nav-link">Our Vision</a></li>
                <li><a href="#services" class="legal-nav-link">Case Studies</a></li>
                <li><a href="#advantage" class="legal-nav-link">Team</a></li>
                <li><a href="/login" class="legal-nav-link" style="color:var(--law-gold);font-weight:600;">Chambers Portal &rarr;</a></li>
            </ul>
        </nav>

        <div>
            <a href="/login" class="legal-consult-btn">
                <span>Request a Consultation</span>
                <span style="font-size:0.9rem;">↗</span>
            </a>
        </div>

        <button class="mobile-menu-toggle" id="mobile-toggle" aria-label="Open Navigation Menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
    </header>

    <!-- Mobile Drawer Navigation -->
    <div class="mobile-drawer" id="mobile-drawer">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div style="display:flex;align-items:center;gap:0.75rem;color:white;">
                <div class="legal-crest-box">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M6 8l6-5 6 5M3 14l3-6 3 6a3 3 0 0 1-6 0zm12 0l3-6 3 6a3 3 0 0 1-6 0z"/></svg>
                </div>
                <span style="font-weight:700;font-size:1.125rem;">Apex Law LLP</span>
            </div>
            <button id="mobile-close" style="background:transparent;border:none;color:white;cursor:pointer;padding:0.5rem;" aria-label="Close Menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="mobile-nav-links">
            <a href="#hero" class="mobile-nav-link" onclick="closeMobileMenu()">Home</a>
            <a href="#about" class="mobile-nav-link" onclick="closeMobileMenu()">Our Vision</a>
            <a href="#services" class="mobile-nav-link" onclick="closeMobileMenu()">Services & Cases</a>
            <a href="#advantage" class="mobile-nav-link" onclick="closeMobileMenu()">Our Advantage</a>
            <a href="/login" class="mobile-nav-link" style="color:var(--law-gold);" onclick="closeMobileMenu()">Chambers Portal (Sign In)</a>
        </div>

        <div style="margin-top:auto;padding-top:2rem;">
            <a href="/login" class="btn btn-primary" style="width:100%;text-align:center;justify-content:center;padding:0.875rem;">
                Request a Consultation ↗
            </a>
        </div>
    </div>

    <!-- Main Landing Page Content -->
    <main>
        <?= $this->yield('content') ?>
    </main>

    <!-- Executive Footer -->
    <footer style="background:#090D14;color:#94A3B8;padding:4.5rem 2rem 2.5rem;border-top:1px solid rgba(255,255,255,0.08);">
        <div style="max-width:1200px;margin:0 auto;">
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:3rem;margin-bottom:3.5rem;">
                <div>
                    <div style="display:flex;align-items:center;gap:0.75rem;color:white;margin-bottom:1rem;">
                        <div class="legal-crest-box" style="width:34px;height:34px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M6 8l6-5 6 5M3 14l3-6 3 6a3 3 0 0 1-6 0zm12 0l3-6 3 6a3 3 0 0 1-6 0z"/></svg>
                        </div>
                        <span style="font-weight:700;font-size:1.125rem;letter-spacing:-0.01em;">Apex Law LLP</span>
                    </div>
                    <p style="font-size:0.875rem;line-height:1.6;color:rgba(255,255,255,0.65);max-width:320px;">
                        Precision advocacy and strategic counsel for high-stakes corporate, regulatory, and litigation matters across London and international jurisdictions.
                    </p>
                </div>

                <div>
                    <div style="font-size:0.8125rem;font-weight:700;color:white;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:1.25rem;">Practice Areas</div>
                    <div style="display:flex;flex-direction:column;gap:0.6rem;font-size:0.875rem;">
                        <a href="#services" style="color:#94A3B8;text-decoration:none;">Corporate Advisory & M&A</a>
                        <a href="#services" style="color:#94A3B8;text-decoration:none;">Commercial Litigation</a>
                        <a href="#services" style="color:#94A3B8;text-decoration:none;">Intellectual Property Protection</a>
                        <a href="#services" style="color:#94A3B8;text-decoration:none;">Conveyancing & Commercial Property</a>
                        <a href="#services" style="color:#94A3B8;text-decoration:none;">Wills, Trusts & Probate</a>
                    </div>
                </div>

                <div>
                    <div style="font-size:0.8125rem;font-weight:700;color:white;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:1.25rem;">Chambers & Locations</div>
                    <div style="display:flex;flex-direction:column;gap:0.75rem;font-size:0.875rem;line-height:1.5;">
                        <div>
                            <strong style="color:white;display:block;">London Headquarters</strong>
                            <span>4 Chancery Lane, Holborn, London WC2A 1LF</span>
                        </div>
                        <div>
                            <strong style="color:white;display:block;">Chambers Direct</strong>
                            <span>+44 (0)20 7946 0912 • clerk@apexlaw.example.com</span>
                        </div>
                    </div>
                </div>

                <div>
                    <div style="font-size:0.8125rem;font-weight:700;color:white;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:1.25rem;">Client & Counsel Access</div>
                    <p style="font-size:0.8125rem;line-height:1.5;margin-bottom:1rem;">
                        Authorized partners, counsel, and instructed clients can sign in to the Intakely CRM and matter tracking docket.
                    </p>
                    <a href="/login" class="btn btn-secondary btn-sm" style="background:rgba(255,255,255,0.06);border-color:rgba(255,255,255,0.15);color:white;">
                        Sign In to Chambers Docket &rarr;
                    </a>
                </div>
            </div>

            <div style="border-top:1px solid rgba(255,255,255,0.06);padding-top:2rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;font-size:0.75rem;">
                <div>
                    © <?= date('Y') ?> Apex Law LLP. Regulated by the Solicitors Regulation Authority (SRA #649102).
                </div>
                <div style="display:flex;gap:1.5rem;">
                    <a href="#" style="color:#94A3B8;text-decoration:none;">Privacy Notice</a>
                    <a href="#" style="color:#94A3B8;text-decoration:none;">Terms of Business</a>
                    <a href="#" style="color:#94A3B8;text-decoration:none;">SRA Regulatory Compliance</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        const toggleBtn = document.getElementById('mobile-toggle');
        const closeBtn = document.getElementById('mobile-close');
        const drawer = document.getElementById('mobile-drawer');

        if (toggleBtn && drawer) {
            toggleBtn.addEventListener('click', () => drawer.classList.add('open'));
        }
        if (closeBtn && drawer) {
            closeBtn.addEventListener('click', () => drawer.classList.remove('open'));
        }
        function closeMobileMenu() {
            if (drawer) drawer.classList.remove('open');
        }
    </script>
</body>
</html>
