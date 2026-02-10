<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Models\Tenant;
use Core\Controller;

/**
 * Authentication Controller
 *
 * Handles all authentication-related workflows: login, registration, logout,
 * password reset, email verification and two-factor authentication. After a
 * successful login, super-admin users are redirected to /admin while regular
 * client users are sent to /dashboard.
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

    /**
     * Display the login form.
     */
    public function loginForm(): string
    {
        // If already logged in, redirect to the appropriate dashboard.
        if ($this->isAuthenticated()) {
            $this->redirectAuthenticatedUser();
        }

        return $this->view('auth/login', [
            'title'    => 'Login',
            'intended' => $_SESSION['url.intended'] ?? '/dashboard',
        ]);
    }

    /**
     * Process a login request.
     */
    public function login(): string|null
    {
        // ---- CSRF check ----
        if (!$this->verifyCsrf()) {
            return $this->redirectBack(['error' => 'Invalid security token. Please try again.']);
        }

        // ---- Validation ----
        $rules = [
            'email'    => 'required|email|max:255',
            'password' => 'required|min:6|max:128',
        ];

        $data   = $this->only(['email', 'password', 'remember']);
        $errors = $this->validate($data, $rules);

        if (!empty($errors)) {
            return $this->redirectBack([
                'errors' => $errors,
                'old'    => ['email' => $data['email'] ?? ''],
            ]);
        }

        // ---- Authentication ----
        $user = $this->userModel->authenticate($data['email'], $data['password']);

        if ($user === null) {
            $this->logFailedLogin($data['email']);
            return $this->redirectBack([
                'error' => 'Invalid email or password.',
                'old'   => ['email' => $data['email']],
            ]);
        }

        // ---- Tenant status check ----
        if (!$this->checkTenantStatus($user)) {
            return $this->redirectBack([
                'error' => 'Your account has been suspended. Please contact support.',
                'old'   => ['email' => $data['email']],
            ]);
        }

        // ---- Two-factor authentication ----
        if ($this->userModel->hasTwoFactor($user)) {
            $_SESSION['2fa_user_id'] = (int) $user['id'];
            $_SESSION['2fa_remember'] = !empty($data['remember']);
            return $this->redirect('/login/2fa');
        }

        // ---- Create session ----
        $this->createUserSession($user, !empty($data['remember']));

        // ---- Redirect ----
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

    /**
     * Display the registration form.
     */
    public function registerForm(): string
    {
        if ($this->isAuthenticated()) {
            $this->redirectAuthenticatedUser();
        }

        return $this->view('auth/register', [
            'title' => 'Create Account',
            'plans' => $this->getAvailablePlans(),
        ]);
    }

    /**
     * Process a registration request.
     *
     * Creates a new tenant and the first admin user for that tenant, then
     * sends an email verification link.
     */
    public function register(): string|null
    {
        if (!$this->verifyCsrf()) {
            return $this->redirectBack(['error' => 'Invalid security token.']);
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
        $errors = $this->validate($data, $rules);

        // Password confirmation.
        if (($data['password'] ?? '') !== ($data['password_confirmation'] ?? '')) {
            $errors['password_confirmation'] = 'Passwords do not match.';
        }

        // Check if email already exists.
        if ($this->userModel->findByEmail($data['email'] ?? '') !== null) {
            $errors['email'] = 'This email address is already registered.';
        }

        if (!empty($errors)) {
            return $this->redirectBack([
                'errors' => $errors,
                'old'    => array_diff_key($data, ['password' => 1, 'password_confirmation' => 1]),
            ]);
        }

        // ---- Create tenant ----
        $slug = $this->generateTenantSlug($data['company_name']);

        $tenantData = [
            'name'       => $data['company_name'],
            'slug'       => $slug,
            'plan_id'    => $this->resolvePlanId($data['plan'] ?? 'free'),
            'status'     => 'trialing',
            'trial_ends_at' => date('Y-m-d H:i:s', strtotime('+14 days')),
            'settings'   => json_encode(['locale' => 'pt_BR', 'timezone' => 'America/Sao_Paulo']),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $tenantId = $this->tenantModel->create($tenantData);

        if (!$tenantId) {
            return $this->redirectBack([
                'error' => 'An error occurred while creating your account. Please try again.',
                'old'   => array_diff_key($data, ['password' => 1, 'password_confirmation' => 1]),
            ]);
        }

        // ---- Create admin user ----
        $verificationToken = $this->generateToken(64);

        $userData = [
            'tenant_id'          => $tenantId,
            'email'              => $data['email'],
            'password_hash'      => password_hash($data['password'], PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost'   => 4,
                'threads'     => 3,
            ]),
            'first_name'         => $data['first_name'],
            'last_name'          => $data['last_name'],
            'role'               => 'admin',
            'is_active'          => 1,
            'email_verified_at'  => null,
            'locale'             => 'pt_BR',
            'timezone'           => 'America/Sao_Paulo',
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ];

        $userId = $this->userModel->create($userData);

        if (!$userId) {
            // Rollback: delete the tenant.
            $this->tenantModel->delete((int) $tenantId);
            return $this->redirectBack([
                'error' => 'An error occurred while creating your user account.',
                'old'   => array_diff_key($data, ['password' => 1, 'password_confirmation' => 1]),
            ]);
        }

        // ---- Store verification token ----
        $this->storeVerificationToken((int) $userId, $verificationToken);

        // ---- Send verification email ----
        $this->sendVerificationEmail($data['email'], $data['first_name'], $verificationToken);

        // ---- Flash success and redirect ----
        $this->flash('success', 'Account created successfully! Please check your email to verify your account.');

        return $this->redirect('/login');
    }

    // ==================================================================
    // LOGOUT
    // ==================================================================

    /**
     * Log the user out and destroy the session.
     */
    public function logout(): null
    {
        $this->destroySession();

        return $this->redirect('/login');
    }

    // ==================================================================
    // PASSWORD RESET
    // ==================================================================

    /**
     * Display the "forgot password" form.
     */
    public function forgotForm(): string
    {
        return $this->view('auth/forgot-password', [
            'title' => 'Forgot Password',
        ]);
    }

    /**
     * Process a "forgot password" request.
     *
     * Always returns a success message regardless of whether the email exists,
     * to avoid leaking account information.
     */
    public function forgot(): string|null
    {
        if (!$this->verifyCsrf()) {
            return $this->redirectBack(['error' => 'Invalid security token.']);
        }

        $email  = trim($_POST['email'] ?? '');
        $errors = $this->validate(['email' => $email], ['email' => 'required|email']);

        if (!empty($errors)) {
            return $this->redirectBack(['errors' => $errors, 'old' => ['email' => $email]]);
        }

        $user = $this->userModel->findByEmail($email);

        if ($user !== null) {
            $token = $this->generateToken(64);
            $this->storePasswordResetToken($email, $token);
            $this->sendPasswordResetEmail($email, $user['first_name'] ?? '', $token);
        }

        // Always show the same message.
        $this->flash('success', 'If this email is registered, you will receive a password reset link shortly.');

        return $this->redirect('/forgot-password');
    }

    /**
     * Display the password reset form.
     */
    public function resetForm(): string
    {
        $token = $_GET['token'] ?? '';
        $email = $_GET['email'] ?? '';

        if ($token === '' || $email === '') {
            $this->flash('error', 'Invalid password reset link.');
            return $this->redirect('/login');
        }

        return $this->view('auth/reset-password', [
            'title' => 'Reset Password',
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Process a password reset.
     */
    public function reset(): string|null
    {
        if (!$this->verifyCsrf()) {
            return $this->redirectBack(['error' => 'Invalid security token.']);
        }

        $data  = $this->only(['email', 'token', 'password', 'password_confirmation']);
        $rules = [
            'email'    => 'required|email',
            'token'    => 'required|string',
            'password' => 'required|min:8|max:128',
        ];

        $errors = $this->validate($data, $rules);

        if (($data['password'] ?? '') !== ($data['password_confirmation'] ?? '')) {
            $errors['password_confirmation'] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            return $this->redirectBack(['errors' => $errors]);
        }

        // Verify the reset token.
        if (!$this->verifyPasswordResetToken($data['email'], $data['token'])) {
            $this->flash('error', 'Invalid or expired reset token.');
            return $this->redirect('/forgot-password');
        }

        // Update the password.
        $user = $this->userModel->findByEmail($data['email']);

        if ($user === null) {
            $this->flash('error', 'User not found.');
            return $this->redirect('/forgot-password');
        }

        $this->userModel->update((int) $user['id'], [
            'password_hash' => password_hash($data['password'], PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost'   => 4,
                'threads'     => 3,
            ]),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Invalidate the token.
        $this->deletePasswordResetToken($data['email']);

        $this->flash('success', 'Password reset successfully. You can now log in.');

        return $this->redirect('/login');
    }

    // ==================================================================
    // EMAIL VERIFICATION
    // ==================================================================

    /**
     * Verify a user's email address via a signed token link.
     */
    public function verifyEmail(): string|null
    {
        $token  = $_GET['token'] ?? '';
        $userId = (int) ($_GET['id'] ?? 0);

        if ($token === '' || $userId === 0) {
            $this->flash('error', 'Invalid verification link.');
            return $this->redirect('/login');
        }

        // Look up the token.
        if (!$this->verifyEmailToken($userId, $token)) {
            $this->flash('error', 'Invalid or expired verification link.');
            return $this->redirect('/login');
        }

        // Mark email as verified.
        $this->userModel->update($userId, [
            'email_verified_at' => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        // Clean up token.
        $this->deleteEmailVerificationToken($userId);

        $this->flash('success', 'Email verified successfully! You can now log in.');

        return $this->redirect('/login');
    }

    // ==================================================================
    // TWO-FACTOR AUTHENTICATION
    // ==================================================================

    /**
     * Display the 2FA code entry form during login.
     */
    public function enable2FA(): string
    {
        $userId = $_SESSION['2fa_user_id'] ?? null;

        if ($userId === null) {
            return $this->redirect('/login');
        }

        return $this->view('auth/two-factor', [
            'title' => 'Two-Factor Authentication',
        ]);
    }

    /**
     * Verify the 2FA code submitted during login.
     */
    public function verify2FA(): string|null
    {
        if (!$this->verifyCsrf()) {
            return $this->redirectBack(['error' => 'Invalid security token.']);
        }

        $userId = $_SESSION['2fa_user_id'] ?? null;

        if ($userId === null) {
            return $this->redirect('/login');
        }

        $code = trim($_POST['code'] ?? '');

        if ($code === '') {
            return $this->redirectBack(['error' => 'Please enter the verification code.']);
        }

        // Load user to get 2FA secret.
        $user = $this->userModel->find((int) $userId);

        if ($user === null) {
            return $this->redirect('/login');
        }

        // Verify TOTP code.
        if (!$this->verifyTotpCode($user['two_factor_secret'], $code)) {
            return $this->redirectBack(['error' => 'Invalid verification code.']);
        }

        // Clean up 2FA session data and create the real session.
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
        // Regenerate session ID to prevent fixation.
        session_regenerate_id(true);

        // Store essential user data (never store the password hash in session).
        $sessionUser = [
            'id'          => (int) $user['id'],
            'tenant_id'   => (int) ($user['tenant_id'] ?? 0),
            'email'       => $user['email'],
            'first_name'  => $user['first_name'] ?? '',
            'last_name'   => $user['last_name'] ?? '',
            'role'        => $user['role'] ?? 'user',
            'permissions' => json_decode($user['permissions'] ?? '[]', true) ?: [],
            'avatar_url'  => $user['avatar_url'] ?? '',
            'locale'      => $user['locale'] ?? 'pt_BR',
            'timezone'    => $user['timezone'] ?? 'America/Sao_Paulo',
        ];

        $_SESSION['user_id']        = (int) $user['id'];
        $_SESSION['user']           = $sessionUser;
        $_SESSION['_last_activity'] = time();
        $_SESSION['_created']       = time();
        $_SESSION['_ip']            = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['_user_agent']    = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Extended session lifetime for "remember me".
        if ($remember) {
            $lifetime = 60 * 60 * 24 * 30; // 30 days
            session_set_cookie_params(['lifetime' => $lifetime]);
            ini_set('session.gc_maxlifetime', (string) $lifetime);
        }

        // Update last login in database.
        $this->userModel->updateLastLogin(
            (int) $user['id'],
            $_SERVER['REMOTE_ADDR'] ?? null
        );
    }

    /**
     * Destroy the current session completely.
     */
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

    /**
     * Check whether the current user is already logged in.
     */
    private function isAuthenticated(): bool
    {
        return !empty($_SESSION['user_id']) && !empty($_SESSION['user']);
    }

    /**
     * Redirect the authenticated user based on their role.
     */
    private function redirectAuthenticatedUser(?array $user = null): never
    {
        $user ??= $_SESSION['user'] ?? null;

        $role = $user['role'] ?? 'user';

        if ($role === 'super_admin') {
            header('Location: /admin', true, 302);
        } else {
            header('Location: /dashboard', true, 302);
        }

        exit;
    }

    /**
     * Check whether the user's tenant is in an acceptable status.
     */
    private function checkTenantStatus(array $user): bool
    {
        $tenantId = (int) ($user['tenant_id'] ?? 0);

        // Super admins are not scoped to a tenant.
        if (($user['role'] ?? '') === 'super_admin') {
            return true;
        }

        if ($tenantId === 0) {
            return false;
        }

        $tenant = $this->tenantModel->find($tenantId);

        if ($tenant === null) {
            return false;
        }

        return in_array($tenant['status'] ?? '', ['active', 'trialing'], true);
    }

    /**
     * Generate a unique slug for a new tenant based on the company name.
     */
    private function generateTenantSlug(string $companyName): string
    {
        $slug = strtolower(trim($companyName));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        // Ensure uniqueness.
        $baseSlug = $slug;
        $counter  = 1;

        while ($this->tenantModel->findBy('slug', $slug) !== null) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Resolve a plan slug to its database ID.
     */
    private function resolvePlanId(string $planSlug): int
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->prepare('SELECT id FROM plans WHERE slug = :slug AND is_active = 1 LIMIT 1');
            $stmt->execute(['slug' => $planSlug]);
            $plan = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $plan ? (int) $plan['id'] : 1; // Default to plan 1 (free).
        } catch (\Throwable) {
            return 1;
        }
    }

    /**
     * Get the list of available plans for the registration form.
     */
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

    /**
     * Generate a cryptographically secure random token.
     */
    private function generateToken(int $length = 64): string
    {
        return bin2hex(random_bytes(intdiv($length, 2)));
    }

    // ------------------------------------------------------------------
    // Token storage helpers (password reset, email verification)
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
            // Silently fail; the user can request a new verification email.
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
            // Non-critical.
        }
    }

    private function storePasswordResetToken(string $email, string $token): void
    {
        try {
            $db = \Core\Database::getInstance();
            // Delete previous tokens for this email.
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
            // Non-critical.
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
            // Non-critical.
        }
    }

    // ------------------------------------------------------------------
    // Email sending stubs (implement with your mailer of choice)
    // ------------------------------------------------------------------

    private function sendVerificationEmail(string $email, string $name, string $token): void
    {
        // TODO: Integrate with a mailer service (e.g., PHPMailer, SendGrid).
        // The verification link should point to:
        // APP_URL . '/verify-email?id={userId}&token={token}'
    }

    private function sendPasswordResetEmail(string $email, string $name, string $token): void
    {
        // TODO: Integrate with a mailer service.
        // The reset link should point to:
        // APP_URL . '/reset-password?email={email}&token={token}'
    }

    // ------------------------------------------------------------------
    // TOTP verification
    // ------------------------------------------------------------------

    /**
     * Verify a TOTP (Time-based One-Time Password) code against a secret.
     *
     * Implements RFC 6238 with a 30-second time step and a +/- 1 step window
     * to account for clock drift.
     */
    private function verifyTotpCode(string $secret, string $code): bool
    {
        $timeStep = 30;
        $codeLen  = 6;
        $window   = 1; // Allow 1 step before/after current.

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

    /**
     * Decode a Base32-encoded string (RFC 4648).
     */
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

    /**
     * Log a failed login attempt for security auditing.
     */
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
            // Non-critical: don't break the login flow.
        }
    }

    // ------------------------------------------------------------------
    // Request helpers
    // ------------------------------------------------------------------

    /**
     * Return only the specified keys from $_POST.
     */
    private function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $_POST[$key] ?? null;
        }
        return $result;
    }

    /**
     * Verify the CSRF token submitted with the form.
     */
    private function verifyCsrf(): bool
    {
        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $sessionToken = $_SESSION['_csrf_token'] ?? '';

        if ($token === '' || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Set a flash message in the session.
     */
    private function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Redirect back to the previous page with optional flash data.
     */
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
