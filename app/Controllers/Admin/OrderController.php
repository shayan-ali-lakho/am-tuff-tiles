<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\ImageUploader;
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

    /** The customer's EasyPaisa screenshot. Private: only a logged-in admin gets here (the router checks), and it is never cached. */
    public function proof(string $id): void
    {
        $order = ctype_digit($id) ? Order::find((int) $id) : null;
        $path  = $order !== null ? ImageUploader::proofPath((string) ($order['payment_proof'] ?? '')) : null;

        if ($path === null || !is_file($path)) {
            abort(404);
        }

        header('Content-Type: image/jpeg');
        header('Content-Length: ' . (string) filesize($path));
        header('Cache-Control: private, no-store');
        header('X-Robots-Tag: noindex, nofollow');
        header('Content-Disposition: inline; filename="payment-' . preg_replace('/[^A-Za-z0-9-]/', '', (string) $order['order_number']) . '.jpg"');
        readfile($path);
        exit;
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
