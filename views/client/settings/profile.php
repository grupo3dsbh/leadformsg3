<?php
/**
 * Client - Profile Settings
 *
 * Variables from controller:
 * @var array $user
 * @var array $tenant
 */

$user = $user ?? auth();
$tenant = $tenant ?? tenant();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Meu Perfil</h1>
        <p class="page-subtitle">Gerencie suas informacoes pessoais</p>
    </div>
</div>

<div class="grid grid-3 gap-6">
    <!-- Profile Form -->
    <div style="grid-column: span 2;">
        <form method="POST" action="/dashboard/profile" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="PUT">

            <!-- Avatar Section -->
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Foto de Perfil</h3>
                </div>
                <div class="card-body">
                    <div class="flex items-center gap-6">
                        <div style="position:relative;">
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="<?= e($user['avatar']) ?>" alt="Avatar" style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid var(--gray-200);">
                            <?php else: ?>
                                <div class="avatar-initials" style="width:96px;height:96px;font-size:32px;">
                                    <?= strtoupper(substr($user['name'] ?? '', 0, 2)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="btn btn-outline btn-sm" style="cursor:pointer;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                Alterar foto
                                <input type="file" name="avatar" accept="image/*" style="display:none;" onchange="previewAvatar(this)">
                            </label>
                            <p class="form-hint mt-2">JPG, PNG ou GIF. Max 2MB.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Informacoes Pessoais</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Nome Completo <span style="color:var(--danger)">*</span></label>
                            <input type="text" name="name" class="form-input" value="<?= e($user['name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email <span style="color:var(--danger)">*</span></label>
                            <input type="email" name="email" class="form-input" value="<?= e($user['email'] ?? '') ?>" required>
                            <?php if (empty($user['email_verified_at'])): ?>
                                <p class="form-hint" style="color:var(--warning);">Email nao verificado. <a href="/dashboard/profile/verify-email" style="color:var(--primary);text-decoration:underline;">Reenviar verificacao</a></p>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Telefone</label>
                            <input type="text" name="phone" class="form-input" value="<?= e($user['phone'] ?? '') ?>" placeholder="(11) 99999-9999">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Locale Settings -->
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Preferencias Regionais</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Fuso Horario</label>
                            <select name="timezone" class="form-select">
                                <?php
                                $timezones = [
                                    'America/Sao_Paulo' => '(UTC-03:00) Sao Paulo',
                                    'America/Manaus' => '(UTC-04:00) Manaus',
                                    'America/Belem' => '(UTC-03:00) Belem',
                                    'America/Recife' => '(UTC-03:00) Recife',
                                    'America/Fortaleza' => '(UTC-03:00) Fortaleza',
                                    'America/New_York' => '(UTC-05:00) New York',
                                    'America/Chicago' => '(UTC-06:00) Chicago',
                                    'America/Los_Angeles' => '(UTC-08:00) Los Angeles',
                                    'Europe/London' => '(UTC+00:00) London',
                                    'Europe/Lisbon' => '(UTC+00:00) Lisboa',
                                    'Europe/Berlin' => '(UTC+01:00) Berlin',
                                    'Europe/Paris' => '(UTC+01:00) Paris',
                                    'Asia/Tokyo' => '(UTC+09:00) Tokyo',
                                    'UTC' => '(UTC+00:00) UTC',
                                ];
                                foreach ($timezones as $tz => $label): ?>
                                    <option value="<?= $tz ?>" <?= ($user['timezone'] ?? 'America/Sao_Paulo') === $tz ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Idioma</label>
                            <select name="locale" class="form-select">
                                <option value="pt_BR" <?= ($user['locale'] ?? 'pt_BR') === 'pt_BR' ? 'selected' : '' ?>>Portugues (Brasil)</option>
                                <option value="en" <?= ($user['locale'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
                                <option value="es" <?= ($user['locale'] ?? '') === 'es' ? 'selected' : '' ?>>Espanol</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Salvar Alteracoes
                </button>
            </div>
        </form>
    </div>

    <!-- Sidebar Info -->
    <div>
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Resumo da Conta</h3>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label" style="margin-bottom:2px;font-size:var(--font-size-xs);">Organizacao</label>
                    <p class="text-sm font-medium"><?= e($tenant['name'] ?? '-') ?></p>
                </div>
                <div class="mb-4">
                    <label class="form-label" style="margin-bottom:2px;font-size:var(--font-size-xs);">Funcao</label>
                    <span class="badge badge-primary"><?= e(ucfirst($user['role'] ?? 'viewer')) ?></span>
                </div>
                <div class="mb-4">
                    <label class="form-label" style="margin-bottom:2px;font-size:var(--font-size-xs);">Membro desde</label>
                    <p class="text-sm"><?= format_date($user['created_at'] ?? '', 'd/m/Y') ?></p>
                </div>
                <div>
                    <label class="form-label" style="margin-bottom:2px;font-size:var(--font-size-xs);">Ultimo login</label>
                    <p class="text-sm"><?= !empty($user['last_login_at']) ? format_date($user['last_login_at']) : 'Nunca' ?></p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Links Rapidos</h3>
            </div>
            <div class="card-body" style="padding:0;">
                <a href="/dashboard/profile/security" class="flex items-center gap-3 px-6 py-4" style="border-bottom:1px solid var(--gray-100);text-decoration:none;color:var(--gray-700);transition:background 150ms;" onmouseover="this.style.background='var(--gray-50)'" onmouseout="this.style.background='transparent'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    <span class="text-sm">Seguranca e Senha</span>
                </a>
                <a href="/dashboard/billing" class="flex items-center gap-3 px-6 py-4" style="border-bottom:1px solid var(--gray-100);text-decoration:none;color:var(--gray-700);transition:background 150ms;" onmouseover="this.style.background='var(--gray-50)'" onmouseout="this.style.background='transparent'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    <span class="text-sm">Faturamento</span>
                </a>
                <a href="/dashboard/integrations" class="flex items-center gap-3 px-6 py-4" style="text-decoration:none;color:var(--gray-700);transition:background 150ms;" onmouseover="this.style.background='var(--gray-50)'" onmouseout="this.style.background='transparent'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
                    <span class="text-sm">Integracoes</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.querySelector('img[alt="Avatar"]');
            if (img) {
                img.src = e.target.result;
            } else {
                // Replace initials avatar with img
                const initials = document.querySelector('.avatar-initials[style*="96px"]');
                if (initials) {
                    const newImg = document.createElement('img');
                    newImg.src = e.target.result;
                    newImg.alt = 'Avatar';
                    newImg.style = 'width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid var(--gray-200);';
                    initials.parentElement.replaceChild(newImg, initials);
                }
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
