<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Analytics: <?= htmlspecialchars($form['title'] ?? '') ?></h1>
            <p class="text-muted mb-0">Estatisticas e metricas do formulario</p>
        </div>
        <a href="/dashboard/forms/<?= (int) $form['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Visualizacoes</h6>
                    <h2 class="mb-0"><?= number_format($views) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Respostas</h6>
                    <h2 class="mb-0"><?= number_format($totalEntries) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Taxa de Conversao</h6>
                    <h2 class="mb-0"><?= $conversionRate ?>%</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Tempo Medio</h6>
                    <h2 class="mb-0"><?= $avgDuration > 0 ? gmdate('i:s', $avgDuration) : '--:--' ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Device Breakdown -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><h6 class="mb-0">Dispositivos</h6></div>
                <div class="card-body">
                    <?php if (empty($deviceBreakdown)): ?>
                        <p class="text-muted text-center">Sem dados</p>
                    <?php else: ?>
                        <?php foreach ($deviceBreakdown as $device): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?= htmlspecialchars(ucfirst($device['device_type'] ?? 'Unknown')) ?></span>
                                <strong><?= (int) $device['count'] ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Status Breakdown -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><h6 class="mb-0">Status das Respostas</h6></div>
                <div class="card-body">
                    <?php if (empty($statusBreakdown)): ?>
                        <p class="text-muted text-center">Sem dados</p>
                    <?php else: ?>
                        <?php foreach ($statusBreakdown as $s): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="badge bg-<?= match($s['status']) { 'complete' => 'success', 'partial' => 'warning', 'abandoned' => 'danger', default => 'secondary' } ?>"><?= htmlspecialchars(ucfirst($s['status'])) ?></span>
                                <strong><?= (int) $s['count'] ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Referrers -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><h6 class="mb-0">Principais Origens</h6></div>
                <div class="card-body">
                    <?php if (empty($topReferrers)): ?>
                        <p class="text-muted text-center">Sem dados</p>
                    <?php else: ?>
                        <?php foreach ($topReferrers as $ref): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-truncate" style="max-width:200px" title="<?= htmlspecialchars($ref['referrer']) ?>"><?= htmlspecialchars($ref['referrer']) ?></span>
                                <strong><?= (int) $ref['count'] ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Entries Chart (simple table for now) -->
    <div class="card border-0 shadow-sm mb-4">
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

    <!-- Top Countries -->
    <?php if (!empty($topCountries)): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white"><h6 class="mb-0">Principais Paises</h6></div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($topCountries as $c): ?>
                    <div class="col-md-3 mb-2">
                        <div class="d-flex justify-content-between">
                            <span><?= htmlspecialchars($c['country']) ?></span>
                            <strong><?= (int) $c['count'] ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
