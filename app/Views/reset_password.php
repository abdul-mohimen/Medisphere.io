<section class="auth-wrapper">
    <div class="row justify-content-center">
        <div class="col-lg-5" data-aos="zoom-in">
            <div class="glass-panel auth-card">
                <span class="eyebrow">Password recovery</span>
                <h2>Reset your password</h2>
                <form method="POST" action="<?= route_url('reset-password-submit') ?>" class="row g-3 mt-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
                    <div class="col-12"><label class="form-label">New Password</label><input type="password" name="password" class="form-control" required></div>
                    <div class="col-12"><label class="form-label">Confirm New Password</label><input type="password" name="confirm_password" class="form-control" required></div>
                    <div class="col-12 d-grid"><button class="btn btn-primary">Update Password</button></div>
                </form>
            </div>
        </div>
    </div>
</section>
