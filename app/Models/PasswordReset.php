<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Password reset links. Only a SHA-256 hash of the token is stored, so a database leak does not reveal working links.
 * A link works once and for one hour.
 */
final class PasswordReset
{
    public const LIFETIME_MINUTES = 60;
    public const MAX_PER_HOUR     = 3;

    /** Make a new link token for a contact, or null when they asked too often in the last hour. */
    public static function create(int $contactId): ?string
    {
        $pdo = Database::connection();

        $st = $pdo->prepare('SELECT COUNT(*) FROM password_resets WHERE contact_id = ? AND created_at > (NOW() - INTERVAL 1 HOUR)');
        $st->execute([$contactId]);

        if ((int) $st->fetchColumn() >= self::MAX_PER_HOUR) {
            return null;
        }

        $token = bin2hex(random_bytes(32));

        $pdo->prepare(
            'INSERT INTO password_resets (contact_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL ' . self::LIFETIME_MINUTES . ' MINUTE)'
        )->execute([$contactId, hash('sha256', $token)]);

        // Tidy up: remove old rows
        $pdo->exec('DELETE FROM password_resets WHERE created_at < (NOW() - INTERVAL 2 DAY)');

        return $token;
    }

    /** The reset row for a still-valid token, or null. */
    public static function find(string $token): ?array
    {
        if (preg_match('/^[0-9a-f]{64}$/', $token) !== 1) {
            return null;
        }

        $st = Database::connection()->prepare(
            'SELECT r.id, r.contact_id FROM password_resets r JOIN contacts c ON c.id = r.contact_id
              WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > NOW() AND c.is_active = 1'
        );
        $st->execute([hash('sha256', $token)]);

        return $st->fetch() ?: null;
    }

    /** Set the new password and burn every open link for that contact, in one step. */
    public static function complete(int $contactId, string $passwordHash): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            Contact::updatePasswordHash($contactId, $passwordHash);
            $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE contact_id = ? AND used_at IS NULL')->execute([$contactId]);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }
}
