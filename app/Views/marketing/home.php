<?php $this->layout('layouts/marketing'); ?>

<style>
/* ============================================================
   ANIMATED HERO SECTION (Cinematic Courtroom & Moving Elements)
   ============================================================ */
.law-hero {
    position: relative;
    min-height: 94vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 8rem 3rem 4rem;
    color: #FFFFFF;
}

/* Ambient Slow Zoom/Drift on Courtroom Background */
.law-hero-bg {
    position: absolute;
    inset: -30px;
    background: linear-gradient(180deg, rgba(14, 19, 28, 0.74) 0%, rgba(14, 19, 28, 0.86) 55%, rgba(10, 14, 22, 0.98) 100%),
                url('https://images.unsplash.com/photo-1589829545856-d10d557cf95f?auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;
    animation: heroZoomPan 28s ease-in-out infinite alternate;
    z-index: 1;
    will-change: transform;
}

@keyframes heroZoomPan {
    0% { transform: scale(1) translate(0, 0); }
    50% { transform: scale(1.06) translate(-1.5%, -1%); }
    100% { transform: scale(1.03) translate(1.5%, 0.5%); }
}

/* Interactive Cursor Spotlight Glow */
.hero-spotlight {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 2;
    background: radial-gradient(750px circle at var(--mouse-x, 50%) var(--mouse-y, 50%), rgba(212, 175, 55, 0.14), transparent 50%);
    transition: opacity 0.4s ease;
}

/* Content layer over background */
.law-hero-content {
    position: relative;
    z-index: 3;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.law-hero-top-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: auto;
    padding-bottom: 3.5rem;
    flex-wrap: wrap;
    gap: 2rem;
}

.law-hero-stats {
    display: flex;
    gap: 3.5rem;
    align-items: flex-start;
}

.law-stat-item {
    display: flex;
    flex-direction: column;
}

.law-stat-num {
    font-size: 2.85rem;
    font-weight: 700;
    line-height: 1;
    letter-spacing: -0.02em;
    font-family: var(--law-font-display);
    color: #FFFFFF;
    text-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
}

.law-stat-lbl {
    font-size: 0.8125rem;
    color: rgba(255, 255, 255, 0.65);
    margin-top: 0.35rem;
    letter-spacing: 0.02em;
}

.law-hero-quote {
    max-width: 340px;
    font-size: 0.875rem;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 400;
}

/* Floating Rotating Legal Seal */
.floating-seal-wrapper {
    position: absolute;
    top: 7rem;
    right: 3rem;
    z-index: 4;
    pointer-events: none;
    animation: floatGentle 5s ease-in-out infinite;
}

.rotating-seal {
    width: 100px;
    height: 100px;
    animation: spinClockwise 25s linear infinite;
}

@keyframes spinClockwise {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes floatGentle {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

@keyframes floatGentleAlt {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-8px) rotate(1deg); }
}

.law-hero-bottom-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    flex-wrap: wrap;
    gap: 2.5rem;
}

.law-hero-title {
    font-size: clamp(2.5rem, 6vw, 4.5rem);
    font-weight: 600;
    line-height: 1.08;
    letter-spacing: -0.03em;
    max-width: 720px;
    margin: 0;
    color: #FFFFFF;
    font-family: var(--law-font-display);
    text-shadow: 0 6px 24px rgba(0, 0, 0, 0.4);
}

.law-hero-cta-box {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 1.25rem;
}

.law-hero-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    background: #FFFFFF;
    color: #0F172A;
    font-size: 0.9375rem;
    font-weight: 600;
    padding: 0.85rem 1.85rem;
    border-radius: 9999px;
    text-decoration: none;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s ease, background 0.2s ease;
}

.law-hero-btn:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 16px 32px rgba(0, 0, 0, 0.45);
    background: #F8FAFC;
    color: #000000;
}

.btn-arrow {
    display: inline-block;
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.law-hero-btn:hover .btn-arrow,
.law-pill-btn:hover .btn-arrow {
    transform: translateX(6px);
}

.law-hero-since {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.65);
    display: flex;
    align-items: center;
    gap: 0.35rem;
    letter-spacing: 0.02em;
}

