<?php
use App\Core\I18n;

/** @var list<array<string, mixed>> $requests */

$today = date('Y-m-d');

$statusLabels = [
    'pending'          => I18n::get('emp.status_pending'),
    'storno_requested' => I18n::get('emp.status_storno_requested'),
    'approved'         => I18n::get('emp.status_approved'),
];

$statusBadgeClasses = [
    'pending'          => 'bg-amber-100 text-amber-900 border-amber-200',
    'storno_requested' => 'bg-orange-100 text-orange-900 border-orange-300',
    'approved'         => 'bg-green-100 text-green-800 border-green-200',
];

$openRequests = array_values(array_filter(
    $requests,
    static fn (array $req): bool => in_array($req['status'] ?? '', ['pending', 'storno_requested'], true)
));

$plannedRequests = array_values(array_filter(
    $requests,
    static fn (array $req): bool => ($req['status'] ?? '') === 'approved'
        && (string) ($req['end_date'] ?? '') >= $today
));

$sortByStartDesc = static function (array $a, array $b): int {
    return strcmp((string) ($b['start_date'] ?? ''), (string) ($a['start_date'] ?? ''));
};

usort($openRequests, $sortByStartDesc);
usort($plannedRequests, $sortByStartDesc);

$sectionOpen = I18n::get('history.section.open');
$sectionPlanned = I18n::get('history.section.planned');

$renderCard = static function (array $req, string $borderClass) use ($statusLabels, $statusBadgeClasses): void {
    $status = (string) ($req['status'] ?? '');
    $isStorno = $status === 'storno_requested';
    $isPlanned = $status === 'approved';
    $statusLabel = $statusLabels[$status] ?? $status;
    $badgeClass = $statusBadgeClasses[$status] ?? 'bg-lime-50 text-emerald-800 border-lime-200';
    $rid = (int) ($req['id'] ?? 0);
    ?>
    <article
        id="employee-open-request-<?= $rid ?>"
        data-request-id="<?= $rid ?>"
        class="bg-white rounded-3xl p-6 sm:p-7 shadow-xl border <?= $borderClass ?> flex flex-col"
    >
        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
            <div class="space-y-2">
                <div class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500">Antrag #<?= $rid ?></div>
                <span class="inline-flex px-3 py-1.5 rounded-full text-sm font-bold border <?= $badgeClass ?>">
                    <?= htmlspecialchars($statusLabel) ?>
                </span>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold text-emerald-900 tabular-nums leading-none"><?= (int) ($req['net_days'] ?? 0) ?></div>
                <div class="text-sm font-medium text-emerald-600 mt-1"><?= I18n::get('emp.days') ?></div>
            </div>
        </div>

        <div class="mb-6 text-2xl font-bold text-emerald-900 leading-tight tracking-tight">
            <?= date('d.m.Y', strtotime((string) $req['start_date'])) ?>
            <span class="mx-2 text-[#E8007D] font-normal">–</span>
            <?= date('d.m.Y', strtotime((string) $req['end_date'])) ?>
        </div>

        <div class="mt-auto flex flex-wrap gap-2 pt-4 border-t border-lime-100">
            <?php if ($isPlanned): ?>
                <form method="POST" action="/?action=request_storno" class="inline">
                    <input type="hidden" name="request_id" value="<?= $rid ?>">
                    <button type="submit" class="text-orange-600 hover:text-white hover:bg-orange-500 border border-orange-200 px-4 py-2 rounded-xl text-sm font-bold transition-colors">
                        <?= I18n::get('emp.storno') ?>
                    </button>
                </form>
            <?php elseif (!$isStorno): ?>
                <form method="POST" action="/?action=withdraw_request" class="inline">
                    <input type="hidden" name="request_id" value="<?= $rid ?>">
                    <button type="submit" class="text-red-600 hover:text-white hover:bg-red-500 border border-red-200 px-4 py-2 rounded-xl text-sm font-bold transition-colors">
                        <?= I18n::get('emp.retract') ?>
                    </button>
                </form>
            <?php else: ?>
                <form method="POST" action="/?action=withdraw_storno" class="inline">
                    <input type="hidden" name="request_id" value="<?= $rid ?>">
                    <button type="submit" class="et-btn-secondary px-4 py-2 rounded-xl text-sm font-bold transition-colors">
                        <?= I18n::get('emp.cancel_storno') ?>
                    </button>
                </form>
            <?php endif; ?>
            <a
                href="/?tab=history&amp;request_id=<?= $rid ?>"
                class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold border border-lime-200 text-emerald-800 hover:bg-lime-50/50 transition-colors"
            >
                <?= I18n::get('emp.view_in_history') ?>
            </a>
            <button
                type="button"
                onclick="focusEmployeeCalendarRequest(<?= $rid ?>)"
                class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold et-btn-secondary"
            >
                <?= I18n::get('emp.show_on_calendar') ?>
            </button>
        </div>
    </article>
    <?php
};
?>
<div class="space-y-10">
    <section>
        <div class="mb-6">
            <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-[#E8007D] mb-2"><?= htmlspecialchars($sectionOpen) ?></h2>
            <p class="text-sm text-emerald-600/80 leading-relaxed"><?= I18n::get('emp.open_requests_hint') ?></p>
        </div>

        <?php if ($openRequests === []): ?>
            <div class="relative overflow-hidden py-10 text-center bg-white rounded-3xl border border-lime-100 shadow-xl">
                <p class="relative text-base font-bold text-emerald-900"><?= I18n::get('emp.open_requests_empty') ?></p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($openRequests as $req): ?>
                    <?php
                    $borderClass = ($req['status'] ?? '') === 'storno_requested' ? 'border-orange-300' : 'border-amber-300';
                    $renderCard($req, $borderClass);
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section>
        <div class="mb-6">
            <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 mb-2"><?= htmlspecialchars($sectionPlanned) ?></h2>
        </div>

        <?php if ($plannedRequests === []): ?>
            <div class="relative overflow-hidden py-10 text-center bg-white rounded-3xl border border-lime-100 shadow-xl">
                <p class="relative text-base font-bold text-emerald-900"><?= I18n::get('emp.planned_requests_empty') ?></p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($plannedRequests as $req): ?>
                    <?php $renderCard($req, 'border-green-200'); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
