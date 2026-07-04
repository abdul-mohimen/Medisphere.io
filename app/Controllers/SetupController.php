<?php
namespace App\Controllers;

use App\Core\CSRF;
use App\Core\View;
use App\Models\User;

class SetupController
{
    public function show(): void
    {
        $admins = (new User())->all('admin');
        View::render('setup_admin', ['title' => 'Initial Admin Setup', 'adminExists' => !empty($admins)]);
    }

    public function create(): void
    {
        if (!config('app.debug')) {
            flash('error', 'Setup is disabled.', 'danger');
            redirect('home');
        }

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('setup-admin');
        }

        $admins = (new User())->all('admin');
        if (!empty($admins)) {
            flash('error', 'An admin account already exists.', 'danger');
            redirect('auth');
        }

        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = (string) ($_POST['password'] ?? '');
        if (!$email || strlen($password) < 8) {
            flash('error', 'Provide a valid email and password with at least 8 characters.', 'danger');
            redirect('setup-admin');
        }

        (new User())->create($email, $password, 'admin', 'active');
        flash('success', 'Admin account created. You can now sign in.', 'success');
        redirect('auth');
    }
}
