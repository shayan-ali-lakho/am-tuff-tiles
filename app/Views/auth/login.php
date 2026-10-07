<?php
/**
 * @var array<string, string> $errors
 * @var array<string, string> $old
 * @var string $next
 */
$registerUrl = '/register' . ($next !== '/' ? '?next=' . rawurlencode($next) : '');
?>
<section class="page-head">
    <div class="container"><h1>Log in</h1></div>
</section>

<section class="section">
    <div class="container">
        <div class="auth-card">
            <?php if (!empty($errors['form'])): ?>
                <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('/login')) ?>" class="form" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= e($next !== '/' ? $next : '') ?>">

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" required autofocus
                           value="<?= e($old['email'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                </div>

                <button class="btn btn-primary btn-block" type="submit">Log in</button>
            </form>

            <p class="auth-alt">New here? <a href="<?= e(url($registerUrl)) ?>">Create an account</a></p>
        </div>
    </div>
</section>
