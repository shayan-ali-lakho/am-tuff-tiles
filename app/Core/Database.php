<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/**
 * One shared PDO connection, created on first use.
 * Always use prepared statements: $pdo->prepare('... WHERE id = ?')->execute([$id]).
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $db = (array) config('db');

        if ($db['name'] === '' || $db['user'] === '') {
            throw new RuntimeException('Database is not configured. Set DB_NAME, DB_USER and DB_PASS in .env.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['name'],
            $db['charset']
        );

        $pdo = new PDO($dsn, (string) $db['user'], (string) $db['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // Make the database clock follow the site's timezone (default Asia/Karachi), so
        // timestamps and monthly report boundaries match what the shop owner sees.
        $pdo->exec('SET time_zone = ' . $pdo->quote(date('P')));

        return self::$pdo = $pdo;
    }
}
