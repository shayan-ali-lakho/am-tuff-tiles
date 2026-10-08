<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * The shopping cart. Only product ids and quantities are kept in the session; names, prices and
 * stock are always read fresh from the database, so a visitor can never choose their own price.
 */
final class Cart
{
    public const MAX_LINES = 30;
    public const MAX_QTY   = 999;

    /** @return array<int, int> product id => quantity */
    private static function raw(): array
    {
        $cart = $_SESSION['cart'] ?? [];

        return is_array($cart) ? $cart : [];
    }

    /** Total number of items, for the menu badge. Needs no database. */
    public static function count(): int
    {
        return (int) array_sum(self::raw());
    }

    /** Add to the cart. Returns false when the cart already holds too many different products. */
    public static function add(int $productId, int $qty): bool
    {
        $cart = self::raw();

        if (!isset($cart[$productId]) && count($cart) >= self::MAX_LINES) {
            return false;
        }

        $cart[$productId] = min(self::MAX_QTY, ($cart[$productId] ?? 0) + max(1, $qty));
        $_SESSION['cart'] = $cart;

        return true;
    }

    public static function set(int $productId, int $qty): void
    {
        $cart = self::raw();

        if (!isset($cart[$productId])) {
            return;
        }

        if ($qty <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = min(self::MAX_QTY, $qty);
        }

        $_SESSION['cart'] = $cart;
    }

    public static function remove(int $productId): void
    {
        self::set($productId, 0);
    }

    public static function clear(): void
    {
        unset($_SESSION['cart']);
    }

    /**
     * The cart as it stands right now. Products that were hidden, deleted or sold out are dropped, and quantities
     * are lowered to the stock available. What changed is listed in 'notices' so the customer is told.
     *
     * @return array{lines: list<array<string, mixed>>, subtotal: int, delivery: int, total: int, notices: list<string>}
     */
    public static function summary(): array
    {
        $cart    = self::raw();
        $notices = [];
        $lines   = [];

        $products = $cart === [] ? [] : Product::forCart(array_keys($cart));

        foreach ($cart as $id => $qty) {
            $product = $products[$id] ?? null;

            if ($product === null) {
                $notices[] = 'An item in your cart is no longer available and was removed.';
                unset($cart[$id]);
                continue;
            }

            $stock = (int) $product['stock_qty'];

            if ($stock <= 0) {
                $notices[] = $product['name'] . ' is out of stock and was removed from your cart.';
                unset($cart[$id]);
                continue;
            }

            if ($qty > $stock) {
                $notices[] = 'Only ' . $stock . ' of ' . $product['name'] . ' are available, so the quantity was lowered.';
                $qty = $stock;
                $cart[$id] = $qty;
            }

            $product['qty']        = (int) $qty;
            $product['line_total'] = (int) $product['price_paisa'] * (int) $qty;
            $lines[] = $product;
        }

        $_SESSION['cart'] = $cart;

        $subtotal = (int) array_sum(array_column($lines, 'line_total'));
        $delivery = $lines === [] ? 0 : Order::deliveryCharge();

        return [
            'lines'    => $lines,
            'subtotal' => $subtotal,
            'delivery' => $delivery,
            'total'    => $subtotal + $delivery,
            'notices'  => array_values(array_unique($notices)),
        ];
    }
}
