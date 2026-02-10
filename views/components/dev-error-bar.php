<!-- Dev Mode Error Bar - Componente reutilizável -->
<!-- Exibe erros em modo de desenvolvimento sem quebrar o site -->
<?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
<div class="dev-mode-indicator">DEV</div>
<div class="dev-error-bar" id="devErrorBar">
    <div class="dev-error-header">
        <span class="error-type" id="devErrorType">Erro</span>
        <button class="dev-error-close" onclick="document.getElementById('devErrorBar').classList.remove('active')">&times;</button>
    </div>
    <div class="dev-error-body" id="devErrorBody">
        <?php
        // Show PHP errors that were captured
        if (!empty($GLOBALS['__dev_errors'])):
            foreach ($GLOBALS['__dev_errors'] as $err): ?>
                <div style="border-bottom:1px solid rgba(255,255,255,0.1);padding:12px 0;">
                    <div class="dev-error-message">[<?= htmlspecialchars($err['type']) ?>] <?= htmlspecialchars($err['message']) ?></div>
                    <div class="dev-error-file"><?= htmlspecialchars($err['file']) ?>:<?= $err['line'] ?></div>
                </div>
            <?php endforeach;
        endif; ?>
    </div>
</div>
<script>
(function() {
    var bar = document.getElementById('devErrorBar');
    var body = document.getElementById('devErrorBody');
    var errorCount = 0;

    function showError(type, msg, file, stack) {
        errorCount++;
        document.getElementById('devErrorType').textContent = type + ' #' + errorCount;
        var entry = document.createElement('div');
        entry.style.cssText = 'border-bottom:1px solid rgba(255,255,255,0.1);padding:12px 0;';
        entry.innerHTML = '<div class="dev-error-message">' + escapeHtml(msg) + '</div>'
            + (file ? '<div class="dev-error-file">' + escapeHtml(file) + '</div>' : '')
            + (stack ? '<pre class="dev-error-trace">' + escapeHtml(stack) + '</pre>' : '');
        body.appendChild(entry);
        bar.classList.add('active');
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    // Capture JS errors
    window.onerror = function(msg, url, line, col, error) {
        showError('JS Error', msg, (url || '') + ':' + (line || '?') + ':' + (col || '?'), error && error.stack);
        return false;
    };

    // Capture unhandled promise rejections
    window.addEventListener('unhandledrejection', function(e) {
        showError('Promise Rejection', e.reason ? (e.reason.message || String(e.reason)) : 'Unknown', '', e.reason && e.reason.stack);
    });

    // Capture fetch errors (500+)
    var _fetch = window.fetch;
    window.fetch = function() {
        return _fetch.apply(this, arguments).then(function(r) {
            if (!r.ok && r.status >= 500) {
                r.clone().text().then(function(t) {
                    showError('HTTP ' + r.status, t.substring(0, 500), arguments[0]);
                });
            }
            return r;
        }).catch(function(e) {
            showError('Network', e.message, arguments[0]);
            throw e;
        });
    };

    // Show bar if PHP errors were already captured
    <?php if (!empty($GLOBALS['__dev_errors'])): ?>
    bar.classList.add('active');
    <?php endif; ?>
})();
</script>
<?php endif; ?>
