<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Models\Tenant;
use Core\Controller;
use Core\Response;

/**
 * Authentication Controller
 *
 * Handles login, registration, logout, password reset, email verification
 * and two-factor authentication.
 */
class AuthController extends Controller
{
    protected User $userModel;
    protected Tenant $tenantModel;

    public function __construct()
    {
        $this->userModel   = new User();
        $this->tenantModel = new Tenant();
    }

    // ==================================================================
    // LOGIN
    // ==================================================================

    public function loginForm(): string|Response
    {
        if ($this->isAuthenticated()) {
            $this->redirectAuthenticatedUser();
        }

        return $this->view('auth.login', [
            'pageTitle' => 'Login',
            'intended'  => $_SESSION['url.intended'] ?? '/dashboard',
        ], 'layouts.auth');
    }

    public function login(): string|Response|null
    {
        if (!$this->verifyCsrf()) {
            return $this->redirectBack(['error' => 'Token de seguranca invalido. Tente novamente.']);
        }

        $rules = [
            'email'    => 'required|email|max:255',
            'password' => 'required|min:6|max:128',
        ];

        $data   = $this->only(['email', 'password', 'remember']);
        $errors = $this->validateInput($data, $rules);

        if (!empty($errors)) {
            return $this->redirectBack([
                'errors' => $errors,
                'old'    => ['email' => $data['email'] ?? ''],
            ]);
        }

        $user = $this->userModel->authenticate($data['email'], $data['password']);

        if ($user === null) {
            $this->logFailedLogin($data['email']);
            return $this->redirectBack([
                'error' => 'E-mail ou senha invalidos.',
                'old'   => ['email' => $data['email']],
            ]);
        }

        // Tenant status check
        if (!$this->checkTenantStatus($user)) {
            return $this->redirectBack([
                'error' => 'Sua conta foi suspensa. Entre em contato com o suporte.',
                'old'   => ['email' => $data['email']],
            ]);
        }

        // Two-factor authentication
        if ($this->userModel->hasTwoFactor($user)) {
            $_SESSION['2fa_user_id'] = (int) $user['id'];
            $_SESSION['2fa_remember'] = !empty($data['remember']);
            return $this->redirect('/login/2fa');
        }

        // Create session
        $this->createUserSession($user, !empty($data['remember']));

        // Redirect
        $intended = $_SESSION['url.intended'] ?? null;
        unset($_SESSION['url.intended']);

        if ($intended !== null && $intended !== '/login') {
            return $this->redirect($intended);
        }

        $this->redirectAuthenticatedUser($user);
        return null;
    }

    // ==================================================================
    // REGISTRATION
    // ==================================================================

    public function registerForm(): string|Response
    {
        if ($this->isAuthenticated()) {
            $this->redirectAuthenticatedUser();
        }

        return $this->view('auth.register', [
            'pageTitle' => 'Criar Conta',
            'plans'     => $this->getAvailablePlans(),
        ], 'layouts.auth');
    }

