<?php

declare(strict_types=1);

/**
 * Applies any new SQL files from database/migrations/ in name order and remembers which
 * ones have run (table schema_migrations). Safe to run again at any time.
 *
 * Usage (from the site folder):  php bin/migrate.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

/**
 * Split a migration file into statements. Statements must end with ";" at the end of a line.
 *
 * @return list<string>
 */
function split_sql(string $sql): array
{
    $lines = [];

    foreach (preg_split('/\R/', $sql) ?: [] as $line) {
        if (str_starts_with(ltrim($line), '--')) {
            continue; // comment line
        }
        $lines[] = $line;
    }

    $statements = [];

    foreach (explode(";\n", implode("\n", $lines) . "\n") as $statement) {
        $statement = trim($statement);

        if ($statement !== '') {
            $statements[] = $statement;
        }
    }

    return $statements;
}

$pdo = Database::connection();

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        filename   VARCHAR(190) NOT NULL,
        applied_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (filename)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$applied = $pdo->query('SELECT filename FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);

$files = glob(BASE_PATH . '/database/migrations/*.sql') ?: [];
sort($files, SORT_STRING);

$count = 0;

foreach ($files as $file) {
    $name = basename($file);

    if (in_array($name, $applied, true)) {
        echo "skip   {$name}\n";
        continue;
    }

    echo "apply  {$name} ... ";

    foreach (split_sql((string) file_get_contents($file)) as $statement) {
        $pdo->exec($statement);
    }

    $pdo->prepare('INSERT INTO schema_migrations (filename) VALUES (?)')->execute([$name]);

    echo "ok\n";
    $count++;
}

echo $count === 0 ? "Nothing to migrate.\n" : "Applied {$count} migration(s).\n";
