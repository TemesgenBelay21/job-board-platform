<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\Validator;
use App\Helpers\Mailer;
use App\Models\CompanyProfile;
use App\Models\PasswordReset;
use App\Models\SeekerProfile;
use App\Models\User;

/**
 * Registration, login, logout and password recovery.
 */
class AuthController extends Controller
{
    /** Where a user lands after signing in, by role. */
    const HOME_BY_ROLE = [
        'seeker' => '/seeker/applications',
        'employer' => '/employer/dashboard',
        'admin' => '/admin/dashboard',
    ];

    /**
     * Show the registration form.
     *
     * ?role=employer preselects the employer option, which is how the "I am
     * hiring" buttons on the home page deep link into the form.
     */
    public function showRegister()
    {
        if ($this->isLoggedIn()) {
            $this->redirect($this->homePathForRole($this->user()['role']));
        }

        $role = query_param('role');

        if (!in_array($role, User::REGISTRABLE_ROLES, true)) {
            $role = 'seeker';
        }

        echo $this->view('auth/register', [
            'title' => 'Create your account',
            'selectedRole' => $role,
        ], 'auth');
    }

    /**
     * Handle registration.
     *
     * The account and its role profile are created in one transaction: a user
     * row without a profile would break the profile pages later on.
     */
    public function register()
    {
        $this->requireMethod('POST');
        $this->verifyCsrf();

        $userModel = new User();
        $seekerModel = new SeekerProfile();
        $companyModel = new CompanyProfile();

        $validator = new Validator($_POST);
        $validator->validate([
            'name' => 'required|min:2|max:120',
            'email' => 'required|email|max:190',
            'password' => 'required|password_strength|confirmed',
            'role' => 'required|in:' . implode(',', User::REGISTRABLE_ROLES),
            'company_name' => 'nullable|max:150',
        ]);

        $role = $validator->value('role');

        // The admin role is never registrable, however it is posted.
        if (!in_array($role, User::REGISTRABLE_ROLES, true)) {
            $role = 'seeker';
        }

        $email = strtolower(trim((string) $validator->value('email')));

        if ($email !== '' && $userModel->emailExists($email)) {
            $validator->validate([]);
            flash_error('That email address is already registered. Try logging in instead.');
            $this->backWithErrors([], url('auth/register'));
        }

        $companyName = trim((string) $validator->value('company_name'));

        if ($role === 'employer' && $companyName === '') {
            $this->backWithErrors(['Enter your company name.'], url('auth/register'));
        }

        if ($validator->fails()) {
            $this->backWithErrors($validator->errorMessages(), url('auth/register'));
        }

        $userId = Database::transaction(function () use (
            $userModel,
            $seekerModel,
            $companyModel,
            $validator,
            $role,
            $companyName
        ) {
            $userId = $userModel->register(
                $validator->value('name'),
                $validator->value('email'),
                $_POST['password'],
                $role
            );

            if ($role === 'seeker') {
                $seekerModel->createForUser($userId);
            } else {
                $companyModel->createForUser($userId, $companyName);
            }

            return $userId;
        });

        $user = $userModel->find($userId);
        $this->authenticate($user);

        clear_old_input();
        flash_success('Welcome to ' . app_name() . ', ' . $user['name'] . '. Your account is ready.');

        $this->redirect($this->homePathForRole($role));
    }

    /** Show the login form. */
    public function showLogin()
    {
        if ($this->isLoggedIn()) {
            $this->redirect($this->homePathForRole($this->user()['role']));
        }

        echo $this->view('auth/login', [
            'title' => 'Log in',
        ], 'auth');
    }

    /**
     * Handle login.
     *
     * Every failure shows the same message, so the form never reveals whether
     * an email is registered.
     */
    public function login()
    {
        $this->requireMethod('POST');
        $this->verifyCsrf();

        $userModel = new User();
        $genericError = 'Incorrect email or password.';

        $validator = new Validator($_POST);
        $validator->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors([$genericError], url('auth/login'));
        }

        $user = $userModel->attempt($validator->value('email'), $_POST['password']);

        if ($user === null) {
            $this->backWithErrors([$genericError], url('auth/login'));
        }

        // A deactivated account gets its own message, otherwise the user would
        // never learn why a correct password is refused.
        if ($user['status'] !== 'active') {
            flash_error('This account has been deactivated. Contact an administrator for help.');
            $this->redirect('auth/login');
        }

