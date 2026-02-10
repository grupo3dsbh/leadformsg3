<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Codigos de Recuperacao</h1>

    <div class="alert alert-warning">
        <strong>Importante!</strong> Guarde estes codigos em um local seguro. Cada codigo so pode ser usado uma vez.
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row">
                <?php foreach ($recoveryCodes as $code): ?>
                    <div class="col-md-3 mb-2">
                        <code class="d-block bg-light p-2 rounded text-center"><?= htmlspecialchars($code) ?></code>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <a href="/dashboard/security" class="btn btn-primary mt-3">Concluir</a>
</div>
