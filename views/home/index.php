<?php
/**
 * Home / Landing Page
 *
 * Rendered inside layouts/app.php.
 * Available variables: $title, $description, $features, $plans, $testimonials, $stats
 */

$user = auth();
?>

<style>
/* ============================================
   LANDING PAGE STYLES
   ============================================ */

/* Hero */
.hero {
    position: relative;
    padding: var(--space-24) 0 var(--space-20);
    overflow: hidden;
    background: linear-gradient(135deg, #f8f9ff 0%, #eef2ff 50%, #f5f3ff 100%);
}

.hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 800px;
    height: 800px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(79,70,229,0.08) 0%, transparent 70%);
    pointer-events: none;
}

.hero::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -10%;
    width: 600px;
    height: 600px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(124,58,237,0.06) 0%, transparent 70%);
    pointer-events: none;
}

.hero-content {
    position: relative;
    z-index: 2;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-16);
    align-items: center;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: 6px 16px;
    background: var(--primary-100);
    color: var(--primary-700);
    border-radius: var(--radius-full);
    font-size: var(--font-size-sm);
    font-weight: 600;
    margin-bottom: var(--space-6);
}

.hero-title {
    font-size: clamp(2.5rem, 5vw, 3.75rem);
    font-weight: 800;
    line-height: 1.1;
    color: var(--gray-900);
    margin-bottom: var(--space-6);
    letter-spacing: -0.02em;
}

.hero-subtitle {
    font-size: var(--font-size-xl);
    color: var(--gray-600);
    line-height: 1.7;
    margin-bottom: var(--space-8);
    max-width: 540px;
}

.hero-actions {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    margin-bottom: var(--space-8);
}

.hero-stats-row {
    display: flex;
    align-items: center;
    gap: var(--space-8);
}

.hero-stat {
    text-align: left;
}

.hero-stat-value {
    font-size: var(--font-size-2xl);
    font-weight: 800;
    color: var(--gray-900);
}

.hero-stat-label {
    font-size: var(--font-size-sm);
    color: var(--gray-500);
}

/* Hero Mockup */
.hero-mockup {
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
}

.mockup-container {
    position: relative;
    width: 100%;
    max-width: 520px;
    aspect-ratio: 4 / 3.2;
    background: #fff;
    border-radius: var(--radius-2xl);
    box-shadow: var(--shadow-2xl), 0 0 80px rgba(79,70,229,0.12);
    overflow: hidden;
    border: 1px solid var(--gray-200);
}

.mockup-header {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: 14px 20px;
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}

