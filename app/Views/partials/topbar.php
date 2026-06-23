<?php
use App\Core\I18n;

/** @var array $currentUser */
/** @var string $currentRole */
/** @var string $activeTab */
/** @var int $notificationUnreadCount */

$unreadCount = (int) ($notificationUnreadCount ?? 0);
$inboxBadgeLabel = $unreadCount >= 10 ? '9+' : (string) $unreadCount;
$inboxBadgeHtml = $unreadCount > 0
    ? '<span class="absolute -top-1.5 -right-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-lime-500 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white">' . htmlspecialchars($inboxBadgeLabel) . '</span>'
    : '';
$inboxBtnClass = 'relative flex h-10 w-10 items-center justify-center rounded-xl border transition-colors '
    . ($activeTab === 'inbox'
        ? 'border-[var(--et-accent)] bg-[var(--et-accent)] text-[var(--et-accent-text)] shadow-sm'
        : 'border-lime-200 bg-white text-emerald-700 hover:border-lime-300 hover:bg-[#fff8fc]');
$inboxButtonHtml = '<a href="/?tab=inbox" class="' . $inboxBtnClass . '" aria-label="' . htmlspecialchars(I18n::get('nav.inbox')) . '">'
    . '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>'
    . $inboxBadgeHtml
    . '</a>';

$userInitial = strtoupper(substr((string) ($currentUser['firstname'] ?? 'U'), 0, 1));
$userFullName = htmlspecialchars(trim(($currentUser['firstname'] ?? '') . ' ' . ($currentUser['lastname'] ?? '')));
$userEmail = htmlspecialchars((string) ($currentUser['email'] ?? ''));
$userFirstName = htmlspecialchars((string) ($currentUser['firstname'] ?? 'Account'));
$isAdmin = in_array($currentRole ?? '', ['CEO', 'Admin'], true);
$currentLang = $_SESSION['lang'] ?? 'de';
?>
<header class="easytime-topbar sticky top-0 z-50 flex h-[4.5rem] shrink-0 items-center justify-between gap-4 overflow-visible border-b border-lime-200/70 bg-white/95 px-4 shadow-sm backdrop-blur-lg sm:px-6 lg:px-8">
    <a href="<?= $isAdmin ? '/?tab=operations' : '/?tab=calendar' ?>" class="flex min-w-0 items-center gap-3">
        <img src="/assets/icons/urlaubsplaner_icon.svg" alt="EasyTime" class="h-10 w-10 shrink-0 rounded-xl shadow-md shadow-lime-400/20">
        <div class="min-w-0">
            <span class="block truncate text-xl font-bold tracking-tight text-emerald-900 sm:text-2xl">Easy<span class="text-lime-600">Time</span></span>
            <span class="hidden text-[10px] font-bold uppercase tracking-wider text-emerald-600/70 sm:block"><?= $isAdmin ? 'Admin Dashboard' : 'Dashboard' ?></span>
        </div>
    </a>

    <div class="flex shrink-0 items-center gap-2 overflow-visible sm:gap-3">
        <div class="flex items-center gap-1 rounded-xl border border-lime-200 bg-white px-2.5 py-1.5 text-sm">
            <a href="?lang=en" class="<?= $currentLang === 'en' ? 'font-bold text-lime-600' : 'text-emerald-600 hover:text-emerald-900' ?>">EN</a>
            <span class="text-emerald-300">|</span>
            <a href="?lang=de" class="<?= $currentLang === 'de' ? 'font-bold text-lime-600' : 'text-emerald-600 hover:text-emerald-900' ?>">DE</a>
        </div>

        <?= easytime_tooltip(I18n::get('nav.inbox'), $inboxButtonHtml, 'inline-flex', 'bottom') ?>

        <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
            <button
                type="button"
                class="flex items-center gap-2 rounded-full border border-lime-200 bg-white py-1 pl-1 pr-3 text-left transition-colors hover:border-lime-300 hover:bg-lime-50"
                :class="open ? 'border-lime-400 ring-2 ring-lime-200' : ''"
                @click="open = !open"
                aria-haspopup="true"
                :aria-expanded="open.toString()"
            >
                <span class="et-avatar flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold uppercase shadow-inner"><?= $userInitial ?></span>
                <span class="hidden max-w-[8rem] truncate text-sm font-bold text-emerald-900 sm:block"><?= $userFirstName ?></span>
                <svg class="h-4 w-4 text-emerald-500 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                @click.outside="open = false"
                class="absolute right-0 top-[calc(100%+0.5rem)] z-50 w-72 overflow-hidden rounded-2xl border border-lime-200 bg-white shadow-xl"
                x-cloak
            >
                <div class="border-b border-yellow-100 bg-yellow-50/80 px-4 py-4">
                    <div class="flex items-center gap-3">
                        <span class="et-avatar flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-base font-bold uppercase"><?= $userInitial ?></span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-emerald-900"><?= $userFullName ?></p>
                            <p class="truncate text-xs text-emerald-700"><?= $userEmail ?></p>
                        </div>
                    </div>
                </div>
                <div class="p-2">
                    <a
                        href="/?action=logout"
                        class="flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-bold text-red-600 transition-colors hover:bg-red-50"
                    >
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <?= I18n::get('nav.logout') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
