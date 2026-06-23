<?php
use App\Core\I18n;

/** @var list<array<string, mixed>> $requests */
/** @var list<array<string, mixed>> $employees */
/** @var array<string, mixed> $capacitySummary */
/** @var array<int, list<array<string, mixed>>> $requestCommentsById */

$inputClass = 'w-full bg-[#fffdf2] border border-lime-200 rounded-xl px-4 py-3 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400';
$inputClassCompact = 'w-full bg-[#fffdf2] border border-lime-200 rounded-lg px-2.5 py-2 text-xs text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400';
$labelClass = 'block text-sm font-semibold text-emerald-800 mb-1.5';
$labelClassCompact = 'block text-[11px] font-semibold text-emerald-800 mb-1';

$pendingVacations = array_values(array_filter($requests ?? [], static fn ($r) => ($r['status'] ?? '') === 'pending'));
$pendingStorno = array_values(array_filter($requests ?? [], static fn ($r) => ($r['status'] ?? '') === 'storno_requested'));
$pendingChanges = array_values(array_filter($requests ?? [], static fn ($r) => ($r['status'] ?? '') === 'change_requested'));

$emptyStateClass = 'w-full py-8 text-center bg-white rounded-2xl border border-lime-100 shadow-lg';
$cardGridClass = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 w-full';
$datesWarningClass = 'admin-dates-warning hidden rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-2 text-[11px] font-medium leading-snug text-amber-900';

$formatRange = static function (array $req): string {
    return date('d.m.Y', strtotime((string) $req['start_date'])) . ' – ' . date('d.m.Y', strtotime((string) $req['end_date']));
};