.mockup-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.mockup-dot:nth-child(1) { background: #EF4444; }
.mockup-dot:nth-child(2) { background: #F59E0B; }
.mockup-dot:nth-child(3) { background: #10B981; }

.mockup-url {
    flex: 1;
    margin-left: var(--space-3);
    padding: 5px 12px;
    background: #fff;
    border-radius: var(--radius-md);
    font-size: var(--font-size-xs);
    color: var(--gray-400);
    border: 1px solid var(--gray-200);
}

.mockup-body {
    padding: var(--space-8) var(--space-6);
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.mockup-progress {
    display: flex;
    gap: var(--space-2);
}

.mockup-progress-step {
    height: 4px;
    flex: 1;
    border-radius: var(--radius-full);
    background: var(--gray-200);
}

.mockup-progress-step.active {
    background: var(--gradient-primary);
}

.mockup-chat-bubble {
    max-width: 80%;
    padding: 14px 20px;
    border-radius: 20px 20px 20px 4px;
    background: var(--primary-50);
    color: var(--gray-800);
    font-size: var(--font-size-sm);
    line-height: 1.5;
    animation: chatBubbleIn 0.6s ease-out;
}

.mockup-input-row {
    display: flex;
    gap: var(--space-3);
    align-items: center;
}

.mockup-input {
    flex: 1;
    padding: 12px 16px;
    border: 2px solid var(--gray-200);
    border-radius: var(--radius-xl);
    font-size: var(--font-size-sm);
    color: var(--gray-400);
}

.mockup-send-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
    flex-shrink: 0;
}

.mockup-float-card {
    position: absolute;
    padding: 12px 16px;
    background: #fff;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-xl);
    font-size: var(--font-size-xs);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: var(--space-2);
    animation: floatUpDown 3s ease-in-out infinite;
    z-index: 3;
}

.mockup-float-card.top-right {
    top: 10%;
    right: -20px;
    color: var(--success);
    animation-delay: 0.5s;
}

.mockup-float-card.bottom-left {
    bottom: 15%;
    left: -24px;
    color: var(--primary);
}

@keyframes floatUpDown {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

@keyframes chatBubbleIn {
    from { opacity: 0; transform: translateY(10px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

/* Social Proof Bar */
.social-proof {
    padding: var(--space-12) 0;
    background: #fff;
    border-top: 1px solid var(--gray-100);
    border-bottom: 1px solid var(--gray-100);
}

.social-proof-text {
    text-align: center;
    font-size: var(--font-size-sm);
    font-weight: 600;
    color: var(--gray-400);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: var(--space-8);
}

.social-proof-logos {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-12);
    flex-wrap: wrap;
}

.social-proof-logo {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--gray-300);
    transition: color var(--transition-base);
}

.social-proof-logo:hover {
    color: var(--gray-500);
}

/* Sections */
.landing-section {
    padding: var(--space-24) 0;
}

.landing-section.alt-bg {
    background: #fff;
}

.section-header {
    text-align: center;
    max-width: 680px;
    margin: 0 auto var(--space-16);
}

.section-badge {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: 4px 14px;
    background: var(--primary-50);
    color: var(--primary);
    border-radius: var(--radius-full);
    font-size: var(--font-size-xs);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: var(--space-4);
}

.section-title {
    font-size: clamp(1.875rem, 3.5vw, 2.5rem);
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: var(--space-4);
    letter-spacing: -0.01em;
}

.section-subtitle {
    font-size: var(--font-size-lg);
    color: var(--gray-500);
    line-height: 1.7;
}

/* Features Grid */
.features-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--space-6);
}

.feature-card {
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-xl);
    padding: var(--space-8);
    transition: all var(--transition-base);
    position: relative;
    overflow: hidden;
}

.feature-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--gradient-primary);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform var(--transition-base);
}

.feature-card:hover {
    border-color: var(--primary-200);
    box-shadow: var(--shadow-lg);
    transform: translateY(-4px);
}

.feature-card:hover::before {
    transform: scaleX(1);
}

.feature-icon {
    width: 52px;
    height: 52px;
    border-radius: var(--radius-lg);
    background: var(--primary-50);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    margin-bottom: var(--space-5);
}

.feature-title {
    font-size: var(--font-size-base);
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: var(--space-2);
}

.feature-desc {
    font-size: var(--font-size-sm);
    color: var(--gray-500);
    line-height: 1.6;
}

/* How It Works */
.how-it-works-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-8);
    position: relative;
}

.how-it-works-grid::before {
    content: '';
    position: absolute;
    top: 60px;
    left: 20%;
    right: 20%;
    height: 2px;
    background: linear-gradient(90deg, var(--primary-200), var(--secondary-light), var(--primary-200));
    z-index: 0;
}

.step-card {
    text-align: center;
    position: relative;
    z-index: 1;
}

.step-number {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: var(--gradient-primary);
    color: #fff;
    font-size: var(--font-size-2xl);
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto var(--space-6);
    box-shadow: var(--shadow-glow);
}

