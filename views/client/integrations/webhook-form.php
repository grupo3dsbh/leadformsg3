<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><?= ($isEdit ?? false) ? 'Editar Webhook' : 'Novo Webhook' ?></h1>
        <a href="/dashboard/webhooks" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars(is_array($error) ? implode(', ', $error) : $error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= ($isEdit ?? false) ? '/dashboard/webhooks/' . (int) ($webhook['id'] ?? 0) : '/dashboard/webhooks' ?>">
                <div class="mb-3">
                    <label for="url" class="form-label">URL do Webhook</label>
                    <input type="url" class="form-control" id="url" name="url" value="<?= htmlspecialchars($webhook['url'] ?? '') ?>" required placeholder="https://...">
                </div>

                <div class="mb-3">
                    <label for="form_id" class="form-label">Formulario (opcional)</label>
                    <select class="form-select" id="form_id" name="form_id">
                        <option value="">Todos os formularios</option>
                        <?php foreach ($forms ?? [] as $f): ?>
                            <option value="<?= (int) $f['id'] ?>" <?= ((int)($webhook['form_id'] ?? 0)) === (int)$f['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($f['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Eventos</label>
                    <?php
                    $selectedEvents = $webhook['events'] ?? ['entry.created'];
                    if (is_string($selectedEvents)) { $selectedEvents = json_decode($selectedEvents, true) ?: []; }
                    $availableEvents = ['entry.created', 'entry.updated', 'entry.deleted', 'form.published', 'form.updated'];
                    ?>
                    <?php foreach ($availableEvents as $ev): ?>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="event_<?= $ev ?>" name="events[]" value="<?= htmlspecialchars($ev) ?>"
                                <?= in_array($ev, $selectedEvents, true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="event_<?= $ev ?>"><?= htmlspecialchars($ev) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mb-3">
                    <label for="secret" class="form-label">Secret (deixe em branco para gerar automaticamente)</label>
                    <input type="text" class="form-control" id="secret" name="secret" value="<?= htmlspecialchars($webhook['secret'] ?? '') ?>">
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                        <?= ($webhook['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Ativo</label>
                </div>

                <button type="submit" class="btn btn-primary"><?= ($isEdit ?? false) ? 'Atualizar' : 'Criar' ?> Webhook</button>
            </form>
        </div>
    </div>
</div>
