<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Permissoes: <?= htmlspecialchars($user['name'] ?? $user['email'] ?? '') ?></h1>
        <a href="/dashboard/users" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="/dashboard/users/<?= (int) $user['id'] ?>/permissions">
                <?php foreach ($allPermissions as $category => $perms): ?>
                    <h6 class="mt-3 mb-2"><?= htmlspecialchars($category) ?></h6>
                    <?php foreach ($perms as $permKey => $permLabel): ?>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="perm_<?= htmlspecialchars($permKey) ?>"
                                   name="permissions[]" value="<?= htmlspecialchars($permKey) ?>">
                            <label class="form-check-label" for="perm_<?= htmlspecialchars($permKey) ?>">
                                <?= htmlspecialchars($permLabel) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-primary mt-3">Salvar Permissoes</button>
            </form>
        </div>
    </div>
</div>
