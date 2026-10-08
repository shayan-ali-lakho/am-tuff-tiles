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

    // ------------------------------------------------------------------
    // Public shop. Only products that are visible AND whose category is turned on are ever returned.
    // ------------------------------------------------------------------

    /** Sort choices offered in the shop: key => ORDER BY clause (never built from user text). */
    public const SORTS = [
        'newest'     => 'p.created_at DESC, p.id DESC',
        'price_asc'  => 'p.price_paisa ASC, p.id DESC',
        'price_desc' => 'p.price_paisa DESC, p.id DESC',
        'name'       => 'p.name ASC, p.id DESC',
    ];

    private const SHOP_FROM = 'FROM products p JOIN categories c ON c.id = p.category_id WHERE p.is_active = 1 AND c.is_active = 1';

    /**
     * @param array{q?: string, category?: string, min?: ?int, max?: ?int, size?: string, material?: string, instock?: bool} $filters
     */
    public static function shopCount(array $filters): int
    {
        [$where, $args] = self::shopWhere($filters);

        $st = Database::connection()->prepare('SELECT COUNT(*) ' . self::SHOP_FROM . $where);
        $st->execute($args);

        return (int) $st->fetchColumn();
    }

    public static function shopSearch(array $filters, string $sort, int $limit, int $offset): array
    {
        [$where, $args] = self::shopWhere($filters);
        $order = self::SORTS[$sort] ?? self::SORTS['newest'];

        $st = Database::connection()->prepare(
            self::cardColumns() . ' ' . self::SHOP_FROM . $where . ' ORDER BY ' . $order
            . ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset)
        );
        $st->execute($args);

        return $st->fetchAll();
    }

    /** One product for its public page, or null when it does not exist or is hidden. */
    public static function findForShop(string $slug): ?array
    {
        $st = Database::connection()->prepare(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug ' . self::SHOP_FROM . ' AND p.slug = ?'
        );
        $st->execute([$slug]);

        return $st->fetch() ?: null;
    }

    /**
     * Current data for products in a cart (only visible products in turned-on categories).
     *
     * @param list<int> $ids
     * @return array<int, array<string, mixed>> keyed by product id
     */
    public static function forCart(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0));

        if ($ids === []) {
            return [];
        }

        $marks = implode(',', array_fill(0, count($ids), '?'));
        $st = Database::connection()->prepare(
            'SELECT p.id, p.name, p.slug, p.price_paisa, p.stock_qty,
                    (SELECT i.file_path FROM product_images i
                      WHERE i.product_id = p.id
                      ORDER BY i.is_primary DESC, i.sort_order, i.id LIMIT 1) AS image_path '
            . self::SHOP_FROM . " AND p.id IN ($marks)"
        );
        $st->execute($ids);

        $out = [];
        foreach ($st->fetchAll() as $row) {
            $out[(int) $row['id']] = $row;
        }

        return $out;
    }

    /** Other products from the same category, newest first. */
    public static function related(int $categoryId, int $exceptId, int $limit): array
    {
        $st = Database::connection()->prepare(
            self::cardColumns() . ' ' . self::SHOP_FROM . ' AND p.category_id = ? AND p.id <> ?
             ORDER BY p.is_featured DESC, p.created_at DESC, p.id DESC LIMIT ' . max(1, $limit)
        );
        $st->execute([$categoryId, $exceptId]);

        return $st->fetchAll();
    }

    /** Featured products for the home page; if none are marked featured, the newest ones. */
    public static function featured(int $limit): array
    {
        $st = Database::connection()->prepare(
            self::cardColumns() . ' ' . self::SHOP_FROM . ' ORDER BY p.is_featured DESC, p.created_at DESC, p.id DESC LIMIT ' . max(1, $limit)
        );
        $st->execute();

        return $st->fetchAll();
    }

    /**
     * Values for the filter dropdowns, taken from products that are actually for sale.
     *
     * @return array{sizes: list<string>, materials: list<string>}
     */
    public static function shopOptions(): array
    {
        $pdo = Database::connection();

        $sizes = $pdo->query(
            'SELECT DISTINCT p.size ' . self::SHOP_FROM . " AND p.size IS NOT NULL AND p.size <> '' ORDER BY p.size"
        )->fetchAll(\PDO::FETCH_COLUMN);

        $materials = $pdo->query(
            'SELECT DISTINCT p.material ' . self::SHOP_FROM . " AND p.material IS NOT NULL AND p.material <> '' ORDER BY p.material"
        )->fetchAll(\PDO::FETCH_COLUMN);

        return ['sizes' => array_map('strval', $sizes), 'materials' => array_map('strval', $materials)];
    }

    private static function cardColumns(): string
    {
        return 'SELECT p.id, p.name, p.slug, p.short_description, p.price_paisa, p.stock_qty, p.is_featured,
                       c.name AS category_name, c.slug AS category_slug,
                       (SELECT i.file_path FROM product_images i
                         WHERE i.product_id = p.id
                         ORDER BY i.is_primary DESC, i.sort_order, i.id LIMIT 1) AS image_path';
    }

    /** @return array{0: string, 1: list<mixed>} " AND ..." text and its values */
    private static function shopWhere(array $filters): array
    {
        $where = '';
        $args = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where .= ' AND (p.name LIKE ? OR p.short_description LIKE ?)';
            array_push($args, $like, $like);
        }

        if (($filters['category'] ?? '') !== '') {
            $where .= ' AND c.slug = ?';
            $args[] = $filters['category'];
        }

        if (isset($filters['min']) && $filters['min'] > 0) {
            $where .= ' AND p.price_paisa >= ?';
            $args[] = (int) $filters['min'];
        }

        if (isset($filters['max']) && $filters['max'] > 0) {
            $where .= ' AND p.price_paisa <= ?';
            $args[] = (int) $filters['max'];
        }

        if (($filters['size'] ?? '') !== '') {
            $where .= ' AND p.size = ?';
            $args[] = $filters['size'];
        }

        if (($filters['material'] ?? '') !== '') {
            $where .= ' AND p.material = ?';
            $args[] = $filters['material'];
        }

        if (!empty($filters['instock'])) {
            $where .= ' AND p.stock_qty > 0';
        }

        return [$where, $args];
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
