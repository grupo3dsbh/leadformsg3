<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Novo Usuario</h1>
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
            <form method="POST" action="/dashboard/users">
                <div class="mb-3">
                    <label for="name" class="form-label">Nome</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
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
                <button type="submit" class="btn btn-primary">Criar Usuario</button>
            </form>
        </div>
    </div>
</div>
