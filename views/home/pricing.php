<?php
/**
 * Pricing Page
 *
 * Rendered inside layouts/app.php.
 * Available variables: $title, $description, $plans, $faqs
 */

$user = auth();
?>

<style>
/* ============================================
   PRICING PAGE STYLES
   ============================================ */

.pricing-hero {
    padding: var(--space-20) 0 var(--space-12);
    background: linear-gradient(135deg, #f8f9ff 0%, #eef2ff 50%, #f5f3ff 100%);
    text-align: center;
}

.pricing-hero-title {
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: var(--space-4);
    letter-spacing: -0.02em;
}

.pricing-hero-subtitle {
    font-size: var(--font-size-lg);
    color: var(--gray-500);
    max-width: 560px;
    margin: 0 auto;
    line-height: 1.7;
}

/* Billing Toggle */
.billing-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-4);
    margin: var(--space-10) auto 0;
}

.billing-label {
    font-size: var(--font-size-base);
    font-weight: 600;
    color: var(--gray-500);
    cursor: pointer;
    transition: color var(--transition-fast);
}

.billing-label.active {
    color: var(--gray-900);
}

.billing-switch {
    position: relative;
    width: 56px;
    height: 30px;
    cursor: pointer;
}

.billing-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.billing-switch .switch-track {
    position: absolute;
    inset: 0;
    background: var(--gray-300);
    border-radius: var(--radius-full);
    transition: background var(--transition-base);
}

.billing-switch input:checked + .switch-track {
    background: var(--primary);
}

.billing-switch .switch-track::before {
    content: '';
    position: absolute;
    left: 3px;
    top: 3px;
    width: 24px;
    height: 24px;
    background: #fff;
    border-radius: 50%;
    transition: transform var(--transition-base);
    box-shadow: var(--shadow-sm);
}

.billing-switch input:checked + .switch-track::before {
    transform: translateX(26px);
}

.billing-discount {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    background: var(--success-light);
    color: #065F46;
    font-size: var(--font-size-xs);
    font-weight: 700;
    border-radius: var(--radius-full);
}

/* Plans Section */
.pricing-plans-section {
    padding: var(--space-16) 0 var(--space-24);
}

.pricing-plans-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-6);
    align-items: stretch;
    max-width: 1100px;
    margin: 0 auto;
}

.plan-card {
    background: #fff;
    border: 2px solid var(--gray-200);
    border-radius: var(--radius-2xl);
    padding: var(--space-8) var(--space-6);
    position: relative;
    transition: all var(--transition-base);
    display: flex;
    flex-direction: column;
}

.plan-card:hover {
    box-shadow: var(--shadow-xl);
}

.plan-card.featured {
    border-color: var(--primary);
    box-shadow: 0 0 0 1px var(--primary), var(--shadow-glow-lg);
    transform: scale(1.03);
    z-index: 2;
}

