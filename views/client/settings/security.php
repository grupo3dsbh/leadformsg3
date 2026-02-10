<?php
/**
 * Client - Security Settings
 *
 * Variables from controller:
 * @var array $user
 * @var array $sessions
 * @var array $apiTokens
 * @var bool  $twoFactorEnabled
 * @var string|null $twoFactorQrUrl
 */

$user = $user ?? auth();
$sessions = $sessions ?? [];
$apiTokens = $apiTokens ?? [];
$twoFactorEnabled = $twoFactorEnabled ?? !empty($user['two_factor_enabled']);
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Seguranca</h1>
        <p class="page-subtitle">Gerencie sua senha, 2FA e sessoes ativas</p>
    </div>
</div>

<!-- Change Password -->
<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title">Alterar Senha</h3>
    </div>
    <form method="POST" action="/dashboard/profile/password">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        <div class="card-body">
            <div style="max-width:480px;">
                <div class="form-group">
                    <label class="form-label">Senha Atual <span style="color:var(--danger)">*</span></label>
                    <input type="password" name="current_password" class="form-input" required autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label class="form-label">Nova Senha <span style="color:var(--danger)">*</span></label>
                    <input type="password" name="new_password" class="form-input" required autocomplete="new-password" minlength="8">
                    <p class="form-hint">Minimo de 8 caracteres. Recomendamos usar letras, numeros e simbolos.</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirmar Nova Senha <span style="color:var(--danger)">*</span></label>
                    <input type="password" name="new_password_confirmation" class="form-input" required autocomplete="new-password">
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                Atualizar Senha
            </button>
        </div>
    </form>
</div>

