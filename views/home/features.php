<?php
/**
 * Features / Funcionalidades Page
 * Variables: $features, $categories
 */
$features   = $features ?? [];
$categories = $categories ?? [];

$categoryLabels = [
    'form_builder'   => 'Construtor de Formularios',
    'submissions'    => 'Respostas e Submissoes',
    'customisation'  => 'Personalizacao',
    'analytics'      => 'Analiticos',
    'notifications'  => 'Notificacoes',
    'export'         => 'Exportacao e API',
    'security'       => 'Seguranca e Conformidade',
    'support'        => 'Suporte',
    'payments'       => 'Pagamentos',
];
?>

<style>
.features-hero { padding: 80px 0 60px; background: linear-gradient(135deg, #f8f9ff 0%, #eef2ff 50%, #f5f3ff 100%); text-align: center; }
.features-hero h1 { font-size: 2.5rem; font-weight: 800; color: #111827; margin-bottom: 16px; }
.features-hero p { font-size: 1.125rem; color: #6b7280; max-width: 600px; margin: 0 auto; line-height: 1.7; }
.features-section { padding: 64px 24px; max-width: 1100px; margin: 0 auto; }
.feature-category { margin-bottom: 48px; }
.feature-category h2 { font-size: 1.5rem; font-weight: 700; color: #1f2937; margin-bottom: 24px; padding-bottom: 12px; border-bottom: 2px solid #f3f4f6; }
.feature-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; }
.feature-card { background: #fff; border: 1px solid #f3f4f6; border-radius: 12px; padding: 24px; transition: all 0.2s ease; }
.feature-card:hover { border-color: #4f46e5; box-shadow: 0 4px 16px rgba(79,70,229,0.1); transform: translateY(-2px); }
.feature-card h3 { font-size: 1rem; font-weight: 700; color: #1f2937; margin-bottom: 8px; }
.feature-card p { font-size: 0.875rem; color: #6b7280; line-height: 1.6; margin: 0; }
.feature-plans { display: flex; gap: 8px; margin-top: 12px; }
.plan-tag { font-size: 10px; font-weight: 600; text-transform: uppercase; padding: 2px 8px; border-radius: 4px; background: #f3f4f6; color: #6b7280; }
.plan-tag.pro { background: #eef2ff; color: #4f46e5; }
.plan-tag.enterprise { background: #f5f3ff; color: #7c3aed; }
.features-cta { text-align: center; padding: 64px 24px; background: #f9fafb; }
.features-cta h2 { font-size: 1.75rem; font-weight: 800; color: #111827; margin-bottom: 16px; }
.features-cta p { color: #6b7280; margin-bottom: 24px; max-width: 500px; margin-left: auto; margin-right: auto; }
</style>

<section class="features-hero">
    <h1>Funcionalidades</h1>
    <p>Tudo que voce precisa para criar formularios incriveis que convertem visitantes em leads qualificados.</p>
</section>

<section class="features-section">
    <?php
    $grouped = [];
    foreach ($features as $f) {
        $cat = $f['category'] ?? 'other';
        $grouped[$cat][] = $f;
    }
    if (empty($grouped)):
        $grouped = [
            'form_builder' => [
                ['name' => 'Formularios Conversacionais', 'description' => 'Crie formularios envolventes estilo chat que parecem uma conversa real.'],
                ['name' => 'Construtor Drag & Drop', 'description' => 'Monte formularios visualmente com interface intuitiva de arrastar e soltar.'],
                ['name' => 'Logica Condicional', 'description' => 'Mostre ou oculte campos com base nas respostas anteriores.'],
                ['name' => 'Upload de Arquivos', 'description' => 'Permita que respondentes enviem arquivos diretamente pelo formulario.'],
                ['name' => 'Campo de Assinatura', 'description' => 'Capture assinaturas eletronicas dentro dos formularios.'],
            ],
            'analytics' => [
                ['name' => 'Analiticos Avancados', 'description' => 'Acompanhe desempenho com metricas detalhadas de conversao e abandono.'],
                ['name' => 'Lead Scoring', 'description' => 'Pontuacao automatica de leads com base nas respostas.'],
                ['name' => 'Analise por IA', 'description' => 'Classificacao inteligente de leads em frio, morno ou quente.'],
            ],
            'customisation' => [
                ['name' => 'Temas Personalizados', 'description' => 'Crie e salve temas visuais para seus formularios.'],
                ['name' => 'CSS Personalizado', 'description' => 'Adicione CSS customizado para controle total do visual.'],
                ['name' => 'Dominio Proprio', 'description' => 'Use seu proprio dominio para hospedar formularios.'],
            ],
            'export' => [
                ['name' => 'API REST', 'description' => 'Acesso completo via API para formularios, entradas e analiticos.'],
                ['name' => 'Exportar CSV/PDF', 'description' => 'Exporte respostas em CSV ou PDF com um clique.'],
                ['name' => 'Webhooks', 'description' => 'Receba dados em tempo real via webhooks para qualquer sistema.'],
            ],
        ];
    endif;
    foreach ($grouped as $cat => $items):
        $label = $categoryLabels[$cat] ?? ucfirst(str_replace('_', ' ', $cat));
    ?>
    <div class="feature-category">
        <h2><?= htmlspecialchars($label) ?></h2>
        <div class="feature-grid">
            <?php foreach ($items as $f): ?>
            <div class="feature-card">
                <h3><?= htmlspecialchars($f['name'] ?? $f['title'] ?? '') ?></h3>
                <p><?= htmlspecialchars($f['description'] ?? '') ?></p>
                <?php if (!empty($f['plan_required'])):
                    $plans = is_string($f['plan_required']) ? json_decode($f['plan_required'], true) : ($f['plan_required'] ?? []);
                ?>
                <div class="feature-plans">
                    <?php foreach (($plans ?? []) as $plan): ?>
                        <span class="plan-tag <?= $plan ?>"><?= ucfirst($plan) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</section>

<section class="features-cta">
    <h2>Pronto para comecar?</h2>
    <p>Crie sua conta gratuita e comece a construir formularios incriveis hoje mesmo.</p>
    <a href="/register" class="btn btn-gradient btn-lg">Comecar Gratis</a>
</section>
