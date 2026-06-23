<?php
use App\Core\I18n;

/** @var array<int, array<string, mixed>> $requestEventsById */
/** @var list<array<string, mixed>> $requests */
/** @var int $selectedHistoryRequestId */

$eventLabels = [
    'created'          => I18n::get('history.event.created'),
    'comment'          => I18n::get('history.event.comment'),
    'approved'         => I18n::get('history.event.approved'),
    'rejected'         => I18n::get('history.event.rejected'),
    'withdrawn'        => I18n::get('history.event.withdrawn'),
    'storno_requested' => I18n::get('history.event.storno_requested'),
    'storno_withdrawn' => I18n::get('history.event.storno_withdrawn'),
    'cancelled'        => I18n::get('history.event.cancelled'),
    'updated'          => I18n::get('history.event.updated'),
];

$statusLabels = [
    'approved'         => I18n::get('emp.status_approved'),
    'rejected'         => I18n::get('emp.status_rejected'),
    'pending'          => I18n::get('emp.status_pending'),
    'storno_requested' => I18n::get('emp.status_storno_requested'),
    'cancelled'        => I18n::get('emp.status_cancelled'),
];

$openStatuses = ['pending', 'storno_requested'];
$todayYmd = date('Y-m-d');

$historyPayload = [];
foreach ($requests as $req) {
    $rid = (int) $req['id'];
    $status = (string) ($req['status'] ?? '');
    $endDate = (string) ($req['end_date'] ?? '');
    $isOpen = in_array($status, $openStatuses, true);
    $isPlanned = $status === 'approved' && $endDate >= $todayYmd;
    $timeline = $requestEventsById[$rid] ?? [];
    if ($timeline === []) {
        $timeline[] = [
            'event_type' => 'created',
            'created_at' => $req['start_date'] ?? '',
            'actor_name' => trim(($req['firstname'] ?? '') . ' ' . ($req['lastname'] ?? '')),
            'message' => ($req['start_date'] ?? '') . ' – ' . ($req['end_date'] ?? ''),
        ];
    }
    $historyPayload[] = [
        'id' => $rid,
        'start_date' => $req['start_date'],
        'end_date' => $req['end_date'],
        'net_days' => (int) $req['net_days'],
        'status' => $req['status'],
        'status_label' => $statusLabels[$req['status']] ?? $req['status'],
        'is_open' => $isOpen,
        'is_planned' => $isPlanned,
        'is_past' => !$isOpen && !$isPlanned,
        'timeline' => array_map(static function (array $ev) use ($eventLabels) {
            return [
                'type' => $ev['event_type'] ?? 'updated',
                'label' => $eventLabels[$ev['event_type'] ?? 'updated'] ?? ($ev['event_type'] ?? ''),
                'at' => $ev['created_at'] ?? '',
                'actor' => trim((string) ($ev['actor_name'] ?? '')),
                'message' => (string) ($ev['message'] ?? ''),
            ];
        }, $timeline),
    ];
}

$initialSelected = $selectedHistoryRequestId > 0 ? $selectedHistoryRequestId : null;

$sectionOpen = I18n::get('history.section.open');
$sectionPlanned = I18n::get('history.section.planned');
$sectionPast = I18n::get('history.section.past');
?>
<div
    class="relative w-full"
    x-data="employeeHistory(<?= htmlspecialchars(json_encode($historyPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>, <?= $initialSelected !== null ? (int) $initialSelected : 'null' ?>)"
    @keydown.escape.window="closeDetail()"
