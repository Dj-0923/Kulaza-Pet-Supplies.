<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose New Password - Kulaza Pet Supplies</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= e(asset('css/theme.css')) ?>">
    <script src="<?= e(asset('js/theme.js')) ?>"></script>
    <style>
        .login-logo{display:block;width:96px;height:auto;margin:0 auto 2rem}
        .password-visibility-toggle:hover,.password-visibility-toggle:active{transform:translateY(-50%)!important;box-shadow:none;filter:none}
    </style>
</head>
<body class="text-white min-h-screen flex items-center justify-center p-4" style="background:radial-gradient(circle at 20% 15%,#14210f 0,#0a0d0a 45%,#050605 100%)">
    <button type="button" class="theme-toggle" data-theme-toggle aria-pressed="false" aria-label="Switch to light mode" title="Toggle color theme">
        <svg data-theme-icon="moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.985 12.486a9 9 0 1 1-9.473-9.472c.405-.022.617.46.402.803a6.5 6.5 0 0 0 8.268 8.268c.344-.215.825-.003.803.401"/></svg>
        <svg data-theme-icon="sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
    </button>
    <main class="w-full max-w-lg rounded-2xl px-12 pt-8 pb-12" style="background:#171c15;border:2px solid #dfee3a">
        <img src="<?= e(asset('images/logo-icon.png')) ?>" alt="Kulaza Pet Supplies" class="login-logo">
        <h1 class="mb-2 text-xl font-semibold">Choose a new password</h1>
        <p class="mb-6 text-sm text-gray-400">Choose a password with at least 8 characters.</p>
        <?php if ($errors->any()) { ?><div class="mb-5 rounded-lg border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300" role="alert"><?php foreach ($errors->all() as $error) { ?><p><?= e($error) ?></p><?php } ?></div><?php } ?>
        <form method="POST" action="<?= e(route('password.update')) ?>" class="space-y-5">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <div>
                <label for="email" class="mb-1 block text-sm text-gray-300">Recovery email</label>
                <input id="email" type="email" name="email" value="<?= e(old('email', $email)) ?>" autocomplete="email" class="w-full border-b border-gray-500 bg-transparent py-2.5 text-base outline-none focus:border-green-500" required>
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm text-gray-300">New password</label>
                <div class="relative">
                    <input id="password" type="password" name="password" autocomplete="new-password" minlength="8" class="w-full border-b border-gray-500 bg-transparent py-2.5 pr-12 text-base outline-none focus:border-green-500" required>
                    <button type="button" class="password-visibility-toggle absolute right-0 top-1/2 -translate-y-1/2 p-2 text-gray-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-green-500 rounded" data-toggle-password aria-label="Show password" aria-pressed="false">
                        <svg data-eye viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="hidden"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg data-eye-off viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a14.8 14.8 0 0 1-4 4.7"/><path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1 0 2-.2 2.8-.5"/></svg>
                    </button>
                </div>
            </div>
            <div>
                <label for="password_confirmation" class="mb-1 block text-sm text-gray-300">Confirm new password</label>
                <div class="relative">
                    <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" class="w-full border-b border-gray-500 bg-transparent py-2.5 pr-12 text-base outline-none focus:border-green-500" required>
                    <button type="button" class="password-visibility-toggle absolute right-0 top-1/2 -translate-y-1/2 p-2 text-gray-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-green-500 rounded" data-toggle-password aria-label="Show password confirmation" aria-pressed="false">
                        <svg data-eye viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="hidden"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg data-eye-off viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a14.8 14.8 0 0 1-4 4.7"/><path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1 0 2-.2 2.8-.5"/></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="block w-3/4 mx-auto rounded-lg bg-[#2e7d32] py-3 text-base font-semibold">Reset password</button>
        </form>
    </main>
<script>
    document.querySelectorAll('[data-toggle-password]').forEach(button => {
        button.addEventListener('click', () => {
            const input = button.parentElement.querySelector('input');
            const showing = input.type === 'password';
            input.type = showing ? 'text' : 'password';
            button.querySelector('[data-eye]').classList.toggle('hidden', !showing);
            button.querySelector('[data-eye-off]').classList.toggle('hidden', showing);
            button.setAttribute('aria-pressed', String(showing));
            button.setAttribute('aria-label', `${showing ? 'Hide' : 'Show'} ${input.name === 'password_confirmation' ? 'password confirmation' : 'password'}`);
        });
    });
</script>
</body>
</html>