$renderApprovalCard = static function (array $req, bool $isStorno) use ($requestCommentsById, $labelClassCompact, $inputClassCompact, $formatRange, $datesWarningClass): void {
    $rid = (int) ($req['id'] ?? 0);
    $borderClass = $isStorno ? 'border-orange-300' : 'border-amber-200';
    ?>
    <article id="request-card-<?= $rid ?>" data-request-id="<?= $rid ?>" data-user-id="<?= (int) ($req['user_id'] ?? 0) ?>" class="ceo-filterable-card bg-white rounded-2xl p-4 shadow-lg border flex flex-col <?= $borderClass ?>">
        <div class="flex items-start justify-between gap-2 mb-3">
            <div class="min-w-0">
                <div class="text-[10px] font-bold uppercase tracking-[0.16em] text-emerald-500">#<?= $rid ?></div>
                <h3 class="text-sm font-bold text-emerald-900 truncate"><?= htmlspecialchars(($req['firstname'] ?? '') . ' ' . ($req['lastname'] ?? '')) ?></h3>
                <p class="text-[11px] text-emerald-600/80 truncate"><?= htmlspecialchars((string) ($req['email'] ?? '')) ?></p>
            </div>
            <div class="text-right shrink-0">
                <div class="text-2xl font-bold text-emerald-900 tabular-nums leading-none"><?= (int) ($req['net_days'] ?? 0) ?></div>
                <div class="text-[10px] font-medium text-emerald-600 mt-0.5"><?= I18n::get('ceo.days') ?></div>
            </div>
        </div>

        <div class="mb-3 text-sm font-bold text-emerald-900 leading-snug">
            <?= htmlspecialchars($formatRange($req)) ?>
        </div>

        <form
            action="/?action=decide_request"
            method="POST"
            class="admin-dates-form mt-auto space-y-2 pt-3 border-t border-lime-100"
            data-original-start="<?= htmlspecialchars((string) $req['start_date']) ?>"
            data-original-end="<?= htmlspecialchars((string) $req['end_date']) ?>"
            data-warning-msg="<?= htmlspecialchars(I18n::get('ceo.dates_changed_panel')) ?>"
        >
            <input type="hidden" name="request_id" value="<?= $rid ?>">
            <?php if (!$isStorno): ?>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="<?= $labelClassCompact ?>" for="approve-start-<?= $rid ?>"><?= I18n::get('ceo.from') ?></label>
                        <input id="approve-start-<?= $rid ?>" type="date" name="approved_start_date" value="<?= htmlspecialchars((string) $req['start_date']) ?>" class="<?= $inputClassCompact ?>">
                    </div>
                    <div>
                        <label class="<?= $labelClassCompact ?>" for="approve-end-<?= $rid ?>"><?= I18n::get('ceo.to') ?></label>
                        <input id="approve-end-<?= $rid ?>" type="date" name="approved_end_date" value="<?= htmlspecialchars((string) $req['end_date']) ?>" class="<?= $inputClassCompact ?>">
                    </div>
                </div>
                <div class="<?= $datesWarningClass ?>" role="alert"><?= I18n::get('ceo.dates_changed_panel') ?></div>
            <?php endif; ?>
            <input id="comment-<?= $rid ?>" type="text" name="admin_comment" placeholder="<?= htmlspecialchars(I18n::get('ceo.decision_comment_ph')) ?>" class="<?= $inputClassCompact ?>">
            <div class="grid grid-cols-2 gap-2">
                <?php if ($isStorno): ?>
                    <button type="submit" name="status" value="approved" class="text-red-600 hover:text-white hover:bg-red-500 border border-red-200 px-2 py-2 rounded-lg text-xs font-bold transition-colors"><?= I18n::get('ceo.decline_storno') ?></button>
                    <button type="submit" name="status" value="cancelled" class="bg-orange-500 hover:bg-orange-600 text-white px-2 py-2 rounded-lg text-xs font-bold transition-colors"><?= I18n::get('ceo.approve_storno') ?></button>
                <?php else: ?>
                    <button type="submit" name="status" value="rejected" class="text-red-600 hover:text-white hover:bg-red-500 border border-red-200 px-2 py-2 rounded-lg text-xs font-bold transition-colors"><?= I18n::get('ceo.decline') ?></button>
                    <button type="submit" name="status" value="approved" class="et-btn-primary px-2 py-2 rounded-lg text-xs font-bold"><?= I18n::get('ceo.approve_with_dates') ?></button>
                <?php endif; ?>
            </div>
        </form>

        <?php $comments = $requestCommentsById[$rid] ?? []; ?>
        <?php if ($comments !== []): ?>
            <div class="mt-3 pt-3 border-t border-lime-100 space-y-1.5">
                <?php foreach ($comments as $c): ?>
                    <div class="text-[11px] rounded-lg border border-lime-100 bg-[#fffdf2]/60 px-2.5 py-1.5 text-emerald-800">
                        <span class="font-bold"><?= htmlspecialchars(($c['firstname'] ?? '') . ' ' . ($c['lastname'] ?? '')) ?>:</span>
                        <?= htmlspecialchars((string) ($c['comment'] ?? '')) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
    <?php
};

