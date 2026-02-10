<?php
/**
 * Legal Pages (Terms of Service, Privacy Policy, etc.)
 * Variables: $heading, $content
 */
$heading = $heading ?? 'Pagina Legal';
$content = $content ?? '';
?>

<style>
.legal-hero { padding: 80px 0 40px; background: linear-gradient(135deg, #f8f9ff 0%, #eef2ff 50%, #f5f3ff 100%); text-align: center; }
.legal-hero h1 { font-size: 2.5rem; font-weight: 800; color: #111827; margin-bottom: 16px; }
.legal-content { padding: 48px 24px; max-width: 800px; margin: 0 auto; }
.legal-content .content-body { font-size: 0.9375rem; color: #374151; line-height: 1.8; }
.legal-content .content-body h2 { font-size: 1.25rem; font-weight: 700; color: #1f2937; margin: 32px 0 12px; }
.legal-content .content-body h3 { font-size: 1.1rem; font-weight: 600; color: #1f2937; margin: 24px 0 8px; }
.legal-content .content-body p { margin-bottom: 16px; }
.legal-content .content-body ul,
.legal-content .content-body ol { margin-bottom: 16px; padding-left: 24px; }
.legal-content .content-body li { margin-bottom: 8px; }
.legal-content .content-body a { color: #4f46e5; text-decoration: underline; }
.legal-meta { text-align: center; font-size: 0.8125rem; color: #9ca3af; margin-top: 48px; padding-top: 24px; border-top: 1px solid #f3f4f6; }
</style>

<section class="legal-hero">
    <h1><?= htmlspecialchars($heading) ?></h1>
</section>

<section class="legal-content">
    <div class="content-body">
        <?= $content ?>
    </div>
    <div class="legal-meta">
        Ultima atualizacao: <?= date('d/m/Y') ?>
    </div>
</section>
