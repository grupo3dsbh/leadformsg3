<?php
/**
 * Register Page
 *
 * Rendered inside layouts/auth.php.
 * Available variables: $title, $plans (optional)
 */

$errors = $_SESSION['_flash']['errors'] ?? [];
$errorMsg = $_SESSION['_flash']['error'] ?? null;
$oldData = $_SESSION['_flash']['old'] ?? [];
unset($_SESSION['_flash']['error'], $_SESSION['_flash']['errors'], $_SESSION['_flash']['old']);
?>

<style>
.auth-card {
    width: 100%;
    max-width: 480px;
    margin: 0 auto;
}

.auth-logo {
    text-align: center;
    margin-bottom: var(--space-8);
}

.auth-logo a {
    font-size: var(--font-size-2xl);
    font-weight: 800;
    color: var(--gray-900);
    text-decoration: none;
}

.auth-logo a span {
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.auth-box {
    background: #fff;
    border-radius: var(--radius-2xl);
    border: 1px solid var(--gray-200);
    padding: var(--space-8);
    box-shadow: var(--shadow-lg);
}

.auth-title {
    font-size: var(--font-size-2xl);
    font-weight: 800;
    color: var(--gray-900);
    text-align: center;
    margin-bottom: var(--space-2);
}

.auth-subtitle {
    font-size: var(--font-size-sm);
    color: var(--gray-500);
    text-align: center;
    margin-bottom: var(--space-8);
}

.auth-alert {
    padding: 12px 16px;
    border-radius: var(--radius-lg);
    font-size: var(--font-size-sm);
    margin-bottom: var(--space-6);
    display: flex;
    align-items: flex-start;
    gap: var(--space-2);
}

.auth-alert.error {
    background: var(--danger-light);
    color: #991B1B;
    border: 1px solid rgba(239,68,68,0.2);
}

.form-row-2col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-4);
}

.terms-check {
    display: flex;
    align-items: flex-start;
    gap: var(--space-2);
    margin-bottom: var(--space-6);
}

.terms-check input {
    width: 18px;
    height: 18px;
    margin-top: 2px;
    accent-color: var(--primary);
    cursor: pointer;
    flex-shrink: 0;
}

.terms-check label {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    line-height: 1.5;
    cursor: pointer;
}

.terms-check label a {
    color: var(--primary);
    font-weight: 600;
}

.terms-check label a:hover {
    text-decoration: underline;
}

.auth-divider {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    margin: var(--space-6) 0;
}

.auth-divider::before,
.auth-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--gray-200);
}

.auth-divider span {
    font-size: var(--font-size-xs);
    color: var(--gray-400);
    font-weight: 600;
    text-transform: uppercase;
}

.social-buttons {
    display: flex;
    gap: var(--space-3);
}

.btn-social {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    padding: 10px 16px;
    background: #fff;
    border: 2px solid var(--gray-200);
    border-radius: var(--radius-lg);
    font-size: var(--font-size-sm);
    font-weight: 600;
    color: var(--gray-700);
    cursor: pointer;
    transition: all var(--transition-fast);
    text-decoration: none;
    font-family: var(--font-family);
}

.btn-social:hover {
    border-color: var(--gray-300);
    background: var(--gray-50);
    color: var(--gray-900);
}

.btn-social .social-icon {
    font-size: 18px;
}

.auth-footer {
    text-align: center;
    margin-top: var(--space-6);
    font-size: var(--font-size-sm);
    color: var(--gray-500);
}

.auth-footer a {
    color: var(--primary);
    font-weight: 600;
}

.auth-footer a:hover {
    text-decoration: underline;
}

@media (max-width: 480px) {
    .form-row-2col { grid-template-columns: 1fr; }
}
</style>