.plan-popular {
    position: absolute;
    top: -13px;
    left: 50%;
    transform: translateX(-50%);
    padding: 4px 20px;
    background: var(--gradient-primary);
    color: #fff;
    font-size: var(--font-size-xs);
    font-weight: 700;
    border-radius: var(--radius-full);
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.plan-name {
    font-size: var(--font-size-xl);
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: var(--space-2);
}

.plan-description {
    font-size: var(--font-size-sm);
    color: var(--gray-500);
    margin-bottom: var(--space-6);
    line-height: 1.6;
}

.plan-price-block {
    margin-bottom: var(--space-6);
    padding-bottom: var(--space-6);
    border-bottom: 1px solid var(--gray-100);
}

.plan-price {
    display: flex;
    align-items: baseline;
    gap: var(--space-1);
}

.plan-currency {
    font-size: var(--font-size-xl);
    font-weight: 700;
    color: var(--gray-900);
}

.plan-amount {
    font-size: var(--font-size-5xl);
    font-weight: 800;
    color: var(--gray-900);
    line-height: 1;
}

.plan-amount-annual {
    display: none;
}

.plan-period {
    font-size: var(--font-size-sm);
    color: var(--gray-400);
}

.plan-annual-note {
    display: none;
    font-size: var(--font-size-xs);
    color: var(--success);
    font-weight: 600;
    margin-top: var(--space-1);
}

.plan-features-title {
    font-size: var(--font-size-xs);
    font-weight: 700;
    color: var(--gray-400);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: var(--space-4);
}

.plan-features-list {
    list-style: none;
    padding: 0;
    margin: 0 0 var(--space-8);
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}

.plan-features-list li {
    display: flex;
    align-items: flex-start;
    gap: var(--space-2);
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    line-height: 1.5;
}

.plan-features-list li .icon-check {
    color: var(--success);
    font-weight: 700;
    flex-shrink: 0;
    margin-top: 2px;
}

.plan-features-list li .icon-x {
    color: var(--gray-300);
    font-weight: 700;
    flex-shrink: 0;
    margin-top: 2px;
}

.plan-cta {
    margin-top: auto;
}

/* FAQ Section */
.faq-section {
    padding: var(--space-20) 0;
    background: #fff;
}

.faq-section .section-header {
    text-align: center;
    max-width: 680px;
    margin: 0 auto var(--space-12);
}

.faq-section .section-badge {
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

.faq-section .section-title {
    font-size: clamp(1.875rem, 3.5vw, 2.25rem);
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: var(--space-4);
}

.faq-list {
    max-width: 780px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.faq-item {
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-xl);
    overflow: hidden;
    transition: all var(--transition-base);
}

.faq-item:hover {
    border-color: var(--primary-200);
}

.faq-question {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    padding: var(--space-5) var(--space-6);
    background: #fff;
    border: none;
    cursor: pointer;
    width: 100%;
    text-align: left;
    font-size: var(--font-size-base);
    font-weight: 600;
    color: var(--gray-800);
    font-family: var(--font-family);
    transition: color var(--transition-fast);
}

.faq-question:hover {
    color: var(--primary);
}

.faq-chevron {
    font-size: var(--font-size-lg);
    color: var(--gray-400);
    transition: transform var(--transition-base);
    flex-shrink: 0;
}

.faq-item.open .faq-chevron {
    transform: rotate(180deg);
}

.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.35s ease;
}

.faq-item.open .faq-answer {
    max-height: 500px;
}

.faq-answer-inner {
    padding: 0 var(--space-6) var(--space-6);
    font-size: var(--font-size-sm);
    color: var(--gray-500);
    line-height: 1.7;
}

/* Bottom CTA */
.pricing-bottom-cta {
    padding: var(--space-16) 0;
    background: var(--gradient-dark);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.pricing-bottom-cta::before {
    content: '';
    position: absolute;
    top: -50%;
    left: 50%;
    transform: translateX(-50%);
    width: 700px;
    height: 700px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, transparent 70%);
    pointer-events: none;
}

.pricing-bottom-cta .cta-inner {
    position: relative;
    z-index: 2;
}

.pricing-bottom-cta h2 {
    font-size: var(--font-size-3xl);
    font-weight: 800;
    color: #fff;
    margin-bottom: var(--space-4);
}

.pricing-bottom-cta p {
    font-size: var(--font-size-lg);
    color: rgba(255,255,255,0.7);
    margin-bottom: var(--space-8);
    max-width: 520px;
    margin-left: auto;
    margin-right: auto;
}

.pricing-bottom-cta .btn-white {
    display: inline-flex;
    align-items: center;
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

.pricing-bottom-cta .btn-white:hover {
    background: var(--gray-50);
    transform: translateY(-2px);
    box-shadow: var(--shadow-xl);
    color: var(--primary-dark);
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

/* Responsive */
@media (max-width: 1024px) {
    .pricing-plans-grid { grid-template-columns: 1fr; max-width: 460px; margin: 0 auto; }
    .plan-card.featured { transform: scale(1); }
}

@media (max-width: 768px) {
    .pricing-hero { padding: var(--space-16) 0 var(--space-8); }
    .billing-toggle { flex-wrap: wrap; }
}
</style>

<!-- ============================================
     HERO
     ============================================ -->
<section class="pricing-hero">
    <div class="container" data-animate="fade-up">
        <h1 class="pricing-hero-title">Planos que crescem com voce</h1>
        <p class="pricing-hero-subtitle">
            Comece gratis, sem cartao de credito. Faca upgrade quando estiver pronto
            para escalar seus resultados.
        </p>

        <!-- Billing Toggle -->
        <div class="billing-toggle">
            <span class="billing-label monthly-label active" id="monthlyLabel">Mensal</span>
            <label class="billing-switch">
                <input type="checkbox" id="billingToggle">
                <span class="switch-track"></span>
            </label>
            <span class="billing-label annual-label" id="annualLabel">
                Anual
                <span class="billing-discount">-20%</span>
            </span>
        </div>
    </div>
</section>

<!-- ============================================
     PLANS
     ============================================ -->
<section class="pricing-plans-section">
    <div class="container">
        <div class="pricing-plans-grid">
            <?php
            $displayPlans = !empty($plans) ? $plans : [
                [
                    'name' => 'Gratis',
                    'slug' => 'free',
                    'description' => 'Perfeito para testar e projetos pessoais. Sem custos, sem compromisso.',
                    'price_monthly' => '0',
                    'price_yearly' => '0',
                    'currency' => 'BRL',
                    'is_featured' => 0,
                    'features' => json_encode([
                        'Ate 3 formularios',
                        '100 respostas por mes',
                        'Templates basicos',
                        'Exportar CSV',
                        'Marca LeadForm',
                        'Suporte por email',
                    ]),
                ],
                [
                    'name' => 'Pro',
                    'slug' => 'pro',
                    'description' => 'Para profissionais e equipes que querem resultados serios.',
                    'price_monthly' => '97',
                    'price_yearly' => '970',
                    'currency' => 'BRL',
                    'is_featured' => 1,
                    'features' => json_encode([
                        'Formularios ilimitados',
                        '10.000 respostas por mes',
                        'Logica condicional',
                        'Modo conversacional',
                        'Modo Typeform',
                        'Todas as integracoes',
                        'Analytics avancado',
                        'Remover marca LeadForm',
                        'Dominio personalizado',
                        'Salvamento parcial',
                        'Suporte prioritario',
                    ]),
                ],
                [
                    'name' => 'Enterprise',
                    'slug' => 'enterprise',
                    'description' => 'Para grandes operacoes com necessidades personalizadas.',
                    'price_monthly' => '297',
                    'price_yearly' => '2970',
                    'currency' => 'BRL',
                    'is_featured' => 0,
                    'features' => json_encode([
                        'Tudo do plano Pro',
                        'Respostas ilimitadas',
                        'API REST completa',
                        'Webhooks avancados',
                        'Multi-usuarios com permissoes',
                        'White-label completo',
                        'SLA 99.9% de uptime',
                        'Suporte dedicado 24/7',
                        'Onboarding personalizado',
                        'Contrato personalizado',
                        'Faturamento por boleto/NF',
                    ]),
                ],
            ];

            foreach ($displayPlans as $plan):
                $isFeatured = !empty($plan['is_featured']);
                $priceMonthly = $plan['price_monthly'] ?? '0';
                $priceYearly  = $plan['price_yearly'] ?? '0';
                $yearlyPerMonth = $priceYearly > 0 ? number_format((float)$priceYearly / 12, 0, ',', '.') : '0';
                $features = is_string($plan['features'] ?? '') ? json_decode($plan['features'] ?? '[]', true) : ($plan['features'] ?? []);
                if (!is_array($features)) $features = [];
            ?>
                <div class="plan-card<?= $isFeatured ? ' featured' : '' ?>" data-animate="fade-up">
                    <?php if ($isFeatured): ?>
                        <div class="plan-popular">Mais Popular</div>
                    <?php endif; ?>

                    <div class="plan-name"><?= e($plan['name'] ?? '') ?></div>
                    <div class="plan-description"><?= e($plan['description'] ?? '') ?></div>

                    <div class="plan-price-block">
                        <div class="plan-price">
                            <span class="plan-currency">R$</span>
                            <span class="plan-amount plan-amount-monthly"><?= e(number_format((float)$priceMonthly, 0, ',', '.')) ?></span>
                            <span class="plan-amount plan-amount-annual" style="display:none;"><?= e($yearlyPerMonth) ?></span>
                            <span class="plan-period">/mes</span>
                        </div>
                        <div class="plan-annual-note">
                            Cobrado R$ <?= e(number_format((float)$priceYearly, 0, ',', '.')) ?>/ano
                        </div>
                    </div>

                    <div class="plan-features-title">O que esta incluido:</div>
                    <ul class="plan-features-list">
                        <?php foreach ($features as $feat): ?>
                            <li>
                                <span class="icon-check">&#10003;</span>
                                <span><?= e((string)$feat) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="plan-cta">
                        <?php if ((float)$priceMonthly === 0.0): ?>
                            <a href="/register" class="btn btn-outline w-full btn-lg">Comecar Gratis</a>
                        <?php elseif ($plan['slug'] === 'enterprise'): ?>
                            <a href="/contact" class="btn btn-outline w-full btn-lg">Falar com Vendas</a>
                        <?php else: ?>
                            <a href="/register?plan=<?= e($plan['slug'] ?? '') ?>"
                               class="btn <?= $isFeatured ? 'btn-gradient' : 'btn-primary' ?> w-full btn-lg">
                                Assinar Agora
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================
     FAQ
     ============================================ -->
<section class="faq-section">
    <div class="container">
        <div class="section-header" data-animate="fade-up">
            <div class="section-badge">Perguntas Frequentes</div>
            <div class="section-title">Tire suas duvidas</div>
        </div>

        <div class="faq-list">
            <?php
            $displayFaqs = !empty($faqs) ? $faqs : [
                [
                    'question' => 'Posso testar antes de assinar?',
                    'answer' => 'Sim! Todos os planos pagos possuem 14 dias de teste gratis, sem necessidade de cartao de credito. O plano Gratis e gratuito para sempre.',
                ],
                [
                    'question' => 'Como funciona o pagamento?',
                    'answer' => 'Aceitamos cartao de credito, PIX e boleto bancario. O pagamento e processado de forma segura pela Stripe. Voce recebe nota fiscal automaticamente.',
                ],
                [
                    'question' => 'Posso trocar de plano a qualquer momento?',
                    'answer' => 'Sim! Voce pode fazer upgrade ou downgrade a qualquer momento. No upgrade, o valor e ajustado proporcionalmente. No downgrade, o credito e aplicado na proxima fatura.',
                ],
                [
                    'question' => 'O que acontece se eu ultrapassar o limite de respostas?',
                    'answer' => 'Voce recebera uma notificacao quando estiver proximo do limite. Suas respostas continuam sendo coletadas, mas recomendamos fazer upgrade para nao perder dados.',
                ],
                [
                    'question' => 'Existe desconto para ONGs ou educacao?',
                    'answer' => 'Sim! Oferecemos 50% de desconto para organizacoes sem fins lucrativos e instituicoes de ensino. Entre em contato com nosso time comercial.',
                ],
                [
                    'question' => 'Como funciona o cancelamento?',
                    'answer' => 'Voce pode cancelar a qualquer momento, sem multa ou burocracia. Seus dados ficam disponiveis para exportacao por 30 dias apos o cancelamento.',
                ],
                [
                    'question' => 'Quais integracoes estao disponiveis?',
                    'answer' => 'Oferecemos integracoes nativas com WhatsApp, Google Sheets, Zapier, Stripe, PayPal, Mailchimp, HubSpot, Facebook Pixel, Google Analytics, Slack e muito mais. No plano Enterprise, criamos integracoes personalizadas.',
                ],
            ];

            foreach ($displayFaqs as $idx => $faq): ?>
                <div class="faq-item" data-animate="fade-up" style="transition-delay: <?= $idx * 0.06 ?>s;">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <span><?= e($faq['question'] ?? '') ?></span>
                        <span class="faq-chevron">&#9660;</span>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <?= e($faq['answer'] ?? '') ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================
     BOTTOM CTA
     ============================================ -->
<section class="pricing-bottom-cta">
    <div class="cta-inner" data-animate="zoom-in">
        <h2>Ainda com duvidas?</h2>
        <p>
            Nosso time esta pronto para ajudar voce a escolher
            o melhor plano para o seu negocio.
        </p>
        <a href="/contact" class="btn-white">
            Falar com um Especialista &rarr;
        </a>
    </div>
</section>

<!-- ============================================
     SCRIPTS
     ============================================ -->
<script>
// Billing toggle (monthly/annual)
document.addEventListener('DOMContentLoaded', function() {
    var toggle = document.getElementById('billingToggle');
    var monthlyLabel = document.getElementById('monthlyLabel');
    var annualLabel  = document.getElementById('annualLabel');
    var monthlyAmounts = document.querySelectorAll('.plan-amount-monthly');
    var annualAmounts  = document.querySelectorAll('.plan-amount-annual');
    var annualNotes    = document.querySelectorAll('.plan-annual-note');

    if (toggle) {
        toggle.addEventListener('change', function() {
            var isAnnual = this.checked;

            monthlyLabel.classList.toggle('active', !isAnnual);
            annualLabel.classList.toggle('active', isAnnual);

            monthlyAmounts.forEach(function(el) { el.style.display = isAnnual ? 'none' : ''; });
            annualAmounts.forEach(function(el)  { el.style.display = isAnnual ? '' : 'none'; });
            annualNotes.forEach(function(el)    { el.style.display = isAnnual ? 'block' : 'none'; });
        });
    }

    // Scroll animations
    var animatedElements = document.querySelectorAll('[data-animate]');

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        animatedElements.forEach(function(el) { observer.observe(el); });
    } else {
        animatedElements.forEach(function(el) { el.classList.add('animated'); });
    }
});

// FAQ Accordion
function toggleFaq(btn) {
    var item = btn.closest('.faq-item');
    var isOpen = item.classList.contains('open');

    // Close all
    document.querySelectorAll('.faq-item.open').forEach(function(el) {
        el.classList.remove('open');
    });

    // Toggle clicked
    if (!isOpen) {
        item.classList.add('open');
    }
}
</script>
