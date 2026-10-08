<?php
/**
 * @var bool $valid
 * @var string $token
 * @var array<string, string> $errors
 */
?>
<section class="page-head"><div class="container"><h1>Choose a new password</h1></div></section>

<section class="section">
    <div class="container">
        <div class="auth-card">
            <?php if (!$valid): ?>
                <div class="alert alert-error" role="alert">This link is not valid any more. It may have expired or already been used.</div>
                <p><a class="btn btn-primary btn-block" href="<?= e(url('/forgot-password')) ?>">Get a new link</a></p>
            <?php else: ?>
                <form method="post" action="<?= e(url('/reset-password/' . $token)) ?>" class="form" novalidate>
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="password">New password</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" required autofocus minlength="8" maxlength="72"<?= error_attrs($errors, 'password') ?>>
                        <p class="field-hint">At least 8 characters.</p>
                        <?= field_error($errors, 'password') ?>
                    </div>
                    <div class="field">
                        <label for="password_confirm">Repeat new password</label>
                        <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required maxlength="72"<?= error_attrs($errors, 'password_confirm') ?>>
                        <?= field_error($errors, 'password_confirm') ?>
                    </div>
                    <button class="btn btn-primary btn-block" type="submit">Save new password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
