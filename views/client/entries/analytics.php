<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Analytics: <?= htmlspecialchars($form['title'] ?? '') ?></h1>
            <p class="text-muted mb-0">Metricas de respostas do formulario</p>
        </div>
        <a href="/dashboard/forms/<?= (int) $form['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Total de Respostas</h6>
                    <h2 class="mb-0"><?= number_format($totalEntries) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Visualizacoes</h6>
                    <h2 class="mb-0"><?= number_format($views) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Taxa de Conversao</h6>
                    <h2 class="mb-0"><?= $conversionRate ?>%</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white"><h6 class="mb-0">Respostas por Dia (ultimos 30 dias)</h6></div>
        <div class="card-body">
            <?php if (empty($dailyEntries)): ?>
                <p class="text-muted text-center">Sem dados no periodo</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Data</th><th>Respostas</th></tr></thead>
                        <tbody>
                        <?php foreach ($dailyEntries as $day): ?>
                            <tr>
                                <td><?= htmlspecialchars($day['day']) ?></td>
                                <td><strong><?= (int) $day['count'] ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
