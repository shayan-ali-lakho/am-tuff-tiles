<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;

/**
 * Monthly report. Money earned counts COMPLETED orders by the month they were completed;
 * orders received counts every order by the month it was placed.
 */
final class Report
{
    /** "2026-10" -> first day of that month, or null if the text is not a valid month. */
    public static function parseMonth(string $text): ?DateTimeImmutable
    {
        if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $text, $m) !== 1 || (int) $m[1] < 2000 || (int) $m[1] > 2100) {
            return null;
        }

        return new DateTimeImmutable($m[1] . '-' . $m[2] . '-01 00:00:00');
    }

    /** Months that have orders (newest first), always including the current month. */
    public static function months(): array
    {
        $rows = Database::connection()->query(
            "SELECT DISTINCT DATE_FORMAT(placed_at, '%Y-%m') AS m FROM orders
             UNION SELECT DISTINCT DATE_FORMAT(completed_at, '%Y-%m') FROM orders WHERE completed_at IS NOT NULL
             ORDER BY m DESC"
        )->fetchAll(\PDO::FETCH_COLUMN);

        $months = array_map('strval', $rows);
        $now    = date('Y-m');

        if (!in_array($now, $months, true)) {
            array_unshift($months, $now);
            rsort($months);
        }

        return $months;
    }

    /**
     * @return array<string, mixed>
     */
    public static function month(DateTimeImmutable $start): array
    {
        $pdo   = Database::connection();
        $from  = $start->format('Y-m-d H:i:s');
        $to    = $start->modify('+1 month')->format('Y-m-d H:i:s');

        // Orders received this month, by their current status
        $st = $pdo->prepare('SELECT status, COUNT(*) AS n FROM orders WHERE placed_at >= ? AND placed_at < ? GROUP BY status');
        $st->execute([$from, $to]);
        $byStatus = ['pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0];
        foreach ($st->fetchAll() as $row) {
            $byStatus[(string) $row['status']] = (int) $row['n'];
        }

        // Money earned: orders completed this month
        $st = $pdo->prepare(
            "SELECT COUNT(*) AS n, COALESCE(SUM(total_paisa), 0) AS revenue,
                    COALESCE(SUM(subtotal_paisa), 0) AS goods, COALESCE(SUM(delivery_paisa), 0) AS delivery
               FROM orders WHERE status = 'completed' AND completed_at >= ? AND completed_at < ?"
        );
        $st->execute([$from, $to]);
        $done = $st->fetch();

        // Best sellers among the orders completed this month
        $st = $pdo->prepare(
            "SELECT i.product_name, SUM(i.quantity) AS qty, SUM(i.line_total_paisa) AS revenue
               FROM order_items i JOIN orders o ON o.id = i.order_id
              WHERE o.status = 'completed' AND o.completed_at >= ? AND o.completed_at < ?
              GROUP BY i.product_name ORDER BY revenue DESC, qty DESC LIMIT 10"
        );
        $st->execute([$from, $to]);
        $top = $st->fetchAll();

        // Day by day
        $days = [];
        for ($d = $start; $d < $start->modify('+1 month'); $d = $d->modify('+1 day')) {
            $days[$d->format('Y-m-d')] = ['placed' => 0, 'completed' => 0, 'revenue' => 0];
        }

        $st = $pdo->prepare('SELECT DATE(placed_at) AS d, COUNT(*) AS n FROM orders WHERE placed_at >= ? AND placed_at < ? GROUP BY DATE(placed_at)');
        $st->execute([$from, $to]);
        foreach ($st->fetchAll() as $row) {
            if (isset($days[$row['d']])) {
                $days[$row['d']]['placed'] = (int) $row['n'];
            }
        }

        $st = $pdo->prepare(
            "SELECT DATE(completed_at) AS d, COUNT(*) AS n, SUM(total_paisa) AS revenue FROM orders
              WHERE status = 'completed' AND completed_at >= ? AND completed_at < ? GROUP BY DATE(completed_at)"
        );
        $st->execute([$from, $to]);
        foreach ($st->fetchAll() as $row) {
            if (isset($days[$row['d']])) {
                $days[$row['d']]['completed'] = (int) $row['n'];
                $days[$row['d']]['revenue']   = (int) $row['revenue'];
            }
        }

        $completedCount = (int) $done['n'];

        return [
            'placed'         => array_sum($byStatus),
            'by_status'      => $byStatus,
            'completed'      => $completedCount,
            'revenue'        => (int) $done['revenue'],
            'goods'          => (int) $done['goods'],
            'delivery'       => (int) $done['delivery'],
            'average'        => $completedCount > 0 ? intdiv((int) $done['revenue'], $completedCount) : 0,
            'top'            => $top,
            'days'           => $days,
        ];
    }

    /** Orders completed in the month, for the CSV download. */
    public static function completedOrders(DateTimeImmutable $start): array
    {
        $st = Database::connection()->prepare(
            "SELECT order_number, completed_at, customer_name, customer_phone, shipping_city,
                    subtotal_paisa, delivery_paisa, total_paisa
               FROM orders WHERE status = 'completed' AND completed_at >= ? AND completed_at < ?
              ORDER BY completed_at, id"
        );
        $st->execute([$start->format('Y-m-d H:i:s'), $start->modify('+1 month')->format('Y-m-d H:i:s')]);

        return $st->fetchAll();
    }

    /** Numbers for the admin dashboard. */
    public static function dashboard(): array
    {
        $pdo   = Database::connection();
        $start = date('Y-m-01 00:00:00');
        $st    = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(total_paisa), 0) FROM orders WHERE status = 'completed' AND completed_at >= ?");
        $st->execute([$start]);
        [$done, $revenue] = $st->fetch(\PDO::FETCH_NUM);

        return [
            'counts'   => Order::statusCounts(),
            'done'     => (int) $done,
            'revenue'  => (int) $revenue,
            'products' => (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
            'low'      => (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock_qty <= 5')->fetchColumn(),
        ];
    }
}
