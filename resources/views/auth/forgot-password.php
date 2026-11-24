<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - Kulaza Pet Supplies</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= e(asset('css/theme.css')) ?>">
    <script src="<?= e(asset('js/theme.js')) ?>"></script>
    <style>.login-logo{display:block;width:96px;height:auto;margin:0 auto 2rem}</style>
</head>
<body class="auth-scene-page forgot-password-page text-white min-h-screen flex items-center justify-center p-4" style="background:radial-gradient(circle at 20% 15%,#14210f 0,#0a0d0a 45%,#050605 100%)">
    <button type="button" class="theme-toggle" data-theme-toggle aria-pressed="false" aria-label="Switch to light mode" title="Toggle color theme">
        <svg data-theme-icon="moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.985 12.486a9 9 0 1 1-9.473-9.472c.405-.022.617.46.402.803a6.5 6.5 0 0 0 8.268 8.268c.344-.215.825-.003.803.401"/></svg>
        <svg data-theme-icon="sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
    </button>
    <main class="w-full max-w-lg rounded-2xl px-12 pt-8 pb-12" style="background:#171c15;border:2px solid #dfee3a">
        <img src="<?= e(asset('images/logo-icon.png')) ?>" alt="Kulaza Pet Supplies" class="login-logo">
        <h1 class="mb-2 text-xl font-semibold">Reset your password</h1>
        <p class="mb-6 text-sm text-gray-400">Enter the Gmail address linked to your account. If it matches an account, we’ll send a password reset link.</p>
        <?php if (session('status')): ?><div class="mb-5 rounded-lg border border-green-800 bg-green-950/40 px-4 py-3 text-sm text-green-300" role="status"><?= e(session('status')) ?></div><?php endif; ?>
        <?php if ($errors->any()): ?><div class="mb-5 rounded-lg border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300" role="alert"><?php foreach ($errors->all() as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div><?php endif; ?>
        <form method="POST" action="<?= e(route('password.email')) ?>">
            <?= csrf_field() ?>
            <label for="email" class="mb-1 block text-sm text-gray-300">Gmail address</label>
            <input id="email" type="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" class="mb-8 w-full border-b border-gray-500 bg-transparent py-2.5 text-base outline-none focus:border-green-500" required autofocus>
            <button type="submit" class="block w-3/4 mx-auto rounded-lg bg-[#2e7d32] py-3 text-base font-semibold">Send reset link</button>
        </form>
        <a href="<?= e(route('login')) ?>" class="mt-4 block text-center text-sm text-gray-400 hover:text-white">Back to log in</a>
    </main>
</body>
</html>
