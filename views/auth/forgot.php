<?php
/**
 * Forgot Password Page
 *
 * Rendered inside layouts/auth.php.
 * Available variables: $title
 */

$errors = $_SESSION['_flash']['errors'] ?? [];
$errorMsg = $_SESSION['_flash']['error'] ?? null;
$successMsg = get_flash('success');
$oldEmail = old('email', '');
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
        <div class="auth-icon">&#128274;</div>
        <h1 class="auth-title">Esqueceu sua senha?</h1>
        <p class="auth-subtitle">
            Sem problemas. Informe seu e-mail e enviaremos um link
            para voce redefinir sua senha.
        </p>

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

        <!-- Forgot Password Form -->
        <form method="POST" action="/forgot-password" novalidate>
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

            <button type="submit" class="btn btn-gradient w-full btn-lg">
                Enviar Link de Redefinicao
            </button>
        </form>

        <a href="/login" class="auth-back-link">
            <span>&larr;</span>
            <span>Voltar para login</span>
        </a>
    </div>
</div>
