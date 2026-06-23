<?php
use App\Core\I18n;

/** @var string $inboxFilter */
/** @var array<string, int> $inboxCounts */
/** @var bool $isAdmin */

$tabs = [
    ['key' => 'all', 'label' => I18n::get('inbox.filter.all')],
    ['key' => 'unread', 'label' => I18n::get('inbox.filter.unread')],
];

if ($isAdmin) {
    $tabs[] = ['key' => 'tasks', 'label' => I18n::get('inbox.filter.tasks')];
    $tabs[] = ['key' => 'password', 'label' => I18n::get('inbox.filter.password')];
    $tabs[] = ['key' => 'approval', 'label' => I18n::get('inbox.filter.approval')];
}

$tabs[] = ['key' => 'info', 'label' => I18n::get('inbox.filter.info')];
$tabs[] = ['key' => 'done', 'label' => I18n::get('inbox.filter.done')];
?>
<div class="mb-6">
    <div class="flex flex-wrap items-center gap-2 max-lg:overflow-x-auto max-lg:pb-1 max-lg:scrollbar-none">
        <?php foreach ($tabs as $tab): ?>
            <?php
                $isActive = $inboxFilter === $tab['key'];
                $count = (int) ($inboxCounts[$tab['key']] ?? 0);
            ?>
            <a
                href="/?tab=inbox&inbox_filter=<?= urlencode($tab['key']) ?>"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-3 py-2 text-xs font-bold transition-colors <?= $isActive ? 'border-[var(--et-accent)] bg-[var(--et-accent)] text-[var(--et-accent-text)]' : 'border-lime-200 bg-white text-emerald-800 hover:border-lime-300' ?>"
                aria-current="<?= $isActive ? 'page' : 'false' ?>"
            >
                <?= htmlspecialchars($tab['label']) ?>
                <?php if ($count > 0): ?>
                    <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[10px] font-bold <?= $isActive ? 'bg-white/25 text-white' : 'bg-lime-100 text-emerald-800' ?>">
                        <?= $count >= 10 ? '9+' : $count ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
