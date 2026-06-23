<?php
use App\Core\I18n;

/** @var string $inboxFilter */
/** @var array<string, int> $inboxCounts */
/** @var bool $isAdmin */
/** @var string $sidebarTabClass */
/** @var string $sidebarTabActive */
/** @var string $sidebarTabIdle */

$backTab = $isAdmin ? 'operations' : 'calendar';
$backLabel = (($_SESSION['lang'] ?? 'de') === 'de') ? 'Zur App' : 'Back to app';

$tabs = [
    ['key' => 'all', 'label' => I18n::get('inbox.filter.all'), 'icon' => 'inbox-all'],
    ['key' => 'unread', 'label' => I18n::get('inbox.filter.unread'), 'icon' => 'inbox-unread'],
];

if ($isAdmin) {
    $tabs[] = ['key' => 'tasks', 'label' => I18n::get('inbox.filter.tasks'), 'icon' => 'inbox-tasks'];
    $tabs[] = ['key' => 'password', 'label' => I18n::get('inbox.filter.password'), 'icon' => 'inbox-password'];
    $tabs[] = ['key' => 'approval', 'label' => I18n::get('inbox.filter.approval'), 'icon' => 'inbox-approval'];
}

$tabs[] = ['key' => 'info', 'label' => I18n::get('inbox.filter.info'), 'icon' => 'inbox-info'];
$tabs[] = ['key' => 'done', 'label' => I18n::get('inbox.filter.done'), 'icon' => 'inbox-done'];

$renderCount = static function (string $key) use ($inboxCounts): string {
    $count = (int) ($inboxCounts[$key] ?? 0);
    if ($count <= 0) {
        return '';
    }
    $label = $count >= 10 ? '9+' : (string) $count;
    return '<span class="sidebar-badge ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-lime-500 px-1.5 text-[10px] font-bold text-white">' . htmlspecialchars($label) . '</span>';
};
?>
<nav class="flex flex-wrap gap-2 max-lg:flex-row lg:flex-col" aria-label="Inbox Navigation">
    <a href="/?tab=<?= htmlspecialchars($backTab) ?>" class="<?= $sidebarTabClass ?> <?= $sidebarTabIdle ?> border-dashed" title="<?= htmlspecialchars($backLabel) ?>">
        <?= easytime_nav_icon('back') ?>
        <span class="sidebar-label flex-1"><?= htmlspecialchars($backLabel) ?></span>
    </a>

    <div class="hidden lg:block px-1 pt-1 w-full">
        <p class="sidebar-section-title text-[11px] font-bold uppercase tracking-wider text-emerald-500"><?= I18n::get('inbox.sidebar_title') ?></p>
    </div>

    <?php foreach ($tabs as $tab): ?>
        <?php $isActive = $inboxFilter === $tab['key']; ?>
        <a
            href="/?tab=inbox&inbox_filter=<?= urlencode($tab['key']) ?>"
            class="<?= $sidebarTabClass ?> <?= $isActive ? $sidebarTabActive : $sidebarTabIdle ?>"
            aria-current="<?= $isActive ? 'page' : 'false' ?>"
            title="<?= htmlspecialchars($tab['label']) ?>"
        >
            <?= easytime_nav_icon($tab['icon']) ?>
            <span class="sidebar-label flex-1"><?= htmlspecialchars($tab['label']) ?></span>
            <?= $renderCount($tab['key']) ?>
        </a>
    <?php endforeach; ?>
</nav>
