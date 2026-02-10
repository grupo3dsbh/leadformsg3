<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Faturamento</h1>

    <?php if (!empty($_SESSION['_flash']['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['_flash']['success']) ?></div>
        <?php unset($_SESSION['_flash']['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['_flash']['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['_flash']['error']) ?></div>
        <?php unset($_SESSION['_flash']['error']); ?>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Current Plan -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Plano Atual</h5>
                </div>
                <div class="card-body">
                    <?php if ($plan): ?>
                        <h3 class="text-primary mb-2"><?= htmlspecialchars($plan['name']) ?></h3>
                        <p class="text-muted"><?= htmlspecialchars($plan['description'] ?? '') ?></p>

                        <ul class="list-unstyled mb-3">
                            <li><strong>Formularios:</strong> <?= $formsCount ?> / <?= (int) ($plan['max_forms'] ?? 0) === 0 ? 'Ilimitado' : $plan['max_forms'] ?></li>
                            <li><strong>Respostas/mes:</strong> <?= $entriesThisMonth ?> / <?= (int) ($plan['max_entries_per_month'] ?? 0) === 0 ? 'Ilimitado' : $plan['max_entries_per_month'] ?></li>
                            <li><strong>Usuarios:</strong> <?= $usersCount ?> / <?= (int) ($plan['max_users'] ?? 0) === 0 ? 'Ilimitado' : $plan['max_users'] ?></li>
                        </ul>

                        <?php if ($subscription): ?>
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="badge bg-<?= $subscription['status'] === 'active' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($subscription['status']) ?>
                                </span>
                                <small class="text-muted">
                                    <?= htmlspecialchars($subscription['interval'] ?? 'monthly') ?> -
                                    R$ <?= number_format((float)($subscription['amount'] ?? 0), 2, ',', '.') ?>
                                </small>
                            </div>
                            <?php if ($subscription['status'] === 'active'): ?>
                                <form method="POST" action="/dashboard/billing/cancel" onsubmit="return confirm('Tem certeza que deseja cancelar?')">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Cancelar Assinatura</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted">Nenhum plano ativo. Escolha um plano abaixo.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Usage -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Uso</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            <span>Formularios</span>
                            <span><?= $formsCount ?> / <?= (int) ($plan['max_forms'] ?? 0) === 0 ? '&infin;' : $plan['max_forms'] ?? '5' ?></span>
                        </label>
                        <?php $maxForms = (int) ($plan['max_forms'] ?? 5); $pctForms = $maxForms > 0 ? min(100, ($formsCount / $maxForms) * 100) : 0; ?>
                        <div class="progress" style="height: 8px;"><div class="progress-bar" style="width: <?= $pctForms ?>%"></div></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            <span>Respostas este mes</span>
                            <span><?= $entriesThisMonth ?> / <?= (int) ($plan['max_entries_per_month'] ?? 0) === 0 ? '&infin;' : $plan['max_entries_per_month'] ?? '100' ?></span>
                        </label>
                        <?php $maxEntries = (int) ($plan['max_entries_per_month'] ?? 100); $pctEntries = $maxEntries > 0 ? min(100, ($entriesThisMonth / $maxEntries) * 100) : 0; ?>
                        <div class="progress" style="height: 8px;"><div class="progress-bar" style="width: <?= $pctEntries ?>%"></div></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            <span>Usuarios</span>
                            <span><?= $usersCount ?> / <?= (int) ($plan['max_users'] ?? 0) === 0 ? '&infin;' : $plan['max_users'] ?? '1' ?></span>
                        </label>
                        <?php $maxUsers = (int) ($plan['max_users'] ?? 1); $pctUsers = $maxUsers > 0 ? min(100, ($usersCount / $maxUsers) * 100) : 0; ?>
                        <div class="progress" style="height: 8px;"><div class="progress-bar" style="width: <?= $pctUsers ?>%"></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Plans -->
    <h4 class="mt-5 mb-3">Planos Disponiveis</h4>
    <div class="row g-3">
        <?php foreach ($plans as $p): ?>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm <?= ($plan && (int)$plan['id'] === (int)$p['id']) ? 'border-primary' : '' ?>">
                    <div class="card-body text-center">
                        <?php if (!empty($p['is_featured'])): ?>
                            <span class="badge bg-primary mb-2">Recomendado</span>
                        <?php endif; ?>
                        <h5><?= htmlspecialchars($p['name']) ?></h5>
                        <p class="text-muted small"><?= htmlspecialchars($p['description'] ?? '') ?></p>
                        <h3 class="mb-0">R$ <?= number_format((float)$p['price_monthly'], 2, ',', '.') ?></h3>
                        <small class="text-muted">/mes</small>
                        <hr>
                        <ul class="list-unstyled small text-start">
                            <li>Ate <?= (int) $p['max_forms'] === 0 ? 'ilimitados' : $p['max_forms'] ?> formularios</li>
                            <li>Ate <?= (int) $p['max_entries_per_month'] === 0 ? 'ilimitadas' : $p['max_entries_per_month'] ?> respostas/mes</li>
                            <li>Ate <?= (int) $p['max_users'] === 0 ? 'ilimitados' : $p['max_users'] ?> usuarios</li>
                            <li><?= (int) $p['max_file_storage'] ?>MB armazenamento</li>
                        </ul>
                        <?php if (!$plan || (int)$plan['id'] !== (int)$p['id']): ?>
                            <form method="POST" action="/dashboard/billing/subscribe">
                                <input type="hidden" name="plan_id" value="<?= (int) $p['id'] ?>">
                                <input type="hidden" name="interval" value="monthly">
                                <button type="submit" class="btn btn-primary btn-sm w-100">Assinar</button>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-outline-secondary btn-sm w-100" disabled>Plano Atual</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Payment History -->
    <?php if (!empty($payments)): ?>
    <h4 class="mt-5 mb-3">Historico de Pagamentos</h4>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Gateway</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?= htmlspecialchars($payment['created_at'] ?? '') ?></td>
                        <td>R$ <?= number_format((float)($payment['amount'] ?? 0), 2, ',', '.') ?></td>
                        <td>
                            <span class="badge bg-<?= match($payment['status'] ?? '') { 'succeeded' => 'success', 'pending' => 'warning', 'failed' => 'danger', default => 'secondary' } ?>">
                                <?= htmlspecialchars(ucfirst($payment['status'] ?? '')) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($payment['payment_gateway'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