/* ============================================================
   INFINITE MOVING JURISDICTION MARQUEE TICKER
   ============================================================ */
.law-ticker-ribbon {
    background: #0B0F17;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    overflow: hidden;
    white-space: nowrap;
    padding: 0.85rem 0;
    position: relative;
    z-index: 10;
}

.law-ticker-track {
    display: flex;
    width: max-content;
    animation: tickerRoll 28s linear infinite;
}

.law-ticker-track:hover {
    animation-play-state: paused;
}

.law-ticker-content {
    display: flex;
    align-items: center;
    gap: 2.25rem;
    padding-right: 2.25rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: #94A3B8;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.ticker-dot {
    color: #F59E0B;
    font-size: 0.85rem;
}

@keyframes tickerRoll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

/* ============================================================
   ABOUT US SECTION (3-Column Editorial Asymmetric)
   ============================================================ */
.law-about-section {
    padding: 6.5rem 3rem 5rem;
    max-width: 1240px;
    margin: 0 auto;
}

.law-about-grid {
    display: grid;
    grid-template-columns: 220px 1.5fr 220px;
    gap: 3.5rem;
    align-items: start;
}

.law-section-marker {
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: underline;
    text-underline-offset: 4px;
    color: #111827;
    margin-bottom: 1.5rem;
    display: inline-block;
}

/* Kinetic Thumbnail with Floating Status Chip */
.law-thumb-wrap {
    position: relative;
    display: inline-block;
    width: 100%;
}

.law-thumb-box {
    width: 100%;
    aspect-ratio: 4/3;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 0.85rem;
    background: #E2E8F0;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.35s ease;
}

.law-thumb-box:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.14);
}

.law-thumb-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.5s ease;
}

.law-thumb-box:hover .law-thumb-img {
    transform: scale(1.06);
}

/* Floating Micro-Pill */
.floating-badge {
    position: absolute;
    bottom: 1.25rem;
    left: -0.75rem;
    background: rgba(15, 23, 42, 0.92);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #FFFFFF;
    padding: 0.3rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
    animation: floatGentleAlt 4s ease-in-out infinite;
    pointer-events: none;
    z-index: 5;
}

.law-thumb-caption {
    font-size: 0.75rem;
    color: #64748B;
    line-height: 1.45;
}

.law-about-lead {
    font-size: clamp(1.35rem, 2.5vw, 1.85rem);
    font-weight: 500;
    line-height: 1.45;
    color: #1E293B;
    letter-spacing: -0.015em;
    margin: 0 0 2rem;
}

.law-divider {
    border: none;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    margin: 2rem 0;
}

.law-pill-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.55rem 1.45rem;
    border-radius: 9999px;
    border: 1px solid rgba(0, 0, 0, 0.25);
    color: #1E293B;
    font-size: 0.8125rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.law-pill-btn:hover {
    background: #0F172A;
    color: #FFFFFF;
    border-color: #0F172A;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
}

/* ============================================================
   OUR SERVICE SECTION (Numbered Rows with Handled-By Thumbnails)
   ============================================================ */
.law-service-section {
    padding: 4rem 3rem 6rem;
    max-width: 1240px;
    margin: 0 auto;
}

.law-service-heading-row {
    margin-bottom: 3.5rem;
}

.law-service-title {
    font-size: clamp(1.85rem, 3.5vw, 2.75rem);
    font-weight: 600;
    line-height: 1.2;
    letter-spacing: -0.02em;
    color: #0F172A;
    max-width: 820px;
    margin: 0.75rem 0 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: default;
}

.law-service-title .btn-arrow {
    font-size: 1.75rem;
    font-weight: 400;
    color: #94A3B8;
}

.law-service-title:hover .btn-arrow {
    transform: translate(4px, -4px);
    color: #3B82F6;
}

.law-service-row {
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: 3rem;
    padding: 2.25rem 1rem;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    align-items: center;
    border-radius: 12px;
    transition: background 0.25s ease, transform 0.25s ease, box-shadow 0.25s ease;
}

.law-service-row:hover {
    background: #FFFFFF;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
}

