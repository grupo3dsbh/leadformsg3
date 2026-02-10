<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Tema: <?= htmlspecialchars($form['title'] ?? '') ?></h1>
        <a href="/dashboard/forms/<?= (int) $form['id'] ?>" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="/dashboard/forms/<?= (int) $form['id'] ?>/theme">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="layout" class="form-label">Layout</label>
                        <select class="form-select" id="layout" name="layout">
                            <option value="default" <?= ($theme['layout'] ?? '') === 'default' ? 'selected' : '' ?>>Padrao</option>
                            <option value="card" <?= ($theme['layout'] ?? '') === 'card' ? 'selected' : '' ?>>Card</option>
                            <option value="inline" <?= ($theme['layout'] ?? '') === 'inline' ? 'selected' : '' ?>>Inline</option>
                            <option value="conversational" <?= ($theme['layout'] ?? '') === 'conversational' ? 'selected' : '' ?>>Conversacional</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="font_family" class="form-label">Fonte</label>
                        <select class="form-select" id="font_family" name="font_family">
                            <option value="Inter" <?= ($theme['font_family'] ?? '') === 'Inter' ? 'selected' : '' ?>>Inter</option>
                            <option value="Roboto" <?= ($theme['font_family'] ?? '') === 'Roboto' ? 'selected' : '' ?>>Roboto</option>
                            <option value="Open Sans" <?= ($theme['font_family'] ?? '') === 'Open Sans' ? 'selected' : '' ?>>Open Sans</option>
                            <option value="Poppins" <?= ($theme['font_family'] ?? '') === 'Poppins' ? 'selected' : '' ?>>Poppins</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="primary_color" class="form-label">Cor Primaria</label>
                        <input type="color" class="form-control form-control-color w-100" id="primary_color" name="primary_color" value="<?= htmlspecialchars($theme['primary_color'] ?? '#3B82F6') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="background_color" class="form-label">Cor de Fundo</label>
                        <input type="color" class="form-control form-control-color w-100" id="background_color" name="background_color" value="<?= htmlspecialchars($theme['background_color'] ?? '#FFFFFF') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="text_color" class="form-label">Cor do Texto</label>
                        <input type="color" class="form-control form-control-color w-100" id="text_color" name="text_color" value="<?= htmlspecialchars($theme['text_color'] ?? '#111827') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="border_radius" class="form-label">Border Radius</label>
                        <input type="text" class="form-control" id="border_radius" name="border_radius" value="<?= htmlspecialchars($theme['border_radius'] ?? '8px') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="button_style" class="form-label">Estilo do Botao</label>
                        <select class="form-select" id="button_style" name="button_style">
                            <option value="filled" <?= ($theme['button_style'] ?? '') === 'filled' ? 'selected' : '' ?>>Preenchido</option>
                            <option value="outlined" <?= ($theme['button_style'] ?? '') === 'outlined' ? 'selected' : '' ?>>Contorno</option>
                            <option value="rounded" <?= ($theme['button_style'] ?? '') === 'rounded' ? 'selected' : '' ?>>Arredondado</option>
                        </select>
                    </div>
                </div>

                <hr class="my-4">

                <div class="form-check form-switch mb-2">
                    <input type="checkbox" class="form-check-input" id="show_progress_bar" name="show_progress_bar" value="1" <?= !empty($theme['show_progress_bar']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="show_progress_bar">Mostrar barra de progresso</label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input type="checkbox" class="form-check-input" id="show_field_labels" name="show_field_labels" value="1" <?= !empty($theme['show_field_labels']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="show_field_labels">Mostrar labels dos campos</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input type="checkbox" class="form-check-input" id="show_branding" name="show_branding" value="1" <?= !empty($theme['show_branding']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="show_branding">Mostrar branding</label>
                </div>

                <div class="mb-3">
                    <label for="custom_css" class="form-label">CSS Personalizado</label>
                    <textarea class="form-control" id="custom_css" name="custom_css" rows="4"><?= htmlspecialchars($theme['custom_css'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Salvar Tema</button>
            </form>
        </div>
    </div>
</div>
