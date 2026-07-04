<section class="auth-wrapper">
    <div class="row justify-content-center">
        <div class="col-lg-5" data-aos="zoom-in">
            <div class="glass-panel auth-card">
                <span class="eyebrow">First-time setup</span>
                <h2>Create Initial Admin</h2>
                <?php if ($adminExists): ?>
                    <div class="alert alert-info">An admin account already exists. Please sign in instead.</div>
                    <a href="<?= route_url('login') ?>" class="btn btn-primary">Go to Login</a>
                <?php else: ?>
                    <form method="POST" action="<?= route_url('setup-admin/create') ?>" class="row g-3 mt-2">
                        <?= csrf_field() ?>
                        <div class="col-12"><label class="form-label">Admin Email</label><input type="email" name="email" class="form-control" required></div>
                        <div class="col-12"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                        <div class="col-12 d-grid"><button class="btn btn-primary">Create Admin Account</button></div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
