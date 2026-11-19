<?php
/** @var bool $ok */
/** @var bool $isStaff */
/** @var \Illuminate\Support\ViewErrorBag $errors */
$pageTitle = 'Profile';
include resource_path('views/layouts/header.php');
?>
<?php if (!$ok): ?>
<div class="card">You do not have access to this page.</div>
<?php else: ?>
<?php if (session('status')): ?><div class="card text-green-400 mb-4" role="status"><?= e(session('status')) ?></div><?php endif; ?>
<?php if ($errors->any()): ?><div class="card text-red-300 mb-4" role="alert"><?php foreach ($errors->all() as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div><?php endif; ?>
<div class="grid gap-4 md:grid-cols-2">
    <section class="card">
        <h2 class="font-semibold mb-1">Account details</h2>
        <p class="text-sm text-gray-400 mb-4">Update your account details and recovery email address.</p>
        <form method="POST" action="<?= e(route('profile.update')) ?>" class="space-y-4">
            <?= csrf_field() ?>
            <?= method_field('PUT') ?>
            <div>
                <label class="lbl" for="name">Name</label>
                <input id="name" type="text" name="name" value="<?= e(old('name', auth()->user()->name)) ?>" class="inp" autocomplete="name" maxlength="255" required>
                <?php if ($errors->has('name')): ?><p class="mt-1 text-sm text-red-300"><?= e($errors->first('name')) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="lbl" for="username">Username</label>
                <input id="username" type="text" name="username" value="<?= e(old('username', auth()->user()->username)) ?>" class="inp" autocomplete="username" maxlength="50" required>
                <?php if ($errors->has('username')): ?><p class="mt-1 text-sm text-red-300"><?= e($errors->first('username')) ?></p><?php endif; ?>
                <p class="mt-1 text-xs text-gray-400">Use letters, numbers, dashes, or underscores.</p>
            </div>
            <div>
                <label class="lbl" for="email">Recovery email</label>
                <input id="email" type="email" name="email" value="<?= e(old('email', auth()->user()->email)) ?>" class="inp" autocomplete="email" maxlength="255" required>
                <?php if ($errors->has('email')): ?><p class="mt-1 text-sm text-red-300"><?= e($errors->first('email')) ?></p><?php endif; ?>
                <p class="mt-1 text-xs text-gray-400">Enter an inbox you can access. Changing this address requires your current password.</p>
            </div>
            <div>
                <label class="lbl" for="profile_current_password">Current password</label>
                <input id="profile_current_password" type="password" name="current_password" class="inp" autocomplete="current-password">
                <?php if ($errors->has('current_password')): ?><p class="mt-1 text-sm text-red-300"><?= e($errors->first('current_password')) ?></p><?php endif; ?>
            </div>
            <div>
                <span class="lbl">Role</span>
                <p><?= e(ucfirst(auth()->user()->role)) ?></p>
            </div>
            <button type="submit" class="btn">Save profile</button>
        </form>
    </section>
    <section class="card">
        <h2 class="font-semibold mb-1">Gmail sending setup</h2>
        <p class="text-sm text-gray-400 mb-4">Save a Gmail address and Google App Password so password-reset links can be sent directly from this app.</p>
        <?php $mailSettings = \App\Models\MailSettings::current(); ?>
        <?php if ($mailSettings): ?>
            <p class="mb-4 text-sm text-green-300" role="status">Gmail is configured for <?= e($mailSettings->smtp_username) ?>. The App Password is stored encrypted and never shown here.</p>
        <?php else: ?>
            <p class="mb-4 text-sm text-amber-300">Gmail is not configured yet. Password reset emails cannot be sent until you save these settings.</p>
        <?php endif; ?>
        <form method="POST" action="<?= e(route('profile.mail-settings.update')) ?>" class="space-y-4">
            <?= csrf_field() ?>
            <?= method_field('PUT') ?>
            <div>
                <label class="lbl" for="smtp_username">Gmail address</label>
                <input id="smtp_username" type="email" name="smtp_username" value="<?= e(old('smtp_username', $mailSettings?->smtp_username)) ?>" class="inp" autocomplete="email" maxlength="255" required>
                <?php if ($errors->has('smtp_username')): ?><p class="mt-1 text-sm text-red-300"><?= e($errors->first('smtp_username')) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="lbl" for="smtp_password">Google App Password</label>
                <input id="smtp_password" type="password" name="smtp_password" class="inp" autocomplete="new-password" minlength="16" maxlength="255" required>
                <?php if ($errors->has('smtp_password')): ?><p class="mt-1 text-sm text-red-300"><?= e($errors->first('smtp_password')) ?></p><?php endif; ?>
                <p class="mt-1 text-xs text-gray-400">Use an App Password, not your regular Google password. Spaces are removed automatically.</p>
            </div>
            <div>
                <label class="lbl" for="mail_current_password">Current account password</label>
                <input id="mail_current_password" type="password" name="current_password" class="inp" autocomplete="current-password" required>
                <?php if ($errors->has('current_password')): ?><p class="mt-1 text-sm text-red-300"><?= e($errors->first('current_password')) ?></p><?php endif; ?>
            </div>
            <button type="submit" class="btn"><?= $mailSettings ? 'Update Gmail settings' : 'Save Gmail settings' ?></button>
        </form>
        <?php if ($mailSettings): ?>
            <form method="POST" action="<?= e(route('profile.mail-settings.destroy')) ?>" class="space-y-4">
                <?= csrf_field() ?>
                <?= method_field('DELETE') ?>
                <div>
                    <label class="lbl" for="remove_mail_current_password">Current account password</label>
                    <input id="remove_mail_current_password" type="password" name="current_password" class="inp" autocomplete="current-password" required>
                    <?php if ($errors->has('current_password')): ?><p class="mt-1 text-sm text-red-300"><?= e($errors->first('current_password')) ?></p><?php endif; ?>
                </div>
                <button type="submit" class="btn">Remove Gmail settings</button>
            </form>
        <?php endif; ?>
    </section>
    <section class="card">
        <h2 class="font-semibold mb-1">Change password</h2>
        <p class="text-sm text-gray-400 mb-4">Enter your current password, then choose and confirm a new password of at least 8 characters.</p>
        <form method="POST" action="<?= e(route('profile.password')) ?>" class="space-y-4">
            <?= csrf_field() ?>
            <?= method_field('PUT') ?>
            <div>
                <label class="lbl" for="current_password">Current password</label>
                <div class="relative">
                    <input id="current_password" type="password" name="current_password" class="inp pr-12" autocomplete="current-password" required>
                    <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-gray-400 hover:text-white" data-toggle-password="#current_password" aria-label="Show current password" aria-pressed="false">
                        <svg data-eye viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg data-eye-off viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="hidden"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a14.8 14.8 0 0 1-4 4.7"/><path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1 0 2-.2 2.8-.5"/></svg>
                    </button>
                </div>
                <?php if ($errors->has('current_password')): ?><p class="mt-1 text-sm text-red-300"><?= e($errors->first('current_password')) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="lbl" for="password">New password</label>
                <div class="relative">
                    <input id="password" type="password" name="password" class="inp pr-12" autocomplete="new-password" minlength="8" required>
                    <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-gray-400 hover:text-white" data-toggle-password="#password" aria-label="Show new password" aria-pressed="false">
                        <svg data-eye viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg data-eye-off viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="hidden"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a14.8 14.8 0 0 1-4 4.7"/><path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1 0 2-.2 2.8-.5"/></svg>
                    </button>
                </div>
                <?php if ($errors->has('password')): ?><p class="mt-1 text-sm text-red-300"><?= e($errors->first('password')) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="lbl" for="password_confirmation">Confirm new password</label>
                <div class="relative">
                    <input id="password_confirmation" type="password" name="password_confirmation" class="inp pr-12" autocomplete="new-password" minlength="8" required>
                    <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-gray-400 hover:text-white" data-toggle-password="#password_confirmation" aria-label="Show password confirmation" aria-pressed="false">
                        <svg data-eye viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg data-eye-off viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="hidden"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a14.8 14.8 0 0 1-4 4.7"/><path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1 0 2-.2 2.8-.5"/></svg>
                    </button>
                </div>
                <?php if ($errors->has('password_confirmation')): ?><p class="mt-1 text-sm text-red-300"><?= e($errors->first('password_confirmation')) ?></p><?php endif; ?>
            </div>
            <button type="submit" class="btn">Change password</button>
        </form>
    </section>
</div>
<?php
$extraScript = <<<'HTML'
<script>
    document.querySelectorAll('[data-toggle-password]').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.querySelector(button.dataset.togglePassword);
            const showing = input.type === 'password';
            input.type = showing ? 'text' : 'password';
            button.querySelector('[data-eye]').classList.toggle('hidden', showing);
            button.querySelector('[data-eye-off]').classList.toggle('hidden', !showing);
            button.setAttribute('aria-label', `${showing ? 'Hide' : 'Show'} ${button.getAttribute('aria-label').replace(/^(Show|Hide) /, '')}`);
            button.setAttribute('aria-pressed', String(showing));
        });
    });
</script>
HTML;
?>
<?php endif; ?>
<?php include resource_path('views/layouts/footer.php'); ?>