<!-- Two-Factor Authentication -->
<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title">Autenticacao de Dois Fatores (2FA)</h3>
        <?php if ($twoFactorEnabled): ?>
            <span class="badge badge-success">Ativo</span>
        <?php else: ?>
            <span class="badge badge-gray">Desativado</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($twoFactorEnabled): ?>
            <div class="flex items-start gap-4 mb-4">
                <div style="width:48px;height:48px;border-radius:var(--radius-lg);background:var(--success-light);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <div>
                    <h4 class="font-semibold text-sm mb-1">2FA esta ativo</h4>
                    <p class="text-sm text-gray-500">Sua conta esta protegida com autenticacao de dois fatores. Voce precisara do codigo do seu aplicativo autenticador ao fazer login.</p>
                </div>
            </div>
            <form method="POST" action="/dashboard/profile/2fa/disable">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline text-danger" onclick="return confirm('Deseja desativar a autenticacao de dois fatores? Isso tornara sua conta menos segura.')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                    Desativar 2FA
                </button>
            </form>
        <?php else: ?>
            <div class="flex items-start gap-4 mb-4">
                <div style="width:48px;height:48px;border-radius:var(--radius-lg);background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--gray-400)" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <div>
                    <h4 class="font-semibold text-sm mb-1">2FA nao esta ativo</h4>
                    <p class="text-sm text-gray-500">Adicione uma camada extra de seguranca a sua conta usando um aplicativo autenticador como Google Authenticator ou Authy.</p>
                </div>
            </div>

            <?php if (!empty($twoFactorQrUrl)): ?>
                <div style="text-align:center;padding:var(--space-6);background:var(--gray-50);border-radius:var(--radius-lg);margin-bottom:var(--space-4);">
                    <img src="<?= e($twoFactorQrUrl) ?>" alt="QR Code" style="max-width:200px;margin:0 auto;">
                    <p class="text-xs text-gray-400 mt-3">Escaneie este QR code com seu aplicativo autenticador</p>
                </div>
                <form method="POST" action="/dashboard/profile/2fa/enable">
                    <?= csrf_field() ?>
                    <div class="form-group" style="max-width:300px;">
                        <label class="form-label">Codigo de Verificacao</label>
                        <input type="text" name="code" class="form-input" placeholder="000000" maxlength="6" required autofocus style="font-size:var(--font-size-xl);text-align:center;letter-spacing:0.3em;">
                    </div>
                    <button type="submit" class="btn btn-primary">Verificar e Ativar</button>
                </form>
            <?php else: ?>
                <form method="POST" action="/dashboard/profile/2fa">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Configurar 2FA
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Active Sessions -->
<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title">Sessoes Ativas</h3>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (!empty($sessions)): ?>
            <?php foreach ($sessions as $session): ?>
                <div class="flex items-center gap-4 px-6 py-4" style="border-bottom:1px solid var(--gray-100);">
                    <div style="width:40px;height:40px;border-radius:var(--radius-lg);background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <?php if (str_contains($session['user_agent'] ?? '', 'Mobile')): ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--gray-500)" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                        <?php else: ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--gray-500)" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="font-medium text-sm">
                            <?= e($session['ip_address'] ?? '-') ?>
                            <?php if (!empty($session['is_current'])): ?>
                                <span class="badge badge-success" style="margin-left:8px;">Sessao Atual</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-gray-400 truncate"><?= e(mb_substr($session['user_agent'] ?? '', 0, 80)) ?></div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-400">
                            <?php if (!empty($session['last_activity'])): ?>
                                <?= format_date(date('Y-m-d H:i:s', (int)$session['last_activity']), 'd/m H:i') ?>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($session['is_current'])): ?>
                            <form method="POST" action="/dashboard/profile/sessions/<?= e($session['id'] ?? '') ?>/revoke" style="display:inline;">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-ghost text-danger" onclick="return confirm('Revogar esta sessao?')">Revogar</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state" style="padding:var(--space-8)">
                <p class="text-sm text-gray-400">Nenhuma sessao ativa encontrada.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- API Tokens -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Tokens de API</h3>
        <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('new-token-form').style.display = document.getElementById('new-token-form').style.display === 'none' ? 'block' : 'none'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Novo Token
        </button>
    </div>

    <!-- New Token Form (hidden) -->
    <div id="new-token-form" style="display:none;padding:var(--space-4) var(--space-6);border-bottom:1px solid var(--gray-100);background:var(--gray-50);">
        <form method="POST" action="/dashboard/profile/tokens">
            <?= csrf_field() ?>
            <div class="grid grid-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Nome do Token</label>
                    <input type="text" name="token_name" class="form-input" placeholder="Ex: Integracao CRM" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Expiracao</label>
                    <select name="token_expiry" class="form-select">
                        <option value="">Nunca expira</option>
                        <option value="30">30 dias</option>
                        <option value="90">90 dias</option>
                        <option value="365">1 ano</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-2 mt-2">
                <button type="submit" class="btn btn-sm btn-primary">Criar Token</button>
                <button type="button" class="btn btn-sm btn-ghost" onclick="document.getElementById('new-token-form').style.display='none'">Cancelar</button>
            </div>
        </form>
    </div>

    <div class="card-body" style="padding:0;">
        <?php if (!empty($apiTokens)): ?>
            <?php foreach ($apiTokens as $token): ?>
                <div class="flex items-center gap-4 px-6 py-4" style="border-bottom:1px solid var(--gray-100);">
                    <div style="width:36px;height:36px;border-radius:var(--radius-lg);background:var(--primary-50);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 11-7.778 7.778 5.5 5.5 0 017.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="font-medium text-sm"><?= e($token['name'] ?? '') ?></div>
                        <div class="text-xs text-gray-400">
                            Criado: <?= format_date($token['created_at'] ?? '', 'd/m/Y') ?>
                            <?php if (!empty($token['last_used_at'])): ?>
                                &middot; Ultimo uso: <?= format_date($token['last_used_at'], 'd/m/Y H:i') ?>
                            <?php else: ?>
                                &middot; Nunca utilizado
                            <?php endif; ?>
                            <?php if (!empty($token['expires_at'])): ?>
                                &middot; Expira: <?= format_date($token['expires_at'], 'd/m/Y') ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <form method="POST" action="/dashboard/profile/tokens/<?= (int)$token['id'] ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-sm btn-ghost text-danger" onclick="return confirm('Revogar este token? Todas as integracoes usando este token deixarao de funcionar.')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                            Revogar
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state" style="padding:var(--space-8)">
                <div class="empty-state-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--gray-300)" stroke-width="1.5"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 11-7.778 7.778 5.5 5.5 0 017.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                </div>
                <h3 class="empty-state-title">Nenhum token de API</h3>
                <p class="empty-state-text">Crie tokens de API para integrar com sistemas externos.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
