<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Failed-login throttling (table: login_attempts).
 *
 * A login is blocked for WINDOW_MINUTES when, within that window, there were
 *  - MAX_PER_PAIR failed attempts for the same email from the same IP address, or
 *  - MAX_PER_IP failed attempts from the same IP address (any email), or
 *  - MAX_PER_EMAIL failed attempts for the same email from anywhere (spread-out guessing).
 * Using the email+IP pair as the main rule stops a stranger from locking you out of your own account.
 */
final class LoginAttempt
{
    public const WINDOW_MINUTES = 15;
    private const MAX_PER_PAIR  = 5;
    private const MAX_PER_IP    = 20;
    private const MAX_PER_EMAIL = 40;

    public static function isLocked(string $email, string $ip): bool
    {
        $st = Database::connection()->prepare(
            'SELECT SUM(email = ? AND ip_address = ?) AS pair_count,
                    SUM(ip_address = ?)               AS ip_count,
                    SUM(email = ?)                    AS email_count
               FROM login_attempts
              WHERE attempted_at > (NOW() - INTERVAL ' . (int) self::WINDOW_MINUTES . ' MINUTE)
                AND (email = ? OR ip_address = ?)'
        );
        $st->execute([$email, $ip, $ip, $email, $email, $ip]);
        $row = $st->fetch() ?: [];

        return (int) ($row['pair_count'] ?? 0) >= self::MAX_PER_PAIR
            || (int) ($row['ip_count'] ?? 0) >= self::MAX_PER_IP
            || (int) ($row['email_count'] ?? 0) >= self::MAX_PER_EMAIL;
    }

    public static function recordFailure(string $email, string $ip): void
    {
        $pdo = Database::connection();
        $pdo->prepare('INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)')
            ->execute([mb_substr($email, 0, 190), mb_substr($ip, 0, 45)]);

        // Now and then, remove rows older than a day so the table stays small.
        if (random_int(1, 50) === 1) {
            $pdo->exec('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
        }
    }

    /** After a successful login, forget earlier failures for that email and IP. */
    public static function clear(string $email, string $ip): void
    {
        Database::connection()->prepare('DELETE FROM login_attempts WHERE email = ? AND ip_address = ?')
            ->execute([$email, $ip]);
    }
}
