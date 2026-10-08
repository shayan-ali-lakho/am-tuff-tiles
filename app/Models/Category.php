<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Product categories (table: categories). The slug is created once and never changes,
 * so shop links stay stable even if the admin renames a category.
 */
final class Category
{
    /** All categories with their product counts, in display order. */
    public static function all(): array
    {
        return Database::connection()->query(
            'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
               FROM categories c
              ORDER BY c.sort_order, c.name'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $st = Database::connection()->prepare('SELECT * FROM categories WHERE id = ?');
        $st->execute([$id]);

        return $st->fetch() ?: null;
    }

    public static function nameTaken(string $name, int $exceptId = 0): bool
    {
        $st = Database::connection()->prepare('SELECT 1 FROM categories WHERE name = ? AND id <> ?');
        $st->execute([$name, $exceptId]);

        return $st->fetchColumn() !== false;
    }

    public static function productCount(int $id): int
    {
        $st = Database::connection()->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $st->execute([$id]);

        return (int) $st->fetchColumn();
    }

    public static function create(string $name, ?string $description, int $sortOrder, bool $active): int
    {
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO categories (name, slug, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?)'
        )->execute([$name, self::uniqueSlug($name), $description, $sortOrder, $active ? 1 : 0]);

        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, string $name, ?string $description, int $sortOrder, bool $active): void
    {
        Database::connection()->prepare(
            'UPDATE categories SET name = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?'
        )->execute([$name, $description, $sortOrder, $active ? 1 : 0, $id]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    }

    private static function uniqueSlug(string $name): string
    {
        $base = mb_substr(slugify($name), 0, 100);
        $base = $base !== '' ? $base : 'category';

        $st = Database::connection()->prepare('SELECT 1 FROM categories WHERE slug = ?');
        $slug = $base;

        for ($i = 2; ; $i++) {
            $st->execute([$slug]);

            if ($st->fetchColumn() === false) {
                return $slug;
            }

            $slug = $base . '-' . $i;
        }
    }
}
