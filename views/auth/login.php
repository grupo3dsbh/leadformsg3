<?php
/**
 * Login Page
 *
 * Rendered inside layouts/auth.php.
 * Available variables: $title, $intended
 */

$errors = $_SESSION['_flash']['errors'] ?? [];
$oldEmail = old('email', '');
$successMsg = get_flash('success');
$errorMsg = $_SESSION['_flash']['error'] ?? null;
unset($_SESSION['_flash']['error'], $_SESSION['_flash']['errors'], $_SESSION['_flash']['old']);
?>

<style>
.auth-card {
    width: 100%;
    max-width: 440px;
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

.auth-alert.success {
    background: var(--success-light);
    color: #065F46;
    border: 1px solid rgba(16,185,129,0.2);
}

.auth-alert.error {
    background: var(--danger-light);
    color: #991B1B;
    border: 1px solid rgba(239,68,68,0.2);
}

.form-row-split {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-6);
}

.remember-check {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    cursor: pointer;
}

.remember-check input {
    width: 16px;
    height: 16px;
    accent-color: var(--primary);
    cursor: pointer;
}

.forgot-link {
    font-size: var(--font-size-sm);
    color: var(--primary);
    font-weight: 600;
}

.forgot-link:hover {
    color: var(--primary-dark);
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
</style>

<div class="auth-card">
    <!-- Logo -->
    <div class="auth-logo">
        <a href="/">Lead<span>Form</span></a>
    </div>

    <div class="auth-box">
        <h1 class="auth-title">Bem-vindo de volta</h1>
        <p class="auth-subtitle">Entre na sua conta para continuar</p>

        <!-- Success Message -->
        <?php if ($successMsg): ?>
            <div class="auth-alert success">
                <span>&#10003;</span>
                <span><?= e($successMsg) ?></span>
            </div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if ($errorMsg): ?>
            <div class="auth-alert error">
                <span>&#10007;</span>
                <span><?= e($errorMsg) ?></span>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="/login" novalidate>
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="email">E-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-input<?= !empty($errors['email']) ? ' error' : '' ?>"
                    placeholder="seu@email.com"
                    value="<?= $oldEmail ?>"
                    required
                    autofocus
                >
                <?php if (!empty($errors['email'])): ?>
                    <div class="form-error"><?= e($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Senha</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input<?= !empty($errors['password']) ? ' error' : '' ?>"
                    placeholder="Sua senha"
                    required
                    minlength="6"
                >
                <?php if (!empty($errors['password'])): ?>
                    <div class="form-error"><?= e($errors['password']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-row-split">
                <label class="remember-check">
                    <input type="checkbox" name="remember" value="1">
                    <span>Lembrar de mim</span>
                </label>
                <a href="/forgot-password" class="forgot-link">Esqueceu a senha?</a>
            </div>

            <button type="submit" class="btn btn-gradient w-full btn-lg">
                Entrar
            </button>
        </form>

        <!-- Social Login Divider -->
        <div class="auth-divider">
            <span>ou continue com</span>
        </div>

        <!-- Social Login Buttons -->
        <div class="social-buttons">
            <a href="#" class="btn-social" title="Entrar com Google">
                <span class="social-icon">G</span>
                <span>Google</span>
            </a>
            <a href="#" class="btn-social" title="Entrar com Facebook">
                <span class="social-icon">f</span>
                <span>Facebook</span>
            </a>
        </div>
    </div>

    <!-- Footer Link -->
    <div class="auth-footer">
        Nao tem uma conta? <a href="/register">Criar conta gratis</a>
    </div>
</div>