.law-service-row:last-child {
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.law-service-left {
    display: flex;
    flex-direction: column;
}

.law-handled-by {
    font-size: 0.6875rem;
    font-weight: 700;
    color: #94A3B8;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    margin-top: 0.5rem;
}

.law-counsel-names {
    font-size: 0.75rem;
    color: #475569;
    line-height: 1.4;
    margin-top: 0.2rem;
}

.law-service-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
    flex-wrap: wrap;
}

.law-service-info {
    display: flex;
    gap: 2.5rem;
    align-items: flex-start;
    flex: 1;
}

.law-service-num {
    font-size: 1.125rem;
    font-weight: 600;
    color: #0F172A;
    font-family: var(--law-font-display);
    padding-top: 0.25rem;
}

.law-service-desc-box {
    max-width: 580px;
}

.law-service-name {
    font-size: 1.35rem;
    font-weight: 600;
    color: #0F172A;
    margin: 0 0 0.5rem;
    letter-spacing: -0.01em;
    transition: color 0.2s ease;
}

.law-service-row:hover .law-service-name {
    color: #2563EB;
}

.law-service-desc {
    font-size: 0.875rem;
    color: #64748B;
    line-height: 1.6;
    margin: 0;
}

.law-quote-btn {
    background: #0F172A;
    color: #FFFFFF;
    font-size: 0.8125rem;
    font-weight: 600;
    padding: 0.65rem 1.45rem;
    border-radius: 4px;
    text-decoration: none;
    transition: background 0.2s ease, transform 0.2s ease;
    white-space: nowrap;
    display: inline-block;
}

.law-quote-btn:hover {
    background: #2563EB;
    color: #FFFFFF;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
}

/* ============================================================
   OUR ADVANTAGE SECTION (2x2 Matrix with Moving Hover Light)
   ============================================================ */
.law-advantage-section {
    padding: 4rem 3rem 7rem;
    max-width: 1240px;
    margin: 0 auto;
}

.law-advantage-grid {
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: 3.5rem;
    align-items: start;
}

.law-advantage-content {
    display: flex;
    flex-direction: column;
}

.law-advantage-title {
    font-size: clamp(1.85rem, 3.2vw, 2.5rem);
    font-weight: 600;
    color: #0F172A;
    letter-spacing: -0.02em;
    margin: 0 0 2.5rem;
}

.law-matrix {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    border-left: 1px solid rgba(0, 0, 0, 0.1);
}

.law-matrix-cell {
    padding: 2.25rem 2rem;
    border-right: 1px solid rgba(0, 0, 0, 0.1);
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
    transition: background 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
}

.law-matrix-cell:hover {
    background: #FFFFFF;
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
}

.law-matrix-cell::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 60%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.8), transparent);
    transition: left 0.6s ease;
    pointer-events: none;
}

.law-matrix-cell:hover::after {
    left: 140%;
}

.law-matrix-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #0F172A;
    margin: 0 0 0.5rem;
}

.law-matrix-desc {
    font-size: 0.8125rem;
    color: #64748B;
    line-height: 1.6;
    margin: 0;
}

/* ============================================================
   SCROLL REVEAL ANIMATION CLASSES
   ============================================================ */
.reveal-item {
    opacity: 0;
    transform: translateY(28px);
    transition: opacity 0.75s cubic-bezier(0.16, 1, 0.3, 1), transform 0.75s cubic-bezier(0.16, 1, 0.3, 1);
    will-change: opacity, transform;
}

.reveal-item.revealed {
    opacity: 1;
    transform: translateY(0);
}

/* ============================================================
   RESPONSIVE DESIGN FOR MOBILE & TABLET
   ============================================================ */
@media (max-width: 992px) {
    .floating-seal-wrapper {
        display: none;
    }
    .law-about-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    .law-advantage-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    .law-service-row {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    .law-service-left {
        max-width: 220px;
    }
}

@media (max-width: 768px) {
    .law-hero {
        padding: 6.5rem 1.5rem 3rem;
        min-height: 85vh;
    }
    .law-hero-top-row {
        flex-direction: column;
        gap: 1.5rem;
    }
    .law-hero-bottom-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 2rem;
    }
    .law-hero-cta-box {
        align-items: flex-start;
        width: 100%;
    }
    .law-hero-btn {
        width: 100%;
        justify-content: center;
    }
    .law-about-section,
    .law-service-section,
    .law-advantage-section {
        padding: 3.5rem 1.5rem;
    }
    .law-matrix {
        grid-template-columns: 1fr;
    }
    .law-service-info {
        flex-direction: column;
        gap: 0.75rem;
    }
    .law-quote-btn {
        width: 100%;
        text-align: center;
    }
}
</style>

