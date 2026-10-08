<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Photos of a product (table: product_images). Exactly one photo per product is "primary"
 * (shown first in the shop); the first photo added becomes primary automatically.
 */
final class ProductImage
{
    public static function forProduct(int $productId): array
    {
        $st = Database::connection()->prepare(
            'SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order, id'
        );
        $st->execute([$productId]);

        return $st->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $st = Database::connection()->prepare('SELECT * FROM product_images WHERE id = ?');
        $st->execute([$id]);

        return $st->fetch() ?: null;
    }

    public static function count(int $productId): int
    {
        $st = Database::connection()->prepare('SELECT COUNT(*) FROM product_images WHERE product_id = ?');
        $st->execute([$productId]);

        return (int) $st->fetchColumn();
    }

    public static function add(int $productId, string $filePath, ?string $altText): int
    {
        $pdo = Database::connection();

        $st = $pdo->prepare('SELECT COUNT(*), COALESCE(MAX(sort_order), 0) FROM product_images WHERE product_id = ?');
        $st->execute([$productId]);
        [$existing, $maxSort] = array_map('intval', $st->fetch(\PDO::FETCH_NUM));

        $pdo->prepare(
            'INSERT INTO product_images (product_id, file_path, alt_text, is_primary, sort_order) VALUES (?, ?, ?, ?, ?)'
        )->execute([$productId, $filePath, $altText, $existing === 0 ? 1 : 0, $maxSort + 1]);

        return (int) $pdo->lastInsertId();
    }

    public static function setPrimary(int $productId, int $imageId): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $pdo->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = ?')->execute([$productId]);
            $pdo->prepare('UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?')->execute([$imageId, $productId]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Remove the row (the caller deletes the file). If it was the primary photo, the next one takes over. */
    public static function delete(int $productId, int $imageId): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM product_images WHERE id = ? AND product_id = ?')->execute([$imageId, $productId]);

        $st = $pdo->prepare('SELECT COUNT(*) FROM product_images WHERE product_id = ? AND is_primary = 1');
        $st->execute([$productId]);

        if ((int) $st->fetchColumn() === 0) {
            $pdo->prepare(
                'UPDATE product_images SET is_primary = 1 WHERE product_id = ? ORDER BY sort_order, id LIMIT 1'
            )->execute([$productId]);
        }
    }
}
