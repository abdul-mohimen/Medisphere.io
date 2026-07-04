<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Database;
use App\Core\Security;
use App\Core\View;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use App\Services\Mailer;

class AuthController
{
    public function show(): void
    {
        View::render('auth', [
            'title' => __('common.sign_in') . ' / Register',
            'returnTo' => $this->stageReturnTo((string) ($_GET['return_to'] ?? '')),
        ]);
    }

    public function showLogin(): void
    {
        $returnTo = $this->stageReturnTo((string) ($_GET['return_to'] ?? ''));
        View::render('login', [
            'title' => __('common.sign_in'),
            'registerType' => Security::cleanString($_GET['type'] ?? 'patient'),
            'returnTo' => $returnTo,
        ]);
    }

    public function showRegister(): void
    {
        $activeRole = Security::cleanString($_GET['type'] ?? ($_SESSION['_old']['user_type'] ?? 'patient'));
        if (!in_array($activeRole, ['patient', 'doctor', 'hospital'], true)) {
            $activeRole = 'patient';
        }

        View::render('register', [
            'title' => 'Create Account',
            'activeRole' => $activeRole,
            'hospitals' => (new Hospital())->all(),
            'returnTo' => $this->stageReturnTo((string) ($_GET['return_to'] ?? '')),
        ]);
    }

    public function login(): void
    {
        $returnTo = $this->stageReturnTo((string) ($_POST['return_to'] ?? ''));
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token. Please try again.', 'danger');
            redirect('login', ['return_to' => $returnTo]);
        }

        $email = Security::cleanEmail($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $user = (new User())->findByEmail($email);
        if (!$user || !password_verify($password, $user['password'])) {
            flash('error', 'Invalid email or password.', 'danger');
            redirect('login', ['return_to' => $returnTo]);
        }

        if ($user['status'] === 'suspended') {
            flash('error', 'Your account is suspended. Please contact support.', 'danger');
            redirect('login', ['return_to' => $returnTo]);
        }

        if ($user['status'] !== 'active') {
            $message = $user['status'] === 'pending'
                ? 'Your professional account is awaiting admin approval. Please sign in after the Verification Center approves it.'
                : 'Your account is not active yet. Please contact support if you need help.';
            flash('error', $message, 'danger');
            redirect('login', ['return_to' => $returnTo]);
        }

        Auth::login($user);
        flash('success', 'Welcome back, ' . $email . '!', 'success');
        $this->redirectToUrl(Auth::consumeIntendedUrl($returnTo));
    }

    public function register(): void
    {
        $returnTo = $this->stageReturnTo((string) ($_POST['return_to'] ?? ''));
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token. Please try again.', 'danger');
            redirect('register', ['return_to' => $returnTo]);
        }