<!-- SECTION 1: HERO (Cinematic Courtroom with Moving Elements) -->
<section class="law-hero" id="hero">
    <!-- Kinetic Background with Slow Zoom/Pan -->
    <div class="law-hero-bg"></div>
    <!-- Interactive Mouse Spotlight Glow -->
    <div class="hero-spotlight" id="hero-spotlight"></div>

    <!-- Floating Rotating Legal Stamp / Seal -->
    <div class="floating-seal-wrapper">
        <svg class="rotating-seal" viewBox="0 0 120 120">
            <path id="seal-curve" d="M 60, 60 m -45, 0 a 45,45 0 1,1 90,0 a 45,45 0 1,1 -90,0" fill="none" />
            <text font-size="9" font-weight="700" fill="#C5A059" letter-spacing="2.5">
                <textPath href="#seal-curve" startOffset="0%">
                    • APEX LAW LLP • SRA REGULATED #649102 • SINCE 2016
                </textPath>
            </text>
            <circle cx="60" cy="60" r="28" fill="rgba(255,255,255,0.06)" stroke="rgba(197,160,89,0.4)" stroke-width="1.5"/>
            <!-- Scales icon in center -->
            <path d="M 60 48 L 60 72 M 52 54 L 68 54 M 48 64 L 56 64 A 4 4 0 0 1 48 64 M 64 64 L 72 64 A 4 4 0 0 1 64 64" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round" fill="none"/>
        </svg>
    </div>

    <div class="law-hero-content">
        <div class="law-hero-top-row">
            <!-- Left Stats with Smooth Counter Animation (95% Client success & 1,200+ Cases) -->
            <div class="law-hero-stats">
                <div class="law-stat-item reveal-item" style="transition-delay: 0.1s;">
                    <span class="law-stat-num count-up" data-target="95" data-suffix="%">95%</span>
                    <span class="law-stat-lbl">Client success</span>
                </div>
                <div class="law-stat-item reveal-item" style="transition-delay: 0.2s;">
                    <span class="law-stat-num count-up" data-target="1200" data-suffix="+">1,200+</span>
                    <span class="law-stat-lbl">Successful Cases</span>
                </div>
            </div>

            <!-- Right Mission Statement Quote -->
            <div class="law-hero-quote reveal-item" style="transition-delay: 0.3s;">
                Transforming complexities into strategic outcomes through unparalleled legal insight and innovative solutions.
            </div>
        </div>

        <!-- Main Headline & Kinetic CTA -->
        <div class="law-hero-bottom-row">
            <h1 class="law-hero-title reveal-item" style="transition-delay: 0.4s;">
                Precision Advocacy for Modern Legal Challenges
            </h1>

            <div class="law-hero-cta-box reveal-item" style="transition-delay: 0.5s;">
                <a href="/login" class="law-hero-btn">
                    <span>Find a Lawyer</span>
                    <span class="btn-arrow">&rarr;</span>
                </a>
                <div class="law-hero-since">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    <span>Serving clients since 2016</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- INFINITE MOVING JURISDICTION MARQUEE TICKER -->