        $this->authenticate($user);

        // Send them where they were originally heading, if we recorded it.
        $intended = Session::get('intended_url');
        Session::forget('intended_url');

        clear_old_input();
        flash_success('Welcome back, ' . $user['name'] . '.');

        if (is_string($intended) && $intended !== '' && $this->isOwnUrl($intended)) {
            header('Location: ' . $intended, true, 302);
            exit;
        }

        $this->redirect($this->homePathForRole($user['role']));
    }

    /** Sign out and destroy the session. */
    public function logout()
    {
        $this->requireMethod('POST');
        $this->verifyCsrf();

        $this->signOut();

        $this->redirect('/');
    }

    /** Show the "forgot your password" form. */
    public function showForgotPassword()
    {
        echo $this->view('auth/forgot-password', [
            'title' => 'Reset your password',
        ], 'auth');
    }

    /**
     * Issue a reset link for an email address.
     *
     * The same confirmation is shown whether or not the address is registered,
     * so this page cannot be used to discover which emails have accounts.
     */
    public function sendResetLink()
    {
        $this->requireMethod('POST');
        $this->verifyCsrf();

        $userModel = new User();
        $tokens = new PasswordReset();

        $validator = new Validator($_POST);
        $validator->validate(['email' => 'required|email']);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errorMessages(), url('auth/forgot-password'));
        }

        $email = strtolower(trim((string) $validator->value('email')));
        $user = $userModel->findByEmail($email);

        if ($user !== null) {
            $token = $tokens->createForUser($user['id']);
            $link = url('auth/reset-password', ['token' => $token]);

            $sent = Mailer::sendPasswordReset($user['email'], $user['name'], $link);

            // Locally there is usually no mail server, so show the link instead
            // of leaving the developer guessing whether it was delivered.
            if (!$sent && Mailer::isLocal()) {
                flash_info('Mail is not configured on this machine, so here is the reset link: ' . $link);
            }
        }

        clear_old_input();

        // The same wording either way: this page must not reveal which email
        // addresses have an account.
        flash_success('If that email address has an account, a reset link is on its way.');

        $this->redirect('auth/login');
    }

    /** Show the new-password form for a token from a reset email. */
    public function showResetPassword()
    {
        $token = isset($_GET['token']) ? (string) $_GET['token'] : '';

        if (!PasswordReset::looksLikeToken($token)) {
            flash_error('That password reset link is not valid.');

            $this->redirect('auth/forgot-password');
        }

        $reset = new PasswordReset();

        if ($reset->findValidByToken($token) === null) {
            flash_error('That password reset link has expired or has already been used.');

            $this->redirect('auth/forgot-password');
        }

        echo $this->view('auth/reset-password', [
            'title' => 'Choose a new password',
            'token' => $token,
        ], 'auth');
    }

    /**
     * Store the new password.
     *
     * The token is spent first: if the update then failed, the user requests a
     * new link rather than reusing one, which avoids a partial application.
     */
    public function resetPassword()
    {
        $this->requireMethod('POST');
        $this->verifyCsrf();

        $token = isset($_POST['token']) ? (string) $_POST['token'] : '';

        $validator = new Validator($_POST);
        $validator->validate([
            'password' => 'required|password_strength|confirmed',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors(
                $validator->errorMessages(),
                url('auth/reset-password', ['token' => $token])
            );
        }

        $tokens = new PasswordReset();
        $reset = PasswordReset::looksLikeToken($token) ? $tokens->findValidByToken($token) : null;

        if ($reset === null) {
            flash_error('That password reset link has expired or has already been used.');

            $this->redirect('auth/forgot-password');
        }

        if (!$tokens->consume($reset['id'])) {
            // Two requests raced: the first one to consume the token wins.
            flash_error('That password reset link has already been used.');

            $this->redirect('auth/forgot-password');
        }

        (new User())->updatePassword($reset['user_id'], $_POST['password']);

        clear_old_input();
        flash_success('Your password has been changed. You can sign in now.');

        $this->redirect('auth/login');
    }

    private function homePathForRole($role)
    {
        return isset(self::HOME_BY_ROLE[$role]) ? self::HOME_BY_ROLE[$role] : '/';
    }

    /** Only follow a stored redirect that points back at this installation. */
    private function isOwnUrl($url)
    {
        $base = \App\Core\App::baseUrl();

        return strpos($url, $base) === 0;
    }
}
