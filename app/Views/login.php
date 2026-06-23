<?php
use App\Core\I18n;

$isForgot = isset($_GET['forgot']);
$loginError = isset($_GET['error']) ? (string) $_GET['error'] : '';
$loginSuccess = isset($_GET['success']) ? (string) $_GET['success'] : '';

$loginErrorMessage = match ($loginError) {
    'login_failed' => I18n::get('login.invalid_credentials'),
    default => $loginError !== '' ? I18n::get('msg.generic_error') : '',
};

$loginSuccessMessage = match ($loginSuccess) {
    'password_reset_requested' => I18n::get('msg.password_reset_requested'),
    default => '',
};
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'de' ?>" class="antialiased h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>EasyTime | <?= I18n::get('login.welcome') ?></title>
    <link rel="icon" type="image/svg+xml" href="/assets/icons/urlaubsplaner_icon.svg">
    <script>
        window.easytimeTailwindConfig = {
            theme: {
                extend: {
                    colors: {
                        emerald: {
                            50: '#fffdf2',
                            100: '#fff7cc',
                            200: '#fff0a3',
                            300: '#ffe866',
                            400: '#FFD600',
                            500: '#E8007D',
                            600: '#4a4a4a',
                            700: '#2d2d2d',
                            800: '#1f1f1f',
                            900: '#1a1a1a',
                            950: '#111111'
                        },
                        lime: {
                            50: '#fff0f7',
                            100: '#ffd6eb',
                            200: '#ffadd8',
                            300: '#ff73bd',
                            400: '#E8007D',
                            500: '#c8006c',
                            600: '#a60059',
                            700: '#7d0044',
                            800: '#56002f',
                            900: '#33001c'
                        },
                        yellow: {
                            50: '#fffdf2',
                            100: '#fff7cc',
                            200: '#fff0a3',
                            300: '#ffe866',
                            400: '#FFD600',
                            500: '#e6c100',
                            600: '#b89600',
                            700: '#806900',
                            800: '#4d3f00',
                            900: '#1a1a1a'
                        },
                        green: {
                            50:  '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534'
                        },
                        red: {
                            50:  '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b'
                        },
                        orange: {
                            50:  '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412'
                        },
                        blue: {
                            50:  '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8'
                        },
                        pink: {
                            50: '#fff0f7',
                            100: '#ffd6eb',
                            200: '#ffadd8',
                            300: '#ff73bd',
                            400: '#f52b95',
                            500: '#E8007D',
                            600: '#c8006c'
                        }
                    }
                }
            }
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        window.tailwind.config = window.easytimeTailwindConfig;
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php include __DIR__ . '/partials/design-tokens.php'; ?>
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #fffdf2; color: #1a1a1a; }
    </style>
</head>
<body class="h-full bg-yellow-50 text-emerald-950 flex relative">
    <!-- Language Toggle -->
    <div class="absolute top-[max(1rem,env(safe-area-inset-top))] right-[max(1rem,env(safe-area-inset-right))] z-50 flex gap-2 bg-white/70 backdrop-blur px-3 py-1.5 rounded-full border border-yellow-200 shadow-sm">
        <a href="?lang=en" class="<?= ($_SESSION['lang'] ?? 'de') === 'en' ? 'font-bold text-lime-600' : 'text-emerald-600' ?>">EN</a>
        <span class="text-emerald-300">|</span>
        <a href="?lang=de" class="<?= ($_SESSION['lang'] ?? 'de') === 'de' ? 'font-bold text-lime-600' : 'text-emerald-600' ?>">DE</a>
    </div>

    <!-- Left Side: Image / Holiday Vibe -->
    <div class="hidden lg:flex w-1/2 bg-yellow-100 flex-col justify-center items-center relative overflow-hidden">
        <!-- Floating decor circles -->
        <div class="absolute top-[-5%] left-[-5%] w-96 h-96 bg-lime-200 rounded-full mix-blend-multiply opacity-50 blur-3xl"></div>
        <div class="absolute bottom-[-5%] right-[-5%] w-96 h-96 bg-yellow-300 rounded-full mix-blend-multiply opacity-50 blur-3xl"></div>
        
        <img src="/img/lemongrass.png" alt="Relaxing Lemongrass" class="absolute inset-0 w-full h-full object-cover z-10 opacity-25 grayscale mix-blend-multiply" />
        
        <div class="absolute inset-0 bg-yellow-400/25 z-20"></div>
        <div class="absolute inset-0 bg-lime-400/10 z-20 mix-blend-multiply"></div>
        
        <div class="relative z-30 p-12 text-center bg-white/60 backdrop-blur-md m-8 rounded-3xl border border-white/50 shadow-xl">
            <h1 class="text-4xl font-bold text-emerald-900 mb-4 tracking-tight"><?= I18n::get('login.title') ?></h1>
            <p class="text-emerald-800 text-lg font-medium"><?= I18n::get('login.subtitle') ?></p>
        </div>
    </div>

    <!-- Right Side: Login Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-4 sm:p-8 pt-16 sm:pt-8 pb-[max(1.5rem,env(safe-area-inset-bottom))] bg-yellow-50 relative min-h-screen">
        <div class="max-w-md w-full space-y-6 sm:space-y-8 bg-white p-6 sm:p-10 rounded-3xl shadow-xl shadow-lime-900/5 relative z-10 border border-yellow-100/50">
            <div class="text-center">
                <img src="/assets/icons/urlaubsplaner_icon.svg" alt="Urlaubsplaner" class="mx-auto mb-6 h-16 w-16 rounded-2xl shadow-lg shadow-lime-400/30">
                <h2 class="text-3xl font-bold text-emerald-900 tracking-tight">Easy<span class="text-lime-600">Time</span></h2>
                <?php if ($isForgot): ?>
                    <p class="mt-2 text-emerald-600"><?= I18n::get('reset.title') ?></p>
                <?php else: ?>
                    <p class="mt-2 text-emerald-600"><?= I18n::get('login.welcome') ?></p>
                <?php endif; ?>
            </div>

            <?php if ($loginErrorMessage !== ''): ?>
                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium leading-snug text-red-800" role="alert">
                    <?= htmlspecialchars($loginErrorMessage) ?>
                </div>
            <?php endif; ?>

            <?php if ($loginSuccessMessage !== ''): ?>
                <div class="mt-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium leading-snug text-green-800" role="status">
                    <?= htmlspecialchars($loginSuccessMessage) ?>
                </div>
            <?php endif; ?>

            <?php if ($isForgot): ?>
                <!-- FORGOT PASSWORD FORM -->
                <form class="mt-8 space-y-6" action="/?action=forgot_password" method="POST">
                    <div>
                        <p class="text-sm text-emerald-600 mb-4"><?= I18n::get('reset.info') ?></p>
                        <label for="login" class="block text-sm font-semibold text-emerald-800 mb-1.5"><?= I18n::get('reset.login') ?></label>
                        <input id="login" name="login" type="text" pattern="[a-zA-Z0-9@.\-_]+" required class="appearance-none relative block w-full px-4 py-3 border border-yellow-200 bg-yellow-50/50 text-emerald-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-lime-400 transition-all font-medium">
                    </div>
                    <div class="flex flex-col gap-3">
                        <button type="submit" class="et-btn-primary w-full flex justify-center py-3.5 px-4 border border-transparent text-sm font-bold rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-lime-500 transition-all shadow-lg shadow-lime-400/40">
                            <?= I18n::get('reset.send') ?>
                        </button>
                        <a href="/" class="text-center text-sm font-medium text-emerald-600 hover:text-lime-600 transition-colors">
                            <?= I18n::get('reset.back') ?>
                        </a>
                    </div>
                </form>
            <?php else: ?>
                <!-- LOGIN FORM -->
                <form class="mt-8 space-y-6" action="/?action=login" method="POST">
                    <div class="space-y-4">
                        <div>
                            <label for="login" class="block text-sm font-semibold text-emerald-800 mb-1.5"><?= I18n::get('login.email_mnr') ?></label>
                            <input id="login" name="login" type="text" pattern="[a-zA-Z0-9@.\-_]+" required class="appearance-none relative block w-full px-4 py-3 border border-yellow-200 bg-yellow-50/50 text-emerald-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-lime-400 transition-all font-medium">
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-semibold text-emerald-800 mb-1.5"><?= I18n::get('login.password') ?></label>
                            <div class="relative">
                                <input id="password" name="password" type="password" required class="appearance-none relative block w-full px-4 py-3 border border-yellow-200 bg-yellow-50/50 text-emerald-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-lime-400 transition-all font-medium">
                                <button type="button" onclick="togglePw('password')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-emerald-500 hover:text-lime-600">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-lime-500 border-yellow-300 rounded focus:ring-lime-400">
                            <label for="remember-me" class="ml-2 block text-sm font-medium text-emerald-700"><?= I18n::get('login.remember_me') ?></label>
                        </div>
                        <div class="text-sm">
                            <a href="?forgot=1" class="font-bold text-lime-600 hover:text-lime-500"><?= I18n::get('login.forgot_password') ?></a>
                        </div>
                    </div>
    
                    <div>
                        <button type="submit" class="et-btn-primary w-full flex justify-center py-3.5 px-4 border border-transparent text-sm font-bold rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-lime-500 transition-all shadow-lg shadow-lime-400/40">
                            <?= I18n::get('login.sign_in') ?>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function togglePw(id) {
            const input = document.getElementById(id);
            if (input.type === 'password') {
                input.type = 'text';
            } else {
                input.type = 'password';
            }
        }

        (function cleanLoginQueryParams() {
            const params = new URLSearchParams(window.location.search);
            if (!params.has('error') && !params.has('success')) return;
            params.delete('error');
            params.delete('success');
            const query = params.toString();
            const nextUrl = window.location.pathname + (query ? '?' + query : '') + window.location.hash;
            window.history.replaceState({}, '', nextUrl);
        })();
    </script>
</body>
</html>
