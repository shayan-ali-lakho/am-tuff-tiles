<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;

/**
 * Admin home. Access control happens in the router for every /admin URL
 * (logged in and portal_role = admin), so new admin pages are protected automatically.
 */
final class DashboardController
{
    public function index(): void
    {
        echo view('admin/dashboard', [
            'title' => 'Admin panel',
            'admin' => Auth::user(),
        ]);
    }
}
