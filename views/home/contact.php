<?php
/**
 * Contact Page
 * Variables: $errors (optional), $old (optional)
 */
$errors = $errors ?? [];
$old    = $old ?? [];

$flash_error   = $_SESSION['_flash']['error'] ?? '';
$flash_success = $_SESSION['_flash']['success'] ?? '';
unset($_SESSION['_flash']['error'], $_SESSION['_flash']['success']);
?>

<style>
.contact-hero { padding: 80px 0 60px; background: linear-gradient(135deg, #f8f9ff 0%, #eef2ff 50%, #f5f3ff 100%); text-align: center; }
.contact-hero h1 { font-size: 2.5rem; font-weight: 800; color: #111827; margin-bottom: 16px; }
.contact-hero p { font-size: 1.125rem; color: #6b7280; max-width: 600px; margin: 0 auto; line-height: 1.7; }
.contact-section { padding: 64px 24px; max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: 1fr 380px; gap: 48px; }
@media (max-width: 768px) { .contact-section { grid-template-columns: 1fr; } }
.contact-form { background: #fff; border: 1px solid #f3f4f6; border-radius: 12px; padding: 32px; }
.contact-form h2 { font-size: 1.5rem; font-weight: 700; color: #1f2937; margin-bottom: 24px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 6px; }
.form-group input,
.form-group textarea,
.form-group select { width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem; transition: border-color 0.2s; background: #fff; }
.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.1); }
.form-group textarea { min-height: 140px; resize: vertical; }
.form-group .error-text { color: #dc2626; font-size: 0.75rem; margin-top: 4px; }
.form-group.has-error input,
.form-group.has-error textarea { border-color: #dc2626; }
.contact-info { display: flex; flex-direction: column; gap: 24px; }
.contact-info-card { background: #fff; border: 1px solid #f3f4f6; border-radius: 12px; padding: 24px; }
.contact-info-card h3 { font-size: 1rem; font-weight: 700; color: #1f2937; margin-bottom: 12px; }
.contact-info-card p { font-size: 0.875rem; color: #6b7280; line-height: 1.6; margin: 0; }
.contact-info-card a { color: #4f46e5; text-decoration: none; }
.contact-info-card a:hover { text-decoration: underline; }
.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.875rem; }
.alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
</style>

<section class="contact-hero">
    <h1>Fale Conosco</h1>
    <p>Tem alguma duvida ou sugestao? Envie sua mensagem e responderemos o mais rapido possivel.</p>
</section>

<section class="contact-section">
    <div class="contact-form">
        <h2>Envie sua mensagem</h2>

        <?php if ($flash_success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($flash_success) ?></div>
        <?php endif; ?>

        <?php if ($flash_error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($flash_error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/contact">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">

            <div class="form-group <?= !empty($errors['name']) ? 'has-error' : '' ?>">
                <label for="name">Nome *</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>" placeholder="Seu nome completo" required>
                <?php if (!empty($errors['name'])): ?>
                    <div class="error-text"><?= htmlspecialchars(is_array($errors['name']) ? ($errors['name'][0] ?? '') : $errors['name']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group <?= !empty($errors['email']) ? 'has-error' : '' ?>">
                <label for="email">E-mail *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="seu@email.com" required>
                <?php if (!empty($errors['email'])): ?>
                    <div class="error-text"><?= htmlspecialchars(is_array($errors['email']) ? ($errors['email'][0] ?? '') : $errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group <?= !empty($errors['subject']) ? 'has-error' : '' ?>">
                <label for="subject">Assunto *</label>
                <select id="subject" name="subject" required>
                    <option value="">Selecione um assunto</option>
                    <option value="Duvida sobre planos" <?= ($old['subject'] ?? '') === 'Duvida sobre planos' ? 'selected' : '' ?>>Duvida sobre planos</option>
                    <option value="Suporte tecnico" <?= ($old['subject'] ?? '') === 'Suporte tecnico' ? 'selected' : '' ?>>Suporte tecnico</option>
                    <option value="Parceria" <?= ($old['subject'] ?? '') === 'Parceria' ? 'selected' : '' ?>>Parceria</option>
                    <option value="Feedback" <?= ($old['subject'] ?? '') === 'Feedback' ? 'selected' : '' ?>>Feedback</option>
                    <option value="Outro" <?= ($old['subject'] ?? '') === 'Outro' ? 'selected' : '' ?>>Outro</option>
                </select>
                <?php if (!empty($errors['subject'])): ?>
                    <div class="error-text"><?= htmlspecialchars(is_array($errors['subject']) ? ($errors['subject'][0] ?? '') : $errors['subject']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group <?= !empty($errors['message']) ? 'has-error' : '' ?>">
                <label for="message">Mensagem *</label>
                <textarea id="message" name="message" placeholder="Escreva sua mensagem aqui..." required><?= htmlspecialchars($old['message'] ?? '') ?></textarea>
                <?php if (!empty($errors['message'])): ?>
                    <div class="error-text"><?= htmlspecialchars(is_array($errors['message']) ? ($errors['message'][0] ?? '') : $errors['message']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-gradient btn-lg" style="width: 100%;">Enviar Mensagem</button>
        </form>
    </div>

    <div class="contact-info">
        <div class="contact-info-card">
            <h3>E-mail</h3>
            <p><a href="mailto:contato@leadform.com.br">contato@leadform.com.br</a></p>
        </div>
        <div class="contact-info-card">
            <h3>Horario de Atendimento</h3>
            <p>Segunda a Sexta<br>09:00 - 18:00 (BRT)</p>
        </div>
        <div class="contact-info-card">
            <h3>Redes Sociais</h3>
            <p>Siga-nos para novidades e dicas sobre formularios conversacionais.</p>
        </div>
        <div class="contact-info-card">
            <h3>Suporte Tecnico</h3>
            <p>Para clientes com plano ativo, acesse o painel e abra um ticket de suporte para atendimento prioritario.</p>
        </div>
    </div>
</section>
