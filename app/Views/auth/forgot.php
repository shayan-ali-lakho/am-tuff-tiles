<?php /** @var bool $sent */ ?>
<section class="page-head"><div class="container"><h1>Forgot password</h1></div></section>

<section class="section">
    <div class="container">
        <div class="auth-card">
            <?php if ($sent): ?>
                <div class="alert alert-success" role="status">
                    If an account exists for that email, we have sent a link to choose a new password. It works for one hour.
                    Please check your spam folder too.
                </div>
                <p class="auth-alt"><a href="<?= e(url('/login')) ?>">Back to log in</a></p>
            <?php else: ?>
                <p>Enter the email you used to register and we will email you a link to choose a new password.</p>
                <form method="post" action="<?= e(url('/forgot-password')) ?>" class="form" novalidate>
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" autocomplete="email" required autofocus>
                    </div>
                    <button class="btn btn-primary btn-block" type="submit">Send reset link</button>
                </form>
                <p class="auth-alt"><a href="<?= e(url('/login')) ?>">Back to log in</a></p>
            <?php endif; ?>
        </div>
    </div>
</section>