<div class="law-ticker-ribbon" aria-hidden="true">
    <div class="law-ticker-track">
        <div class="law-ticker-content">
            <span>Corporate Advisory</span><span class="ticker-dot">✦</span>
            <span>High Court Litigation</span><span class="ticker-dot">✦</span>
            <span>M&A & Private Equity</span><span class="ticker-dot">✦</span>
            <span>Intellectual Property</span><span class="ticker-dot">✦</span>
            <span>SRA Regulated #649102</span><span class="ticker-dot">✦</span>
            <span>Commercial Conveyancing</span><span class="ticker-dot">✦</span>
            <span>Cross-Border Disputes</span><span class="ticker-dot">✦</span>
            <span>1,200+ Matters Resolved</span><span class="ticker-dot">✦</span>
        </div>
        <div class="law-ticker-content">
            <span>Corporate Advisory</span><span class="ticker-dot">✦</span>
            <span>High Court Litigation</span><span class="ticker-dot">✦</span>
            <span>M&A & Private Equity</span><span class="ticker-dot">✦</span>
            <span>Intellectual Property</span><span class="ticker-dot">✦</span>
            <span>SRA Regulated #649102</span><span class="ticker-dot">✦</span>
            <span>Commercial Conveyancing</span><span class="ticker-dot">✦</span>
            <span>Cross-Border Disputes</span><span class="ticker-dot">✦</span>
            <span>1,200+ Matters Resolved</span><span class="ticker-dot">✦</span>
        </div>
    </div>
</div>

<!-- SECTION 2: ABOUT US (3-Column Asymmetric Editorial Layout with Kinetic Badges) -->
<section class="law-about-section" id="about">
    <div class="law-about-grid">
        <!-- Col 1: Marker + Thumbnail + Floating Chip + Caption -->
        <div class="reveal-item" style="transition-delay: 0.1s;">
            <span class="law-section-marker">About Us.</span>
            <div class="law-thumb-wrap">
                <div class="law-thumb-box">
                    <img src="https://images.unsplash.com/photo-1450133064473-71024230f91b?auto=format&fit=crop&w=600&q=80" alt="Legal Chambers Courtroom" class="law-thumb-img">
                </div>
                <div class="floating-badge">
                    <span>🌍</span>
                    <span>15+ Jurisdictions</span>
                </div>
            </div>
            <div class="law-thumb-caption">
                Active in 15+ countries with cross-border expertise.
            </div>
        </div>

        <!-- Col 2: Large Lead Statement + Divider + Button -->
        <div class="reveal-item" style="transition-delay: 0.25s;">
            <p class="law-about-lead">
                Since our inception in 2016, we have redefined what it means to provide legal services by combining strategic foresight, meticulous analysis, and a commitment to meaningful results.
            </p>
            <hr class="law-divider">
            <div>
                <a href="/login" class="law-pill-btn">
                    <span>Learn More</span>
                    <span class="btn-arrow">&rarr;</span>
                </a>
            </div>
        </div>

        <!-- Col 3: Counter + Executive Portfolio Thumbnail -->
        <div class="reveal-item" style="transition-delay: 0.35s;">
            <div style="font-size:0.8125rem;font-weight:700;color:#94A3B8;letter-spacing:0.04em;margin-bottom:1.5rem;text-align:right;">
                50+ Attorneys
            </div>
            <div class="law-thumb-wrap">
                <div class="law-thumb-box">
                    <img src="https://images.unsplash.com/photo-1505664194779-8beaceb93744?auto=format&fit=crop&w=600&q=80" alt="Chambers Dossier" class="law-thumb-img">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 3: OUR SERVICE (Numbered Rows with Handled-By Thumbnails) -->
