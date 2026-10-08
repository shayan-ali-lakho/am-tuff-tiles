<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Contact;
use App\Core\Mailer;
use App\Models\LoginAttempt;
use App\Models\PasswordReset;
use PDOException;

/**
 * Register, log in and log out. POST requests are CSRF-checked by the router.
 * Forms use post-redirect-get: errors and old input are flashed, then the form page is shown again.
 */
final class AuthController
{
    /** A real bcrypt hash of a throwaway string, checked when the email is unknown so that
     *  "unknown email" and "wrong password" take about the same time. */
    private const DUMMY_HASH = '$2y$10$oxsz6fhorWsz3ULA51Oz/uE.AY8xLiZrMdpCwz1sGh5Ik.AZAVnsK';

    public function showRegister(): void
    {
        if (Auth::check()) {
            redirect('/');
        }

        echo view('auth/register', [
            'title'  => 'Create account',
            'errors' => flash_get('errors', []),
            'old'    => flash_get('old', []),
            'next'   => safe_next($_GET['next'] ?? null),
        ]);
    }

    public function register(): void
    {
        $name     = trim((string) ($_POST['full_name'] ?? ''));
        $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phone    = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['password_confirm'] ?? '');
        $next     = safe_next($_POST['next'] ?? null);

        $errors = [];

        $nameLength = mb_strlen($name);
        if ($nameLength < 2 || $nameLength > 120) {
            $errors['full_name'] = 'Please enter your full name (2 to 120 characters).';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors['email'] = 'Please enter a valid email address.';
        } elseif (Contact::emailExists($email)) {
            $errors['email'] = 'An account with this email already exists. Try logging in instead.';
        }

        if ($phone !== '' && preg_match('/^[0-9+()\-\s]{7,20}$/', $phone) !== 1) {
            $errors['phone'] = 'Please enter a valid phone number, or leave it empty.';
        }

        if (strlen($password) < 8) {
            $errors['password'] = 'Your password must be at least 8 characters.';
        } elseif (strlen($password) > 72) {
            $errors['password'] = 'Your password can be at most 72 characters.';
        } elseif ($password !== $confirm) {
            $errors['password_confirm'] = 'The two passwords do not match.';
        }

        if ($errors !== []) {
            $this->backToRegister($errors, ['full_name' => $name, 'email' => $email, 'phone' => $phone], $next);
        }

        try {
            $id = Contact::create($name, $email, $phone, Auth::hash($password));
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') { // someone registered this email a moment ago
                $this->backToRegister(
                    ['email' => 'An account with this email already exists. Try logging in instead.'],
                    ['full_name' => $name, 'email' => $email, 'phone' => $phone],
                    $next
                );
            }