    public function register(): string|Response|null
    {
        if (!$this->verifyCsrf()) {
            return $this->redirectBack(['error' => 'Token de seguranca invalido.']);
        }

        $rules = [
            'first_name'   => 'required|min:2|max:100',
            'last_name'    => 'required|min:2|max:100',
            'email'        => 'required|email|max:255',
            'password'     => 'required|min:8|max:128',
            'company_name' => 'required|min:2|max:255',
            'plan'         => 'nullable|string',
        ];

        $data   = $this->only(['first_name', 'last_name', 'email', 'password', 'password_confirmation', 'company_name', 'plan']);
        $errors = $this->validateInput($data, $rules);

        if (($data['password'] ?? '') !== ($data['password_confirmation'] ?? '')) {
            $errors['password_confirmation'] = 'As senhas nao conferem.';
        }

        if ($this->userModel->findByEmail($data['email'] ?? '') !== null) {
            $errors['email'] = 'Este e-mail ja esta cadastrado.';
        }

        if (!empty($errors)) {
            return $this->redirectBack([
                'errors' => $errors,
                'old'    => array_diff_key($data, ['password' => 1, 'password_confirmation' => 1]),
            ]);
        }

        // Create tenant
        $slug = $this->generateTenantSlug($data['company_name']);

        try {
            $db = \Core\Database::getInstance();

            // Insert tenant directly
            $stmt = $db->prepare(
                'INSERT INTO tenants (name, slug, plan_id, status, subscription_status, subscription_ends_at, settings, created_at, updated_at)
                 VALUES (:name, :slug, :plan_id, :status, :sub_status, :sub_ends, :settings, :created_at, :updated_at)'
            );
            $stmt->execute([
                'name'       => $data['company_name'],
                'slug'       => $slug,
                'plan_id'    => $this->resolvePlanId($data['plan'] ?? 'free'),
                'status'     => 'active',
                'sub_status' => 'trialing',
                'sub_ends'   => date('Y-m-d H:i:s', strtotime('+14 days')),
                'settings'   => json_encode(['locale' => 'pt_BR', 'timezone' => 'America/Sao_Paulo']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $tenantId = (int) $db->lastInsertId();
        } catch (\Throwable $e) {
            return $this->redirectBack([
                'error' => 'Erro ao criar sua conta. Tente novamente.',
                'old'   => array_diff_key($data, ['password' => 1, 'password_confirmation' => 1]),
            ]);
        }

        // Create admin user
        $fullName = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

        try {
            $stmt = $db->prepare(
                'INSERT INTO users (tenant_id, name, email, password, role, status, locale, timezone, created_at, updated_at)
                 VALUES (:tenant_id, :name, :email, :password, :role, :status, :locale, :timezone, :created_at, :updated_at)'
            );
            $stmt->execute([
                'tenant_id'  => $tenantId,
                'name'       => $fullName,
                'email'      => $data['email'],
                'password'   => password_hash($data['password'], PASSWORD_BCRYPT),
                'role'       => 'admin',
                'status'     => 'active',
                'locale'     => 'pt_BR',
                'timezone'   => 'America/Sao_Paulo',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $userId = (int) $db->lastInsertId();
        } catch (\Throwable $e) {
            // Rollback tenant
            $db->prepare('DELETE FROM tenants WHERE id = :id')->execute(['id' => $tenantId]);
            return $this->redirectBack([
                'error' => 'Erro ao criar o usuario. Tente novamente.',
                'old'   => array_diff_key($data, ['password' => 1, 'password_confirmation' => 1]),
            ]);
        }

        // Store verification token
        $verificationToken = $this->generateToken(64);
        $this->storeVerificationToken($userId, $verificationToken);

        // Send verification email (stub)
        $this->sendVerificationEmail($data['email'], $fullName, $verificationToken);

        $this->flash('success', 'Conta criada com sucesso! Verifique seu e-mail para ativar sua conta.');

        return $this->redirect('/login');
    }

    // ==================================================================
    // LOGOUT
    // ==================================================================

    public function logout(): Response|null
    {
        $this->destroySession();
        return $this->redirect('/login');
    }

    // ==================================================================
    // PASSWORD RESET
    // ==================================================================

    public function forgotForm(): string|Response
    {
        return $this->view('auth.forgot', [
            'pageTitle' => 'Esqueci minha Senha',
        ], 'layouts.auth');
    }

    public function forgot(): string|Response|null
    {
        if (!$this->verifyCsrf()) {
            return $this->redirectBack(['error' => 'Token de seguranca invalido.']);
        }

        $email  = trim($_POST['email'] ?? '');
        $errors = $this->validateInput(['email' => $email], ['email' => 'required|email']);

        if (!empty($errors)) {
            return $this->redirectBack(['errors' => $errors, 'old' => ['email' => $email]]);
        }

        $user = $this->userModel->findByEmail($email);

        if ($user !== null) {
            $token = $this->generateToken(64);
            $this->storePasswordResetToken($email, $token);
            $this->sendPasswordResetEmail($email, $user['name'] ?? '', $token);
        }

        $this->flash('success', 'Se este e-mail estiver cadastrado, voce recebera um link para redefinir sua senha.');

        return $this->redirect('/forgot-password');
    }

    public function resetForm(): string|Response
    {
        $token = $_GET['token'] ?? '';
        $email = $_GET['email'] ?? '';

        if ($token === '' || $email === '') {
            $this->flash('error', 'Link de redefinicao invalido.');
            return $this->redirect('/login');
        }

        return $this->view('auth.reset', [
            'pageTitle' => 'Redefinir Senha',
            'token'     => $token,
            'email'     => $email,
        ], 'layouts.auth');
    }

    public function reset(): string|Response|null
    {
        if (!$this->verifyCsrf()) {
            return $this->redirectBack(['error' => 'Token de seguranca invalido.']);
        }

        $data  = $this->only(['email', 'token', 'password', 'password_confirmation']);
        $rules = [
            'email'    => 'required|email',
            'token'    => 'required|string',
            'password' => 'required|min:8|max:128',
        ];

        $errors = $this->validateInput($data, $rules);

        if (($data['password'] ?? '') !== ($data['password_confirmation'] ?? '')) {
            $errors['password_confirmation'] = 'As senhas nao conferem.';
        }

        if (!empty($errors)) {
            return $this->redirectBack(['errors' => $errors]);
        }

        if (!$this->verifyPasswordResetToken($data['email'], $data['token'])) {
            $this->flash('error', 'Token invalido ou expirado.');
            return $this->redirect('/forgot-password');
        }

        $user = $this->userModel->findByEmail($data['email']);

        if ($user === null) {
            $this->flash('error', 'Usuario nao encontrado.');
            return $this->redirect('/forgot-password');
        }

        $this->userModel->update((int) $user['id'], [
            'password'   => password_hash($data['password'], PASSWORD_BCRYPT),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->deletePasswordResetToken($data['email']);

        $this->flash('success', 'Senha redefinida com sucesso! Voce ja pode fazer login.');

        return $this->redirect('/login');
    }

    // ==================================================================
    // EMAIL VERIFICATION
    // ==================================================================

    public function verifyEmail(): string|Response|null
    {
        $token  = $_GET['token'] ?? '';
        $userId = (int) ($_GET['id'] ?? 0);

        if ($token === '' || $userId === 0) {
            $this->flash('error', 'Link de verificacao invalido.');
            return $this->redirect('/login');
        }

        if (!$this->verifyEmailToken($userId, $token)) {
            $this->flash('error', 'Link de verificacao invalido ou expirado.');
            return $this->redirect('/login');
        }

        $this->userModel->update($userId, [
            'email_verified_at' => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        $this->deleteEmailVerificationToken($userId);

        $this->flash('success', 'E-mail verificado com sucesso! Voce ja pode fazer login.');

        return $this->redirect('/login');
    }

    // ==================================================================
    // TWO-FACTOR AUTHENTICATION
    // ==================================================================

    public function enable2FA(): string|Response
    {
        $userId = $_SESSION['2fa_user_id'] ?? null;

        if ($userId === null) {
            return $this->redirect('/login');
        }

        return $this->view('auth.two-factor', [
            'pageTitle' => 'Autenticacao de Dois Fatores',
        ], 'layouts.auth');
    }

    public function verify2FA(): string|Response|null
    {
        if (!$this->verifyCsrf()) {
            return $this->redirectBack(['error' => 'Token de seguranca invalido.']);
        }

        $userId = $_SESSION['2fa_user_id'] ?? null;

        if ($userId === null) {
            return $this->redirect('/login');
        }

        $code = trim($_POST['code'] ?? '');

        if ($code === '') {
            return $this->redirectBack(['error' => 'Digite o codigo de verificacao.']);
        }

        $user = $this->userModel->findBy('id', $userId);

        if ($user === null) {
            return $this->redirect('/login');
        }

        if (!$this->verifyTotpCode($user['two_factor_secret'], $code)) {
            return $this->redirectBack(['error' => 'Codigo de verificacao invalido.']);
        }

        $remember = $_SESSION['2fa_remember'] ?? false;
        unset($_SESSION['2fa_user_id'], $_SESSION['2fa_remember']);

        $this->createUserSession($user, (bool) $remember);

        $intended = $_SESSION['url.intended'] ?? null;
        unset($_SESSION['url.intended']);

        if ($intended !== null && $intended !== '/login') {
            return $this->redirect($intended);
        }

        $this->redirectAuthenticatedUser($user);
        return null;
    }

    // ==================================================================
    // PRIVATE HELPERS
    // ==================================================================

    /**
     * Create an authenticated session for the user.
     */
    private function createUserSession(array $user, bool $remember = false): void
    {
        session_regenerate_id(true);

        $sessionUser = [
            'id'        => (int) $user['id'],
            'tenant_id' => (int) ($user['tenant_id'] ?? 0),
            'email'     => $user['email'],
            'name'      => $user['name'] ?? '',
            'role'      => $user['role'] ?? 'viewer',
            'avatar'    => $user['avatar'] ?? '',
            'locale'    => $user['locale'] ?? 'pt_BR',
            'timezone'  => $user['timezone'] ?? 'America/Sao_Paulo',
        ];

        $_SESSION['user_id']        = (int) $user['id'];
        $_SESSION['user']           = $sessionUser;
        $_SESSION['_last_activity'] = time();
        $_SESSION['_created']       = time();
        $_SESSION['_ip']            = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['_user_agent']    = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if ($remember) {
            $lifetime = 60 * 60 * 24 * 30; // 30 days
            session_set_cookie_params(['lifetime' => $lifetime]);
            ini_set('session.gc_maxlifetime', (string) $lifetime);
        }

        $this->userModel->updateLastLogin((int) $user['id']);
    }

    private function destroySession(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly'],
            );
        }

        session_destroy();
    }

    protected function isAuthenticated(): bool
    {
        return !empty($_SESSION['user_id']) && !empty($_SESSION['user']);
    }

    private function redirectAuthenticatedUser(?array $user = null): never
    {
        $user ??= $_SESSION['user'] ?? null;
        $role = $user['role'] ?? 'viewer';

        if ($role === 'super_admin') {
            header('Location: /admin', true, 302);
        } else {
            header('Location: /dashboard', true, 302);
        }

        exit;
    }

    private function checkTenantStatus(array $user): bool
    {
        $tenantId = (int) ($user['tenant_id'] ?? 0);

        if (($user['role'] ?? '') === 'super_admin') {
            return true;
        }

        if ($tenantId === 0) {
            return false;
        }

        $tenant = $this->tenantModel->findBy('id', $tenantId);

        if ($tenant === null) {
            return false;
        }

        return in_array($tenant['status'] ?? '', ['active', 'inactive'], true);
    }

    private function generateTenantSlug(string $companyName): string
    {
        $slug = strtolower(trim($companyName));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        $baseSlug = $slug;
        $counter  = 1;

        while ($this->tenantModel->findBy('slug', $slug) !== null) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function resolvePlanId(string $planSlug): int
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->prepare('SELECT id FROM plans WHERE slug = :slug AND is_active = 1 LIMIT 1');
            $stmt->execute(['slug' => $planSlug]);
            $plan = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $plan ? (int) $plan['id'] : 1;
        } catch (\Throwable) {
            return 1;
        }
    }

    private function getAvailablePlans(): array
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->query('SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order ASC');
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function generateToken(int $length = 64): string
    {
        return bin2hex(random_bytes(intdiv($length, 2)));
    }

    // ------------------------------------------------------------------
    // Token storage helpers
    // ------------------------------------------------------------------

    private function storeVerificationToken(int $userId, string $token): void
    {
        try {
            $db = \Core\Database::getInstance();
            $db->prepare(
                'INSERT INTO email_verifications (user_id, token, created_at)
                 VALUES (:user_id, :token, :created_at)
                 ON DUPLICATE KEY UPDATE token = :token2, created_at = :created_at2'
            )->execute([
                'user_id'      => $userId,
                'token'        => hash('sha256', $token),
                'created_at'   => date('Y-m-d H:i:s'),
                'token2'       => hash('sha256', $token),
                'created_at2'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
        }
    }

    private function verifyEmailToken(int $userId, string $token): bool
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->prepare(
                'SELECT * FROM email_verifications
                 WHERE user_id = :user_id AND token = :token AND created_at > DATE_SUB(NOW(), INTERVAL 48 HOUR)
                 LIMIT 1'
            );
            $stmt->execute([
                'user_id' => $userId,
                'token'   => hash('sha256', $token),
            ]);
            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return false;
        }
    }

    private function deleteEmailVerificationToken(int $userId): void
    {
        try {
            $db = \Core\Database::getInstance();
            $db->prepare('DELETE FROM email_verifications WHERE user_id = :user_id')
               ->execute(['user_id' => $userId]);
        } catch (\Throwable) {
        }
    }

    private function storePasswordResetToken(string $email, string $token): void
    {
        try {
            $db = \Core\Database::getInstance();
            $db->prepare('DELETE FROM password_resets WHERE email = :email')
               ->execute(['email' => $email]);

            $db->prepare(
                'INSERT INTO password_resets (email, token, created_at)
                 VALUES (:email, :token, :created_at)'
            )->execute([
                'email'      => $email,
                'token'      => hash('sha256', $token),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
        }
    }

    private function verifyPasswordResetToken(string $email, string $token): bool
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->prepare(
                'SELECT * FROM password_resets
                 WHERE email = :email AND token = :token AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                 LIMIT 1'
            );
            $stmt->execute([
                'email' => $email,
                'token' => hash('sha256', $token),
            ]);
            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return false;
        }
    }

    private function deletePasswordResetToken(string $email): void
    {
        try {
            $db = \Core\Database::getInstance();
            $db->prepare('DELETE FROM password_resets WHERE email = :email')
               ->execute(['email' => $email]);
        } catch (\Throwable) {
        }
    }

    // ------------------------------------------------------------------
    // Email sending stubs
    // ------------------------------------------------------------------

    private function sendVerificationEmail(string $email, string $name, string $token): void
    {
        // TODO: Integrate with mailer
    }

    private function sendPasswordResetEmail(string $email, string $name, string $token): void
    {
        // TODO: Integrate with mailer
    }

    // ------------------------------------------------------------------
    // TOTP verification
    // ------------------------------------------------------------------

    private function verifyTotpCode(string $secret, string $code): bool
    {
        $timeStep = 30;
        $codeLen  = 6;
        $window   = 1;

        $currentTimeSlice = intdiv(time(), $timeStep);
        $decodedSecret = $this->base32Decode($secret);

        if ($decodedSecret === '') {
            return false;
        }

        for ($i = -$window; $i <= $window; $i++) {
            $timeSlice = $currentTimeSlice + $i;
            $timeBytes = pack('N*', 0) . pack('N*', $timeSlice);
            $hash      = hash_hmac('sha1', $timeBytes, $decodedSecret, true);
            $offset    = ord($hash[19]) & 0x0F;

            $otp = (
                ((ord($hash[$offset])     & 0x7F) << 24) |
                ((ord($hash[$offset + 1]) & 0xFF) << 16) |
                ((ord($hash[$offset + 2]) & 0xFF) << 8)  |
                (ord($hash[$offset + 3])  & 0xFF)
            ) % (10 ** $codeLen);

            $expected = str_pad((string) $otp, $codeLen, '0', STR_PAD_LEFT);

            if (hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    private function base32Decode(string $input): string
    {
        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper(rtrim($input, '='));

        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0, $len = strlen($input); $i < $len; $i++) {
            $val = strpos($map, $input[$i]);
            if ($val === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }

    // ------------------------------------------------------------------
    // Logging
    // ------------------------------------------------------------------

    private function logFailedLogin(string $email): void
    {
        try {
            $db = \Core\Database::getInstance();
            $db->prepare(
                'INSERT INTO audit_logs (action, entity_type, details, ip_address, user_agent, created_at)
                 VALUES (:action, :entity_type, :details, :ip, :ua, :created_at)'
            )->execute([
                'action'      => 'login_failed',
                'entity_type' => 'auth',
                'details'     => json_encode(['email' => $email]),
                'ip'          => $_SERVER['REMOTE_ADDR'] ?? '',
                'ua'          => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
        }
    }

    // ------------------------------------------------------------------
    // Request helpers
    // ------------------------------------------------------------------

    protected function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $_POST[$key] ?? null;
        }
        return $result;
    }

    private function validateInput(array $data, array $rules): array
    {
        $validator = new \Core\Validator($data, $rules);
        if (!$validator->passes()) {
            return $validator->errors();
        }
        return [];
    }

    private function verifyCsrf(): bool
    {
        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $sessionToken = $_SESSION['_csrf_token'] ?? '';

        if ($token === '' || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    protected function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    private function redirectBack(array $flash = []): never
    {
        foreach ($flash as $key => $value) {
            $_SESSION['_flash'][$key] = $value;
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '/login';
        header("Location: {$referer}", true, 302);
        exit;
    }
}
