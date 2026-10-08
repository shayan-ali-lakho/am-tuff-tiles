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

    // ------------------------------------------------------------------
    // Admin > Users
    // ------------------------------------------------------------------

    private static function userWhere(string $q): array
    {
        if ($q === '') {
            return ['', []];
        }

        $like = '%' . addcslashes($q, '%_\\') . '%';

        return [' WHERE (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)', [$like, $like, $like]];
    }

    public static function userCount(string $q): int
    {
        [$where, $args] = self::userWhere($q);
        $st = Database::connection()->prepare('SELECT COUNT(*) FROM contacts' . $where);
        $st->execute($args);

        return (int) $st->fetchColumn();
    }

    public static function userSearch(string $q, int $limit, int $offset): array
    {
        [$where, $args] = self::userWhere($q);
        $st = Database::connection()->prepare(
            'SELECT ' . self::PUBLIC_COLUMNS . ' FROM contacts' . $where
            . " ORDER BY (portal_role = 'admin') DESC, created_at DESC, id DESC LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset)
        );
        $st->execute($args);

        return $st->fetchAll();
    }

    /**
     * Give or take away admin access. Never lets the shop end up with no admin, and an admin cannot remove their own access.
     *
     * @throws \RuntimeException with a message that is safe to show
     */
    public static function changeRole(int $actorId, int $targetId, string $role): void
    {
        if (!in_array($role, ['admin', 'customer'], true)) {
            throw new \RuntimeException('Unknown role.');
        }

        if ($role === 'customer' && $actorId === $targetId) {
            throw new \RuntimeException('You cannot remove your own admin access. Ask another admin to do it.');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            // Lock all admin rows and the target, so two changes at once cannot remove the last admin
            $pdo->query("SELECT id FROM contacts WHERE portal_role = 'admin' FOR UPDATE")->fetchAll();
            $st = $pdo->prepare('SELECT portal_role, is_active FROM contacts WHERE id = ? FOR UPDATE');
            $st->execute([$targetId]);
            $target = $st->fetch();

            if (!$target) {
                throw new \RuntimeException('User not found.');
            }

            if ($role === 'admin' && (int) $target['is_active'] !== 1) {
                throw new \RuntimeException('This account is turned off, so it cannot be made an admin.');
            }

            if ($role === 'customer' && $target['portal_role'] === 'admin') {
                $count = (int) $pdo->query("SELECT COUNT(*) FROM contacts WHERE portal_role = 'admin' AND is_active = 1")->fetchColumn();

                if ($count <= 1) {
                    throw new \RuntimeException('There must always be at least one admin.');
                }
            }

            $pdo->prepare('UPDATE contacts SET portal_role = ? WHERE id = ?')->execute([$role, $targetId]);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }
}
