<?php
/**
 * Client - Integrations Page
 *
 * Variables from controller:
 * @var array $integrations
 * @var array $webhooks
 * @var array $availableIntegrations
 */

$integrations = $integrations ?? [];
$webhooks = $webhooks ?? [];

// Available integration catalog
$catalog = [
    'crm' => [
        'title' => 'CRM',
        'items' => [
            ['type' => 'hubspot', 'name' => 'HubSpot', 'description' => 'Sincronize leads automaticamente com seu HubSpot CRM.', 'icon' => 'H', 'color' => '#FF7A59'],
            ['type' => 'crm', 'name' => 'Salesforce', 'description' => 'Integre com Salesforce para gestao completa de leads.', 'icon' => 'S', 'color' => '#00A1E0'],
            ['type' => 'crm', 'name' => 'Pipedrive', 'description' => 'Envie leads diretamente para seu funil no Pipedrive.', 'icon' => 'P', 'color' => '#017737'],
            ['type' => 'crm', 'name' => 'RD Station', 'description' => 'Conecte com RD Station para automacao de marketing.', 'icon' => 'RD', 'color' => '#FF5722'],
        ],
    ],
    'marketing' => [
        'title' => 'Marketing',
        'items' => [
            ['type' => 'mailchimp', 'name' => 'Mailchimp', 'description' => 'Adicione contatos as suas listas do Mailchimp automaticamente.', 'icon' => 'M', 'color' => '#FFE01B'],
            ['type' => 'custom', 'name' => 'ActiveCampaign', 'description' => 'Sincronize com ActiveCampaign para email marketing.', 'icon' => 'AC', 'color' => '#356AE6'],
            ['type' => 'custom', 'name' => 'ConvertKit', 'description' => 'Adicione subscribers ao ConvertKit automaticamente.', 'icon' => 'CK', 'color' => '#FB6970'],
        ],
    ],
    'payments' => [
        'title' => 'Pagamentos',
        'items' => [
            ['type' => 'stripe', 'name' => 'Stripe', 'description' => 'Aceite pagamentos via Stripe nos seus formularios.', 'icon' => 'S', 'color' => '#635BFF'],
            ['type' => 'paypal', 'name' => 'PayPal', 'description' => 'Integre com PayPal para processar pagamentos.', 'icon' => 'PP', 'color' => '#003087'],
        ],
    ],
    'analytics' => [
        'title' => 'Analytics',
        'items' => [
            ['type' => 'google_analytics', 'name' => 'Google Analytics', 'description' => 'Rastreie conversoes e eventos no Google Analytics.', 'icon' => 'GA', 'color' => '#E37400'],
            ['type' => 'google_tag_manager', 'name' => 'Google Tag Manager', 'description' => 'Gerencie tags e pixels via GTM.', 'icon' => 'GTM', 'color' => '#4285F4'],
            ['type' => 'facebook_pixel', 'name' => 'Facebook Pixel', 'description' => 'Rastreie conversoes de anuncios do Facebook.', 'icon' => 'FB', 'color' => '#1877F2'],
        ],
    ],
    'automation' => [
        'title' => 'Automacao',
        'items' => [
            ['type' => 'zapier', 'name' => 'Zapier', 'description' => 'Conecte com mais de 5000+ apps via Zapier.', 'icon' => 'Z', 'color' => '#FF4A00'],
            ['type' => 'custom', 'name' => 'Make (Integromat)', 'description' => 'Crie automacoes complexas com Make.', 'icon' => 'MK', 'color' => '#6D00CC'],
            ['type' => 'google_sheets', 'name' => 'Google Sheets', 'description' => 'Envie respostas automaticamente para uma planilha.', 'icon' => 'GS', 'color' => '#0F9D58'],
            ['type' => 'webhooks', 'name' => 'Webhooks', 'description' => 'Envie dados em tempo real para qualquer URL via HTTP POST.', 'icon' => 'WH', 'color' => '#6B7280'],
        ],
    ],
    'communication' => [
        'title' => 'Comunicacao',
        'items' => [
            ['type' => 'whatsapp', 'name' => 'WhatsApp', 'description' => 'Receba notificacoes e envie mensagens via WhatsApp Business.', 'icon' => 'WA', 'color' => '#25D366'],
            ['type' => 'slack', 'name' => 'Slack', 'description' => 'Receba notificacoes de novas entradas em canais do Slack.', 'icon' => 'SL', 'color' => '#4A154B'],
            ['type' => 'custom_crm', 'name' => 'Custom CRM', 'description' => 'Conecte com seu CRM personalizado via API REST.', 'icon' => 'CC', 'color' => '#1F2937'],
        ],
    ],
];

