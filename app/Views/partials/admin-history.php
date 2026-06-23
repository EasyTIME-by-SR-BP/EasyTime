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
    'change_requested' => I18n::get('history.event.change_requested'),
    'change_withdrawn' => I18n::get('history.event.change_withdrawn'),
    'change_approved'  => I18n::get('history.event.change_approved'),
    'change_rejected'  => I18n::get('history.event.change_rejected'),
    'dates_adjusted'   => I18n::get('history.event.dates_adjusted'),
];

$statusLabels = [
    'approved'         => I18n::get('emp.status_approved'),
    'rejected'         => I18n::get('emp.status_rejected'),
    'pending'          => I18n::get('emp.status_pending'),
    'storno_requested' => I18n::get('emp.status_storno_requested'),
    'change_requested' => I18n::get('emp.status_change_requested'),
    'cancelled'        => I18n::get('emp.status_cancelled'),
];

$openStatuses = ['pending', 'storno_requested', 'change_requested'];
$todayYmd = date('Y-m-d');

$historyPayload = [];
foreach ($requests as $req) {
    $rid = (int) $req['id'];
    $status = (string) ($req['status'] ?? '');
    $endDate = (string) ($req['end_date'] ?? '');
    $isOpen = in_array($status, $openStatuses, true);
    $isPlanned = $status === 'approved' && $endDate >= $todayYmd;
    $employeeName = trim(($req['firstname'] ?? '') . ' ' . ($req['lastname'] ?? ''));
    $timeline = $requestEventsById[$rid] ?? [];
    if ($timeline === []) {
        $timeline[] = [
            'event_type' => 'created',
            'created_at' => $req['start_date'] ?? '',
            'actor_name' => $employeeName,
            'message' => ($req['start_date'] ?? '') . ' – ' . ($req['end_date'] ?? ''),
        ];
    }
    $historyPayload[] = [
        'id' => $rid,
        'user_id' => (int) ($req['user_id'] ?? 0),
        'employee_name' => $employeeName,
        'start_date' => $req['start_date'],
        'end_date' => $req['end_date'],
        'net_days' => (int) $req['net_days'],
        'wunsch_start_date' => $req['wunsch_start_date'] ?? null,
        'wunsch_end_date' => $req['wunsch_end_date'] ?? null,
        'wunsch_net_days' => $req['wunsch_net_days'] ?? null,
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
<div class="space-y-6">
    <?php include __DIR__ . '/admin-employee-filter.php'; ?>

    <div
        class="relative w-full"
        x-data="adminHistory(<?= htmlspecialchars(json_encode($historyPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>, <?= $initialSelected !== null ? (int) $initialSelected : 'null' ?>)"
        @keydown.escape.window="closeDetail()"
    >
        <div class="mb-6" x-show="selectedId === null">
            <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 mb-2"><?= I18n::get('history.title') ?></h2>
            <p class="text-sm text-emerald-600/80 leading-relaxed"><?= I18n::get('history.subtitle_admin') ?></p>
        </div>

        <div class="w-full bg-white rounded-3xl border border-lime-100 shadow-xl p-6 sm:p-7" data-tour="history-list" x-show="selectedId === null">
            <div class="flex flex-col sm:flex-row gap-3 mb-6">
                <input
                    type="search"
                    x-model="search"
                    placeholder="<?= htmlspecialchars(I18n::get('history.search_admin')) ?>"
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
                    <option value="change_requested"><?= I18n::get('emp.status_change_requested') ?></option>
                    <option value="cancelled"><?= I18n::get('emp.status_cancelled') ?></option>
                </select>
            </div>

            <?php $showEmployeeName = true; include __DIR__ . '/history-list-sections.php'; ?>
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
            class="history-detail-panel"
        >
            <template x-if="selected">
                <div class="history-detail-panel__shell">
                    <button
                        type="button"
                        @click="closeDetail()"
                        class="mb-5 inline-flex items-center gap-2 text-sm font-bold text-emerald-800 hover:text-[#E8007D] transition-colors"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <?= I18n::get('history.back_to_list') ?>
                    </button>

                    <div class="history-detail-panel__card bg-white rounded-3xl border border-lime-100 shadow-xl p-6 sm:p-7">
                        <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
                            <div class="space-y-3">
                                <div class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500" x-text="'Antrag #' + selected.id"></div>
                                <div class="text-sm font-semibold text-emerald-700" x-text="selected.employee_name"></div>
                                <h3 class="text-2xl sm:text-3xl font-bold text-emerald-900 leading-tight tracking-tight" x-text="formatRange(selected)"></h3>
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="inline-flex px-3 py-1.5 rounded-full text-sm font-bold border" :class="statusBadgeClass(selected.status)" x-text="selected.status_label"></span>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-3xl font-bold text-emerald-900 tabular-nums leading-none" x-text="selected.net_days"></span>
                                        <span class="text-sm font-medium text-emerald-600"><?= I18n::get('emp.days') ?></span>
                                    </div>
                                </div>
                            </div>
                            <a
                                :href="'/?tab=operations&request_id=' + selected.id"
                                class="text-xs font-bold uppercase tracking-[0.15em] text-[#E8007D] hover:text-emerald-900 transition-colors"
                            ><?= I18n::get('history.open_operations') ?></a>
                        </div>

                        <template x-if="selected.status === 'change_requested' && selected.wunsch_start_date">
                            <div class="mb-8 pb-6 border-b border-lime-100 rounded-xl border border-violet-100 bg-violet-50/50 p-4 text-sm text-emerald-800">
                                <div class="text-xs font-bold uppercase tracking-[0.15em] text-violet-600 mb-1"><?= I18n::get('ceo.proposed_dates') ?></div>
                                <div class="font-bold" x-text="formatRange({ start_date: selected.wunsch_start_date, end_date: selected.wunsch_end_date })"></div>
                            </div>
                        </template>

                        <h4 class="text-xs font-bold uppercase tracking-[0.2em] text-[#E8007D] mb-4"><?= I18n::get('history.timeline') ?></h4>
                        <div class="space-y-4 mb-8">
                            <template x-for="(ev, idx) in selected.timeline" :key="idx">
                                <div class="relative pl-7 ml-0.5 border-l-2 border-lime-200">
                                    <span class="absolute left-0 top-1 h-3 w-3 -translate-x-1/2 rounded-full bg-[#E8007D] ring-2 ring-white"></span>
                                    <div class="text-xs font-bold text-emerald-500" x-text="ev.at"></div>
                                    <div class="text-sm font-bold text-emerald-900" x-text="ev.label"></div>
                                    <div class="text-xs text-emerald-600" x-show="ev.actor" x-text="ev.actor"></div>
                                    <p class="text-sm text-emerald-800 mt-1" x-show="ev.message" x-text="ev.message"></p>
                                </div>
                            </template>
                        </div>

                        <form method="POST" action="/?action=add_request_comment" class="flex flex-col sm:flex-row gap-2 border-t border-lime-100 pt-6">
                            <input type="hidden" name="request_id" :value="selected.id">
                            <input type="hidden" name="return_tab" value="history">
                            <input type="text" name="comment" required class="flex-1 bg-[#fffdf2] border border-lime-200 rounded-xl px-4 py-3 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400" placeholder="<?= htmlspecialchars(I18n::get('history.comment_placeholder')) ?>">
                            <button type="submit" class="et-btn-primary px-4 py-2.5 rounded-xl text-sm font-bold shrink-0"><?= I18n::get('history.comment_send') ?></button>
                        </form>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function adminHistory(requests, initialSelectedId) {
    return {
        requests,
        search: '',
        statusFilter: 'all',
        selectedId: initialSelectedId,
        employeeFilterTick: 0,
        matchesEmployeeFilter(item) {
            void this.employeeFilterTick;
            const selected = window.getCeoEmployeeFilterSelectedIds?.();
            if (!selected) return true;
            return selected.has(String(item.user_id));
        },
        matchesFilters(item) {
            if (!this.matchesEmployeeFilter(item)) return false;
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
                item.employee_name,
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
                change_requested: 'bg-violet-100 text-violet-900 border-violet-300',
                rejected: 'bg-red-100 text-red-800 border-red-200',
                cancelled: 'bg-gray-100 text-gray-600 border-gray-200',
            };
            return map[status] || 'bg-lime-50 text-emerald-800 border-lime-200';
        },
        init() {
            const bump = () => { this.employeeFilterTick++; };
            window.addEventListener('easytime:ceo-employee-filter-changed', bump);
            window.addEventListener('storage', (e) => {
                if (e.key === 'easytime_ceo_employee_filter') bump();
            });
            if (this.selectedId !== null) {
                document.body.classList.add('overflow-hidden');
            }
        },
    };
}
</script>