<section class="law-service-section" id="services">
    <div class="law-service-heading-row reveal-item">
        <span class="law-section-marker">Our Service.</span>
        <h2 class="law-service-title">
            <span>Comprehensive Legal Services Tailored to Your Needs</span>
            <span class="btn-arrow">↗</span>
        </h2>
    </div>

    <!-- Row 01: Corporate Advisory (Expanded with Handled By) -->
    <div class="law-service-row reveal-item" style="transition-delay: 0.1s;">
        <div class="law-service-left">
            <div class="law-thumb-wrap">
                <div class="law-thumb-box" style="margin-bottom:0.5rem;aspect-ratio:16/10;">
                    <img src="https://images.unsplash.com/photo-1556155092-490a1ba16284?auto=format&fit=crop&w=600&q=80" alt="Corporate Counsel Team" class="law-thumb-img">
                </div>
                <div class="floating-badge" style="bottom: 0.5rem; left: -0.5rem;">
                    <span>⚡</span> <span>15m Intake SLA</span>
                </div>
            </div>
            <div class="law-handled-by">HANDLED BY</div>
            <div class="law-counsel-names">John Carter • Emily Richards • David Kim</div>
        </div>

        <div class="law-service-body">
            <div class="law-service-info">
                <div class="law-service-num">01.</div>
                <div class="law-service-desc-box">
                    <h3 class="law-service-name">Corporate Advisory</h3>
                    <p class="law-service-desc">
                        Comprehensive legal guidance for business formations, commercial contracts, M&A transactions, and SRA regulatory compliance at competitive rates.
                    </p>
                </div>
            </div>
            <div>
                <a href="/login" class="law-quote-btn">Get a Quote</a>
            </div>
        </div>
    </div>

    <!-- Row 02: Intellectual Property Protection -->
    <div class="law-service-row reveal-item" style="transition-delay: 0.2s;">
        <div class="law-service-left">
            <div class="law-thumb-box" style="margin-bottom:0.5rem;aspect-ratio:16/10;">
                <img src="https://images.unsplash.com/photo-1450133064473-71024230f91b?auto=format&fit=crop&w=600&q=80" alt="IP Counsel" class="law-thumb-img">
            </div>
            <div class="law-handled-by">HANDLED BY</div>
            <div class="law-counsel-names">Sarah Jenkins • Marcus Vance</div>
        </div>

        <div class="law-service-body">
            <div class="law-service-info">
                <div class="law-service-num">02.</div>
                <div class="law-service-desc-box">
                    <h3 class="law-service-name">Intellectual Property Protection</h3>
                    <p class="law-service-desc">
                        Safeguarding high-value patents, international trademarks, copyright portfolios, and proprietary technology assets with proactive defense.
                    </p>
                </div>
            </div>
            <div>
                <a href="/login" class="law-quote-btn" style="background:transparent;color:#0F172A;border:1px solid rgba(0,0,0,0.2);">Learn More</a>
            </div>
        </div>
    </div>

    <!-- Row 03: Criminal Defense & Dispute Resolution -->
    <div class="law-service-row reveal-item" style="transition-delay: 0.3s;">
        <div class="law-service-left">
            <div class="law-thumb-box" style="margin-bottom:0.5rem;aspect-ratio:16/10;">
                <img src="https://images.unsplash.com/photo-1505664194779-8beaceb93744?auto=format&fit=crop&w=600&q=80" alt="Litigation Bench" class="law-thumb-img">
            </div>
            <div class="law-handled-by">HANDLED BY</div>
            <div class="law-counsel-names">Eleanor Vance • Alistair Cross KC</div>
        </div>

        <div class="law-service-body">
            <div class="law-service-info">
                <div class="law-service-num">03.</div>
                <div class="law-service-desc-box">
                    <h3 class="law-service-name">Criminal Defense & Commercial Litigation</h3>
                    <p class="law-service-desc">
                        High-stakes courtroom advocacy in financial fraud, white-collar defense, commercial arbitrations, and High Court Chancery litigation.
                    </p>
                </div>
            </div>
            <div>
                <a href="/login" class="law-quote-btn" style="background:transparent;color:#0F172A;border:1px solid rgba(0,0,0,0.2);">Learn More</a>
            </div>
        </div>
    </div>

    <!-- Row 04: Commercial Conveyancing & Property -->
    <div class="law-service-row reveal-item" style="transition-delay: 0.4s;">
        <div class="law-service-left">
            <div class="law-thumb-box" style="margin-bottom:0.5rem;aspect-ratio:16/10;">
                <img src="https://images.unsplash.com/photo-1521791136064-7986c2920216?auto=format&fit=crop&w=600&q=80" alt="Conveyancing Contract" class="law-thumb-img">
            </div>
            <div class="law-handled-by">HANDLED BY</div>
            <div class="law-counsel-names">David Ross • Chloe Davenport</div>
        </div>

        <div class="law-service-body">
            <div class="law-service-info">
                <div class="law-service-num">04.</div>
                <div class="law-service-desc-box">
                    <h3 class="law-service-name">Commercial Conveyancing & Real Estate</h3>
                    <p class="law-service-desc">
                        Full-spectrum legal management of commercial property portfolios, institutional leaseholds, zoning permissions, and acquisitions.
                    </p>
                </div>
            </div>
            <div>
                <a href="/login" class="law-quote-btn" style="background:transparent;color:#0F172A;border:1px solid rgba(0,0,0,0.2);">Learn More</a>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 4: OUR ADVANTAGE (2x2 Matrix with Moving Hover Light) -->
