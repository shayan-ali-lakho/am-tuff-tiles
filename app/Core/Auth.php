<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Contact;
use Throwable;

/**
 * Who is logged in. The session stores only the contact id; the contact (including the
 * portal_role that decides admin access) is re-read from the database on every request,
 * so deactivating a user or removing their admin role takes effect immediately.
 */
final class Auth
{
    private static bool $loaded = false;
    private static ?array $user = null;

    /** The logged-in contact (without password hash), or null for guests. */
    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }

        self::$loaded = true;

        $id = $_SESSION['contact_id'] ?? null;

        if (!is_int($id)) {
            return null;
        }

        try {
            $contact = Contact::find($id);
        } catch (Throwable $e) {
            // Database trouble: treat as logged out rather than breaking every page.
            error_log('Auth::user() could not load contact: ' . $e->getMessage());

            return null;
        }

        if ($contact === null || (int) $contact['is_active'] !== 1) {
            unset($_SESSION['contact_id']);

            return null;
        }

        return self::$user = $contact;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['portal_role'] ?? '') === 'admin';
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /** Log a contact in. A fresh session id is issued to prevent session fixation. */
    public static function login(array $contact): void
    {
        session_regenerate_id(true);
        unset($_SESSION['_csrf']);

        $_SESSION['contact_id'] = (int) $contact['id'];

        self::$loaded = false;
        self::$user = null;

        Contact::touchLogin((int) $contact['id']);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);

        self::$loaded = true;
        self::$user = null;
    }

    /**
     * Gatekeeper for everything under /admin (called by the router).
     * Guests are sent to the login page; logged-in non-admins get a 403.
     */
    public static function requireAdmin(): void
    {
        header('Cache-Control: no-store');

        if (!self::check()) {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                flash('error', 'Please log in to continue.');
                redirect('/login?next=' . rawurlencode(current_path()));
            }

            self::deny();
        }

        if (!self::isAdmin()) {
            self::deny();
        }
    }

    private static function deny(): never
    {
        http_response_code(403);
        echo view('errors/403', ['title' => 'Access denied']);
        exit;
    }
}
