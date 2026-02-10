<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Ativar Autenticacao em Dois Fatores</h1>
        <a href="/dashboard/security" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars(is_array($error) ? implode(', ', $error) : $error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <p>Escaneie o QR code abaixo com seu aplicativo autenticador (Google Authenticator, Authy, etc.).</p>

            <?php if (!empty($otpAuthUrl)): ?>
                <div class="text-center mb-3">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($otpAuthUrl) ?>" alt="QR Code" class="img-fluid">
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label">Chave secreta (manual):</label>
                <code class="d-block bg-light p-2 rounded"><?= htmlspecialchars($secret ?? '') ?></code>
            </div>

            <form method="POST" action="/dashboard/security/2fa/verify">
                <div class="mb-3">
                    <label for="code" class="form-label">Codigo de Verificacao</label>
                    <input type="text" class="form-control" id="code" name="code" placeholder="000000" required maxlength="6" pattern="[0-9]{6}">
                    <small class="text-muted">Digite o codigo de 6 digitos do seu app.</small>
                </div>
                <button type="submit" class="btn btn-primary">Verificar e Ativar</button>
            </form>
        </div>
    </div>
</div>
