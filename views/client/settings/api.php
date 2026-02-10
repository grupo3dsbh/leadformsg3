<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Chaves de API</h1>
    </div>

    <?php if (!empty($_SESSION['_flash']['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['_flash']['success']) ?></div>
        <?php unset($_SESSION['_flash']['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_api_key'])): ?>
        <div class="alert alert-info">
            <strong>Sua nova chave de API:</strong>
            <code><?= htmlspecialchars($_SESSION['flash_api_key']) ?></code>
            <br><small>Copie agora - esta chave nao sera exibida novamente.</small>
        </div>
        <?php unset($_SESSION['flash_api_key']); ?>
    <?php endif; ?>

    <!-- Generate new key -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white"><h6 class="mb-0">Gerar Nova Chave</h6></div>
        <div class="card-body">
            <form method="POST" action="/dashboard/api-keys" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="name" class="form-label">Nome</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Ex: Integracao com CRM" required>
                </div>
                <div class="col-md-3">
                    <label for="expires_days" class="form-label">Expira em (dias)</label>
                    <select class="form-select" id="expires_days" name="expires_days">
                        <option value="">Nunca</option>
                        <option value="30">30 dias</option>
                        <option value="90">90 dias</option>
                        <option value="365">1 ano</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Gerar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing keys -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white"><h6 class="mb-0">Chaves Ativas</h6></div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Prefixo</th>
                        <th>Criada em</th>
                        <th>Expira em</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($apiKeys)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma chave de API criada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($apiKeys as $key): ?>
                        <tr>
                            <td><?= htmlspecialchars($key['name'] ?? '') ?></td>
                            <td><code><?= htmlspecialchars($key['key_prefix'] ?? '...') ?></code></td>
                            <td><?= htmlspecialchars($key['created_at'] ?? '') ?></td>
                            <td><?= $key['expires_at'] ? htmlspecialchars($key['expires_at']) : 'Nunca' ?></td>
                            <td>
                                <form method="POST" action="/dashboard/api-keys/<?= (int) $key['id'] ?>/revoke" onsubmit="return confirm('Revogar esta chave?')">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Revogar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
