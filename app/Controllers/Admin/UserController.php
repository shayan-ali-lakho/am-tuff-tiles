<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\Contact;
use RuntimeException;

/** Admin > Users: see accounts and give or take away admin access. Admin login and CSRF are enforced by the router. */
final class UserController
{
    private const PER_PAGE = 20;

    public function index(): void
    {
        $q     = trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 100));
        $total = Contact::userCount($q);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($pages, max(1, (int) ($_GET['page'] ?? 1)));

        echo view('admin/users/index', [
            'title' => 'Users',
            'users' => Contact::userSearch($q, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'q'     => $q,
            'total' => $total,
            'page'  => $page,
            'pages' => $pages,
            'me'    => (int) (Auth::user()['id'] ?? 0),
        ]);
    }

    public function role(string $id): void
    {
        $target = ctype_digit($id) ? Contact::find((int) $id) : null;

        if ($target === null) {
            abort(404);
        }

        $role = (string) ($_POST['role'] ?? '');

        try {
            Contact::changeRole((int) (Auth::user()['id'] ?? 0), (int) $target['id'], $role);
            notify('success', $role === 'admin' ? 'Admin added' : 'Admin removed', $target['full_name'] . ($role === 'admin' ? ' is now an admin.' : ' is no longer an admin.'));
        } catch (RuntimeException $e) {
            notify('error', 'Could not change role', $e->getMessage());
        }

        $q    = trim(mb_substr((string) ($_POST['q'] ?? ''), 0, 100));
        $page = max(1, (int) ($_POST['page'] ?? 1));
        $query = http_build_query(array_filter(['q' => $q, 'page' => $page > 1 ? $page : ''], static fn ($v): bool => $v !== ''));

        redirect('/admin/users' . ($query !== '' ? '?' . $query : ''));
    }
}
