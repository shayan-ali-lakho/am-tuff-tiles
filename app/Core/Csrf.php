<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSRF protection. Every form posts a hidden "_token" field (use csrf_field() in views).
 * The router rejects any POST whose token does not match the one stored in the session.
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    public static function verify(mixed $token): bool
    {
        return is_string($token) && $token !== '' && hash_equals(self::token(), $token);
    }
}
