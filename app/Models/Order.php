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

    // ------------------------------------------------------------------
    // Admin
    // ------------------------------------------------------------------

    /** Which statuses each tab shows. */
    public const TABS = [
        'received'  => ['pending', 'confirmed'],
        'completed' => ['completed'],
        'cancelled' => ['cancelled'],
        'all'       => ['pending', 'confirmed', 'completed', 'cancelled'],
    ];

    /** Allowed status changes: current status => statuses it can move to. */
    public const TRANSITIONS = [
        'pending'   => ['confirmed', 'completed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    /** @param array{tab: string, q: string} $filters */
    private static function adminWhere(array $filters): array
    {
        $statuses = self::TABS[$filters['tab']] ?? self::TABS['received'];
        $where    = ' WHERE o.status IN (' . implode(',', array_fill(0, count($statuses), '?')) . ')';
        $args     = $statuses;

        if ($filters['q'] !== '') {
            $like   = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $where .= ' AND (o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ?)';
            array_push($args, $like, $like, $like);
        }

        return [$where, $args];
    }

    public static function adminCount(array $filters): int
    {
        [$where, $args] = self::adminWhere($filters);
        $st = Database::connection()->prepare('SELECT COUNT(*) FROM orders o' . $where);
        $st->execute($args);

        return (int) $st->fetchColumn();
    }

    public static function adminSearch(array $filters, int $limit, int $offset): array
    {
        [$where, $args] = self::adminWhere($filters);
        $order = $filters['tab'] === 'completed' ? 'o.completed_at DESC, o.id DESC' : 'o.placed_at DESC, o.id DESC';

        $st = Database::connection()->prepare(
            'SELECT o.*, (SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE order_id = o.id) AS item_count
               FROM orders o' . $where . ' ORDER BY ' . $order . ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset)
        );
        $st->execute($args);

        return $st->fetchAll();
    }

    /** Number of orders per status, for the tab labels. */
    public static function statusCounts(): array
    {
        $counts = ['pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0];

        foreach (Database::connection()->query('SELECT status, COUNT(*) AS n FROM orders GROUP BY status')->fetchAll() as $row) {
            $counts[(string) $row['status']] = (int) $row['n'];
        }

        return $counts;
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::connection();
        $st  = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $st->execute([$id]);
        $order = $st->fetch();

        if (!$order) {
            return null;
        }

        $items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
        $items->execute([$id]);
        $order['items'] = $items->fetchAll();

        return $order;
    }

    /**
     * Move an order to a new status if that change is allowed. Cancelling puts the stock back.
     * The order row is locked so two admins clicking at once cannot both succeed.
     *
     * @throws RuntimeException when the change is not allowed
     */
    public static function changeStatus(int $id, string $new): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $st = $pdo->prepare('SELECT status FROM orders WHERE id = ? FOR UPDATE');
            $st->execute([$id]);
            $current = $st->fetchColumn();

            if (!is_string($current)) {
                throw new RuntimeException('Order not found.');
            }

            if (!in_array($new, self::TRANSITIONS[$current] ?? [], true)) {
                throw new RuntimeException('This order cannot be changed from "' . $current . '" to "' . $new . '".');
            }

            $stamp = ['confirmed' => 'confirmed_at', 'completed' => 'completed_at', 'cancelled' => 'cancelled_at'][$new];
            // $stamp comes from the fixed list above, never from user input
            $pdo->prepare("UPDATE orders SET status = ?, $stamp = NOW() WHERE id = ?")->execute([$new, $id]);

            if ($new === 'cancelled') {
                $pdo->prepare(
                    'UPDATE products p JOIN order_items i ON i.product_id = p.id
                        SET p.stock_qty = p.stock_qty + i.quantity WHERE i.order_id = ?'
                )->execute([$id]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    /** Send the "new order" email to the shop and the confirmation email to the customer. Never throws. */
    public static function notify(string $number): void
    {
        try {
            $pdo = Database::connection();
            $st  = $pdo->prepare('SELECT id FROM orders WHERE order_number = ?');
            $st->execute([$number]);
            $id = $st->fetchColumn();
            $order = $id ? self::find((int) $id) : null;

            if ($order === null) {
                return;
            }

            $lines = [];
            foreach ($order['items'] as $item) {
                $lines[] = sprintf('- %s x %d  (%s)', $item['product_name'], $item['quantity'], money((int) $item['line_total_paisa']));
            }
            $items = implode("\n", $lines);

            $site  = (string) config('app.name');
            $money = static fn (int $p): string => money($p);
            $delivery = (int) $order['delivery_paisa'] > 0 ? $money((int) $order['delivery_paisa']) : 'Free';

            $shopEmail = (string) config('shop.email');
            if ($shopEmail !== '') {
                $admin = rtrim((string) config('app.url'), '/') . '/admin/orders/' . $order['id'];
                \App\Core\Mailer::send(
                    $shopEmail,
                    'New order ' . $number . ' (' . $money((int) $order['total_paisa']) . ')',
                    "A new cash-on-delivery order was placed.\n\nOrder: $number\nCustomer: {$order['customer_name']}\nPhone: {$order['customer_phone']}\nEmail: {$order['customer_email']}\n"
                    . "Address: {$order['shipping_address']}, {$order['shipping_city']}\n"
                    . ($order['notes'] ? "Notes: {$order['notes']}\n" : '')
                    . "\nItems:\n$items\n\nDelivery: $delivery\nTotal to collect: " . $money((int) $order['total_paisa'])
                    . "\n\nOpen the order: $admin\n",
                    (string) $order['customer_email']
                );
            }

            \App\Core\Mailer::send(
                (string) $order['customer_email'],
                'Your ' . $site . ' order ' . $number,
                "Hello {$order['customer_name']},\n\nThank you for your order. We will call you on {$order['customer_phone']} to confirm it.\n\n"
                . "Order: $number\n\nItems:\n$items\n\nDelivery: $delivery\nTotal to pay on delivery (cash): " . $money((int) $order['total_paisa'])
                . "\n\nDelivering to: {$order['shipping_address']}, {$order['shipping_city']}\n\n$site\n",
                $shopEmail !== '' ? $shopEmail : null
            );
        } catch (Throwable $e) {
            error_log('Order emails failed: ' . $e->getMessage());
        }
    }
}
