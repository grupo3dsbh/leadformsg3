<?php
/**
 * 404 - Page Not Found
 */
?>

<style>
.error-page { min-height: 60vh; display: flex; align-items: center; justify-content: center; padding: 64px 24px; text-align: center; }
.error-page .error-content { max-width: 500px; }
.error-page .error-code { font-size: 8rem; font-weight: 900; color: #e5e7eb; line-height: 1; margin-bottom: 16px; }
.error-page h1 { font-size: 1.75rem; font-weight: 800; color: #111827; margin-bottom: 12px; }
.error-page p { font-size: 1rem; color: #6b7280; line-height: 1.7; margin-bottom: 32px; }
.error-page .error-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
</style>

<section class="error-page">
    <div class="error-content">
        <div class="error-code">404</div>
        <h1>Pagina nao encontrada</h1>
        <p>A pagina que voce esta procurando nao existe ou foi movida. Verifique o endereco digitado ou volte para a pagina inicial.</p>
        <div class="error-actions">
            <a href="/" class="btn btn-gradient btn-lg">Voltar ao Inicio</a>
            <a href="/contact" class="btn btn-outline btn-lg">Fale Conosco</a>
        </div>
    </div>
</section>