            throw $e;
        }

        Auth::login(Contact::find($id) ?? []);
        flash('success', 'Welcome, ' . $name . '! Your account has been created.');
        redirect($next);
    }

    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect(Auth::isAdmin() ? '/admin' : '/');
        }

        echo view('auth/login', [
            'title'  => 'Log in',
            'errors' => flash_get('errors', []),
            'old'    => flash_get('old', []),
            'next'   => safe_next($_GET['next'] ?? null),
        ]);
    }

    public function login(): void
    {
        $email     = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password  = (string) ($_POST['password'] ?? '');
        $requested = safe_next($_POST['next'] ?? null, '');
        $ip        = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        if (LoginAttempt::isLocked($email, $ip)) {
            http_response_code(429);
            echo view('auth/login', [
                'title'  => 'Log in',
                'errors' => ['form' => 'Too many failed attempts. Please wait ' . LoginAttempt::WINDOW_MINUTES . ' minutes and try again.'],
                'old'    => ['email' => $email],
                'next'   => $requested !== '' ? $requested : '/',
            ]);

            return;
        }

        $contact = $email !== '' ? Contact::findForLogin($email) : null;

        // Always run a password check, even for unknown emails, so timing does not reveal which emails exist.
        $passwordOk = password_verify($password, $contact['password_hash'] ?? self::DUMMY_HASH);

        if ($contact === null || !$passwordOk || (int) $contact['is_active'] !== 1) {
            LoginAttempt::recordFailure($email, $ip);
            flash('errors', ['form' => 'Incorrect email or password.']);
            flash('old', ['email' => $email]);
            redirect('/login' . ($requested !== '' ? '?next=' . rawurlencode($requested) : ''));
        }

        LoginAttempt::clear($email, $ip);

        if (password_needs_rehash($contact['password_hash'], PASSWORD_DEFAULT)) {
            Contact::updatePasswordHash((int) $contact['id'], Auth::hash($password));
        }

        Auth::login($contact);
        flash('success', 'Welcome back, ' . $contact['full_name'] . '.');

        $default = $contact['portal_role'] === 'admin' ? '/admin' : '/';
        redirect($requested !== '' ? $requested : $default);
    }

    public function logout(): void
    {
        Auth::logout();
        flash('success', 'You have been logged out.');
        redirect('/');
    }

    public function showForgot(): void
    {
        echo view('auth/forgot', ['title' => 'Forgot password', 'sent' => flash_get('sent', false), 'old' => flash_get('old', [])]);
    }

    public function forgot(): void
    {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $contact = Contact::findForLogin($email);

            if ($contact !== null && (int) $contact['is_active'] === 1) {
                $token = PasswordReset::create((int) $contact['id']);

                if ($token !== null) {
                    Mailer::send(
                        $email,
                        'Reset your ' . config('app.name') . ' password',
                        "Hello " . $contact['full_name'] . ",\n\nSomeone asked to reset the password for your account. To choose a new password open this link within "
                        . PasswordReset::LIFETIME_MINUTES . " minutes:\n\n" . Mailer::baseUrl() . '/reset-password/' . $token
                        . "\n\nIf you did not ask for this, you can ignore this email. Your password stays the same.\n\n" . config('app.name') . "\n"
                    );
                }
            }
        }

        // The same answer for every address, so nobody can find out which emails have accounts.
        flash('sent', true);
        redirect('/forgot-password');
    }

    public function showReset(string $token): void
    {
        $valid = PasswordReset::find($token) !== null;

        if (!$valid) {
            http_response_code(410);
        }

        echo view('auth/reset', ['title' => 'Choose a new password', 'valid' => $valid, 'token' => $token, 'errors' => flash_get('errors', [])]);
    }

    public function reset(string $token): void
    {
        $row = PasswordReset::find($token);

        if ($row === null) {
            http_response_code(410);
            echo view('auth/reset', ['title' => 'Choose a new password', 'valid' => false, 'token' => $token, 'errors' => []]);

            return;
        }

        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['password_confirm'] ?? '');
        $errors   = [];

        if (strlen($password) < 8) {
            $errors['password'] = 'Your password must be at least 8 characters.';
        } elseif (strlen($password) > 72) {
            $errors['password'] = 'Your password can be at most 72 characters.';
        } elseif ($password !== $confirm) {
            $errors['password_confirm'] = 'The two passwords do not match.';
        }

        if ($errors !== []) {
            flash('errors', $errors);
            redirect('/reset-password/' . $token);
        }

        PasswordReset::complete((int) $row['contact_id'], Auth::hash($password));

        $contact = Contact::find((int) $row['contact_id']);
        if ($contact !== null) {
            LoginAttempt::clear(strtolower((string) $contact['email']), (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
        }

        flash('success', 'Your password was changed. Please log in with your new password.');
        redirect('/login');
    }

    private function backToRegister(array $errors, array $old, string $next): never
    {
        flash('errors', $errors);
        flash('old', $old);
        redirect('/register' . ($next !== '/' ? '?next=' . rawurlencode($next) : ''));
    }
}