>
    <div class="mb-6" x-show="selectedId === null">
        <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 mb-2"><?= I18n::get('history.title') ?></h2>
        <p class="text-sm text-emerald-600/80 leading-relaxed"><?= I18n::get('history.subtitle') ?></p>
    </div>

    <div class="w-full bg-white rounded-3xl border border-lime-100 shadow-xl p-6 sm:p-7" x-show="selectedId === null">
        <div class="flex flex-col sm:flex-row gap-3 mb-6">
            <input
                type="search"
                x-model="search"
                placeholder="<?= htmlspecialchars(I18n::get('history.search')) ?>"
                class="w-full sm:flex-1 bg-[#fffdf2] border border-lime-200 rounded-xl px-4 py-3 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400"
            >
            <select
                x-model="statusFilter"
                class="w-full sm:w-56 bg-[#fffdf2] border border-lime-200 rounded-xl px-4 py-3 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400"
            >
                <option value="all"><?= I18n::get('history.filter.all') ?></option>
                <option value="open"><?= htmlspecialchars($sectionOpen) ?></option>
                <option value="planned"><?= I18n::get('history.filter.planned') ?></option>
                <option value="past"><?= htmlspecialchars($sectionPast) ?></option>
                <option value="pending"><?= I18n::get('emp.status_pending') ?></option>
                <option value="approved"><?= I18n::get('emp.status_approved') ?></option>
                <option value="rejected"><?= I18n::get('emp.status_rejected') ?></option>
                <option value="storno_requested"><?= I18n::get('emp.status_storno_requested') ?></option>
                <option value="cancelled"><?= I18n::get('emp.status_cancelled') ?></option>
            </select>
        </div>

        <template x-if="openItems.length > 0">
            <div class="mb-8">
                <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-[#E8007D] mb-4"><?= htmlspecialchars($sectionOpen) ?></h3>
                <div class="space-y-3">
                    <template x-for="item in openItems" :key="'open-' + item.id">
                        <button
                            type="button"
                            @click="openDetail(item.id)"
                            class="w-full text-left rounded-2xl border border-lime-100 bg-white p-4 sm:p-5 transition-all hover:border-lime-300 hover:shadow-sm"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="space-y-2 min-w-0">
                                    <div class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500">
                                        Antrag #<span x-text="item.id"></span>
                                    </div>
                                    <div class="text-xl font-bold text-emerald-900 leading-tight" x-text="formatRange(item)"></div>
                                    <div class="flex items-baseline gap-2 text-emerald-600">
                                        <span class="text-2xl font-bold text-emerald-900 tabular-nums leading-none" x-text="item.net_days"></span>
                                        <span class="text-sm font-medium"><?= I18n::get('emp.days') ?></span>
                                    </div>
                                </div>
                                <span class="inline-flex px-3 py-1.5 rounded-full text-sm font-bold border shrink-0" :class="statusBadgeClass(item.status)" x-text="item.status_label"></span>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </template>

        <template x-if="plannedItems.length > 0">
            <div class="mb-8">
                <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 mb-4"><?= htmlspecialchars($sectionPlanned) ?></h3>
                <div class="space-y-3">
                    <template x-for="item in plannedItems" :key="'planned-' + item.id">
                        <button
                            type="button"
                            @click="openDetail(item.id)"
                            class="w-full text-left rounded-2xl border border-green-200 bg-white p-4 sm:p-5 transition-all hover:border-green-300 hover:shadow-sm"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="space-y-2 min-w-0">
                                    <div class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500">
                                        Antrag #<span x-text="item.id"></span>
                                    </div>
                                    <div class="text-xl font-bold text-emerald-900 leading-tight" x-text="formatRange(item)"></div>
                                    <div class="flex items-baseline gap-2 text-emerald-600">
                                        <span class="text-2xl font-bold text-emerald-900 tabular-nums leading-none" x-text="item.net_days"></span>
                                        <span class="text-sm font-medium"><?= I18n::get('emp.days') ?></span>
                                    </div>
                                </div>
                                <span class="inline-flex px-3 py-1.5 rounded-full text-sm font-bold border shrink-0" :class="statusBadgeClass(item.status)" x-text="item.status_label"></span>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </template>

        <template x-if="pastItems.length > 0">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 mb-4" x-show="openItems.length > 0 || plannedItems.length > 0"><?= htmlspecialchars($sectionPast) ?></h3>
                <div class="space-y-3">
                    <template x-for="item in pastItems" :key="'past-' + item.id">
                        <button
                            type="button"
                            @click="openDetail(item.id)"
                            class="w-full text-left rounded-2xl border border-lime-100 bg-white p-4 sm:p-5 transition-all hover:border-lime-300 hover:shadow-sm"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="space-y-2 min-w-0">
                                    <div class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500">
                                        Antrag #<span x-text="item.id"></span>
                                    </div>
                                    <div class="text-xl font-bold text-emerald-900 leading-tight" x-text="formatRange(item)"></div>
                                    <div class="flex items-baseline gap-2 text-emerald-600">
                                        <span class="text-2xl font-bold text-emerald-900 tabular-nums leading-none" x-text="item.net_days"></span>
                                        <span class="text-sm font-medium"><?= I18n::get('emp.days') ?></span>
                                    </div>
                                </div>
                                <span class="inline-flex px-3 py-1.5 rounded-full text-sm font-bold border shrink-0" :class="statusBadgeClass(item.status)" x-text="item.status_label"></span>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </template>

        <div x-show="openItems.length === 0 && plannedItems.length === 0 && pastItems.length === 0" class="relative overflow-hidden py-14 text-center">
            <div class="pointer-events-none absolute -right-6 top-0 h-28 w-28 rounded-full bg-lime-100/80 blur-2xl" aria-hidden="true"></div>
            <div class="relative mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-lime-50 to-emerald-50 text-emerald-500 shadow-inner">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="relative text-base font-bold text-emerald-900"><?= I18n::get('history.empty') ?></p>
        </div>
    </div>

    <div
        x-show="selectedId !== null"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[60] overflow-y-auto bg-[#fffdf2] lg:left-[4.5rem] lg:top-[4.5rem]"
    >
        <template x-if="selected">
            <div class="w-full p-4 sm:p-6 lg:p-8">
            <button
                type="button"
                @click="closeDetail()"
                class="mb-5 inline-flex items-center gap-2 text-sm font-bold text-emerald-800 hover:text-[#E8007D] transition-colors"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <?= I18n::get('history.back_to_list') ?>
            </button>

            <div class="w-full bg-white rounded-3xl border border-lime-100 shadow-xl p-6 sm:p-7 lg:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
                    <div class="space-y-3">
                        <div class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500" x-text="'Antrag #' + selected.id"></div>
                        <h3 class="text-2xl sm:text-3xl font-bold text-emerald-900 leading-tight tracking-tight" x-text="formatRange(selected)"></h3>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="inline-flex px-3 py-1.5 rounded-full text-sm font-bold border" :class="statusBadgeClass(selected.status)" x-text="selected.status_label"></span>
                            <div class="flex items-baseline gap-2">
                                <span class="text-3xl font-bold text-emerald-900 tabular-nums leading-none" x-text="selected.net_days"></span>
                                <span class="text-sm font-medium text-emerald-600"><?= I18n::get('emp.days') ?></span>
                            </div>
                        </div>
                    </div>
                    <a href="/?tab=calendar" class="text-xs font-bold uppercase tracking-[0.15em] text-[#E8007D] hover:text-emerald-900 transition-colors"><?= I18n::get('history.open_calendar') ?></a>
                </div>

                <div class="flex flex-wrap gap-2 mb-8 pb-6 border-b border-lime-100">
                    <template x-if="selected.status === 'pending'">
                        <form method="POST" action="/?action=withdraw_request" class="inline">
                            <input type="hidden" name="request_id" :value="selected.id">
                            <button type="submit" class="text-red-600 hover:text-white hover:bg-red-500 border border-red-200 px-4 py-2 rounded-xl text-sm font-bold transition-colors">
                                <?= I18n::get('emp.retract') ?>
                            </button>
                        </form>
                    </template>
                    <template x-if="selected.status === 'approved'">
                        <form method="POST" action="/?action=request_storno" class="inline">
                            <input type="hidden" name="request_id" :value="selected.id">
                            <button type="submit" class="text-orange-600 hover:text-white hover:bg-orange-500 border border-orange-200 px-4 py-2 rounded-xl text-sm font-bold transition-colors">
                                <?= I18n::get('emp.storno') ?>
                            </button>
                        </form>
                    </template>
                    <template x-if="selected.status === 'storno_requested'">
                        <form method="POST" action="/?action=withdraw_storno" class="inline">
                            <input type="hidden" name="request_id" :value="selected.id">
                            <input type="hidden" name="return_tab" value="history">
                            <button type="submit" class="et-btn-secondary px-4 py-2 rounded-xl text-sm font-bold transition-colors">
                                <?= I18n::get('emp.cancel_storno') ?>
                            </button>
                        </form>
                    </template>
                </div>

                <h4 class="text-xs font-bold uppercase tracking-[0.2em] text-[#E8007D] mb-4"><?= I18n::get('history.timeline') ?></h4>
                <div class="space-y-4 mb-8">
                    <template x-for="(ev, idx) in selected.timeline" :key="idx">
                        <div class="relative pl-6 border-l-2 border-lime-200">
                            <span class="absolute left-[-0.4rem] top-1 h-3 w-3 rounded-full bg-[#E8007D] ring-2 ring-white"></span>
                            <div class="text-xs font-bold text-emerald-500" x-text="ev.at"></div>
                            <div class="text-sm font-bold text-emerald-900" x-text="ev.label"></div>
                            <div class="text-xs text-emerald-600" x-show="ev.actor" x-text="ev.actor"></div>
                            <p class="text-sm text-emerald-800 mt-1" x-show="ev.message" x-text="ev.message"></p>
                        </div>
                    </template>
                </div>

                <form method="POST" action="/?action=add_request_comment" class="flex flex-col sm:flex-row gap-2 border-t border-lime-100 pt-6">
                    <input type="hidden" name="request_id" :value="selected.id">
                    <input type="text" name="comment" required class="flex-1 bg-[#fffdf2] border border-lime-200 rounded-xl px-4 py-3 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400" placeholder="<?= htmlspecialchars(I18n::get('history.comment_placeholder')) ?>">
                    <button type="submit" class="et-btn-primary px-4 py-2.5 rounded-xl text-sm font-bold shrink-0"><?= I18n::get('history.comment_send') ?></button>
                </form>
            </div>
        </div>
        </template>
    </div>
</div>

<script>
function employeeHistory(requests, initialSelectedId) {
    return {
        requests,
        search: '',
        statusFilter: 'all',
        selectedId: initialSelectedId,
        matchesFilters(item) {
            if (this.statusFilter === 'open' && !item.is_open) return false;
            if (this.statusFilter === 'planned' && !item.is_planned) return false;
            if (this.statusFilter === 'past' && !item.is_past) return false;
            if (!['all', 'open', 'planned', 'past'].includes(this.statusFilter) && item.status !== this.statusFilter) return false;
            const q = this.search.trim().toLowerCase();
            if (!q) return true;
            const hay = [
                String(item.id),
                item.start_date,
                item.end_date,
                item.status,
                item.status_label,
            ].join(' ').toLowerCase();
            return hay.includes(q);
        },
        get filtered() {
            return this.requests.filter((item) => this.matchesFilters(item));
        },
        get openItems() {
            return this.filtered.filter((item) => item.is_open);
        },
        get plannedItems() {
            return this.filtered.filter((item) => item.is_planned);
        },
        get pastItems() {
            return this.filtered.filter((item) => item.is_past);
        },
        get selected() {
            return this.requests.find((r) => r.id === this.selectedId) || null;
        },
        openDetail(id) {
            this.selectedId = id;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', 'history');
            url.searchParams.set('request_id', String(id));
            window.history.replaceState({}, '', url);
            document.body.classList.add('overflow-hidden');
        },
        closeDetail() {
            this.selectedId = null;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', 'history');
            url.searchParams.delete('request_id');
            window.history.replaceState({}, '', url);
            document.body.classList.remove('overflow-hidden');
        },
        formatRange(item) {
            return item.start_date + ' – ' + item.end_date;
        },
        statusBadgeClass(status) {
            const map = {
                approved: 'bg-green-100 text-green-800 border-green-200',
                pending: 'bg-amber-100 text-amber-900 border-amber-200',
                storno_requested: 'bg-orange-100 text-orange-900 border-orange-300',
                rejected: 'bg-red-100 text-red-800 border-red-200',
                cancelled: 'bg-gray-100 text-gray-600 border-gray-200',
            };
            return map[status] || 'bg-lime-50 text-emerald-800 border-lime-200';
        },
        init() {
            if (this.selectedId !== null) {
                document.body.classList.add('overflow-hidden');
            }
        },
    };
}
</script>