        set_old($_POST);
        $role = Security::cleanString($_POST['user_type'] ?? 'patient');
        $email = Security::cleanEmail($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        $errors = [];
        if (!in_array($role, ['patient', 'doctor', 'hospital'], true)) {
            $errors[] = 'Invalid account type selected.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }
        if ((new User())->findByEmail($email)) {
            $errors[] = 'That email is already registered.';
        }

        if ($role === 'patient' && empty(trim((string) ($_POST['name'] ?? '')))) {
            $errors[] = 'Patient name is required.';
        }
        if ($role === 'doctor') {
            foreach (['name', 'specialization', 'qualification_details', 'license_number'] as $field) {
                if (empty(trim((string) ($_POST[$field] ?? '')))) {
                    $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
                }
            }
        }
        if ($role === 'hospital') {
            foreach (['hospital_name', 'registration_number'] as $field) {
                if (empty(trim((string) ($_POST[$field] ?? '')))) {
                    $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
                }
            }
        }

        if ($errors) {
            flash('error', implode(' ', $errors), 'danger');
            redirect('register', ['type' => $role, 'return_to' => $returnTo]);
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $user = new User();
            $status = $role === 'patient' ? 'active' : 'pending';
            $userId = $user->create($email, $password, $role, $status);

            if ($role === 'patient') {
                (new Patient())->create([
                    'user_id' => $userId,
                    'name' => Security::cleanString($_POST['name'] ?? ''),
                    'age' => Security::cleanInt($_POST['age'] ?? 0),
                    'gender' => Security::cleanString($_POST['gender'] ?? ''),
                    'blood_group' => Security::cleanString($_POST['blood_group'] ?? ''),
                    'address' => Security::cleanString($_POST['address'] ?? ''),
                    'phone' => Security::cleanString($_POST['phone'] ?? ''),
                    'city' => Security::cleanString($_POST['city'] ?? ''),
                    'country' => Security::cleanString($_POST['country'] ?? ''),
                ]);
            } elseif ($role === 'doctor') {
                (new Doctor())->create([
                    'user_id' => $userId,
                    'name' => Security::cleanString($_POST['name'] ?? ''),
                    'specialization' => Security::cleanString($_POST['specialization'] ?? ''),
                    'qualification_details' => Security::cleanString($_POST['qualification_details'] ?? ''),
                    'license_number' => Security::cleanString($_POST['license_number'] ?? ''),
                    'experience' => Security::cleanInt($_POST['experience'] ?? 0),
                    'hospital_id' => Security::cleanInt($_POST['hospital_id'] ?? 0),
                    'verified_status' => 'pending',
                    'consultation_fee' => Security::cleanInt($_POST['consultation_fee'] ?? 0),
                    'languages_spoken' => Security::cleanString($_POST['languages_spoken'] ?? ''),
                    'bio' => Security::cleanString($_POST['bio'] ?? ''),
                ]);
            } elseif ($role === 'hospital') {
                (new Hospital())->create([
                    'user_id' => $userId,
                    'name' => Security::cleanString($_POST['hospital_name'] ?? ''),
                    'type' => Security::cleanString($_POST['hospital_type'] ?? 'private'),
                    'address' => Security::cleanString($_POST['address'] ?? ''),
                    'city' => Security::cleanString($_POST['city'] ?? ''),
                    'country' => Security::cleanString($_POST['country'] ?? ''),
                    'phone' => Security::cleanString($_POST['phone'] ?? ''),
                    'email' => $email,
                    'registration_number' => Security::cleanString($_POST['registration_number'] ?? ''),
                    'facilities' => isset($_POST['facilities']) ? implode(', ', array_map('trim', (array) $_POST['facilities'])) : '',
                    'departments' => Security::cleanString($_POST['departments'] ?? ''),
                    'coordinates' => Security::cleanString($_POST['coordinates'] ?? ''),
                    'verified_status' => 'pending',
                ]);
            }

            $db->commit();
            clear_old();
            $createdUser = $user->findById($userId);
            if ($role === 'patient' && $createdUser) {
                Auth::login($createdUser);
                flash('success', 'Registration successful. Welcome to MediSphere.', 'success');
                $this->redirectToUrl(Auth::consumeIntendedUrl($returnTo));
            }

            flash('success', 'Registration submitted. Verification is pending. Please sign in after approval.', 'success');
            redirect('login', ['return_to' => $returnTo]);
        } catch (\Throwable $e) {
            $db->rollBack();
            flash('error', 'Registration failed. ' . (config('app.debug') ? $e->getMessage() : 'Please try again.'), 'danger');
            redirect('register', ['type' => $role, 'return_to' => $returnTo]);
        }
    }

    public function forgotPassword(): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('login');
        }

        $email = Security::cleanEmail($_POST['email'] ?? '');
        $user = (new User())->findByEmail($email);
        if (!$user) {
            flash('success', 'If the email exists, a reset link has been sent.', 'success');
            redirect('login');
        }

        $token = bin2hex(random_bytes(24));
        $db = Database::connection();
        $db->prepare('DELETE FROM password_resets WHERE email = :email')->execute(['email' => $email]);
        $db->prepare('INSERT INTO password_resets (email, token, expires_at, created_at) VALUES (:email, :token, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())')->execute([
            'email' => $email,
            'token' => $token,
        ]);

