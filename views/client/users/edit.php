<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Editar Usuario</h1>
        <a href="/dashboard/users" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $field => $error): ?>
                    <li><?= htmlspecialchars(is_array($error) ? implode(', ', $error) : $error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="/dashboard/users/<?= (int) $user['id'] ?>">
                <input type="hidden" name="_method" value="PUT">
                <div class="mb-3">
                    <label for="name" class="form-label">Nome</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                    <small class="text-muted">Email nao pode ser alterado</small>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Funcao</label>
                    <select class="form-select" id="role" name="role">
                        <?php foreach ($availableRoles as $roleKey => $roleLabel): ?>
                            <option value="<?= htmlspecialchars($roleKey) ?>" <?= ($user['role'] ?? '') === $roleKey ? 'selected' : '' ?>>
                                <?= htmlspecialchars($roleLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Nova Senha (deixe em branco para manter)</label>
                    <input type="password" class="form-control" id="password" name="password" minlength="8">
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                        <?= ($user['status'] ?? 'active') === 'active' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Ativo</label>
                </div>
                <button type="submit" class="btn btn-primary">Salvar</button>
            </form>
        </div>
    </div>
</div>