<div class="auth-card">
    <!-- Logo -->
    <div class="auth-logo">
        <a href="/">Lead<span>Form</span></a>
    </div>

    <div class="auth-box">
        <h1 class="auth-title">Crie sua conta</h1>
        <p class="auth-subtitle">Comece gratis, sem cartao de credito</p>

        <!-- Error Message -->
        <?php if ($errorMsg): ?>
            <div class="auth-alert error">
                <span>&#10007;</span>
                <span><?= e($errorMsg) ?></span>
            </div>
        <?php endif; ?>

        <!-- Social Register Buttons -->
        <div class="social-buttons">
            <a href="#" class="btn-social" title="Registrar com Google">
                <span class="social-icon">G</span>
                <span>Google</span>
            </a>
            <a href="#" class="btn-social" title="Registrar com Facebook">
                <span class="social-icon">f</span>
                <span>Facebook</span>
            </a>
        </div>

        <div class="auth-divider">
            <span>ou registre com e-mail</span>
        </div>

        <!-- Register Form -->
        <form method="POST" action="/register" novalidate>
            <?= csrf_field() ?>

            <div class="form-row-2col">
                <div class="form-group">
                    <label class="form-label" for="first_name">Nome</label>
                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        class="form-input<?= !empty($errors['first_name']) ? ' error' : '' ?>"
                        placeholder="Seu nome"
                        value="<?= e($oldData['first_name'] ?? '') ?>"
                        required
                        autofocus
                    >
                    <?php if (!empty($errors['first_name'])): ?>
                        <div class="form-error"><?= e($errors['first_name']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="last_name">Sobrenome</label>
                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        class="form-input<?= !empty($errors['last_name']) ? ' error' : '' ?>"
                        placeholder="Seu sobrenome"
                        value="<?= e($oldData['last_name'] ?? '') ?>"
                        required
                    >
                    <?php if (!empty($errors['last_name'])): ?>
                        <div class="form-error"><?= e($errors['last_name']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">E-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-input<?= !empty($errors['email']) ? ' error' : '' ?>"
                    placeholder="seu@email.com"
                    value="<?= e($oldData['email'] ?? '') ?>"
                    required
                >
                <?php if (!empty($errors['email'])): ?>
                    <div class="form-error"><?= e($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="company_name">Empresa <span class="text-gray-400">(opcional)</span></label>
                <input
                    type="text"
                    id="company_name"
                    name="company_name"
                    class="form-input<?= !empty($errors['company_name']) ? ' error' : '' ?>"
                    placeholder="Nome da sua empresa"
                    value="<?= e($oldData['company_name'] ?? '') ?>"
                >
                <?php if (!empty($errors['company_name'])): ?>
                    <div class="form-error"><?= e($errors['company_name']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-row-2col">
                <div class="form-group">
                    <label class="form-label" for="password">Senha</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input<?= !empty($errors['password']) ? ' error' : '' ?>"
                        placeholder="Min. 8 caracteres"
                        required
                        minlength="8"
                    >
                    <?php if (!empty($errors['password'])): ?>
                        <div class="form-error"><?= e($errors['password']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Confirmar Senha</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        class="form-input<?= !empty($errors['password_confirmation']) ? ' error' : '' ?>"
                        placeholder="Repita a senha"
                        required
                        minlength="8"
                    >
                    <?php if (!empty($errors['password_confirmation'])): ?>
                        <div class="form-error"><?= e($errors['password_confirmation']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="terms-check">
                <input type="checkbox" id="terms" name="terms" value="1" required>
                <label for="terms">
                    Eu concordo com os
                    <a href="/terms" target="_blank">Termos de Servico</a>
                    e a
                    <a href="/privacy" target="_blank">Politica de Privacidade</a>
                </label>
            </div>

            <button type="submit" class="btn btn-gradient w-full btn-lg">
                Criar Conta Gratis
            </button>
        </form>
    </div>

    <!-- Footer Link -->
    <div class="auth-footer">
        Ja tem uma conta? <a href="/login">Fazer login</a>
    </div>
</div>
