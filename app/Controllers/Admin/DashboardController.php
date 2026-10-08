<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\Report;
use Throwable;

/**
 * Admin home. Access control happens in the router for every /admin URL
 * (logged in and portal_role = admin), so new admin pages are protected automatically.
 */
final class DashboardController
{
    public function index(): void
    {
        try {
            $stats = Report::dashboard();
        } catch (Throwable $e) {
            error_log('Dashboard numbers failed: ' . $e->getMessage());
            $stats = null;
        }

        echo view('admin/dashboard', [
            'title' => 'Admin panel',
            'admin' => Auth::user(),
            'stats' => $stats,
        ]);
    }
}
