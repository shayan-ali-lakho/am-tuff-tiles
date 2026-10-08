<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Order;
use RuntimeException;

/** Admin > Orders. Admin login and CSRF are enforced by the router. */
final class OrderController
{
    private const PER_PAGE = 20;

    public function index(): void
    {
        $tab = (string) ($_GET['tab'] ?? 'received');
        $tab = array_key_exists($tab, Order::TABS) ? $tab : 'received';

        $filters = ['tab' => $tab, 'q' => trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 100))];

        $total = Order::adminCount($filters);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($pages, max(1, (int) ($_GET['page'] ?? 1)));

        echo view('admin/orders/index', [
            'title'   => 'Orders',
            'orders'  => Order::adminSearch($filters, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'filters' => $filters,
            'counts'  => Order::statusCounts(),
            'total'   => $total,
            'page'    => $page,
            'pages'   => $pages,
        ]);
    }

    public function show(string $id): void
    {
        $order = ctype_digit($id) ? Order::find((int) $id) : null;

        if ($order === null) {
            abort(404);
        }

        echo view('admin/orders/show', ['title' => 'Order ' . $order['order_number'], 'order' => $order]);
    }

    public function status(string $id): void
    {
        $order = ctype_digit($id) ? Order::find((int) $id) : null;

        if ($order === null) {
            abort(404);
        }

        $new = (string) ($_POST['status'] ?? '');

        try {
            Order::changeStatus((int) $order['id'], $new);
            flash('success', 'Order ' . $order['order_number'] . ' is now ' . $new . '.' . ($new === 'cancelled' ? ' Stock was put back.' : ''));
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }

        redirect('/admin/orders/' . (int) $order['id']);
    }
}