// Build a map of active integrations by type
$activeMap = [];
foreach ($integrations as $integration) {
    $activeMap[$integration['type'] ?? ''] = $integration;
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Integracoes</h1>
        <p class="page-subtitle">Conecte seus formularios com ferramentas externas</p>
    </div>
</div>

<!-- Integration Categories -->
<?php foreach ($catalog as $catKey => $category): ?>
    <div class="mb-8">
        <h2 style="font-size:var(--font-size-lg);font-weight:700;margin-bottom:var(--space-4);color:var(--gray-800);"><?= e($category['title']) ?></h2>
        <div class="grid grid-3 gap-4">
            <?php foreach ($category['items'] as $item): ?>
                <?php
                $isActive = isset($activeMap[$item['type']]);
                $integration = $activeMap[$item['type']] ?? null;
                ?>
                <div class="card" style="<?= $isActive ? 'border-color:var(--success);' : '' ?>">
                    <div class="card-body">
                        <div class="flex items-start gap-4">
                            <!-- Integration Icon -->
                            <div style="width:48px;height:48px;border-radius:var(--radius-lg);background:<?= e($item['color']) ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;font-weight:800;font-size:<?= strlen($item['icon']) > 1 ? '12px' : '18px' ?>;">
                                <?= e($item['icon']) ?>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="font-semibold text-sm"><?= e($item['name']) ?></h4>
                                    <?php if ($isActive): ?>
                                        <span class="badge badge-success" style="font-size:10px;">Ativo</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-gray-400" style="line-height:1.4;"><?= e($item['description']) ?></p>
                            </div>
                        </div>
                        <div style="margin-top:var(--space-4);">
                            <?php if ($isActive): ?>
                                <div class="flex items-center gap-2">
                                    <a href="/dashboard/integrations/<?= (int)$integration['id'] ?>/configure" class="btn btn-sm btn-outline" style="flex:1;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33"/></svg>
                                        Configurar
                                    </a>
                                    <form method="POST" action="/dashboard/integrations/<?= (int)$integration['id'] ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="btn btn-sm btn-icon btn-ghost text-danger" title="Desconectar" onclick="return confirm('Desconectar esta integracao?')">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-primary w-full" onclick="installIntegration('<?= e($item['type']) ?>', '<?= e($item['name']) ?>')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    Instalar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>

<!-- Webhook Management -->
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <h2 style="font-size:var(--font-size-lg);font-weight:700;color:var(--gray-800);">Webhooks</h2>
        <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('webhook-form').style.display = document.getElementById('webhook-form').style.display === 'none' ? 'block' : 'none'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Novo Webhook
        </button>
    </div>

    <!-- New Webhook Form (hidden) -->
    <div id="webhook-form" class="card mb-4" style="display:none;">
        <div class="card-body">
            <form method="POST" action="/dashboard/integrations/webhooks">
                <?= csrf_field() ?>
                <div class="grid grid-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">URL do Webhook <span style="color:var(--danger)">*</span></label>
                        <input type="url" name="url" class="form-input" placeholder="https://exemplo.com/webhook" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Formulario</label>
                        <select name="form_id" class="form-select">
                            <option value="">Todos os formularios</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Eventos</label>
                        <div class="flex flex-wrap gap-3">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="events[]" value="form.submitted" checked>
                                form.submitted
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="events[]" value="form.partial">
                                form.partial
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="events[]" value="entry.updated">
                                entry.updated
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Secret (HMAC)</label>
                        <input type="text" name="secret" class="form-input" placeholder="Chave secreta para verificacao">
                        <p class="form-hint">Usado para assinar as requisicoes. Opcional.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Criar Webhook</button>
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('webhook-form').style.display='none'">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Webhooks List -->
    <?php if (!empty($webhooks)): ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>URL</th>
                        <th>Formulario</th>
                        <th>Eventos</th>
                        <th>Status</th>
                        <th>Ultimo Disparo</th>
                        <th class="text-right">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($webhooks as $webhook): ?>
                        <?php $events = is_string($webhook['events'] ?? '') ? json_decode($webhook['events'], true) : ($webhook['events'] ?? []); ?>
                        <tr>
                            <td>
                                <div class="font-mono text-sm truncate" style="max-width:250px;font-family:var(--font-mono);"><?= e($webhook['url'] ?? '') ?></div>
                            </td>
                            <td>
                                <span class="text-sm"><?= !empty($webhook['form_id']) ? '#' . (int)$webhook['form_id'] : 'Todos' ?></span>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    <?php if (is_array($events)): ?>
                                        <?php foreach ($events as $ev): ?>
                                            <span class="badge badge-gray" style="font-size:10px;"><?= e($ev) ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($webhook['is_active'])): ?>
                                    <span class="badge badge-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge badge-gray">Inativo</span>
                                <?php endif; ?>
                                <?php if (!empty($webhook['last_status_code'])): ?>
                                    <span class="text-xs text-gray-400 ml-1"><?= (int)$webhook['last_status_code'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-sm text-gray-500">
                                <?= !empty($webhook['last_triggered_at']) ? format_date($webhook['last_triggered_at'], 'd/m H:i') : 'Nunca' ?>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <form method="POST" action="/dashboard/integrations/webhooks/<?= (int)$webhook['id'] ?>/toggle" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-ghost" title="<?= !empty($webhook['is_active']) ? 'Desativar' : 'Ativar' ?>">
                                            <?= !empty($webhook['is_active']) ? 'Pausar' : 'Ativar' ?>
                                        </button>
                                    </form>
                                    <form method="POST" action="/dashboard/integrations/webhooks/<?= (int)$webhook['id'] ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="btn btn-sm btn-icon btn-ghost text-danger" title="Excluir" onclick="return confirm('Excluir este webhook?')">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="empty-state" style="padding:var(--space-8);">
                <div class="empty-state-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--gray-300)" stroke-width="1.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <h3 class="empty-state-title">Nenhum webhook configurado</h3>
                <p class="empty-state-text">Webhooks enviam dados das respostas para URLs externas em tempo real.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Install Integration Modal Script -->
<script>
function installIntegration(type, name) {
    if (confirm('Deseja instalar a integracao "' + name + '"?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/dashboard/integrations';

        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = '<?= csrf_token() ?>';
        form.appendChild(tokenInput);

        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'type';
        typeInput.value = type;
        form.appendChild(typeInput);

        const nameInput = document.createElement('input');
        nameInput.type = 'hidden';
        nameInput.name = 'name';
        nameInput.value = name;
        form.appendChild(nameInput);

        document.body.appendChild(form);
        form.submit();
    }
}
</script>