.step-illustration {
    width: 100%;
    height: 160px;
    border-radius: var(--radius-xl);
    margin-bottom: var(--space-6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 56px;
}

.step-illustration.create { background: linear-gradient(135deg, #EEF2FF, #E0E7FF); }
.step-illustration.publish { background: linear-gradient(135deg, #F0FDF4, #DCFCE7); }
.step-illustration.convert { background: linear-gradient(135deg, #FEF3C7, #FDE68A); }

.step-title {
    font-size: var(--font-size-xl);
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: var(--space-3);
}

.step-desc {
    font-size: var(--font-size-sm);
    color: var(--gray-500);
    line-height: 1.6;
    max-width: 300px;
    margin: 0 auto;
}

/* Pricing Preview */
.pricing-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-6);
    align-items: stretch;
}

.pricing-card {
    background: #fff;
    border: 2px solid var(--gray-200);
    border-radius: var(--radius-2xl);
    padding: var(--space-8);
    position: relative;
    transition: all var(--transition-base);
    display: flex;
    flex-direction: column;
}

.pricing-card.featured {
    border-color: var(--primary);
    box-shadow: var(--shadow-glow-lg);
    transform: scale(1.04);
    z-index: 2;
}

.pricing-card:hover {
    box-shadow: var(--shadow-xl);
}

.pricing-popular-badge {
    position: absolute;
    top: -14px;
    left: 50%;
    transform: translateX(-50%);
    padding: 4px 20px;
    background: var(--gradient-primary);
    color: #fff;
    font-size: var(--font-size-xs);
    font-weight: 700;
    border-radius: var(--radius-full);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
}

.pricing-plan-name {
    font-size: var(--font-size-xl);
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: var(--space-2);
}

.pricing-plan-desc {
    font-size: var(--font-size-sm);
    color: var(--gray-500);
    margin-bottom: var(--space-6);
}

.pricing-price {
    display: flex;
    align-items: baseline;
    gap: var(--space-1);
    margin-bottom: var(--space-2);
}

.pricing-currency {
    font-size: var(--font-size-xl);
    font-weight: 700;
    color: var(--gray-900);
}

.pricing-amount {
    font-size: var(--font-size-5xl);
    font-weight: 800;
    color: var(--gray-900);
    line-height: 1;
}

.pricing-period {
    font-size: var(--font-size-sm);
    color: var(--gray-400);
}

.pricing-features {
    list-style: none;
    padding: 0;
    margin: var(--space-6) 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}

.pricing-features li {
    display: flex;
    align-items: flex-start;
    gap: var(--space-2);
    font-size: var(--font-size-sm);
    color: var(--gray-600);
}

.pricing-features li .check {
    color: var(--success);
    font-weight: 700;
    flex-shrink: 0;
    margin-top: 1px;
}

.pricing-cta {
    margin-top: auto;
}

/* Integrations Section */
.integrations-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: var(--space-4);
}

.integration-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-6) var(--space-4);
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-xl);
    transition: all var(--transition-base);
    text-align: center;
}

.integration-card:hover {
    border-color: var(--primary-200);
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.integration-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.integration-name {
    font-size: var(--font-size-sm);
    font-weight: 600;
    color: var(--gray-700);
}

/* Testimonials */
.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-6);
}

.testimonial-card {
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-xl);
    padding: var(--space-8);
    transition: all var(--transition-base);
}

.testimonial-card:hover {
    box-shadow: var(--shadow-lg);
}

.testimonial-stars {
    color: #F59E0B;
    font-size: var(--font-size-sm);
    margin-bottom: var(--space-4);
    letter-spacing: 2px;
}

.testimonial-text {
    font-size: var(--font-size-base);
    color: var(--gray-600);
    line-height: 1.7;
    margin-bottom: var(--space-6);
    font-style: italic;
}

.testimonial-author {
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.testimonial-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: var(--font-size-sm);
}

.testimonial-name {
    font-size: var(--font-size-sm);
    font-weight: 700;
    color: var(--gray-900);
}

.testimonial-role {
    font-size: var(--font-size-xs);
    color: var(--gray-400);
}

/* CTA Section */
.cta-section {
    padding: var(--space-20) 0;
    background: var(--gradient-dark);
    position: relative;
    overflow: hidden;
}

