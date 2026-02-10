<?php
/**
 * Reset Password Page
 *
 * Rendered inside layouts/auth.php.
 * Available variables: $title, $token, $email
 */

$errors = $_SESSION['_flash']['errors'] ?? [];
$errorMsg = $_SESSION['_flash']['error'] ?? null;
unset($_SESSION['_flash']['error'], $_SESSION['_flash']['errors']);
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

.auth-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: var(--primary-50);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin: 0 auto var(--space-6);
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
    line-height: 1.6;
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

.password-requirements {
    margin-top: var(--space-1);
    font-size: var(--font-size-xs);
    color: var(--gray-400);
    line-height: 1.6;
}

.auth-back-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    margin-top: var(--space-6);
    font-size: var(--font-size-sm);
    color: var(--gray-500);
    text-decoration: none;
    transition: color var(--transition-fast);
}

.auth-back-link:hover {
    color: var(--primary);
}
</style>

<div class="auth-card">
    <!-- Logo -->
    <div class="auth-logo">
        <a href="/">Lead<span>Form</span></a>
    </div>

    <div class="auth-box">
        <div class="auth-icon">&#128272;</div>
        <h1 class="auth-title">Redefinir Senha</h1>
        <p class="auth-subtitle">
            Escolha uma nova senha segura para sua conta.
        </p>

        <!-- Error Message -->
        <?php if ($errorMsg): ?>
            <div class="auth-alert error">
                <span>&#10007;</span>
                <span><?= e($errorMsg) ?></span>
            </div>
        <?php endif; ?>

        <!-- Reset Password Form -->
        <form method="POST" action="/reset-password" novalidate>
            <?= csrf_field() ?>

            <!-- Hidden fields -->
            <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
            <input type="hidden" name="email" value="<?= e($email ?? '') ?>">

            <div class="form-group">
                <label class="form-label" for="password">Nova Senha</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input<?= !empty($errors['password']) ? ' error' : '' ?>"
                    placeholder="Min. 8 caracteres"
                    required
                    minlength="8"
                    autofocus
                >
                <?php if (!empty($errors['password'])): ?>
                    <div class="form-error"><?= e($errors['password']) ?></div>
                <?php endif; ?>
                <div class="password-requirements">
                    Use pelo menos 8 caracteres com letras e numeros.
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirmation">Confirmar Nova Senha</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="form-input<?= !empty($errors['password_confirmation']) ? ' error' : '' ?>"
                    placeholder="Repita a nova senha"
                    required
                    minlength="8"
                >
                <?php if (!empty($errors['password_confirmation'])): ?>
                    <div class="form-error"><?= e($errors['password_confirmation']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-gradient w-full btn-lg">
                Redefinir Senha
            </button>
        </form>

        <a href="/login" class="auth-back-link">
            <span>&larr;</span>
            <span>Voltar para login</span>
        </a>
    </div>
</div>
