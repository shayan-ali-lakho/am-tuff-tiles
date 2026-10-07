<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Customers and staff (table: contacts). All queries use prepared statements.
 */
final class Contact
{
    private const PUBLIC_COLUMNS = 'id, full_name, email, phone, portal_role, address, city, is_active, last_login_at, created_at';

    /** Contact by id, without the password hash. */
    public static function find(int $id): ?array
    {
        $st = Database::connection()->prepare('SELECT ' . self::PUBLIC_COLUMNS . ' FROM contacts WHERE id = ?');
        $st->execute([$id]);

        return $st->fetch() ?: null;
    }

    /** Contact by email including password_hash. Only for checking a login. */
    public static function findForLogin(string $email): ?array
    {
        $st = Database::connection()->prepare('SELECT ' . self::PUBLIC_COLUMNS . ', password_hash FROM contacts WHERE email = ?');
        $st->execute([$email]);

        return $st->fetch() ?: null;
    }

    public static function emailExists(string $email): bool
    {
        $st = Database::connection()->prepare('SELECT 1 FROM contacts WHERE email = ?');
        $st->execute([$email]);

        return $st->fetchColumn() !== false;
    }

    /**
     * Create a contact and return its id. New accounts are always customers unless a role is
     * passed explicitly, which only the admin creation script does. Never pass user input as role.
     */
    public static function create(string $fullName, string $email, ?string $phone, string $passwordHash, string $role = 'customer'): int
    {
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO contacts (full_name, email, phone, password_hash, portal_role) VALUES (?, ?, ?, ?, ?)'
        )->execute([$fullName, $email, $phone !== '' ? $phone : null, $passwordHash, $role]);

        return (int) $pdo->lastInsertId();
    }

    public static function setRole(int $id, string $role): void
    {
        Database::connection()->prepare('UPDATE contacts SET portal_role = ? WHERE id = ?')->execute([$role, $id]);
    }

    public static function updatePasswordHash(int $id, string $hash): void
    {
        Database::connection()->prepare('UPDATE contacts SET password_hash = ? WHERE id = ?')->execute([$hash, $id]);
    }

    public static function touchLogin(int $id): void
    {
        Database::connection()->prepare('UPDATE contacts SET last_login_at = NOW() WHERE id = ?')->execute([$id]);
    }
}
