<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use RuntimeException;
use Throwable;

/** Orders (tables orders and order_items). Prices and stock are always taken from the database. */
final class Order
{
    /** Delivery charge in paisa from the settings table (0 = free delivery). */
    public static function deliveryCharge(): int
    {
        try {
            $st = Database::connection()->prepare("SELECT setting_value FROM settings WHERE setting_key = 'delivery_charge_paisa'");
            $st->execute();
            $value = $st->fetchColumn();
        } catch (Throwable) {
            return 0;
        }

        return is_string($value) && ctype_digit($value) ? (int) $value : 0;
    }

    /**
     * Place a cash-on-delivery order for the cart, in one transaction: the products are locked,
     * stock is checked and reduced, and the order and its lines are saved together or not at all.
     *
     * @param array<int, int> $cart product id => quantity
     * @param array{name: string, phone: string, email: string, address: string, city: string, notes: string} $details
     * @return string the order number
     * @throws RuntimeException with a message that is safe to show to the customer
     */
    public static function place(int $contactId, array $cart, array $details): string
    {
        if ($cart === []) {
            throw new RuntimeException('Your cart is empty.');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $ids = array_keys($cart);
            sort($ids); // same lock order every time avoids deadlocks
            $marks = implode(',', array_fill(0, count($ids), '?'));

            $st = $pdo->prepare(
                "SELECT p.id, p.name, p.price_paisa, p.stock_qty
                   FROM products p JOIN categories c ON c.id = p.category_id
                  WHERE p.id IN ($marks) AND p.is_active = 1 AND c.is_active = 1
                  ORDER BY p.id FOR UPDATE"
            );
            $st->execute($ids);

            $products = [];
            foreach ($st->fetchAll() as $row) {
                $products[(int) $row['id']] = $row;
            }

            $subtotal = 0;
            $items    = [];

            foreach ($ids as $id) {
                $qty = (int) $cart[$id];
                $p   = $products[$id] ?? null;

                if ($p === null || $qty < 1) {
                    throw new RuntimeException('An item in your cart is no longer available. Please review your cart.');
                }

                if ((int) $p['stock_qty'] < $qty) {
                    throw new RuntimeException('Sorry, ' . $p['name'] . ' does not have enough stock any more. Please review your cart.');
                }

                $line = (int) $p['price_paisa'] * $qty;
                $subtotal += $line;
                $items[] = [$id, (string) $p['name'], (int) $p['price_paisa'], $qty, $line];
            }

            $delivery = self::deliveryCharge();
            $total    = $subtotal + $delivery;

            $number = null;
            for ($try = 0; $try < 5 && $number === null; $try++) {
                $candidate = 'AM-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $check = $pdo->prepare('SELECT 1 FROM orders WHERE order_number = ?');
                $check->execute([$candidate]);

                if ($check->fetchColumn() === false) {
                    $number = $candidate;
                }
            }

            if ($number === null) {
                throw new RuntimeException('Could not create the order number. Please try again.');
            }

            $pdo->prepare(
                'INSERT INTO orders (order_number, contact_id, customer_name, customer_phone, customer_email,
                                     shipping_address, shipping_city, notes, status, payment_method,
                                     subtotal_paisa, delivery_paisa, total_paisa)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'pending\', \'cod\', ?, ?, ?)'
            )->execute([
                $number, $contactId, $details['name'], $details['phone'], $details['email'],
                $details['address'], $details['city'], $details['notes'] !== '' ? $details['notes'] : null,
                $subtotal, $delivery, $total,
            ]);

            $orderId  = (int) $pdo->lastInsertId();
            $insert   = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, unit_price_paisa, quantity, line_total_paisa)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $reduce   = $pdo->prepare('UPDATE products SET stock_qty = stock_qty - ? WHERE id = ? AND stock_qty >= ?');

            foreach ($items as [$id, $name, $price, $qty, $line]) {
                $insert->execute([$orderId, $id, $name, $price, $qty, $line]);
                $reduce->execute([$qty, $id, $qty]);

                if ($reduce->rowCount() !== 1) {
                    throw new RuntimeException('Sorry, ' . $name . ' does not have enough stock any more. Please review your cart.');
                }
            }

            $pdo->commit();

            return $number;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e instanceof RuntimeException ? $e : new RuntimeException('We could not save your order. Please try again.', 0, $e);
        }
    }

    /** One order with its lines, only if it belongs to this contact. */
    public static function findForContact(string $number, int $contactId): ?array
    {
        $pdo = Database::connection();
        $st  = $pdo->prepare('SELECT * FROM orders WHERE order_number = ? AND contact_id = ?');
        $st->execute([$number, $contactId]);
        $order = $st->fetch();

        if (!$order) {
            return null;
        }

        $items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
        $items->execute([$order['id']]);
        $order['items'] = $items->fetchAll();

        return $order;
    }

    /** A customer's own orders, newest first. */
    public static function forContact(int $contactId): array
    {
        $st = Database::connection()->prepare(
            'SELECT o.*, (SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE order_id = o.id) AS item_count
               FROM orders o WHERE o.contact_id = ? ORDER BY o.placed_at DESC, o.id DESC LIMIT 100'
        );
        $st->execute([$contactId]);

        return $st->fetchAll();
    }
}
