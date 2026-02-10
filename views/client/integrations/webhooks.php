<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Webhooks</h1>
        <a href="/dashboard/webhooks/create" class="btn btn-primary">Novo Webhook</a>
    </div>

    <?php if (!empty($_SESSION['_flash']['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['_flash']['success']) ?></div>
        <?php unset($_SESSION['_flash']['success']); ?>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>URL</th>
                        <th>Formulario</th>
                        <th>Eventos</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($webhooks)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Nenhum webhook configurado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($webhooks as $wh): ?>
                        <tr>
                            <td class="text-truncate" style="max-width:200px">
                                <code><?= htmlspecialchars($wh['url'] ?? '') ?></code>
                            </td>
                            <td><?= htmlspecialchars($wh['form_title'] ?? 'Todos') ?></td>
                            <td>
                                <?php $events = json_decode($wh['events'] ?? '[]', true) ?: []; ?>
                                <?php foreach ($events as $ev): ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($ev) ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= ($wh['is_active'] ?? 0) ? 'success' : 'secondary' ?>">
                                    <?= ($wh['is_active'] ?? 0) ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td>
                                <a href="/dashboard/webhooks/<?= (int) $wh['id'] ?>/edit" class="btn btn-sm btn-outline-primary">Editar</a>
                                <form method="POST" action="/dashboard/webhooks/<?= (int) $wh['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Excluir webhook?')">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
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
