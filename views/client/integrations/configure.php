<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Configurar Integracao: <?= htmlspecialchars(ucfirst($type ?? '')) ?></h1>
        <a href="/dashboard/integrations" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="/dashboard/integrations">
                <input type="hidden" name="type" value="<?= htmlspecialchars($type ?? '') ?>">
                <?php if (!empty($integration['id'])): ?>
                    <input type="hidden" name="integration_id" value="<?= (int) $integration['id'] ?>">
                <?php endif; ?>

                <?php if ($type === 'email'): ?>
                    <div class="mb-3">
                        <label class="form-label">Email de notificacao</label>
                        <input type="email" class="form-control" name="config[email]" value="<?= htmlspecialchars($config['email'] ?? '') ?>" required>
                    </div>
                <?php elseif ($type === 'slack'): ?>
                    <div class="mb-3">
                        <label class="form-label">Webhook URL do Slack</label>
                        <input type="url" class="form-control" name="config[webhook_url]" value="<?= htmlspecialchars($config['webhook_url'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Canal</label>
                        <input type="text" class="form-control" name="config[channel]" value="<?= htmlspecialchars($config['channel'] ?? '') ?>" placeholder="#general">
                    </div>
                <?php elseif ($type === 'zapier'): ?>
                    <div class="mb-3">
                        <label class="form-label">Zapier Webhook URL</label>
                        <input type="url" class="form-control" name="config[webhook_url]" value="<?= htmlspecialchars($config['webhook_url'] ?? '') ?>" required>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label">API Key</label>
                        <input type="text" class="form-control" name="config[api_key]" value="<?= htmlspecialchars($config['api_key'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL</label>
                        <input type="url" class="form-control" name="config[url]" value="<?= htmlspecialchars($config['url'] ?? '') ?>">
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary">Salvar Configuracao</button>
            </form>
        </div>
    </div>
</div>
