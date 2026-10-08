<?php
/**
 * @var list<array<string, mixed>> $users
 * @var string $q
 * @var int $total
 * @var int $page
 * @var int $pages
 * @var int $me
 */
$pageUrl = static fn (int $n): string => url('/admin/users?' . http_build_query(array_filter(['q' => $q, 'page' => $n > 1 ? $n : ''], static fn ($v): bool => $v !== '')));
?>
<?= partial('admin/_nav', ['active' => 'users']) ?>

<section class="page-head">
    <div class="container">
        <h1>Users</h1>
        <p class="page-head-sub">Give a person admin access, or take it away. Admins can manage orders, products and reports.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="alert alert-info" role="note">The person must first create an account on the site (Create account). Then find them here and press "Make admin".</div>

        <div class="toolbar">
            <form method="get" action="<?= e(url('/admin/users')) ?>" class="form filter-form">
                <div class="field">
                    <label for="q">Search</label>
                    <input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Name, email or phone">
                </div>
                <button class="btn btn-primary btn-sm" type="submit">Search</button>
                <?php if ($q !== ''): ?><a class="btn btn-secondary btn-sm" href="<?= e(url('/admin/users')) ?>">Clear</a><?php endif; ?>
            </form>
        </div>

        <?php if ($users === []): ?>
            <div class="empty-state"><p>No users found.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th><th>Role</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): $isAdmin = $u['portal_role'] === 'admin'; $isMe = (int) $u['id'] === $me; ?>
                        <tr>
                            <td><?= e($u['full_name']) ?><?= $isMe ? ' <span class="muted">(you)</span>' : '' ?></td>
                            <td><?= e($u['email']) ?></td>
                            <td><?= e($u['phone'] ?? '') ?></td>
                            <td><?= e(date('j M Y', strtotime((string) $u['created_at']))) ?></td>
                            <td>
                                <span class="badge <?= $isAdmin ? 'badge-ok' : 'badge-off' ?>"><?= $isAdmin ? 'Admin' : 'Customer' ?></span>
                                <?php if ((int) $u['is_active'] !== 1): ?><span class="badge badge-warn">Turned off</span><?php endif; ?>
                            </td>
                            <td class="actions">
                                <?php if ($isMe): ?>
                                    <span class="muted">&mdash;</span>
                                <?php else: ?>
                                    <form method="post" action="<?= e(url('/admin/users/' . $u['id'] . '/role')) ?>" class="inline-form"
                                          <?= $isAdmin ? 'data-confirm="Remove admin access for ' . e($u['full_name']) . '?"' : 'data-confirm="Give ' . e($u['full_name']) . ' full admin access to orders, products and reports?"' ?>>
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="role" value="<?= $isAdmin ? 'customer' : 'admin' ?>">
                                        <input type="hidden" name="q" value="<?= e($q) ?>">
                                        <input type="hidden" name="page" value="<?= e($page) ?>">
                                        <button class="btn btn-sm <?= $isAdmin ? 'btn-secondary' : 'btn-primary' ?>" type="submit"><?= $isAdmin ? 'Remove admin' : 'Make admin' ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pages > 1): ?>
                <nav class="pager" aria-label="Pages">
                    <?php if ($page > 1): ?><a class="btn btn-secondary btn-sm" href="<?= e($pageUrl($page - 1)) ?>" rel="prev">Previous</a><?php endif; ?>
                    <span class="pager-info">Page <?= e($page) ?> of <?= e($pages) ?></span>
                    <?php if ($page < $pages): ?><a class="btn btn-secondary btn-sm" href="<?= e($pageUrl($page + 1)) ?>" rel="next">Next</a><?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
