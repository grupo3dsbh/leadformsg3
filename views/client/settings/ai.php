<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Configuracoes de IA</h1>
    </div>

    <?php if (!empty($_SESSION['_flash']['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['_flash']['success']) ?></div>
        <?php unset($_SESSION['_flash']['success']); ?>
    <?php endif; ?>

    <?php if (empty($aiEnabledGlobally)): ?>
        <div class="alert alert-info">
            As funcionalidades de IA estao desabilitadas globalmente pelo administrador.
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="/dashboard/ai-settings">
                <div class="form-check form-switch mb-3">
                    <input type="checkbox" class="form-check-input" id="ai_enabled" name="ai_enabled" value="1"
                        <?= !empty($aiSettings['enabled']) ? 'checked' : '' ?>
                        <?= empty($aiEnabledGlobally) ? 'disabled' : '' ?>>
                    <label class="form-check-label" for="ai_enabled">Habilitar IA</label>
                </div>

                <div class="form-check form-switch mb-3">
                    <input type="checkbox" class="form-check-input" id="ai_form_generation" name="ai_form_generation" value="1"
                        <?= !empty($aiSettings['form_generation']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ai_form_generation">Geracao de formularios com IA</label>
                </div>

                <div class="form-check form-switch mb-3">
                    <input type="checkbox" class="form-check-input" id="ai_entry_analysis" name="ai_entry_analysis" value="1"
                        <?= !empty($aiSettings['entry_analysis']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ai_entry_analysis">Analise de respostas com IA</label>
                </div>

                <div class="form-check form-switch mb-3">
                    <input type="checkbox" class="form-check-input" id="ai_auto_categorization" name="ai_auto_categorization" value="1"
                        <?= !empty($aiSettings['auto_categorization']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ai_auto_categorization">Categorizacao automatica</label>
                </div>

                <div class="mb-3">
                    <label for="ai_custom_prompt" class="form-label">Prompt personalizado (opcional)</label>
                    <textarea class="form-control" id="ai_custom_prompt" name="ai_custom_prompt" rows="3" placeholder="Instrucoes adicionais para a IA..."><?= htmlspecialchars($aiSettings['custom_prompt'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Salvar Configuracoes</button>
            </form>
        </div>
    </div>
</div>
