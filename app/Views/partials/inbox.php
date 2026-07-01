<?php
use App\Core\I18n;
use App\Services\Inbox;

/** @var string $inboxFilter */
/** @var array<string, int> $inboxCounts */
/** @var int $highlightNotificationId */

$highlightNotificationId = (int) ($highlightNotificationId ?? 0);

$categoryLabels = [
    'approval' => 'Aufgabe',
    'password' => 'Passwort',
    'success'  => 'Erfolg',
    'rejected' => 'Abgelehnt',
    'info'     => 'Info',
    'task'     => 'Aufgabe',
];
$categoryColors = [
    'approval' => 'bg-orange-100 text-orange-900 border-orange-200',
    'password' => 'bg-[#fff8fc] text-[#E8007D] border-[#E8007D]/25',
    'success'  => 'bg-green-100 text-green-800 border-green-200',
    'rejected' => 'bg-red-100 text-red-800 border-red-200',
    'info'     => 'bg-lime-50 text-emerald-800 border-lime-200',
    'task'     => 'bg-orange-100 text-orange-900 border-orange-200',
];
$filterLabels = [
    'all'      => I18n::get('inbox.filter.all'),
    'unread'   => I18n::get('inbox.filter.unread'),
    'tasks'    => I18n::get('inbox.filter.tasks'),
    'password' => I18n::get('inbox.filter.password'),
    'approval' => I18n::get('inbox.filter.approval'),
    'info'     => I18n::get('inbox.filter.info'),
    'done'     => I18n::get('inbox.filter.done'),
];
$currentFilterLabel = $filterLabels[$inboxFilter] ?? $filterLabels['all'];
$currentFilterCount = (int) ($inboxCounts[$inboxFilter] ?? count($notificationList ?? []));
?>
<div class="w-full max-w-none" data-tour="inbox-content">
    <?php
        $isAdmin = in_array($currentRole ?? '', ['CEO', 'Admin'], true);
        include __DIR__ . '/mobile-inbox-filters.php';
    ?>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 mb-2"><?= I18n::get('inbox.title') ?></h2>
            <p class="text-sm text-emerald-600/80 leading-relaxed"><?= I18n::get('inbox.subtitle') ?></p>
        </div>
        <div class="rounded-2xl border border-lime-200 bg-white px-4 py-2.5 text-sm shadow-sm">
            <span class="font-bold text-emerald-900"><?= htmlspecialchars($currentFilterLabel) ?></span>
            <span class="text-emerald-600"> · <?= $currentFilterCount ?></span>
        </div>
    </div>

    <div class="bg-white rounded-3xl border border-lime-100 shadow-xl p-6 sm:p-7 space-y-4">
        <?php foreach (($notificationList ?? []) as $note): ?>
            <?php
                $cat = (string) ($note['category'] ?? 'info');
                $type = (string) ($note['type'] ?? 'info');
                $badgeClass = $categoryColors[$cat] ?? $categoryColors['info'];
                $badgeLabel = $categoryLabels[$cat] ?? ucfirst($cat);
                $isUnread = (int) ($note['is_read'] ?? 0) === 0;
                $isTask = $type === 'task';
                $isResolved = (int) ($note['is_resolved'] ?? 0) === 1;
                $isOpenTask = $isTask && !$isResolved;
                $isShared = Inbox::isShared($note);
                $actionUrl = trim((string) ($note['action_url'] ?? ''));
                $resolvedByName = trim((string) ($note['resolved_by_name'] ?? ''));
                $cardClass = $isOpenTask
                    ? 'border-[#E8007D]/25 bg-[#fff8fc]/50'
                    : ($isUnread ? 'border-lime-200 bg-[#fffdf2]/40' : 'border-lime-100 bg-white');
                $isHighlighted = $highlightNotificationId > 0 && (int) ($note['id'] ?? 0) === $highlightNotificationId;
                if ($isHighlighted) {
                    $cardClass .= ' ring-2 ring-[#E8007D]/40 shadow-md';
                }
            ?>
            <article id="notification-<?= (int) $note['id'] ?>" class="rounded-2xl border p-5 sm:p-6 transition-shadow hover:shadow-sm <?= $cardClass ?>">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <?php if ($isUnread && !$isResolved): ?>
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-[#E8007D]" title="<?= I18n::get('inbox.filter.unread') ?>"></span>
                        <?php endif; ?>
                        <h3 class="text-base font-bold text-emerald-900 leading-tight"><?= htmlspecialchars((string) $note['title']) ?></h3>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <?php if ($isTask): ?>
                            <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold border <?= $isShared ? 'bg-blue-50 text-blue-800 border-blue-200' : 'bg-purple-50 text-purple-800 border-purple-200' ?>">
                                <?= $isShared ? I18n::get('inbox.mode.shared') : I18n::get('inbox.mode.individual') ?>
                            </span>
                            <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold border <?= $isResolved ? 'bg-green-100 text-green-800 border-green-200' : 'bg-orange-100 text-orange-900 border-orange-200' ?>">
                                <?= $isResolved ? I18n::get('inbox.resolved') : I18n::get('inbox.open_task') ?>
                            </span>
                        <?php endif; ?>
                        <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold border <?= $badgeClass ?>"><?= htmlspecialchars($badgeLabel) ?></span>
                    </div>
                </div>

                <p class="text-sm text-emerald-800 leading-relaxed"><?= htmlspecialchars((string) $note['message']) ?></p>
                <?php if ($isTask && $isOpenTask): ?>
                    <p class="text-xs text-emerald-600 mt-2 leading-relaxed">
                        <?= $isShared ? I18n::get('inbox.mode.shared_hint') : I18n::get('inbox.mode.individual_hint') ?>
                    </p>
                <?php endif; ?>

                <?php if ($isResolved && $resolvedByName !== ''): ?>
                    <p class="text-xs text-emerald-600 mt-2">
                        <?= I18n::get('inbox.resolved_by') ?>:
                        <span class="font-bold"><?= htmlspecialchars($resolvedByName) ?></span>
                        <?php if (!empty($note['resolved_at'])): ?>
                            · <?= htmlspecialchars((string) $note['resolved_at']) ?>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>

                <div class="mt-4 flex flex-wrap items-center gap-2 pt-4 border-t border-lime-100">
                    <?php if ($isOpenTask && $actionUrl !== ''): ?>
                        <a href="<?= htmlspecialchars($actionUrl) ?>" class="et-btn-primary inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold">
                            <?= I18n::get('inbox.open_action') ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($isOpenTask): ?>
                        <form method="POST" action="/?action=resolve_notification" class="inline">
                            <input type="hidden" name="notification_id" value="<?= (int) $note['id'] ?>">
                            <input type="hidden" name="inbox_filter" value="<?= htmlspecialchars($inboxFilter) ?>">
                            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold border border-lime-200 text-emerald-800 hover:bg-lime-50/50 transition-colors">
                                <?= I18n::get('inbox.mark_done') ?>
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($isUnread): ?>
                        <form method="POST" action="/?action=mark_notification_read" class="inline">
                            <input type="hidden" name="notification_id" value="<?= (int) $note['id'] ?>">
                            <input type="hidden" name="inbox_filter" value="<?= htmlspecialchars($inboxFilter) ?>">
                            <button type="submit" class="et-btn-secondary inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold">
                                <?= I18n::get('inbox.mark_read') ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <time class="block text-[11px] text-emerald-500 mt-3 font-medium uppercase tracking-wider"><?= htmlspecialchars((string) ($note['created_at'] ?? '')) ?></time>
            </article>
        <?php endforeach; ?>

        <?php if (empty($notificationList)): ?>
            <div class="relative overflow-hidden py-14 text-center">
                <div class="pointer-events-none absolute -right-6 top-0 h-28 w-28 rounded-full bg-lime-100/80 blur-2xl" aria-hidden="true"></div>
                <div class="relative mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-lime-50 to-emerald-50 text-emerald-500 shadow-inner">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                </div>
                <p class="relative text-base font-bold text-emerald-900">
                    <?= ($inboxFilter ?? 'all') === 'all' ? I18n::get('inbox.empty_title') : I18n::get('inbox.empty_filter') ?>
                </p>
                <p class="relative text-sm text-emerald-600/80 mt-2"><?= I18n::get('inbox.empty_desc') ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php if ($highlightNotificationId > 0): ?>
<script>
document.getElementById('notification-<?= (int) $highlightNotificationId ?>')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
</script>
<?php endif; ?>