.cta-section::before {
    content: '';
    position: absolute;
    top: -50%;
    left: 50%;
    transform: translateX(-50%);
    width: 800px;
    height: 800px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(99,102,241,0.2) 0%, transparent 70%);
    pointer-events: none;
}

.cta-content {
    position: relative;
    text-align: center;
    z-index: 2;
}

.cta-title {
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 800;
    color: #fff;
    margin-bottom: var(--space-4);
}

.cta-subtitle {
    font-size: var(--font-size-lg);
    color: rgba(255,255,255,0.7);
    margin-bottom: var(--space-8);
    max-width: 580px;
    margin-left: auto;
    margin-right: auto;
}

.cta-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-4);
}

.btn-white {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    padding: 14px 32px;
    background: #fff;
    color: var(--primary);
    font-weight: 700;
    font-size: var(--font-size-base);
    border: none;
    border-radius: var(--radius-xl);
    cursor: pointer;
    transition: all var(--transition-base);
    text-decoration: none;
}

.btn-white:hover {
    background: var(--gray-50);
    transform: translateY(-2px);
    box-shadow: var(--shadow-xl);
    color: var(--primary-dark);
}

.btn-outline-white {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    padding: 14px 32px;
    background: transparent;
    color: #fff;
    font-weight: 600;
    font-size: var(--font-size-base);
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: var(--radius-xl);
    cursor: pointer;
    transition: all var(--transition-base);
    text-decoration: none;
}

.btn-outline-white:hover {
    border-color: #fff;
    background: rgba(255,255,255,0.1);
    color: #fff;
}

/* Animations */
[data-animate] {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.7s ease, transform 0.7s ease;
}

[data-animate].animated {
    opacity: 1;
    transform: translateY(0);
}

[data-animate="fade-up"] { transform: translateY(30px); }
[data-animate="fade-left"] { transform: translateX(-30px); }
[data-animate="fade-right"] { transform: translateX(30px); }
[data-animate="zoom-in"] { transform: scale(0.92); }

[data-animate="fade-left"].animated,
[data-animate="fade-right"].animated {
    transform: translateX(0);
}

[data-animate="zoom-in"].animated {
    transform: scale(1);
}

/* Responsive */
@media (max-width: 1024px) {
    .hero-content { grid-template-columns: 1fr; text-align: center; }
    .hero-subtitle { margin: 0 auto var(--space-8); }
    .hero-actions { justify-content: center; }
    .hero-stats-row { justify-content: center; }
    .hero-mockup { margin-top: var(--space-8); }
    .features-grid { grid-template-columns: repeat(2, 1fr); }
    .integrations-grid { grid-template-columns: repeat(3, 1fr); }
    .pricing-card.featured { transform: scale(1); }
}

@media (max-width: 768px) {
    .hero { padding: var(--space-16) 0 var(--space-12); }
    .features-grid { grid-template-columns: 1fr; }
    .how-it-works-grid { grid-template-columns: 1fr; }
    .how-it-works-grid::before { display: none; }
    .pricing-grid { grid-template-columns: 1fr; max-width: 420px; margin: 0 auto; }
    .testimonials-grid { grid-template-columns: 1fr; }
    .integrations-grid { grid-template-columns: repeat(2, 1fr); }
    .hero-actions { flex-direction: column; }
    .hero-stats-row { flex-direction: column; gap: var(--space-4); }
    .cta-actions { flex-direction: column; }
    .mockup-float-card { display: none; }
}
</style>