        $resetLink = route_url('reset-password', ['token' => $token]);
        (new Mailer())->send($email, 'Reset Your MediSphere Password', "<p>Click the link below to reset your password:</p><p><a href=\"{$resetLink}\">{$resetLink}</a></p>");

        flash('success', 'If the email exists, a reset link has been sent.', 'success');
        redirect('login');
    }

    public function showResetPassword(): void
    {
        View::render('reset_password', ['title' => __('auth.reset_title'), 'token' => $_GET['token'] ?? '']);
    }

    public function resetPassword(): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('login');
        }

        $token = Security::cleanString($_POST['token'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if (strlen($password) < 8 || $password !== $confirm) {
            flash('error', 'Please provide matching passwords with at least 8 characters.', 'danger');
            redirect('reset-password', ['token' => $token]);
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM password_resets WHERE token = :token AND expires_at > NOW() LIMIT 1');
        $stmt->execute(['token' => $token]);
        $record = $stmt->fetch();

        if (!$record) {
            flash('error', 'Reset link is invalid or expired.', 'danger');
            redirect('login');
        }

        $db->prepare('UPDATE users SET password = :password WHERE email = :email')->execute([
            'password' => password_hash($password, config('security.password_algo')),
            'email' => $record['email'],
        ]);
        $db->prepare('DELETE FROM password_resets WHERE email = :email')->execute(['email' => $record['email']]);

        flash('success', 'Password updated successfully. Please sign in.', 'success');
        redirect('login');
    }

    public function googleRedirect(): void
    {
        $this->redirectToOAuthProvider('google');
    }

    public function facebookRedirect(): void
    {
        $this->redirectToOAuthProvider('facebook');
    }

    public function xRedirect(): void
    {
        $this->redirectToOAuthProvider('x');
    }

    public function googleCallback(): void
    {
        $this->handleOAuthCallback('google');
    }

    public function facebookCallback(): void
    {
        $this->handleOAuthCallback('facebook');
    }

    public function xCallback(): void
    {
        $this->handleOAuthCallback('x');
    }

    private function redirectToOAuthProvider(string $provider): void
    {
        $settings = $this->oauthSettings($provider);
        if (!$settings) {
            flash('error', $this->providerLabel($provider) . ' login is not configured. Add the Client ID and Secret in .env or config/config.php.', 'danger');
            redirect('login');
        }

        $state = bin2hex(random_bytes(24));
        $_SESSION['_oauth_state'][$provider] = $state;
        $_SESSION['_oauth_redirect_uri'][$provider] = $settings['redirect_uri'];
        $_SESSION['_auth_return_to'] = $this->stageReturnTo((string) ($_GET['return_to'] ?? ($_SESSION['_auth_return_to'] ?? '')));

        if ($provider === 'google') {
            $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'client_id' => $settings['client_id'],
                'redirect_uri' => $settings['redirect_uri'],
                'response_type' => 'code',
                'scope' => 'openid email profile',
                'state' => $state,
                'prompt' => 'select_account',
                'access_type' => 'online',
            ]);
        } elseif ($provider === 'facebook') {
            $url = 'https://www.facebook.com/dialog/oauth?' . http_build_query([
                'client_id' => $settings['client_id'],
                'redirect_uri' => $settings['redirect_uri'],
                'response_type' => 'code',
                'scope' => 'email,public_profile',
                'state' => $state,
            ]);
        } else {
            $codeVerifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            $_SESSION['_oauth_pkce'][$provider] = $codeVerifier;
            $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

            $url = 'https://twitter.com/i/oauth2/authorize?' . http_build_query([
                'client_id' => $settings['client_id'],
                'redirect_uri' => $settings['redirect_uri'],
                'response_type' => 'code',
                'scope' => 'users.read tweet.read',
                'state' => $state,
                'code_challenge' => $codeChallenge,
                'code_challenge_method' => 'S256',
            ]);
        }

        header('Location: ' . $url);
        exit;
    }

    private function handleOAuthCallback(string $provider): void
    {
        $returnTo = $this->safeReturnTo((string) ($_SESSION['_auth_return_to'] ?? route_url('dashboard')));
        if (!empty($_GET['error'])) {
            $error = Security::cleanString($_GET['error_description'] ?? $_GET['error']);
            flash('error', $this->providerLabel($provider) . ' login was cancelled or denied. ' . $error, 'danger');
            redirect('login', ['return_to' => $returnTo]);
        }

        $settings = $this->oauthSettings($provider);
        if (!$settings) {
            flash('error', $this->providerLabel($provider) . ' login is not configured correctly.', 'danger');
            redirect('login', ['return_to' => $returnTo]);
        }

        $expectedState = (string) ($_SESSION['_oauth_state'][$provider] ?? '');
        $state = (string) ($_GET['state'] ?? '');
        $redirectUri = (string) ($_SESSION['_oauth_redirect_uri'][$provider] ?? $settings['redirect_uri']);
        unset($_SESSION['_oauth_state'][$provider], $_SESSION['_oauth_redirect_uri'][$provider]);

        if ($expectedState === '' || $state === '' || !hash_equals($expectedState, $state)) {
            flash('error', 'Social login session expired. Please try again.', 'danger');
            redirect('login', ['return_to' => $returnTo]);
        }

        $code = (string) ($_GET['code'] ?? '');
        if ($code === '') {
            flash('error', $this->providerLabel($provider) . ' did not return an authorization code.', 'danger');
            redirect('login', ['return_to' => $returnTo]);
        }

        $settings['redirect_uri'] = $redirectUri;

        try {
            $profile = match ($provider) {
                'google' => $this->fetchGoogleProfile($settings, $code),
                'facebook' => $this->fetchFacebookProfile($settings, $code),
                'x' => $this->fetchXProfile($settings, $code),
                default => throw new \RuntimeException('Unsupported social login provider.'),
            };

            $user = $this->findOrCreateSocialPatient($profile);
            Auth::login($user);
            flash('success', 'Welcome, ' . ($profile['name'] ?: $user['email']) . '!', 'success');
            unset($_SESSION['_auth_return_to']);
            $this->redirectToUrl(Auth::consumeIntendedUrl($returnTo));
        } catch (\Throwable $e) {
            $message = config('app.debug')
                ? 'Social login failed. ' . $e->getMessage()
                : 'Social login failed. Please try again or use email/password login.';
            flash('error', $message, 'danger');
            redirect('login', ['return_to' => $returnTo]);
        }
    }

    private function oauthSettings(string $provider): ?array
    {
        if (!in_array($provider, ['google', 'facebook', 'x'], true)) {
            return null;
        }

        $settings = config('services.oauth_' . $provider, []);
        if ($provider === 'x' && (!$settings || empty($settings['client_id']))) {
            $settings = config('services.oauth_twitter', []);
        }
        $clientId = trim((string) ($settings['client_id'] ?? ''));
        $clientSecret = trim((string) ($settings['client_secret'] ?? ''));
        if ($clientId === '' || $clientSecret === '') {
            return null;
        }

        $redirectUri = trim((string) ($settings['redirect_uri'] ?? ''));
        if ($redirectUri === '') {
            $redirectUri = app_url('index.php?route=auth/' . $provider . '/callback');
        }

        return [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
        ];
    }

    private function fetchGoogleProfile(array $settings, string $code): array
    {
        $token = $this->httpJson('https://oauth2.googleapis.com/token', [
            'method' => 'POST',
            'form' => [
                'code' => $code,
                'client_id' => $settings['client_id'],
                'client_secret' => $settings['client_secret'],
                'redirect_uri' => $settings['redirect_uri'],
                'grant_type' => 'authorization_code',
            ],
        ]);

        if (empty($token['access_token'])) {
            throw new \RuntimeException('Google did not return an access token.');
        }

        $profile = $this->httpJson('https://www.googleapis.com/oauth2/v3/userinfo', [
            'headers' => ['Authorization: Bearer ' . $token['access_token']],
        ]);

        if (array_key_exists('email_verified', $profile) && !$profile['email_verified']) {
            throw new \RuntimeException('Google account email is not verified.');
        }

        return [
            'provider' => 'google',
            'provider_id' => (string) ($profile['sub'] ?? ''),
            'email' => (string) ($profile['email'] ?? ''),
            'name' => (string) ($profile['name'] ?? ''),
            'avatar' => (string) ($profile['picture'] ?? ''),
        ];
    }

    private function fetchFacebookProfile(array $settings, string $code): array
    {
        $token = $this->httpJson('https://graph.facebook.com/oauth/access_token?' . http_build_query([
            'client_id' => $settings['client_id'],
            'client_secret' => $settings['client_secret'],
            'redirect_uri' => $settings['redirect_uri'],
            'code' => $code,
        ]));

        if (empty($token['access_token'])) {
            throw new \RuntimeException('Facebook did not return an access token.');
        }

        $profile = $this->httpJson('https://graph.facebook.com/me?' . http_build_query([
            'fields' => 'id,name,email,picture.type(large)',
            'access_token' => $token['access_token'],
        ]));

        $avatar = '';
        if (!empty($profile['picture']['data']['url'])) {
            $avatar = (string) $profile['picture']['data']['url'];
        }

        return [
            'provider' => 'facebook',
            'provider_id' => (string) ($profile['id'] ?? ''),
            'email' => (string) ($profile['email'] ?? ''),
            'name' => (string) ($profile['name'] ?? ''),
            'avatar' => $avatar,
        ];
    }

    private function fetchXProfile(array $settings, string $code): array
    {
        $codeVerifier = (string) ($_SESSION['_oauth_pkce']['x'] ?? '');
        unset($_SESSION['_oauth_pkce']['x']);
        if ($codeVerifier === '') {
            throw new \RuntimeException('X login session expired. Please try again.');
        }

        $tokenHeaders = [
            'Authorization: Basic ' . base64_encode($settings['client_id'] . ':' . $settings['client_secret']),
        ];
        $token = $this->httpJson('https://api.twitter.com/2/oauth2/token', [
            'method' => 'POST',
            'headers' => $tokenHeaders,
            'form' => [
                'code' => $code,
                'grant_type' => 'authorization_code',
                'client_id' => $settings['client_id'],
                'redirect_uri' => $settings['redirect_uri'],
                'code_verifier' => $codeVerifier,
            ],
        ]);

        if (empty($token['access_token'])) {
            throw new \RuntimeException('X did not return an access token.');
        }

        $profile = $this->httpJson('https://api.twitter.com/2/users/me?' . http_build_query([
            'user.fields' => 'name,username,profile_image_url,verified',
        ]), [
            'headers' => ['Authorization: Bearer ' . $token['access_token']],
        ]);

        $data = $profile['data'] ?? [];
        $providerId = (string) ($data['id'] ?? '');
        if ($providerId === '') {
            throw new \RuntimeException('X did not return a user profile.');
        }

        $username = trim((string) ($data['username'] ?? $providerId));
        return [
            'provider' => 'x',
            'provider_id' => $providerId,
            'email' => 'x-' . strtolower(preg_replace('/[^a-z0-9_-]+/i', '', $providerId)) . '@social.medisphere.local',
            'name' => (string) ($data['name'] ?? $username),
            'avatar' => (string) ($data['profile_image_url'] ?? ''),
        ];
    }

    private function findOrCreateSocialPatient(array $profile): array
    {
        $email = Security::cleanEmail($profile['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('The social account did not provide a valid email address.');
        }

        $name = Security::cleanString($profile['name'] ?? '');
        if ($name === '') {
            $name = strstr($email, '@', true) ?: 'Patient';
        }

        $avatar = trim((string) ($profile['avatar'] ?? ''));
        if ($avatar !== '' && !filter_var($avatar, FILTER_VALIDATE_URL)) {
            $avatar = '';
        }

        $db = Database::connection();
        $userModel = new User();
        $patientModel = new Patient();

        $db->beginTransaction();
        try {
            $user = $userModel->findByEmail($email);
            if (!$user) {
                $userId = $userModel->create($email, bin2hex(random_bytes(32)), 'patient', 'active');
                $patientModel->create([
                    'user_id' => $userId,
                    'name' => $name,
                    'age' => null,
                    'gender' => '',
                    'blood_group' => '',
                    'address' => '',
                    'phone' => '',
                    'city' => '',
                    'country' => '',
                    'profile_image' => $avatar !== '' ? substr($avatar, 0, 255) : null,
                ]);
                $user = $userModel->findById($userId);
            } elseif ($user['status'] !== 'active') {
                throw new \RuntimeException($user['status'] === 'pending'
                    ? 'Your professional account is awaiting admin approval.'
                    : 'Your account is not active. Please contact support.');
            } elseif (($user['user_type'] ?? '') === 'patient' && !$patientModel->findByUserId((int) $user['id'])) {
                $patientModel->create([
                    'user_id' => (int) $user['id'],
                    'name' => $name,
                    'age' => null,
                    'gender' => '',
                    'blood_group' => '',
                    'address' => '',
                    'phone' => '',
                    'city' => '',
                    'country' => '',
                    'profile_image' => $avatar !== '' ? substr($avatar, 0, 255) : null,
                ]);
            }

            if (!$user) {
                throw new \RuntimeException('Could not create the social login user.');
            }

            $db->commit();
            return $user;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    private function providerLabel(string $provider): string
    {
        return $provider === 'x' ? 'X' : ucfirst($provider);
    }

    private function httpJson(string $url, array $options = []): array
    {
        $method = strtoupper((string) ($options['method'] ?? 'GET'));
        $headers = $options['headers'] ?? [];
        $body = null;
        if (isset($options['form']) && is_array($options['form'])) {
            $body = http_build_query($options['form']);
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_CUSTOMREQUEST => $method,
            ]);
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body ?? '');
            }
            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => $method,
                    'header' => implode("\r\n", $headers),
                    'content' => $body ?? '',
                    'timeout' => 25,
                    'ignore_errors' => true,
                ],
            ]);
            $response = @file_get_contents($url, false, $context);
            $status = 0;
            foreach (($http_response_header ?? []) as $header) {
                if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
                    $status = (int) $matches[1];
                    break;
                }
            }
            $error = $response === false ? 'HTTP request failed.' : '';
        }

        if ($response === false || $response === null || $response === '') {
            throw new \RuntimeException($error ?: 'Empty response from OAuth provider.');
        }

        $decoded = json_decode((string) $response, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('OAuth provider returned an invalid JSON response.');
        }

        if ($status >= 400 || isset($decoded['error'])) {
            $providerError = $decoded['error_description'] ?? ($decoded['error']['message'] ?? $decoded['error'] ?? 'OAuth request failed.');
            throw new \RuntimeException(is_string($providerError) ? $providerError : 'OAuth request failed.');
        }

        return $decoded;
    }

    public function logout(): void
    {
        Auth::logout();
        session_name(config('security.session_name'));
        session_start();
        flash('success', 'You have been signed out.', 'success');
        redirect('login');
    }

    private function safeReturnTo(string $url): string
    {
        return Auth::safeReturnUrl($url, route_url('dashboard'));
    }

    private function stageReturnTo(string $url): string
    {
        $url = trim($url);
        if ($url !== '') {
            return Auth::rememberIntendedUrl($url);
        }

        return Auth::intendedUrl(route_url('dashboard'));
    }

    private function redirectToUrl(string $url): void
    {
        header('Location: ' . $this->safeReturnTo($url));
        exit;
    }
}