<section class="law-advantage-section" id="advantage">
    <div class="law-advantage-grid">
        <!-- Left: Marker + Partner Consultation Photo -->
        <div class="reveal-item" style="transition-delay: 0.1s;">
            <span class="law-section-marker">Our Advantage.</span>
            <div class="law-thumb-box" style="aspect-ratio:3/4;">
                <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=600&q=80" alt="Client Consultation" class="law-thumb-img">
            </div>
        </div>

        <!-- Right: Heading + 2x2 Feature Matrix -->
        <div class="law-advantage-content">
            <h2 class="law-advantage-title reveal-item" style="transition-delay: 0.2s;">
                The Strategic Edge That Sets Us Apart
            </h2>

            <div class="law-matrix reveal-item" style="transition-delay: 0.3s;">
                <!-- Cell 1 -->
                <div class="law-matrix-cell">
                    <h3 class="law-matrix-title">Experienced Attorneys</h3>
                    <p class="law-matrix-desc">
                        Over 50+ legal specialists with proven expertise in corporate, litigation, and regulatory domains across the UK and international jurisdictions.
                    </p>
                </div>

                <!-- Cell 2 -->
                <div class="law-matrix-cell">
                    <h3 class="law-matrix-title">Proven Track Record</h3>
                    <p class="law-matrix-desc">
                        1,200+ cases resolved with a verified 95% client satisfaction rate and multi-million pound recoveries for commercial enterprises.
                    </p>
                </div>

                <!-- Cell 3 -->
                <div class="law-matrix-cell">
                    <h3 class="law-matrix-title">Dedicated Intake & Support</h3>
                    <p class="law-matrix-desc">
                        Intelligent lead routing within 15 minutes, automated SRA conflict checks, and transparent real-time case tracking on the Chambers Docket.
                    </p>
                </div>

                <!-- Cell 4 -->
                <div class="law-matrix-cell">
                    <h3 class="law-matrix-title">Global Reach</h3>
                    <p class="law-matrix-desc">
                        Direct chambers in London with established correspondent counsel across New York, Zurich, Dubai, and Singapore.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- KINETIC JAVASCRIPT: Scroll Reveal, Counters, Spotlight -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Mouse Spotlight Tracker on Hero
    const hero = document.getElementById('hero');
    const spotlight = document.getElementById('hero-spotlight');
    if (hero && spotlight) {
        hero.addEventListener('mousemove', (e) => {
            const rect = hero.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            spotlight.style.setProperty('--mouse-x', `${x}px`);
            spotlight.style.setProperty('--mouse-y', `${y}px`);
        });
    }

    // 2. IntersectionObserver for Smooth Scroll Reveal
    const revealElements = document.querySelectorAll('.reveal-item');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.12,
        rootMargin: '0px 0px -40px 0px'
    });

    revealElements.forEach(el => observer.observe(el));

    // 3. Smooth Counter Animation on Numbers
    const counterElements = document.querySelectorAll('.count-up');
    let countersStarted = false;

    function runCounters() {
        if (countersStarted) return;
        countersStarted = true;

        counterElements.forEach(el => {
            const target = parseInt(el.dataset.target, 10);
            const suffix = el.dataset.suffix || '';
            const duration = 1600; // ms
            const startTime = performance.now();

            function updateCounter(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                // Ease-out cubic
                const easeOut = 1 - Math.pow(1 - progress, 3);
                const currentVal = Math.round(target * easeOut);

                if (target >= 1000) {
                    el.textContent = currentVal.toLocaleString() + suffix;
                } else {
                    el.textContent = currentVal + suffix;
                }

                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                }
            }

            requestAnimationFrame(updateCounter);
        });
    }

    // Start counters when hero is in view
    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                runCounters();
                statsObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });

    const statsWrap = document.querySelector('.law-hero-stats');
    if (statsWrap) {
        statsObserver.observe(statsWrap);
    }
});
</script>