<!-- ============================================
     HERO SECTION
     ============================================ -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text" data-animate="fade-right">
                <div class="hero-badge">
                    <span>&#9889;</span>
                    <span>Novo: Modo Typeform disponivel</span>
                </div>
                <h1 class="hero-title">
                    Formularios Conversacionais que
                    <span class="text-gradient">Convertem</span>
                </h1>
                <p class="hero-subtitle">
                    Crie formularios inteligentes que aumentam suas conversoes em
                    <strong>mais de 40%</strong>. Arraste, solte e publique em minutos
                    com nosso builder visual.
                </p>
                <div class="hero-actions">
                    <a href="/register" class="btn btn-gradient btn-xl">
                        Comecar Gratis
                        <span>&rarr;</span>
                    </a>
                    <a href="#demo" class="btn btn-outline btn-lg">
                        <span>&#9654;</span>
                        Ver Demo
                    </a>
                </div>
                <div class="hero-stats-row">
                    <div class="hero-stat">
                        <div class="hero-stat-value"><?= e(number_format($stats['tenants'] ?? 500, 0, ',', '.')) ?>+</div>
                        <div class="hero-stat-label">Empresas</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-value"><?= e(number_format($stats['forms'] ?? 12000, 0, ',', '.')) ?>+</div>
                        <div class="hero-stat-label">Formularios</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-value"><?= e(number_format($stats['entries'] ?? 2000000, 0, ',', '.')) ?>+</div>
                        <div class="hero-stat-label">Respostas</div>
                    </div>
                </div>
            </div>
            <div class="hero-mockup" data-animate="fade-left">
                <div class="mockup-container">
                    <div class="mockup-header">
                        <div class="mockup-dot"></div>
                        <div class="mockup-dot"></div>
                        <div class="mockup-dot"></div>
                        <div class="mockup-url"><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'seusite.com') ?>/f/meu-formulario</div>
                    </div>
                    <div class="mockup-body">
                        <div class="mockup-progress">
                            <div class="mockup-progress-step active"></div>
                            <div class="mockup-progress-step active"></div>
                            <div class="mockup-progress-step"></div>
                            <div class="mockup-progress-step"></div>
                        </div>
                        <div class="mockup-chat-bubble">
                            Ola! Qual e o seu nome completo? &#128075;
                        </div>
                        <div class="mockup-input-row">
                            <div class="mockup-input">Digite sua resposta...</div>
                            <div class="mockup-send-btn">&uarr;</div>
                        </div>
                    </div>
                </div>
                <div class="mockup-float-card top-right">
                    <span style="color: var(--success);">&#9650;</span>
                    +42% conversao
                </div>
                <div class="mockup-float-card bottom-left">
                    <span>&#9889;</span>
                    1.2s tempo medio
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================
     SOCIAL PROOF
     ============================================ -->
<section class="social-proof" data-animate="fade-up">
    <div class="container">
        <p class="social-proof-text">Confiado por +500 empresas em todo o Brasil</p>
        <div class="social-proof-logos">
            <div class="social-proof-logo">TechCorp</div>
            <div class="social-proof-logo">StartupBR</div>
            <div class="social-proof-logo">AgenciaMKT</div>
            <div class="social-proof-logo">EduOnline</div>
            <div class="social-proof-logo">SaudePlus</div>
            <div class="social-proof-logo">FinanTech</div>
        </div>
    </div>
</section>

<!-- ============================================
     FEATURES SECTION
     ============================================ -->
