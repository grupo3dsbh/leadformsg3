<?php
/**
 * Client - Team Users List
 *
 * Variables from controller:
 * @var array  $users       - Array of team user records
 * @var int    $totalUsers  - Total user count
 * @var int    $usersLimit  - Plan user limit
 * @var array  $roles       - Available roles
 * @var string $currentRole - Authenticated user's role
 */

$users       = $users ?? [];
$totalUsers  = (int)($totalUsers ?? count($users));
$usersLimit  = (int)($usersLimit ?? 0);
$roles       = $roles ?? ['owner' => 'Proprietario', 'admin' => 'Administrador', 'editor' => 'Editor', 'viewer' => 'Visualizador'];
$currentRole = $currentRole ?? 'owner';

function userRoleBadge(string $role): string {
    switch ($role) {
        case 'owner':  return '<span class="badge badge-primary">Proprietario</span>';
        case 'admin':  return '<span class="badge badge-info">Administrador</span>';
        case 'editor': return '<span class="badge badge-success">Editor</span>';
        case 'viewer': return '<span class="badge badge-gray">Visualizador</span>';
        default:       return '<span class="badge badge-gray">' . e(ucfirst($role)) . '</span>';
    }
}

function userStatusBadge(bool $isActive): string {
    return $isActive
        ? '<span style="display:inline-flex;align-items:center;gap:4px;"><span style="width:8px;height:8px;border-radius:50%;background:var(--success);display:inline-block;"></span> <span class="text-sm">Ativo</span></span>'
        : '<span style="display:inline-flex;align-items:center;gap:4px;"><span style="width:8px;height:8px;border-radius:50%;background:var(--gray-300);display:inline-block;"></span> <span class="text-sm text-gray-400">Inativo</span></span>';
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Usuarios da Equipe</h1>
        <p class="page-subtitle"><?= $totalUsers ?> usuario<?= $totalUsers !== 1 ? 's' : '' ?><?php if ($usersLimit > 0): ?> &middot; Limite: <?= $usersLimit ?><?php endif; ?></p>
    </div>
    <div class="page-actions">
        <?php if (in_array($currentRole, ['owner', 'admin'])): ?>
            <button class="btn btn-gradient" onclick="document.getElementById('inviteModal').classList.add('active')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                Convidar Usuario
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Users Table -->
<?php if (!empty($users)): ?>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Funcao</th>
                    <th>Status</th>
                    <th>Ultimo Acesso</th>
                    <?php if (in_array($currentRole, ['owner', 'admin'])): ?>
                        <th class="text-right">Acoes</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <?php
                        $userId      = (int)($u['id'] ?? 0);
                        $userName    = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                        $userEmail   = $u['email'] ?? '';
                        $userRole    = $u['role'] ?? 'viewer';
                        $userActive  = !empty($u['is_active']);
                        $lastLogin   = $u['last_login_at'] ?? null;
                        $userInitial = strtoupper(mb_substr($userName ?: $userEmail, 0, 1));
                        $isPending   = !empty($u['invite_pending']);
                    ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <?php if (!empty($u['avatar_url'])): ?>
                                    <img src="<?= e($u['avatar_url']) ?>" alt="" class="avatar avatar-sm">
                                <?php else: ?>
                                    <div class="avatar-initials avatar-sm" style="width:36px;height:36px;font-size:13px;"><?= $userInitial ?></div>
                                <?php endif; ?>
                                <div>
                                    <div class="font-medium text-sm">
                                        <?= e($userName ?: 'Sem nome') ?>
                                        <?php if ($isPending): ?>
                                            <span class="badge badge-warning" style="font-size:9px;padding:1px 6px;margin-left:4px;">Pendente</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="text-sm"><?= e($userEmail) ?></span>
                        </td>
                        <td>
                            <?= userRoleBadge($userRole) ?>
                        </td>
                        <td>
                            <?= userStatusBadge($userActive) ?>
                        </td>
                        <td>
                            <?php if ($lastLogin): ?>
                                <span class="text-sm"><?= format_date($lastLogin, 'd/m/Y H:i') ?></span>
                            <?php else: ?>
                                <span class="text-sm text-gray-400">Nunca</span>
                            <?php endif; ?>
                        </td>
                        <?php if (in_array($currentRole, ['owner', 'admin'])): ?>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <?php if ($userRole !== 'owner' || $currentRole === 'owner'): ?>
                                        <!-- Edit Role -->
                                        <div class="dropdown" id="role-dropdown-<?= $userId ?>">
                                            <button class="btn btn-icon btn-sm btn-ghost" onclick="this.parentElement.classList.toggle('open')" title="Alterar funcao">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </button>
                                            <div class="dropdown-menu">
                                                <?php foreach ($roles as $roleKey => $roleLabel): ?>
                                                    <?php if ($roleKey === 'owner' && $currentRole !== 'owner') continue; ?>
                                                    <form method="POST" action="/dashboard/users/<?= $userId ?>/role" style="display:contents;">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="role" value="<?= e($roleKey) ?>">
                                                        <button type="submit" class="dropdown-item <?= $userRole === $roleKey ? 'font-bold' : '' ?>" style="width:100%;border:none;background:none;cursor:pointer;text-align:left;">
                                                            <?= $userRole === $roleKey ? '&#10003; ' : '&nbsp;&nbsp;&nbsp;' ?>
                                                            <?= e($roleLabel) ?>
                                                        </button>
                                                    </form>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <!-- Toggle Active -->
                                        <?php if ($userRole !== 'owner'): ?>
                                            <form method="POST" action="/dashboard/users/<?= $userId ?>/toggle" style="display:inline;">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-icon btn-sm btn-ghost" title="<?= $userActive ? 'Desativar' : 'Ativar' ?>">
                                                    <?php if ($userActive): ?>
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--warning)" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                                    <?php else: ?>
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                                    <?php endif; ?>
                                                </button>
                                            </form>

                                            <!-- Remove -->
                                            <form method="POST" action="/dashboard/users/<?= $userId ?>" style="display:inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="submit" class="btn btn-icon btn-sm btn-ghost" title="Remover usuario" onclick="return confirm('Remover este usuario da equipe?')" style="color:var(--danger);">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--gray-300)" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            </div>
            <h3 class="empty-state-title">Nenhum usuario na equipe</h3>
            <p class="empty-state-text">Convide membros da sua equipe para colaborar nos formularios.</p>
            <button class="btn btn-gradient" onclick="document.getElementById('inviteModal').classList.add('active')">Convidar Usuario</button>
        </div>
    </div>
