<!-- Site Header - Componente reutilizável -->
<header class="site-header" id="siteHeader">
    <div class="container">
        <div class="header-inner">
            <!-- Logo -->
            <a href="/" class="header-logo">
                <div class="header-logo-icon">
                    <span>LF</span>
                </div>
                <span class="header-logo-text">Lead<span class="text-gradient">Form</span></span>
            </a>

            <!-- Desktop Nav -->
            <nav class="header-nav" id="headerNav">
                <a href="/" class="header-nav-link <?= is_active('/') ?>">Home</a>
                <a href="/features" class="header-nav-link <?= is_active('/features') ?>">Funcionalidades</a>
                <a href="/pricing" class="header-nav-link <?= is_active('/pricing') ?>">Planos</a>
                <a href="/api-docs" class="header-nav-link <?= is_active('/api-docs') ?>">API</a>
                <a href="/contact" class="header-nav-link <?= is_active('/contact') ?>">Contato</a>
            </nav>

            <!-- Auth Actions -->
            <div class="header-actions">
                <?php if (auth()): ?>
                    <a href="<?= auth()['role'] === 'super_admin' ? '/admin' : '/dashboard' ?>" class="btn btn-ghost btn-sm">
                        Painel
                    </a>
                    <a href="/logout" class="btn btn-outline btn-sm">Sair</a>
                <?php else: ?>
                    <a href="/login" class="btn btn-ghost btn-sm">Entrar</a>
                    <a href="/register" class="btn btn-gradient btn-sm">Criar Conta</a>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Toggle -->
            <button class="header-mobile-toggle" id="mobile-menu-toggle" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="header-mobile-menu" id="mobile-menu">
        <nav class="header-mobile-nav">
            <a href="/">Home</a>
            <a href="/features">Funcionalidades</a>
            <a href="/pricing">Planos</a>
            <a href="/api-docs">API</a>
            <a href="/contact">Contato</a>
            <hr>
            <?php if (auth()): ?>
                <a href="<?= auth()['role'] === 'super_admin' ? '/admin' : '/dashboard' ?>">Painel</a>
                <a href="/logout">Sair</a>
            <?php else: ?>
                <a href="/login">Entrar</a>
                <a href="/register" class="btn btn-gradient w-full" style="margin-top:8px;">Criar Conta</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<style>
    .site-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: var(--z-fixed);
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid transparent;
        transition: all var(--transition-base);
    }

    .site-header.scrolled {
        background: rgba(255, 255, 255, 0.95);
        border-bottom-color: var(--gray-200);
        box-shadow: var(--shadow-sm);
    }

    .header-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 70px;
    }

    .header-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: var(--gray-900);
    }

    .header-logo-icon {
        width: 36px;
        height: 36px;
        background: var(--gradient-primary);
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 14px;
        color: #fff;
    }

    .header-logo-text {
        font-size: 20px;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .header-nav {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .header-nav-link {
        padding: 8px 16px;
        font-size: var(--font-size-sm);
        font-weight: 500;
        color: var(--gray-600);
        border-radius: var(--radius-md);
        transition: all var(--transition-fast);
        text-decoration: none;
    }

    .header-nav-link:hover,
    .header-nav-link.active {
        color: var(--primary);
        background: var(--primary-50);
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Mobile toggle */
    .header-mobile-toggle {
        display: none;
        flex-direction: column;
        gap: 5px;
        background: none;
        border: none;
        cursor: pointer;
        padding: 8px;
    }

    .header-mobile-toggle span {
        width: 22px;
        height: 2px;
        background: var(--gray-700);
        border-radius: 2px;
        transition: all 0.3s ease;
    }

    .header-mobile-toggle.active span:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px);
    }

    .header-mobile-toggle.active span:nth-child(2) {
        opacity: 0;
    }

    .header-mobile-toggle.active span:nth-child(3) {
        transform: rotate(-45deg) translate(5px, -5px);
    }

    /* Mobile menu */
    .header-mobile-menu {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #fff;
        border-bottom: 1px solid var(--gray-200);
        box-shadow: var(--shadow-lg);
        padding: 16px;
    }

    .header-mobile-menu.active {
        display: block;
    }

    .header-mobile-nav {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .header-mobile-nav a {
        padding: 10px 12px;
        font-size: var(--font-size-base);
        color: var(--gray-700);
        border-radius: var(--radius-md);
        text-decoration: none;
    }

    .header-mobile-nav a:hover {
        background: var(--gray-50);
        color: var(--primary);
    }

    .header-mobile-nav hr {
        border: none;
        border-top: 1px solid var(--gray-100);
        margin: 8px 0;
    }

    @media (max-width: 768px) {
        .header-nav,
        .header-actions {
            display: none;
        }

        .header-mobile-toggle {
            display: flex;
        }
    }
</style>
