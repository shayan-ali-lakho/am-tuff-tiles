<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Report;

/** Admin > Monthly report, with a CSV download. */
final class ReportController
{
    public function index(): void
    {
        $start = Report::parseMonth((string) ($_GET['month'] ?? '')) ?? new \DateTimeImmutable(date('Y-m-01 00:00:00'));

        echo view('admin/reports/index', [
            'title'  => 'Monthly report',
            'month'  => $start->format('Y-m'),
            'label'  => $start->format('F Y'),
            'months' => Report::months(),
            'report' => Report::month($start),
        ]);
    }

    public function export(): void
    {
        $start = Report::parseMonth((string) ($_GET['month'] ?? '')) ?? new \DateTimeImmutable(date('Y-m-01 00:00:00'));
        $rows  = Report::completedOrders($start);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="orders-' . $start->format('Y-m') . '.csv"');
        header('X-Content-Type-Options: nosniff');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // so Excel reads names correctly
        fputcsv($out, ['Order', 'Completed', 'Customer', 'Phone', 'City', 'Goods (PKR)', 'Delivery (PKR)', 'Total (PKR)'], ',', '"', '\\');

        // A cell starting with = + - @ could run as a formula in Excel, so text cells get a leading apostrophe
        $safe = static fn (string $v): string => preg_match('/^[=+\-@\t\r]/', $v) === 1 ? "'" . $v : $v;

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['order_number'],
                $r['completed_at'],
                $safe((string) $r['customer_name']),
                $safe((string) $r['customer_phone']),
                $safe((string) $r['shipping_city']),
                number_format((int) $r['subtotal_paisa'] / 100, 2, '.', ''),
                number_format((int) $r['delivery_paisa'] / 100, 2, '.', ''),
                number_format((int) $r['total_paisa'] / 100, 2, '.', ''),
            ], ',', '"', '\\');
        }

        fclose($out);
        exit;
    }
}
