<?php
use App\Core\I18n;

/** @var string $currentRole */
/** @var string $activeTab */
/** @var int $notificationUnreadCount */

$isAdmin = in_array($currentRole ?? '', ['CEO', 'Admin'], true);
$activeTab = $activeTab ?? ($isAdmin ? 'operations' : 'calendar');
$unreadCount = (int) ($notificationUnreadCount ?? 0);
$inboxBadgeLabel = $unreadCount >= 10 ? '9+' : (string) $unreadCount;
$inboxAriaLabel = $unreadCount > 0
    ? I18n::get('nav.inbox') . ' (' . $inboxBadgeLabel . ' ' . I18n::get('nav.mobile_unread') . ')'
    : I18n::get('nav.inbox');

$navItemClass = static function (bool $active): string {
    $base = 'et-mobile-nav__item flex flex-1 flex-col items-center justify-center gap-0.5 min-w-0 px-1 py-2 rounded-xl transition-colors';
    return $active
        ? $base . ' et-mobile-nav__item--active text-[var(--et-accent)]'
        : $base . ' text-emerald-600 hover:text-emerald-900 hover:bg-lime-50/80';
};

$renderInboxNav = static function (bool $isActive) use ($navItemClass, $unreadCount, $inboxBadgeLabel, $inboxAriaLabel): void {
    ?>
    <a
        href="/?tab=inbox"
        data-tour="nav-inbox"
        class="<?= $navItemClass($isActive) ?> relative"
        aria-current="<?= $isActive ? 'page' : 'false' ?>"
        aria-label="<?= htmlspecialchars($inboxAriaLabel) ?>"
    >
        <span class="relative inline-flex">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            <?php if ($unreadCount > 0): ?>
                <span class="absolute -top-1.5 -right-2 flex h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-[var(--et-accent)] px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white">
                    <?= htmlspecialchars($inboxBadgeLabel) ?>
                </span>
            <?php endif; ?>
        </span>
        <span class="truncate text-[10px] font-bold leading-tight">
            <?= I18n::get('nav.inbox') ?><?php if ($unreadCount > 0): ?><span class="text-[var(--et-accent)]"> · <?= htmlspecialchars($inboxBadgeLabel) ?></span><?php endif; ?>
        </span>
    </a>
    <?php
};
?>
<nav class="et-mobile-nav lg:hidden shrink-0 w-full max-w-full border-t border-lime-200/80 bg-white/95 backdrop-blur-lg shadow-[0_-4px_24px_rgba(26,26,26,0.08)]" aria-label="<?= htmlspecialchars(I18n::get('nav.mobile_main')) ?>">
    <div class="flex w-full max-w-full items-stretch gap-0 px-1 pt-1 pb-[max(0.5rem,env(safe-area-inset-bottom))]">
        <?php if ($isAdmin): ?>
            <a href="/?tab=operations" data-tour="nav-operations" class="<?= $navItemClass($activeTab === 'operations') ?>" aria-current="<?= $activeTab === 'operations' ? 'page' : 'false' ?>">
                <?= easytime_nav_icon('calendar') ?>
                <span class="truncate text-[10px] font-bold leading-tight"><?= I18n::get('nav.mobile_ops') ?></span>
            </a>
            <a href="/?tab=history" data-tour="nav-history" class="<?= $navItemClass($activeTab === 'history') ?>" aria-current="<?= $activeTab === 'history' ? 'page' : 'false' ?>">
                <?= easytime_nav_icon('history') ?>
                <span class="truncate text-[10px] font-bold leading-tight"><?= I18n::get('ceo.nav_history') ?></span>
            </a>
            <?php $renderInboxNav($activeTab === 'inbox'); ?>
            <a href="/?tab=team" data-tour="nav-team" class="<?= $navItemClass($activeTab === 'team') ?>" aria-current="<?= $activeTab === 'team' ? 'page' : 'false' ?>">
                <?= easytime_nav_icon('team') ?>
                <span class="truncate text-[10px] font-bold leading-tight"><?= I18n::get('nav.mobile_team') ?></span>
            </a>
            <a href="/?tab=settings" data-tour="nav-settings" class="<?= $navItemClass($activeTab === 'settings') ?>" aria-current="<?= $activeTab === 'settings' ? 'page' : 'false' ?>">
                <?= easytime_nav_icon('settings') ?>
                <span class="truncate text-[10px] font-bold leading-tight"><?= I18n::get('nav.mobile_settings') ?></span>
            </a>
        <?php else: ?>
            <a href="/?tab=calendar" data-tour="nav-calendar" class="<?= $navItemClass($activeTab === 'calendar') ?>" aria-current="<?= $activeTab === 'calendar' ? 'page' : 'false' ?>">
                <?= easytime_nav_icon('calendar') ?>
                <span class="truncate text-[10px] font-bold leading-tight"><?= I18n::get('emp.calendar') ?></span>
            </a>
            <a href="/?tab=history" data-tour="nav-history" class="<?= $navItemClass($activeTab === 'history') ?>" aria-current="<?= $activeTab === 'history' ? 'page' : 'false' ?>">
                <?= easytime_nav_icon('history') ?>
                <span class="truncate text-[10px] font-bold leading-tight"><?= I18n::get('history.title') ?></span>
            </a>
            <?php $renderInboxNav($activeTab === 'inbox'); ?>
        <?php endif; ?>
    </div>
</nav>
