<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Products (table: products). Prices are whole numbers in paisa.
 * Use setActive(false) to hide a product; delete() is permanent (old orders keep their own copy of the name and price).
 */
final class Product
{
    /** Columns an admin may set through create() and update(). */
    private const FIELDS = [
        'category_id', 'name', 'short_description', 'description',
        'price_paisa', 'size', 'material', 'stock_qty', 'is_active', 'is_featured',
    ];

    /**
     * @param array{q?: string, category?: int, status?: string} $filters status: '', 'active', 'hidden' or 'out'
     */
    public static function count(array $filters): int
    {
        [$where, $args] = self::where($filters);

        $st = Database::connection()->prepare(
            'SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id WHERE ' . $where
        );
        $st->execute($args);

        return (int) $st->fetchColumn();
    }

    /** One page of products with category name and main photo path. */
    public static function search(array $filters, int $limit, int $offset): array
    {
        [$where, $args] = self::where($filters);

        $st = Database::connection()->prepare(
            'SELECT p.id, p.name, p.slug, p.price_paisa, p.stock_qty, p.is_active, p.is_featured,
                    c.name AS category_name,
                    (SELECT i.file_path FROM product_images i
                      WHERE i.product_id = p.id
                      ORDER BY i.is_primary DESC, i.sort_order, i.id LIMIT 1) AS image_path
               FROM products p
               JOIN categories c ON c.id = p.category_id
              WHERE ' . $where . '
              ORDER BY p.created_at DESC, p.id DESC
              LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset)
        );
        $st->execute($args);

        return $st->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $st = Database::connection()->prepare(
            'SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id = ?'
        );
        $st->execute([$id]);

        return $st->fetch() ?: null;
    }

    /** @param array<string, mixed> $data keys from FIELDS */
    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $columns = implode(', ', self::FIELDS);
        $marks = implode(', ', array_fill(0, count(self::FIELDS), '?'));

        $pdo->prepare("INSERT INTO products (slug, {$columns}) VALUES (?, {$marks})")
            ->execute(array_merge([self::uniqueSlug((string) $data['name'])], self::values($data)));

        return (int) $pdo->lastInsertId();
    }

    /** The slug is not changed, so product links keep working after a rename. */
    public static function update(int $id, array $data): void
    {
        $set = implode(', ', array_map(static fn (string $f): string => $f . ' = ?', self::FIELDS));

        Database::connection()->prepare("UPDATE products SET {$set} WHERE id = ?")
            ->execute(array_merge(self::values($data), [$id]));
    }

    public static function setActive(int $id, bool $active): void
    {
        Database::connection()->prepare('UPDATE products SET is_active = ? WHERE id = ?')
            ->execute([$active ? 1 : 0, $id]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    }

    /** @return list<mixed> */
    private static function values(array $data): array
    {
        return array_map(static fn (string $f): mixed => $data[$f], self::FIELDS);
    }

    /** @return array{0: string, 1: list<mixed>} */
    private static function where(array $filters): array
    {
        $where = ['1 = 1'];
        $args = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = 'p.name LIKE ?';
            $args[] = '%' . addcslashes($q, '%_\\') . '%';
        }

        $category = (int) ($filters['category'] ?? 0);
        if ($category > 0) {
            $where[] = 'p.category_id = ?';
            $args[] = $category;
        }

        switch ($filters['status'] ?? '') {
            case 'active':
                $where[] = 'p.is_active = 1';
                break;
            case 'hidden':
                $where[] = 'p.is_active = 0';
                break;
            case 'out':
                $where[] = 'p.stock_qty = 0';
                break;
        }

        return [implode(' AND ', $where), $args];
    }

    private static function uniqueSlug(string $name): string
    {
        $base = mb_substr(slugify($name), 0, 150);
        $base = $base !== '' ? $base : 'product';

        $st = Database::connection()->prepare('SELECT 1 FROM products WHERE slug = ?');
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