$renderChangeCard = static function (array $req) use ($requestCommentsById, $labelClassCompact, $inputClassCompact, $formatRange, $datesWarningClass): void {
    $rid = (int) ($req['id'] ?? 0);
    $wStart = (string) ($req['wunsch_start_date'] ?? $req['start_date']);
    $wEnd = (string) ($req['wunsch_end_date'] ?? $req['end_date']);
    ?>
    <article id="change-card-<?= $rid ?>" data-request-id="<?= $rid ?>" data-user-id="<?= (int) ($req['user_id'] ?? 0) ?>" class="ceo-filterable-card bg-white rounded-2xl p-4 shadow-lg border border-violet-200 flex flex-col">
        <div class="mb-3">
            <div class="text-[10px] font-bold uppercase tracking-[0.16em] text-violet-600">#<?= $rid ?></div>
            <h3 class="text-sm font-bold text-emerald-900 truncate"><?= htmlspecialchars(($req['firstname'] ?? '') . ' ' . ($req['lastname'] ?? '')) ?></h3>
        </div>

        <div class="space-y-2 mb-3 text-xs">
            <div>
                <div class="font-bold text-emerald-500 uppercase tracking-wide text-[10px]"><?= I18n::get('ceo.current_dates') ?></div>
                <div class="font-semibold text-emerald-900"><?= htmlspecialchars($formatRange($req)) ?></div>
                <div class="text-emerald-600"><?= (int) ($req['net_days'] ?? 0) ?> <?= I18n::get('ceo.days') ?></div>
            </div>
            <div class="rounded-lg border border-violet-100 bg-violet-50/60 px-2.5 py-2">
                <div class="font-bold text-violet-700 uppercase tracking-wide text-[10px]"><?= I18n::get('ceo.proposed_dates') ?></div>
                <div class="font-semibold text-emerald-900">
                    <?= date('d.m.Y', strtotime($wStart)) ?> – <?= date('d.m.Y', strtotime($wEnd)) ?>
                </div>
                <div class="text-emerald-600"><?= (int) ($req['wunsch_net_days'] ?? 0) ?> <?= I18n::get('ceo.days') ?></div>
            </div>
        </div>

        <form
            action="/?action=decide_change_request"
            method="POST"
            class="admin-dates-form mt-auto space-y-2 pt-3 border-t border-lime-100"
            data-original-start="<?= htmlspecialchars($wStart) ?>"
            data-original-end="<?= htmlspecialchars($wEnd) ?>"
            data-warning-msg="<?= htmlspecialchars(I18n::get('ceo.dates_changed_panel_wunsch')) ?>"
        >
            <input type="hidden" name="request_id" value="<?= $rid ?>">
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="<?= $labelClassCompact ?>" for="change-start-<?= $rid ?>"><?= I18n::get('ceo.approved_dates') ?></label>
                    <input id="change-start-<?= $rid ?>" type="date" name="approved_start_date" value="<?= htmlspecialchars($wStart) ?>" class="<?= $inputClassCompact ?>">
                </div>
                <div>
                    <label class="<?= $labelClassCompact ?>" for="change-end-<?= $rid ?>">&nbsp;</label>
                    <input id="change-end-<?= $rid ?>" type="date" name="approved_end_date" value="<?= htmlspecialchars($wEnd) ?>" class="<?= $inputClassCompact ?>">
                </div>
            </div>
            <div class="<?= $datesWarningClass ?>" role="alert"><?= I18n::get('ceo.dates_changed_panel_wunsch') ?></div>
            <input type="text" name="admin_comment" placeholder="<?= htmlspecialchars(I18n::get('ceo.decision_comment_ph')) ?>" class="<?= $inputClassCompact ?>">
            <div class="grid grid-cols-2 gap-2">
                <button type="submit" name="decision" value="reject" class="text-red-600 hover:text-white hover:bg-red-500 border border-red-200 px-2 py-2 rounded-lg text-xs font-bold transition-colors"><?= I18n::get('ceo.reject_change') ?></button>
                <button type="submit" name="decision" value="approve" class="bg-violet-600 hover:bg-violet-700 text-white px-2 py-2 rounded-lg text-xs font-bold transition-colors"><?= I18n::get('ceo.approve_change') ?></button>
            </div>
        </form>
    </article>
    <?php
};
?>
<div class="space-y-6 sm:space-y-8 min-w-0 max-w-full">
    <?php include __DIR__ . '/admin-employee-filter.php'; ?>

    <?php
        $pendingTotal = count($pendingVacations) + count($pendingStorno) + count($pendingChanges);
    ?>
    <?php if ($pendingTotal > 0): ?>
        <div class="lg:hidden flex flex-wrap gap-2 max-w-full">
            <?php if ($pendingChanges !== []): ?>
                <a href="#ceo-section-change" class="inline-flex items-center gap-1.5 rounded-full border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-bold text-violet-800">
                    <?= count($pendingChanges) ?> <?= I18n::get('ceo.section_change_requests') ?>
                </a>
            <?php endif; ?>
            <?php if ($pendingVacations !== []): ?>
                <a href="#ceo-section-vacation" class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-900">
                    <?= count($pendingVacations) ?> <?= I18n::get('ceo.section_vacation_requests') ?>
                </a>
            <?php endif; ?>
            <?php if ($pendingStorno !== []): ?>
                <a href="#ceo-section-storno" class="inline-flex items-center gap-1.5 rounded-full border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-800">
                    <?= count($pendingStorno) ?> <?= I18n::get('ceo.section_storno_requests') ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 sm:gap-8 min-w-0 max-w-full">
        <div class="xl:col-span-2 min-w-0">
            <div class="bg-white p-4 sm:p-7 rounded-3xl shadow-xl border border-lime-100 overflow-hidden min-w-0">
                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between mb-2">
                    <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 shrink-0"><?= I18n::get('ceo.calendar') ?></h2>
                    <button type="button" onclick="openExportModal(true)" class="et-btn-secondary self-start px-3 py-2 rounded-xl text-xs font-bold shrink-0"><?= I18n::get('ceo.ics_export') ?></button>
                </div>
                <p class="text-sm text-emerald-600/80 mb-4 leading-relaxed lg:hidden"><?= I18n::get('emp.calendar_hint_mobile') ?></p>
                <p class="hidden lg:block text-sm text-emerald-600/80 mb-4 leading-relaxed"><?= I18n::get('ceo.calendar_hint') ?></p>
                <?php include __DIR__ . '/admin-calendar-legend.php'; ?>
                <label class="et-checkbox mb-4" for="ceo-show-cancelled">
                    <input type="checkbox" id="ceo-show-cancelled" class="et-checkbox__input">
                    <span class="et-checkbox__box" aria-hidden="true"></span>
                    <span><?= I18n::get('ceo.show_cancelled') ?></span>
                </label>
                <div id="ceo-calendar" data-tour="ceo-calendar"></div>
            </div>
        </div>

        <div class="calendar-side-panel min-w-0" data-tour="admin-side-panel">
            <div class="bg-white p-4 sm:p-7 rounded-3xl shadow-xl border border-lime-100 overflow-hidden min-w-0">
                <section id="admin-calendar-range-section" class="mb-6">
                    <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-[#E8007D] mb-4"><?= I18n::get('emp.panel_period') ?></h3>
                    <div class="space-y-4">
                        <div id="calendar-range-summary" class="hidden text-xl font-bold text-emerald-900 leading-tight tabular-nums"></div>
                        <div id="calendar-range-inputs" class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="<?= $labelClass ?>" for="admin-range-start-date"><?= I18n::get('ceo.from') ?></label>
                                <input id="admin-range-start-date" type="date" class="<?= $inputClass ?>">
                            </div>
                            <div>
                                <label class="<?= $labelClass ?>" for="admin-range-end-date"><?= I18n::get('ceo.to') ?></label>
                                <input id="admin-range-end-date" type="date" class="<?= $inputClass ?>">
                            </div>
                        </div>
                        <p id="calendar-range-hint" class="text-sm text-emerald-600/80 text-center max-w-[18rem] mx-auto"><?= I18n::get('ceo.range_empty') ?></p>
                    </div>
                </section>

                <div class="h-px bg-gradient-to-r from-transparent via-lime-200 to-transparent mb-6" aria-hidden="true"></div>

                <section id="admin-calendar-info-section" class="mb-6">
                    <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 mb-4"><?= I18n::get('emp.panel_info') ?></h3>
                    <div id="calendar-info-empty" class="relative overflow-hidden py-5 text-center">
                        <p class="relative text-base font-bold text-emerald-900"><?= I18n::get('ceo.info_empty_title') ?></p>
                        <p class="relative mt-2 text-sm text-emerald-600/80 max-w-[16rem] mx-auto"><?= I18n::get('ceo.info_empty') ?></p>
                    </div>
                    <div id="calendar-info-content" class="hidden space-y-4">
                        <div id="calendar-info-event-body" class="hidden text-sm text-emerald-800 bg-[#fffdf2] border border-lime-200 rounded-xl p-4 space-y-1"></div>
                        <div class="grid grid-cols-3 gap-2">
                            <div class="rounded-xl border border-lime-200 bg-[#fffdf2]/60 p-3 text-center">
                                <div class="text-[10px] uppercase font-bold text-emerald-500"><?= I18n::get('ceo.capacity_total') ?></div>
                                <div class="text-2xl font-bold text-emerald-900 tabular-nums"><?= (int) ($capacitySummary['employees_total'] ?? 0) ?></div>
                            </div>
                            <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-3 text-center">
                                <div class="text-[10px] uppercase font-bold text-amber-800"><?= I18n::get('ceo.capacity_absent') ?></div>
                                <div class="text-2xl font-bold text-emerald-900 tabular-nums"><?= (int) ($capacitySummary['absent_approved'] ?? 0) ?></div>
                            </div>
                            <div class="rounded-xl border border-lime-200 bg-white p-3 text-center">
                                <div class="text-[10px] uppercase font-bold text-emerald-500"><?= I18n::get('ceo.capacity_available') ?></div>
                                <div class="text-2xl font-bold text-emerald-900 tabular-nums"><?= (int) ($capacitySummary['available'] ?? 0) ?></div>
                            </div>
                        </div>
                        <div id="calendar-info-meta" class="text-sm text-emerald-700 bg-[#fffdf2] border border-lime-200 rounded-xl p-3 hidden"></div>
                    </div>
                </section>

                <div class="h-px bg-gradient-to-r from-transparent via-lime-200 to-transparent mb-6" aria-hidden="true"></div>

                <section>
                    <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 mb-4"><?= I18n::get('emp.panel_action') ?></h3>
                    <div id="calendar-action-empty" class="relative overflow-hidden py-5 text-center">
                        <p class="relative text-base font-bold text-emerald-900"><?= I18n::get('ceo.action_empty_title') ?></p>
                        <p class="relative mt-2 text-sm text-emerald-600/80 max-w-[16rem] mx-auto"><?= I18n::get('ceo.action_empty') ?></p>
                    </div>

                    <div id="calendar-action-range-wrapper" class="hidden space-y-4">
                        <div id="calendar-action-mode-picker" class="grid grid-cols-2 gap-2">
                            <button type="button" id="action-mode-vacation-btn" class="et-btn-primary py-2 rounded-xl text-sm font-bold"><?= I18n::get('ceo.mode_vacation') ?></button>
                            <button type="button" id="action-mode-block-btn" class="et-btn-secondary py-2 rounded-xl text-sm font-bold"><?= I18n::get('ceo.mode_block') ?></button>
                        </div>

                        <form id="calendar-action-vacation-form" method="POST" action="/?action=admin_create_vacation" class="space-y-4 hidden">
                            <input type="hidden" name="confirm_past" value="">
                            <input type="hidden" id="vacation-form-start-date" name="start_date" value="">
                            <input type="hidden" id="vacation-form-end-date" name="end_date" value="">
                            <div>
                                <label class="<?= $labelClass ?>" for="admin-vacation-user"><?= I18n::get('ceo.select_employee') ?></label>
                                <select id="admin-vacation-user" name="user_id" required class="<?= $inputClass ?>">
                                    <option value=""><?= I18n::get('ceo.select_employee_ph') ?></option>
                                    <?php foreach (($employees ?? []) as $empOpt): ?>
                                        <?php if (($empOpt['role'] ?? '') !== 'Employee') continue; ?>
                                        <option value="<?= (int) $empOpt['id'] ?>" data-employee-option="1"><?= htmlspecialchars($empOpt['firstname'] . ' ' . $empOpt['lastname']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="<?= $labelClass ?>" for="admin-vacation-comment"><?= I18n::get('ceo.decision_comment') ?></label>
                                <input id="admin-vacation-comment" type="text" name="admin_comment" class="<?= $inputClass ?>">
                            </div>
                            <div id="admin-vacation-validation" class="hidden" aria-hidden="true"></div>
                            <button type="submit" id="admin-vacation-submit-btn" class="w-full et-btn-primary font-bold py-3 rounded-xl"><?= I18n::get('ceo.book_vacation') ?></button>
                        </form>

                        <form id="calendar-action-block-form" method="POST" action="/?action=create_blocked_period" class="space-y-4 hidden">
                            <input type="hidden" name="confirm_past" value="">
                            <input type="hidden" id="block-form-start-date" name="start_date" value="">
                            <input type="hidden" id="block-form-end-date" name="end_date" value="">
                            <div>
                                <label class="<?= $labelClass ?>" for="blocked-label"><?= I18n::get('ceo.block_label') ?></label>
                                <input id="blocked-label" type="text" name="label" placeholder="<?= htmlspecialchars(I18n::get('ceo.block_label_ph')) ?>" class="<?= $inputClass ?>">
                            </div>
                            <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-3 rounded-xl transition-colors"><?= I18n::get('ceo.block_save') ?></button>
                        </form>
                    </div>

                    <div id="calendar-action-unblock" class="space-y-3 hidden">
                        <div id="calendar-action-unblock-list" class="space-y-2"></div>
                    </div>

                    <div id="calendar-action-event" class="hidden space-y-4">
                        <div id="calendar-event-actions" class="space-y-4"></div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <section id="ceo-section-change" class="w-full scroll-mt-24" data-tour="ceo-section-change">
        <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-violet-600 mb-2"><?= I18n::get('ceo.section_change_requests') ?></h2>
        <p class="text-sm text-emerald-600/80 mb-4"><?= I18n::get('ceo.section_change_requests_hint') ?></p>
        <?php if ($pendingChanges === []): ?>
            <div class="<?= $emptyStateClass ?>">
                <p class="text-sm font-bold text-emerald-900"><?= I18n::get('ceo.empty_change_requests') ?></p>
            </div>
        <?php else: ?>
            <p id="ceo-filter-empty-change" class="hidden <?= $emptyStateClass ?> text-sm font-bold text-emerald-900"><?= I18n::get('ceo.employee_filter_empty') ?></p>
            <div id="ceo-grid-change" class="<?= $cardGridClass ?>">
                <?php foreach ($pendingChanges as $req): ?>
                    <?php $renderChangeCard($req); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section id="ceo-section-vacation" class="w-full scroll-mt-24" data-tour="ceo-section-vacation">
        <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-[#E8007D] mb-2"><?= I18n::get('ceo.section_vacation_requests') ?></h2>
        <p class="text-sm text-emerald-600/80 mb-4"><?= I18n::get('ceo.section_vacation_requests_hint') ?></p>
        <?php if ($pendingVacations === []): ?>
            <div class="<?= $emptyStateClass ?>">
                <p class="text-sm font-bold text-emerald-900"><?= I18n::get('ceo.empty_vacation_requests') ?></p>
            </div>
        <?php else: ?>
            <p id="ceo-filter-empty-vacation" class="hidden <?= $emptyStateClass ?> text-sm font-bold text-emerald-900"><?= I18n::get('ceo.employee_filter_empty') ?></p>
            <div id="ceo-grid-vacation" class="<?= $cardGridClass ?>">
                <?php foreach ($pendingVacations as $req): ?>
                    <?php $renderApprovalCard($req, false); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section id="ceo-section-storno" class="w-full scroll-mt-24" data-tour="ceo-section-storno">
        <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-orange-500 mb-2"><?= I18n::get('ceo.section_storno_requests') ?></h2>
        <p class="text-sm text-emerald-600/80 mb-4"><?= I18n::get('ceo.section_storno_requests_hint') ?></p>
        <?php if ($pendingStorno === []): ?>
            <div class="<?= $emptyStateClass ?>">
                <p class="text-sm font-bold text-emerald-900"><?= I18n::get('ceo.empty_storno_requests') ?></p>
            </div>
        <?php else: ?>
            <p id="ceo-filter-empty-storno" class="hidden <?= $emptyStateClass ?> text-sm font-bold text-emerald-900"><?= I18n::get('ceo.employee_filter_empty') ?></p>
            <div id="ceo-grid-storno" class="<?= $cardGridClass ?>">
                <?php foreach ($pendingStorno as $req): ?>
                    <?php $renderApprovalCard($req, true); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
