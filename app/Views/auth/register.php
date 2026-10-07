<?php
/**
 * @var array<string, string> $errors
 * @var array<string, string> $old
 * @var string $next
 */
$loginUrl = '/login' . ($next !== '/' ? '?next=' . rawurlencode($next) : '');
?>
<section class="page-head">
    <div class="container"><h1>Create your account</h1></div>
</section>

<section class="section">
    <div class="container">
        <div class="auth-card">
            <form method="post" action="<?= e(url('/register')) ?>" class="form" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= e($next !== '/' ? $next : '') ?>">

                <div class="field">
                    <label for="full_name">Full name</label>
                    <input id="full_name" name="full_name" type="text" autocomplete="name" required autofocus
                           value="<?= e($old['full_name'] ?? '') ?>"<?= error_attrs($errors, 'full_name') ?>>
                    <?= field_error($errors, 'full_name') ?>
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" required
                           value="<?= e($old['email'] ?? '') ?>"<?= error_attrs($errors, 'email') ?>>
                    <?= field_error($errors, 'email') ?>
                </div>

                <div class="field">
                    <label for="phone">Phone <span class="optional">(optional)</span></label>
                    <input id="phone" name="phone" type="tel" autocomplete="tel"
                           value="<?= e($old['phone'] ?? '') ?>"<?= error_attrs($errors, 'phone') ?>>
                    <?= field_error($errors, 'phone') ?>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required
                           minlength="8"<?= error_attrs($errors, 'password') ?>>
                    <p class="field-hint">At least 8 characters.</p>
                    <?= field_error($errors, 'password') ?>
                </div>

                <div class="field">
                    <label for="password_confirm">Confirm password</label>
                    <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required
                           <?= error_attrs($errors, 'password_confirm') ?>>
                    <?= field_error($errors, 'password_confirm') ?>
                </div>

                <button class="btn btn-primary btn-block" type="submit">Create account</button>
            </form>

            <p class="auth-alt">Already have an account? <a href="<?= e(url($loginUrl)) ?>">Log in</a></p>
        </div>
    </div>
</section>
