<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Pixels de Rastreamento</h1>
    </div>

    <?php if (!empty($_SESSION['_flash']['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['_flash']['success']) ?></div>
        <?php unset($_SESSION['_flash']['success']); ?>
    <?php endif; ?>

    <!-- Add new pixel -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white"><h6 class="mb-0">Adicionar Pixel</h6></div>
        <div class="card-body">
            <form method="POST" action="/dashboard/pixels">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="name" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="col-md-3">
                        <label for="type" class="form-label">Tipo</label>
                        <select class="form-select" id="type" name="type">
                            <option value="facebook">Facebook Pixel</option>
                            <option value="google_analytics">Google Analytics</option>
                            <option value="google_tag_manager">Google Tag Manager</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="pixel_id" class="form-label">ID do Pixel</label>
                        <input type="text" class="form-control" id="pixel_id" name="pixel_id" placeholder="Ex: UA-XXXXX-X" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Salvar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing pixels -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>Nome</th><th>Tipo</th><th>ID</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    <?php if (empty($pixels)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Nenhum pixel configurado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pixels as $pixel): ?>
                        <tr>
                            <td><?= htmlspecialchars($pixel['name'] ?? '') ?></td>
                            <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $pixel['type'] ?? ''))) ?></td>
                            <td><code><?= htmlspecialchars($pixel['pixel_id'] ?? '') ?></code></td>
                            <td>
                                <span class="badge bg-<?= ($pixel['is_active'] ?? 0) ? 'success' : 'secondary' ?>">
                                    <?= ($pixel['is_active'] ?? 0) ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="/dashboard/pixels/<?= (int) $pixel['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Excluir pixel?')">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Excluir</button>
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