<?php endif; ?>

<!-- Invite User Modal -->
<div class="modal-backdrop" id="inviteModal">
    <div class="modal">
        <div class="modal-header">
            <h3 style="font-size:var(--font-size-lg);font-weight:700;">Convidar Usuario</h3>
            <button onclick="document.getElementById('inviteModal').classList.remove('active')" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--gray-400);">&times;</button>
        </div>
        <form method="POST" action="/dashboard/users/invite">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" placeholder="usuario@empresa.com" required>
                    <span class="form-hint">Um convite sera enviado para este endereco de email.</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Nome (opcional)</label>
                    <input type="text" name="name" class="form-input" placeholder="Nome do usuario">
                </div>
                <div class="form-group">
                    <label class="form-label">Funcao</label>
                    <select name="role" class="form-select" required>
                        <option value="editor">Editor - Pode editar formularios e ver entradas</option>
                        <option value="viewer">Visualizador - Apenas visualizacao</option>
                        <?php if ($currentRole === 'owner'): ?>
                            <option value="admin">Administrador - Acesso completo exceto billing</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('inviteModal').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    Enviar Convite
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Close modal on backdrop click
document.getElementById('inviteModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('active');
});

// Close dropdowns on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown.open').forEach(function(d) {
            d.classList.remove('open');
        });
    }
});
</script>
