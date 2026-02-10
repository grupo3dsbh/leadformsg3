<?php
/**
 * Super Admin - Settings Page with Tabs
 *
 * Variables from controller:
 * @var array $settings
 */

$activeTab = $_GET['tab'] ?? 'geral';
$tabs = [
    'geral' => 'Geral',
    'seo' => 'SEO',
    'tema' => 'Tema',
    'ia' => 'IA',
    'api' => 'API',
    'dev' => 'Desenvolvimento',
    'site' => 'Site',
];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Configuracoes</h1>
        <p class="page-subtitle">Gerencie as configuracoes da plataforma</p>
    </div>
</div>

<!-- Tabs -->
<div class="tabs">
    <?php foreach ($tabs as $key => $label): ?>
        <button type="button" class="tab <?= $activeTab === $key ? 'active' : '' ?>" onclick="switchTab('<?= $key ?>')"><?= $label ?></button>
    <?php endforeach; ?>
</div>

<!-- Tab: Geral -->
<div class="tab-content" id="tab-geral" style="<?= $activeTab !== 'geral' ? 'display:none;' : '' ?>">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Configuracoes Gerais</h3>
        </div>
        <form method="POST" action="/admin/settings">
            <?= csrf_field() ?>
            <input type="hidden" name="tab" value="geral">
            <div class="card-body">
                <div class="grid grid-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Fuso Horario</label>
                        <select name="app_timezone" class="form-select">
                            <?php foreach (['America/Sao_Paulo', 'America/New_York', 'Europe/London', 'Asia/Tokyo', 'UTC'] as $tz): ?>
                                <option value="<?= $tz ?>" <?= ($settings['app_timezone'] ?? 'America/Sao_Paulo') === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Idioma Padrao</label>
                        <select name="default_locale" class="form-select">
                            <option value="pt_BR" <?= ($settings['default_locale'] ?? 'pt_BR') === 'pt_BR' ? 'selected' : '' ?>>Portugues (BR)</option>
                            <option value="en" <?= ($settings['default_locale'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
                            <option value="es" <?= ($settings['default_locale'] ?? '') === 'es' ? 'selected' : '' ?>>Espanol</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Itens por Pagina</label>
                        <input type="number" name="items_per_page" class="form-input" value="<?= e($settings['items_per_page'] ?? '20') ?>" min="5" max="100">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Registro de Novos Usuarios</label>
                        <select name="registration" class="form-select">
                            <option value="open" <?= ($settings['registration'] ?? 'open') === 'open' ? 'selected' : '' ?>>Aberto</option>
                            <option value="closed" <?= ($settings['registration'] ?? '') === 'closed' ? 'selected' : '' ?>>Fechado</option>
                            <option value="invite" <?= ($settings['registration'] ?? '') === 'invite' ? 'selected' : '' ?>>Apenas Convite</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email - Nome do Remetente</label>
                        <input type="text" name="email_from_name" class="form-input" value="<?= e($settings['email_from_name'] ?? 'LeadForm') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email - Endereco do Remetente</label>
                        <input type="email" name="email_from_email" class="form-input" value="<?= e($settings['email_from_email'] ?? 'noreply@leadform.com') ?>">
                    </div>
                </div>

                <div style="margin-top:var(--space-6);padding-top:var(--space-6);border-top:1px solid var(--gray-100);">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="font-semibold text-sm">Modo de Manutencao</div>
                            <div class="text-xs text-gray-400">Exibe uma pagina de manutencao para todos os visitantes</div>
                        </div>
                        <label class="form-toggle">
                            <input type="checkbox" name="maintenance_mode" value="1" <?= !empty($settings['maintenance_mode']) && $settings['maintenance_mode'] === '1' ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="font-semibold text-sm">Upload de Arquivos</div>
                            <div class="text-xs text-gray-400">Permitir upload de arquivos nos formularios</div>
                        </div>
                        <label class="form-toggle">
                            <input type="checkbox" name="allow_file_uploads" value="1" <?= !empty($settings['allow_file_uploads']) && $settings['allow_file_uploads'] === '1' ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="form-group" style="max-width:200px;">
                        <label class="form-label">Tamanho Max. Upload (MB)</label>
                        <input type="number" name="max_upload_size_mb" class="form-input" value="<?= e($settings['max_upload_size_mb'] ?? '10') ?>" min="1" max="100">
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Salvar Configuracoes</button>
            </div>
        </form>
    </div>
</div>

<!-- Tab: SEO -->
<div class="tab-content" id="tab-seo" style="<?= $activeTab !== 'seo' ? 'display:none;' : '' ?>">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Configuracoes de SEO</h3>
        </div>
        <form method="POST" action="/admin/settings/seo">
            <?= csrf_field() ?>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Titulo do Site (SEO)</label>
                    <input type="text" name="seo_title" class="form-input" value="<?= e($settings['seo_title'] ?? '') ?>" placeholder="LeadForm - Formularios Inteligentes">
                </div>
                <div class="form-group">
                    <label class="form-label">Descricao (Meta Description)</label>
                    <textarea name="seo_description" class="form-textarea" rows="3" placeholder="Descricao do site para mecanismos de busca..."><?= e($settings['seo_description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Palavras-Chave</label>
                    <input type="text" name="seo_keywords" class="form-input" value="<?= e($settings['seo_keywords'] ?? '') ?>" placeholder="formularios, leads, conversao">
                </div>
                <div class="grid grid-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Imagem OG (Open Graph)</label>
                        <input type="text" name="seo_og_image" class="form-input" value="<?= e($settings['seo_og_image'] ?? '') ?>" placeholder="https://exemplo.com/og-image.jpg">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Robots</label>
                        <input type="text" name="seo_robots" class="form-input" value="<?= e($settings['seo_robots'] ?? 'index, follow') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Google Site Verification</label>
                        <input type="text" name="seo_google_verification" class="form-input" value="<?= e($settings['seo_google_verification'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bing Site Verification</label>
                        <input type="text" name="seo_bing_verification" class="form-input" value="<?= e($settings['seo_bing_verification'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">HTML Customizado (Head)</label>
                    <textarea name="seo_custom_head" class="form-textarea" rows="4" placeholder="<!-- Scripts, tags, etc -->" style="font-family:var(--font-mono);font-size:var(--font-size-sm);"><?= e($settings['seo_custom_head'] ?? '') ?></textarea>
                    <p class="form-hint">Codigo inserido antes do &lt;/head&gt;. Use com cuidado.</p>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Salvar SEO</button>
            </div>
        </form>
    </div>
</div>

<!-- Tab: Tema -->
<div class="tab-content" id="tab-tema" style="<?= $activeTab !== 'tema' ? 'display:none;' : '' ?>">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Configuracoes de Tema</h3>
        </div>
        <form method="POST" action="/admin/settings/theme">
            <?= csrf_field() ?>
            <div class="card-body">
                <div class="grid grid-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Cor Primaria</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="theme_primary_color" value="<?= e($settings['theme_primary_color'] ?? '#4F46E5') ?>" style="width:48px;height:36px;border:1px solid var(--gray-200);border-radius:var(--radius-md);cursor:pointer;">
                            <input type="text" class="form-input" value="<?= e($settings['theme_primary_color'] ?? '#4F46E5') ?>" style="flex:1;" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cor Secundaria</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="theme_secondary_color" value="<?= e($settings['theme_secondary_color'] ?? '#10B981') ?>" style="width:48px;height:36px;border:1px solid var(--gray-200);border-radius:var(--radius-md);cursor:pointer;">
                            <input type="text" class="form-input" value="<?= e($settings['theme_secondary_color'] ?? '#10B981') ?>" style="flex:1;" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Familia de Fonte</label>
                        <select name="theme_font_family" class="form-select">
                            <option value="Inter" <?= ($settings['theme_font_family'] ?? 'Inter') === 'Inter' ? 'selected' : '' ?>>Inter</option>
                            <option value="Roboto" <?= ($settings['theme_font_family'] ?? '') === 'Roboto' ? 'selected' : '' ?>>Roboto</option>
                            <option value="Poppins" <?= ($settings['theme_font_family'] ?? '') === 'Poppins' ? 'selected' : '' ?>>Poppins</option>
                            <option value="Open Sans" <?= ($settings['theme_font_family'] ?? '') === 'Open Sans' ? 'selected' : '' ?>>Open Sans</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:var(--space-6);padding-top:var(--space-6);border-top:1px solid var(--gray-100);">
                    <h4 class="font-semibold mb-4">Pagina Inicial (Hero)</h4>
                    <div class="form-group">
                        <label class="form-label">Titulo do Hero</label>
                        <input type="text" name="theme_hero_title" class="form-input" value="<?= e($settings['theme_hero_title'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subtitulo do Hero</label>
                        <textarea name="theme_hero_subtitle" class="form-textarea" rows="2"><?= e($settings['theme_hero_subtitle'] ?? '') ?></textarea>
                    </div>
                    <div class="grid grid-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Texto do CTA</label>
                            <input type="text" name="theme_hero_cta_text" class="form-input" value="<?= e($settings['theme_hero_cta_text'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">URL do CTA</label>
                            <input type="text" name="theme_hero_cta_url" class="form-input" value="<?= e($settings['theme_hero_cta_url'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="font-semibold text-sm">Modo Escuro</div>
                            <div class="text-xs text-gray-400">Habilitar modo escuro na pagina inicial</div>
                        </div>
                        <label class="form-toggle">
                            <input type="checkbox" name="theme_dark_mode" value="1" <?= !empty($settings['theme_dark_mode']) && $settings['theme_dark_mode'] === '1' ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="font-semibold text-sm">Mostrar Precos</div>
                            <div class="text-xs text-gray-400">Exibir secao de precos na pagina inicial</div>
                        </div>
                        <label class="form-toggle">
                            <input type="checkbox" name="theme_show_pricing" value="1" <?= !empty($settings['theme_show_pricing']) && $settings['theme_show_pricing'] === '1' ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                <div class="form-group mt-6">
                    <label class="form-label">CSS Personalizado</label>
                    <textarea name="theme_custom_css" class="form-textarea" rows="5" style="font-family:var(--font-mono);font-size:var(--font-size-sm);"><?= e($settings['theme_custom_css'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Salvar Tema</button>
            </div>
        </form>
    </div>
</div>

<!-- Tab: IA -->
<div class="tab-content" id="tab-ia" style="<?= $activeTab !== 'ia' ? 'display:none;' : '' ?>">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Configuracoes de Inteligencia Artificial</h3>
        </div>
        <form method="POST" action="/admin/settings/ai">
            <?= csrf_field() ?>
            <div class="card-body">
                <div class="grid grid-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Provedor de IA</label>
                        <select name="ai_provider" class="form-select">
                            <option value="disabled" <?= ($settings['ai_provider'] ?? 'disabled') === 'disabled' ? 'selected' : '' ?>>Desabilitado</option>
                            <option value="openai" <?= ($settings['ai_provider'] ?? '') === 'openai' ? 'selected' : '' ?>>OpenAI</option>
                            <option value="anthropic" <?= ($settings['ai_provider'] ?? '') === 'anthropic' ? 'selected' : '' ?>>Anthropic</option>
                            <option value="google" <?= ($settings['ai_provider'] ?? '') === 'google' ? 'selected' : '' ?>>Google AI</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Modelo</label>
                        <input type="text" name="ai_model" class="form-input" value="<?= e($settings['ai_model'] ?? 'gpt-4') ?>" placeholder="gpt-4, claude-3-opus, etc">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Chave de API</label>
                    <input type="password" name="ai_api_key" class="form-input" value="<?= e($settings['ai_api_key'] ?? '') ?>" placeholder="sk-...">
                    <p class="form-hint">A chave e armazenada de forma segura e nunca e exibida.</p>
                </div>
                <div class="grid grid-3 gap-6">
                    <div class="form-group">
                        <label class="form-label">Max Tokens</label>
                        <input type="number" name="ai_max_tokens" class="form-input" value="<?= e($settings['ai_max_tokens'] ?? '4096') ?>" min="100" max="100000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Temperatura</label>
                        <input type="number" name="ai_temperature" class="form-input" value="<?= e($settings['ai_temperature'] ?? '0.7') ?>" min="0" max="2" step="0.1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Limite Mensal</label>
                        <input type="number" name="ai_monthly_limit" class="form-input" value="<?= e($settings['ai_monthly_limit'] ?? '0') ?>" min="0">
                        <p class="form-hint">0 = ilimitado</p>
                    </div>
                </div>
                <div style="margin-top:var(--space-6);padding-top:var(--space-6);border-top:1px solid var(--gray-100);">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="font-semibold text-sm">Habilitar IA para Clientes</div>
                            <div class="text-xs text-gray-400">Permitir que os clientes utilizem recursos de IA</div>
                        </div>
                        <label class="form-toggle">
                            <input type="checkbox" name="ai_enabled_for_clients" value="1" <?= !empty($settings['ai_enabled_for_clients']) && $settings['ai_enabled_for_clients'] === '1' ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="font-semibold text-sm">Geracao de Formularios com IA</div>
                            <div class="text-xs text-gray-400">Gerar formularios automaticamente usando IA</div>
                        </div>
                        <label class="form-toggle">
                            <input type="checkbox" name="ai_form_generation" value="1" <?= !empty($settings['ai_form_generation']) && $settings['ai_form_generation'] === '1' ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-sm">Analise de Respostas com IA</div>
                            <div class="text-xs text-gray-400">Analisar respostas e gerar insights com IA</div>
                        </div>
                        <label class="form-toggle">
                            <input type="checkbox" name="ai_entry_analysis" value="1" <?= !empty($settings['ai_entry_analysis']) && $settings['ai_entry_analysis'] === '1' ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Salvar IA</button>
            </div>
        </form>
    </div>
</div>

<!-- Tab: API -->
<div class="tab-content" id="tab-api" style="<?= $activeTab !== 'api' ? 'display:none;' : '' ?>">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Configuracoes de API</h3>
        </div>
        <form method="POST" action="/admin/settings/api">
            <?= csrf_field() ?>
            <div class="card-body">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <div class="font-semibold text-sm">API Habilitada</div>
                        <div class="text-xs text-gray-400">Habilitar acesso via API REST</div>
                    </div>
                    <label class="form-toggle">
                        <input type="checkbox" name="api_enabled" value="1" <?= !empty($settings['api_enabled']) && $settings['api_enabled'] === '1' ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="grid grid-3 gap-6">
                    <div class="form-group">
                        <label class="form-label">Rate Limit (requisicoes)</label>
                        <input type="number" name="api_rate_limit" class="form-input" value="<?= e($settings['api_rate_limit'] ?? '60') ?>" min="1" max="10000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Janela de Rate Limit (seg)</label>
                        <input type="number" name="api_rate_window" class="form-input" value="<?= e($settings['api_rate_window'] ?? '60') ?>" min="1" max="3600">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Validade do Token (dias)</label>
                        <input type="number" name="api_key_expiry_days" class="form-input" value="<?= e($settings['api_key_expiry_days'] ?? '0') ?>" min="0">
                        <p class="form-hint">0 = sem expiracao</p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">CORS Origins</label>
                    <input type="text" name="api_cors_origins" class="form-input" value="<?= e($settings['api_cors_origins'] ?? '*') ?>" placeholder="* ou https://exemplo.com">
                    <p class="form-hint">Use * para permitir qualquer origem, ou especifique dominios separados por virgula.</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Webhook Timeout (seg)</label>
                    <input type="number" name="api_webhook_timeout" class="form-input" value="<?= e($settings['api_webhook_timeout'] ?? '30') ?>" min="5" max="120" style="max-width:200px;">
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-semibold text-sm">Log de Requisicoes</div>
                        <div class="text-xs text-gray-400">Gravar log de todas as requisicoes da API</div>
                    </div>
                    <label class="form-toggle">
                        <input type="checkbox" name="api_log_requests" value="1" <?= !empty($settings['api_log_requests']) && $settings['api_log_requests'] === '1' ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Salvar API</button>
            </div>
        </form>
    </div>
</div>

<!-- Tab: Desenvolvimento -->
<div class="tab-content" id="tab-dev" style="<?= $activeTab !== 'dev' ? 'display:none;' : '' ?>">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Modo de Desenvolvimento</h3>
            <span class="badge badge-warning">Cuidado</span>
        </div>
        <form method="POST" action="/admin/settings/dev">
            <?= csrf_field() ?>
            <div class="card-body">
                <div style="background:var(--warning-light);color:#92400E;padding:var(--space-4);border-radius:var(--radius-lg);margin-bottom:var(--space-6);font-size:var(--font-size-sm);">
                    <strong>Atencao:</strong> Estas configuracoes podem impactar a seguranca e o desempenho da aplicacao. Utilize com cuidado em producao.
                </div>
                <div class="form-group">
                    <label class="form-label">Ambiente</label>
                    <select name="app_env" class="form-select" style="max-width:300px;">
                        <option value="production" <?= ($settings['app_env'] ?? 'production') === 'production' ? 'selected' : '' ?>>Producao</option>
                        <option value="staging" <?= ($settings['app_env'] ?? '') === 'staging' ? 'selected' : '' ?>>Staging</option>
                        <option value="development" <?= ($settings['app_env'] ?? '') === 'development' ? 'selected' : '' ?>>Desenvolvimento</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nivel de Log</label>
                    <select name="dev_log_level" class="form-select" style="max-width:300px;">
                        <?php foreach (['debug', 'info', 'warning', 'error', 'critical'] as $level): ?>
                            <option value="<?= $level ?>" <?= ($settings['dev_log_level'] ?? 'error') === $level ? 'selected' : '' ?>><?= ucfirst($level) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="margin-top:var(--space-4);">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="font-semibold text-sm">Debug Mode</div>
                            <div class="text-xs text-gray-400">Exibir erros detalhados</div>
                        </div>
                        <label class="form-toggle">
                            <input type="checkbox" name="app_debug" value="1" <?= !empty($settings['app_debug']) && $settings['app_debug'] === '1' ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="font-semibold text-sm">Log de Queries SQL</div>
                            <div class="text-xs text-gray-400">Registrar todas as consultas SQL</div>
                        </div>
                        <label class="form-toggle">
                            <input type="checkbox" name="dev_show_query_log" value="1" <?= !empty($settings['dev_show_query_log']) && $settings['dev_show_query_log'] === '1' ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="font-semibold text-sm">Debug de Rotas</div>
                            <div class="text-xs text-gray-400">Mostrar informacoes de roteamento</div>
                        </div>
                        <label class="form-toggle">
                            <input type="checkbox" name="dev_show_route_debug" value="1" <?= !empty($settings['dev_show_route_debug']) && $settings['dev_show_route_debug'] === '1' ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="font-semibold text-sm">Profiling</div>
                            <div class="text-xs text-gray-400">Habilitar profiling de desempenho</div>
                        </div>
                        <label class="form-toggle">
                            <input type="checkbox" name="dev_profiling_enabled" value="1" <?= !empty($settings['dev_profiling_enabled']) && $settings['dev_profiling_enabled'] === '1' ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-sm">Error Reporting Completo</div>
                            <div class="text-xs text-gray-400">E_ALL error reporting (PHP)</div>
                        </div>
                        <label class="form-toggle">
                            <input type="checkbox" name="dev_error_reporting" value="1" <?= !empty($settings['dev_error_reporting']) && $settings['dev_error_reporting'] === '1' ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Salvar Desenvolvimento</button>
            </div>
        </form>
    </div>
</div>

<!-- Tab: Site -->
<div class="tab-content" id="tab-site" style="<?= $activeTab !== 'site' ? 'display:none;' : '' ?>">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informacoes do Site</h3>
        </div>
        <form method="POST" action="/admin/settings/site" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="card-body">
                <div class="grid grid-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Nome do Site</label>
                        <input type="text" name="site_name" class="form-input" value="<?= e($settings['site_name'] ?? 'LeadForm') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tagline</label>
                        <input type="text" name="site_tagline" class="form-input" value="<?= e($settings['site_tagline'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email de Contato</label>
                        <input type="email" name="site_email" class="form-input" value="<?= e($settings['site_email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="site_phone" class="form-input" value="<?= e($settings['site_phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">URL do Site</label>
                        <input type="url" name="site_url" class="form-input" value="<?= e($settings['site_url'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Endereco</label>
                        <input type="text" name="site_address" class="form-input" value="<?= e($settings['site_address'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Descricao do Site</label>
                    <textarea name="site_description" class="form-textarea" rows="3"><?= e($settings['site_description'] ?? '') ?></textarea>
                </div>
                <div class="grid grid-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Logo</label>
                        <input type="file" name="site_logo" class="form-input" accept="image/*">
                        <?php if (!empty($settings['site_logo'])): ?>
                            <div class="mt-2">
                                <img src="<?= e($settings['site_logo']) ?>" alt="Logo" style="max-height:40px;">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Favicon</label>
                        <input type="file" name="site_favicon" class="form-input" accept="image/x-icon,image/png,image/svg+xml">
                    </div>
                </div>
                <div style="margin-top:var(--space-6);padding-top:var(--space-6);border-top:1px solid var(--gray-100);">
                    <h4 class="font-semibold mb-4">Redes Sociais</h4>
                    <div class="grid grid-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Facebook</label>
                            <input type="url" name="site_social_facebook" class="form-input" value="<?= e($settings['site_social_facebook'] ?? '') ?>" placeholder="https://facebook.com/...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Twitter / X</label>
                            <input type="url" name="site_social_twitter" class="form-input" value="<?= e($settings['site_social_twitter'] ?? '') ?>" placeholder="https://twitter.com/...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">LinkedIn</label>
                            <input type="url" name="site_social_linkedin" class="form-input" value="<?= e($settings['site_social_linkedin'] ?? '') ?>" placeholder="https://linkedin.com/...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">GitHub</label>
                            <input type="url" name="site_social_github" class="form-input" value="<?= e($settings['site_social_github'] ?? '') ?>" placeholder="https://github.com/...">
                        </div>
                    </div>
                </div>
                <div style="margin-top:var(--space-6);padding-top:var(--space-6);border-top:1px solid var(--gray-100);">
                    <h4 class="font-semibold mb-4">Rodape e Legal</h4>
                    <div class="form-group">
                        <label class="form-label">Texto do Rodape</label>
                        <input type="text" name="site_footer_text" class="form-input" value="<?= e($settings['site_footer_text'] ?? '') ?>">
                    </div>
                    <div class="grid grid-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">URL dos Termos de Uso</label>
                            <input type="url" name="site_terms_url" class="form-input" value="<?= e($settings['site_terms_url'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">URL da Politica de Privacidade</label>
                            <input type="url" name="site_privacy_url" class="form-input" value="<?= e($settings['site_privacy_url'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Salvar Site</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));

    // Show selected tab
    const tabEl = document.getElementById('tab-' + tabName);
    if (tabEl) tabEl.style.display = 'block';

    // Update active tab button
    const buttons = document.querySelectorAll('.tab');
    buttons.forEach(btn => {
        if (btn.textContent.trim().toLowerCase().replace(/ã/g,'a').replace(/ç/g,'c') === tabName ||
            btn.onclick.toString().includes("'" + tabName + "'")) {
            btn.classList.add('active');
        }
    });

    // Update URL without reload
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.replaceState({}, '', url);
}
</script>