<section class="landing-section" id="features">
    <div class="container">
        <div class="section-header" data-animate="fade-up">
            <div class="section-badge">Funcionalidades</div>
            <h2 class="section-title">Tudo que voce precisa</h2>
            <p class="section-subtitle">
                Ferramentas poderosas para criar formularios que encantam seus usuarios
                e impulsionam suas conversoes.
            </p>
        </div>

        <div class="features-grid">
            <?php
            $featureCards = [
                ['icon' => '&#128396;', 'title' => 'Form Builder Drag & Drop', 'desc' => 'Construa formularios visualmente com nossa interface intuitiva de arrastar e soltar. Sem codigo necessario.'],
                ['icon' => '&#128172;', 'title' => 'Formularios Conversacionais', 'desc' => 'Transforme formularios chatos em conversas envolventes que aumentam drasticamente suas taxas de conclusao.'],
                ['icon' => '&#9997;', 'title' => 'Modo Typeform', 'desc' => 'Uma pergunta por vez com transicoes suaves. Experiencia moderna e imersiva para seus usuarios.'],
                ['icon' => '&#128256;', 'title' => 'Logica Condicional', 'desc' => 'Mostre ou oculte campos com base em respostas anteriores. Formularios inteligentes que se adaptam.'],
                ['icon' => '&#128200;', 'title' => 'Analytics Avancado', 'desc' => 'Acompanhe taxas de conversao, abandono por campo, tempo de preenchimento e muito mais em tempo real.'],
                ['icon' => '&#128268;', 'title' => 'Integracoes', 'desc' => 'Conecte com +50 ferramentas: Zapier, Google Sheets, WhatsApp, Mailchimp, HubSpot e mais.'],
                ['icon' => '&#128190;', 'title' => 'Salvamento Parcial', 'desc' => 'Nunca perca um lead. Dados sao salvos automaticamente enquanto o usuario preenche o formulario.'],
                ['icon' => '&#128421;', 'title' => 'API REST', 'desc' => 'API completa e documentada para integrar formularios e respostas diretamente no seu sistema.'],
            ];

            foreach ($featureCards as $idx => $fc): ?>
                <div class="feature-card" data-animate="fade-up" style="transition-delay: <?= $idx * 0.08 ?>s;">
                    <div class="feature-icon"><?= $fc['icon'] ?></div>
                    <h3 class="feature-title"><?= e($fc['title']) ?></h3>
                    <p class="feature-desc"><?= e($fc['desc']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================
     HOW IT WORKS
     ============================================ -->
<section class="landing-section alt-bg" id="how-it-works">
    <div class="container">
        <div class="section-header" data-animate="fade-up">
            <div class="section-badge">Como funciona</div>
            <h2 class="section-title">3 passos simples</h2>
            <p class="section-subtitle">
                Comece a coletar leads qualificados em menos de 5 minutos.
            </p>
        </div>

        <div class="how-it-works-grid">
            <div class="step-card" data-animate="fade-up" style="transition-delay: 0.1s;">
                <div class="step-number">1</div>
                <div class="step-illustration create">&#127912;</div>
                <h3 class="step-title">Crie</h3>
                <p class="step-desc">
                    Use nosso builder drag & drop para criar formularios
                    bonitos em minutos. Escolha entre dezenas de templates.
                </p>
            </div>
            <div class="step-card" data-animate="fade-up" style="transition-delay: 0.25s;">
                <div class="step-number">2</div>
                <div class="step-illustration publish">&#127758;</div>
                <h3 class="step-title">Publique</h3>
                <p class="step-desc">
                    Compartilhe com um link, incorpore no seu site ou use
                    como pop-up. Funciona em qualquer plataforma.
                </p>
            </div>
            <div class="step-card" data-animate="fade-up" style="transition-delay: 0.4s;">
                <div class="step-number">3</div>
                <div class="step-illustration convert">&#128176;</div>
                <h3 class="step-title">Converta</h3>
                <p class="step-desc">
                    Acompanhe resultados em tempo real, receba notificacoes
                    e integre com suas ferramentas favoritas.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================
     PRICING PREVIEW
     ============================================ -->
<section class="landing-section" id="pricing">
    <div class="container">
        <div class="section-header" data-animate="fade-up">
            <div class="section-badge">Precos</div>
            <h2 class="section-title">Planos simples e transparentes</h2>
            <p class="section-subtitle">
                Comece gratis e faca upgrade quando precisar. Sem surpresas.
            </p>
        </div>

        <div class="pricing-grid">
            <?php
            // Fallback plans if none are loaded from DB
            $displayPlans = !empty($plans) ? $plans : [
                [
                    'name' => 'Gratis',
                    'slug' => 'free',
                    'description' => 'Para comecar a explorar',
                    'price_monthly' => '0',
                    'currency' => 'BRL',
                    'is_featured' => 0,
                    'features' => json_encode(['3 formularios', '100 respostas/mes', 'Templates basicos', 'Exportar CSV']),
                    'limits' => json_encode(['forms' => 3, 'entries' => 100]),
                ],
                [
                    'name' => 'Pro',
                    'slug' => 'pro',
                    'description' => 'Para profissionais e equipes',
                    'price_monthly' => '97',
                    'currency' => 'BRL',
                    'is_featured' => 1,
                    'features' => json_encode(['Formularios ilimitados', '10.000 respostas/mes', 'Logica condicional', 'Integracoes', 'Analytics avancado', 'Remover marca', 'Dominio personalizado']),
                    'limits' => json_encode(['forms' => -1, 'entries' => 10000]),
                ],
                [
                    'name' => 'Enterprise',
                    'slug' => 'enterprise',
                    'description' => 'Para grandes operacoes',
                    'price_monthly' => '297',
                    'currency' => 'BRL',
                    'is_featured' => 0,
                    'features' => json_encode(['Tudo do Pro', 'Respostas ilimitadas', 'API completa', 'Webhooks', 'Suporte prioritario', 'SLA 99.9%', 'Onboarding dedicado', 'Multi-usuarios']),
                    'limits' => json_encode(['forms' => -1, 'entries' => -1]),
                ],
            ];

            foreach ($displayPlans as $plan):
                $isFeatured = !empty($plan['is_featured']);
                $price = $plan['price_monthly'] ?? '0';
                $features = is_string($plan['features'] ?? '') ? json_decode($plan['features'] ?? '[]', true) : ($plan['features'] ?? []);
                if (!is_array($features)) $features = [];
            ?>
                <div class="pricing-card<?= $isFeatured ? ' featured' : '' ?>" data-animate="fade-up">
                    <?php if ($isFeatured): ?>
                        <div class="pricing-popular-badge">Mais Popular</div>
                    <?php endif; ?>
                    <div class="pricing-plan-name"><?= e($plan['name'] ?? '') ?></div>
                    <div class="pricing-plan-desc"><?= e($plan['description'] ?? '') ?></div>
                    <div class="pricing-price">
                        <span class="pricing-currency">R$</span>
                        <span class="pricing-amount"><?= e(number_format((float)$price, 0, ',', '.')) ?></span>
                        <span class="pricing-period">/mes</span>
                    </div>
                    <ul class="pricing-features">
                        <?php foreach ($features as $feat): ?>
                            <li>
                                <span class="check">&#10003;</span>
                                <span><?= e((string)$feat) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="pricing-cta">
                        <a href="/register?plan=<?= e($plan['slug'] ?? '') ?>"
                           class="btn <?= $isFeatured ? 'btn-gradient' : 'btn-outline' ?> w-full btn-lg">
                            <?= $price == '0' ? 'Comecar Gratis' : 'Assinar Agora' ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="text-center mt-8 text-gray-400 text-sm">
            Todos os planos incluem SSL, backups diarios e suporte via chat.
            <a href="/pricing">Ver todos os detalhes &rarr;</a>
        </p>
    </div>
</section>

<!-- ============================================
     INTEGRATIONS
     ============================================ -->
<section class="landing-section alt-bg" id="integrations">
    <div class="container">
        <div class="section-header" data-animate="fade-up">
            <div class="section-badge">Integracoes</div>
            <h2 class="section-title">Conecte com suas ferramentas</h2>
            <p class="section-subtitle">
                Integracoes nativas com as ferramentas que voce ja usa no dia a dia.
            </p>
        </div>

        <div class="integrations-grid">
            <?php
            $integrations = [
                ['name' => 'WhatsApp', 'icon' => '&#128172;', 'bg' => '#DCFCE7'],
                ['name' => 'Google Sheets', 'icon' => '&#128202;', 'bg' => '#D1FAE5'],
                ['name' => 'Zapier', 'icon' => '&#9889;', 'bg' => '#FEF3C7'],
                ['name' => 'Stripe', 'icon' => '&#128179;', 'bg' => '#EDE9FE'],
                ['name' => 'PayPal', 'icon' => '&#128176;', 'bg' => '#DBEAFE'],
                ['name' => 'Mailchimp', 'icon' => '&#128231;', 'bg' => '#FEF3C7'],
                ['name' => 'HubSpot', 'icon' => '&#127793;', 'bg' => '#FEE2E2'],
                ['name' => 'Facebook Pixel', 'icon' => '&#128308;', 'bg' => '#DBEAFE'],
                ['name' => 'Google Analytics', 'icon' => '&#128200;', 'bg' => '#FEF3C7'],
                ['name' => 'Slack', 'icon' => '&#128488;', 'bg' => '#EDE9FE'],
            ];

            foreach ($integrations as $idx => $int): ?>
                <div class="integration-card" data-animate="fade-up" style="transition-delay: <?= $idx * 0.06 ?>s;">
                    <div class="integration-icon" style="background: <?= $int['bg'] ?>;">
                        <?= $int['icon'] ?>
                    </div>
                    <span class="integration-name"><?= e($int['name']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================
     TESTIMONIALS
     ============================================ -->
<section class="landing-section" id="testimonials">
    <div class="container">
        <div class="section-header" data-animate="fade-up">
            <div class="section-badge">Depoimentos</div>
            <h2 class="section-title">O que nossos clientes dizem</h2>
            <p class="section-subtitle">
                Empresas de todos os tamanhos confiam no LeadForm para capturar mais leads.
            </p>
        </div>

        <div class="testimonials-grid">
            <?php
            $displayTestimonials = !empty($testimonials) ? $testimonials : [
                [
                    'name' => 'Maria Silva',
                    'role' => 'CEO, TechStartup',
                    'text' => 'Aumentamos nossas conversoes em 53% no primeiro mes. O modo conversacional faz toda a diferenca na experiencia do usuario.',
                    'initials' => 'MS',
                ],
                [
                    'name' => 'Joao Santos',
                    'role' => 'Head de Marketing, AgenciaMKT',
                    'text' => 'Usavamos outra ferramenta internacional e migramos para o LeadForm. Alem de mais barato, as integracoes com WhatsApp sao incriveis.',
                    'initials' => 'JS',
                ],
                [
                    'name' => 'Ana Oliveira',
                    'role' => 'Product Manager, EduOnline',
                    'text' => 'A logica condicional e o salvamento parcial mudaram completamente nosso processo de onboarding. Recomendo muito!',
                    'initials' => 'AO',
                ],
            ];

            foreach ($displayTestimonials as $idx => $t): ?>
                <div class="testimonial-card" data-animate="fade-up" style="transition-delay: <?= $idx * 0.12 ?>s;">
                    <div class="testimonial-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                    <p class="testimonial-text">
                        &ldquo;<?= e($t['text'] ?? '') ?>&rdquo;
                    </p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">
                            <?= e($t['initials'] ?? mb_substr($t['name'] ?? 'U', 0, 2)) ?>
                        </div>
                        <div>
                            <div class="testimonial-name"><?= e($t['name'] ?? '') ?></div>
                            <div class="testimonial-role"><?= e($t['role'] ?? '') ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================
     FINAL CTA
     ============================================ -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content" data-animate="zoom-in">
            <h2 class="cta-title">Pronto para converter mais leads?</h2>
            <p class="cta-subtitle">
                Junte-se a centenas de empresas que ja aumentaram suas conversoes
                com formularios conversacionais.
            </p>
            <div class="cta-actions">
                <a href="/register" class="btn-white">
                    Comecar Gratis &rarr;
                </a>
                <a href="/contact" class="btn-outline-white">
                    Falar com Vendas
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ============================================
     SCROLL ANIMATION OBSERVER
     ============================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var animatedElements = document.querySelectorAll('[data-animate]');

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -40px 0px'
        });

        animatedElements.forEach(function(el) {
            observer.observe(el);
        });
    } else {
        // Fallback: show all elements immediately
        animatedElements.forEach(function(el) {
            el.classList.add('animated');
        });
    }
});
</script>
