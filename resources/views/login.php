<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Kulaza Pet Supplies</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= e(asset('css/theme.css')) ?>">
    <script src="<?= e(asset('js/theme.js')) ?>"></script>
    <style>
        input::-ms-reveal,input::-ms-clear{display:none}
        .login-input{background-color:transparent!important;padding-left:.5rem}
        .login-input:-webkit-autofill,.login-input:-webkit-autofill:hover,.login-input:-webkit-autofill:focus{-webkit-box-shadow:0 0 0 1000px #171c15 inset;-webkit-text-fill-color:#fff;caret-color:#fff}
        html.light-mode .login-input:-webkit-autofill,html.light-mode .login-input:-webkit-autofill:hover,html.light-mode .login-input:-webkit-autofill:focus{-webkit-box-shadow:0 0 0 1000px #fff inset;-webkit-text-fill-color:#202a20;caret-color:#202a20}
        .login-logo{display:block;width:96px;height:auto;margin:0 auto 2rem}
        .password-visibility-toggle:hover,.password-visibility-toggle:active{transform:translateY(-50%)!important;box-shadow:none;filter:none}
    </style>
</head>
<body class="auth-scene-page login-page text-white min-h-screen flex items-center justify-center p-4" style="background:radial-gradient(circle at 20% 15%,#14210f 0,#0a0d0a 45%,#050605 100%)">
    <button type="button" class="theme-toggle" data-theme-toggle aria-pressed="false" aria-label="Switch to light mode" title="Toggle color theme">
        <svg data-theme-icon="moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.985 12.486a9 9 0 1 1-9.473-9.472c.405-.022.617.46.402.803a6.5 6.5 0 0 0 8.268 8.268c.344-.215.825-.003.803.401"/></svg>
        <svg data-theme-icon="sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
    </button>
    <form method="POST" action="<?= e(route('login.store')) ?>" class="w-full max-w-lg rounded-2xl px-12 pt-8 pb-12" style="background:#171c15;border:2px solid #dfee3a">
        <?= csrf_field() ?>
        <img src="<?= e(asset('images/logo-icon.png')) ?>" alt="Kulaza Pet Supplies" class="login-logo">
        <?php if (session('status')): ?><div class="mb-5 rounded-lg border border-green-800 bg-green-950/40 px-4 py-3 text-sm text-green-300" role="status"><?= e(session('status')) ?></div><?php endif; ?>
        <?php if ($errors->any()): ?><div class="mb-5 rounded-lg border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300" role="alert"><?php foreach ($errors->all() as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div><?php endif; ?>
        <label for="username" class="block text-sm text-gray-300 mb-1">Username/Email</label>
        <input id="username" type="text" name="username" value="<?= e(old('username')) ?>" autocomplete="username" class="login-input w-full bg-transparent border-b border-gray-500 focus:border-green-500 mb-8 py-2.5 text-base outline-none" required autofocus>
        <label for="password" class="block text-sm text-gray-300 mb-1">Password</label>
        <div class="relative mb-10">
            <input id="password" type="password" name="password" class="login-input w-full bg-transparent border-b border-gray-500 focus:border-green-500 py-2.5 pr-10 text-base outline-none">
            <button type="button" id="togglePw" aria-label="Show password" aria-pressed="false" class="password-visibility-toggle absolute right-0 top-1/2 -translate-y-1/2 p-1"></button>
        </div>
        
        <button type="submit" class="login-submit block w-3/4 mx-auto text-center bg-[#2e7d32] rounded-lg py-3 text-base font-semibold text-white">Log in</button>
        <a href="<?= e(route('password.request')) ?>" class="mt-4 block text-center text-sm text-gray-400 hover:text-white">Forgot password?</a>
    </form>
<script>
        // White show/hide password icon
        const svg = p => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + p + '</svg>';
        const EYE = svg('<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>');
        const EYE_OFF = svg('<path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/>');
        const pw = document.getElementById('password'), tg = document.getElementById('togglePw');
        tg.innerHTML = EYE_OFF;
        tg.onclick = () => { const show = pw.type === 'password'; pw.type = show ? 'text' : 'password'; tg.innerHTML = show ? EYE : EYE_OFF; tg.setAttribute('aria-label', show ? 'Hide password' : 'Show password'); tg.setAttribute('aria-pressed', String(show)); };
    </script>
</body>
</html>
