<?php
$returnTo = $returnTo ?? route_url('dashboard');
$registerReturnTo = ['return_to' => $returnTo];
$socialReturnTo = ['return_to' => $returnTo];
?>
<section class="auth-premium-shell auth-login-shell">
    <aside class="auth-visual-panel" data-aos="fade-right">
        <img class="auth-visual-image" src="<?= e(provider_photo_url('care', 'medisphere-login-care-suite')) ?>" alt="Clinical team coordinating digital care" loading="eager">
        <div class="auth-visual-shade"></div>
        <div class="auth-brand-mark">
            <span><i class="fa-solid fa-heart-pulse"></i></span>
            <strong>MediSphere</strong>
        </div>
        <div class="auth-visual-content">
            <span class="auth-kicker">Secure care access</span>
            <h1>Welcome back to your care workspace.</h1>
            <p>Open appointments, reports, consultations, messaging, and care tasks from one protected account.</p>
            <div class="auth-visual-stats" aria-label="Platform highlights">
                <div><strong>24/7</strong><span>Care access</span></div>
                <div><strong>AI</strong><span>Triage tools</span></div>
                <div><strong>360</strong><span>Patient record view</span></div>
            </div>
            <div class="auth-approval-strip">
                <i class="fa-solid fa-shield-check"></i>
                <span>Doctor and hospital access opens after admin verification.</span>
            </div>
        </div>
    </aside>

    <section class="auth-form-panel" data-aos="fade-left">
        <div class="auth-form-card">
            <div class="auth-form-heading">
                <span class="eyebrow">Login</span>
                <h2>Sign in to MediSphere</h2>
                <p>Use your account email or continue with a trusted provider.</p>
            </div>

            <div class="auth-social-grid" aria-label="Social login options">
                <a href="<?= route_url('auth/google', $socialReturnTo) ?>" class="auth-social-button google">
                    <i class="fa-brands fa-google"></i>
                    <span>Google</span>
                </a>
                <a href="<?= route_url('auth/facebook', $socialReturnTo) ?>" class="auth-social-button facebook">
                    <i class="fa-brands fa-facebook-f"></i>
                    <span>Facebook</span>
                </a>
                <a href="<?= route_url('auth/x', $socialReturnTo) ?>" class="auth-social-button x">
                    <i class="fa-brands fa-x-twitter"></i>
                    <span>X</span>
                </a>
            </div>

            <div class="divider auth-divider"><span><?= e(__('auth.or_continue')) ?></span></div>

            <form method="POST" action="<?= route_url('login') ?>" class="auth-premium-form">
                <?= csrf_field() ?>
                <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                <div class="auth-field">
                    <label for="loginEmail">Email address</label>
                    <div class="auth-input-wrap">
                        <i class="fa-regular fa-envelope"></i>
                        <input id="loginEmail" type="email" name="email" class="form-control" value="<?= old('email') ?>" autocomplete="email" required>
                    </div>
                </div>
                <div class="auth-field">
                    <label for="loginPassword">Password</label>
                    <div class="auth-input-wrap">
                        <i class="fa-solid fa-lock"></i>
                        <input id="loginPassword" type="password" name="password" class="form-control password-field" autocomplete="current-password" required>
                        <button class="password-toggle auth-password-toggle" type="button" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
                    </div>
                </div>
                <div class="auth-form-row">
                    <label class="auth-check">
                        <input type="checkbox" name="remember" value="1">
                        <span>Keep me signed in</span>
                    </label>
                    <button type="button" class="auth-link-button" data-bs-toggle="modal" data-bs-target="#forgotModal"><?= e(__('auth.forgot_password')) ?></button>
                </div>
                <button class="btn btn-primary auth-submit-button">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Sign In</span>
                </button>
            </form>

            <div class="auth-switch-panel auth-premium-switch">
                <div>
                    <strong>New to MediSphere?</strong>
                    <p>Create patient access instantly, or submit a professional profile for review.</p>
                </div>
                <a href="<?= route_url('register', $registerReturnTo) ?>" class="btn btn-outline-primary btn-sm">Create Account</a>
            </div>
        </div>
    </section>
</section>

<div class="modal fade" id="forgotModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= e(__('auth.forgot_password')) ?></h5>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= route_url('forgot-password') ?>">
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <label class="form-label">Enter your account email</label>
                    <input type="email" name="email" class="form-control" autocomplete="email" required>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary"><?= e(__('auth.send_reset')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
