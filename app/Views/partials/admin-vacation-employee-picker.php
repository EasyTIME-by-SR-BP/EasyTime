<?php
use App\Core\I18n;

/** @var list<array<string, mixed>> $employees */
/** @var list<array<string, mixed>> $standortePool */

$inputClassCompact = $inputClassCompact ?? 'w-full bg-[#fffdf2] border border-lime-200 rounded-lg px-2.5 py-2 text-xs text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400';
$selectClassCompact = $inputClassCompact . ' appearance-none pr-8 bg-[length:10px] bg-[right_0.65rem_center] bg-no-repeat';
$selectChevron = "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23166534'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E\")";

$pickerEmployees = array_values(array_filter(
    $employees ?? [],
    static fn (array $e): bool => ($e['role'] ?? '') === 'Employee'
));
usort($pickerEmployees, static function (array $a, array $b): int {
    $primaryA = (int) ($a['primary_standort_id'] ?? 0);
    $primaryB = (int) ($b['primary_standort_id'] ?? 0);
    if ($primaryA !== $primaryB) {
        if ($primaryA === 0) {
            return 1;
        }
        if ($primaryB === 0) {
            return -1;
        }
    }
    $na = strtolower(trim(($a['lastname'] ?? '') . ' ' . ($a['firstname'] ?? '')));
    $nb = strtolower(trim(($b['lastname'] ?? '') . ' ' . ($b['firstname'] ?? '')));
    return $na <=> $nb;
});

$hasStandorte = !empty($standortePool);
?>
<div id="admin-vacation-employee-picker" class="relative">
    <input type="hidden" id="admin-vacation-user" name="user_id" value="" required>
    <label class="<?= $labelClass ?? 'block text-sm font-semibold text-emerald-800 mb-1.5' ?>" for="admin-vacation-employee-picker-toggle"><?= I18n::get('ceo.select_employee') ?></label>
    <div class="flex items-center gap-2">
        <button
            type="button"
            id="admin-vacation-employee-picker-toggle"
            class="admin-vacation-employee-picker-toggle flex min-w-0 flex-1 items-center justify-between gap-3 rounded-xl border border-lime-200 bg-[#fffdf2] px-4 py-3 text-left transition-colors hover:border-lime-300 focus:outline-none focus:ring-2 focus:ring-[#E8007D]/30 focus:border-[#E8007D]"
            aria-expanded="false"
            aria-controls="admin-vacation-employee-picker-dropdown"
            aria-haspopup="listbox"
        >
            <span id="admin-vacation-employee-picker-summary" class="block min-w-0 truncate text-sm font-semibold text-emerald-900/60"><?= I18n::get('ceo.select_employee_ph') ?></span>
            <span class="shrink-0 flex h-7 w-7 items-center justify-center rounded-lg bg-white border border-lime-100 text-emerald-500" aria-hidden="true">
                <svg class="h-3.5 w-3.5 admin-vacation-employee-picker-chevron transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </span>
        </button>
        <?= easytime_tooltip(
            I18n::get('ceo.vacation_employee_reset_filters'),
            '<button type="button" id="admin-vacation-employee-picker-reset-btn" class="et-btn-secondary shrink-0 inline-flex h-[2.875rem] w-[2.875rem] items-center justify-center rounded-xl p-0" aria-label="' . htmlspecialchars(I18n::get('ceo.vacation_employee_reset_filters'), ENT_QUOTES, 'UTF-8') . '"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></button>',
            'inline-flex shrink-0 self-stretch'
        ) ?>
        <?= easytime_tooltip(
            I18n::get('ceo.vacation_employee_clear'),
            '<button type="button" id="admin-vacation-employee-picker-clear-btn" class="et-btn-secondary shrink-0 inline-flex h-[2.875rem] w-[2.875rem] items-center justify-center rounded-xl p-0" aria-label="' . htmlspecialchars(I18n::get('ceo.vacation_employee_clear'), ENT_QUOTES, 'UTF-8') . '"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>',
            'inline-flex shrink-0 self-stretch'
        ) ?>
    </div>

    <div
        id="admin-vacation-employee-picker-dropdown"
        class="hidden rounded-2xl border border-lime-200 bg-white p-3 sm:p-4 shadow-xl"
        role="listbox"
        aria-labelledby="admin-vacation-employee-picker-toggle"
    >
        <?php if ($hasStandorte): ?>
            <div class="mb-3 pb-3 border-b border-lime-100">
                <label class="block text-[11px] font-bold uppercase tracking-[0.12em] text-emerald-500 mb-1" for="admin-vacation-employee-picker-standort"><?= I18n::get('ceo.employee_filter_standort_label') ?></label>
                <select
                    id="admin-vacation-employee-picker-standort"
                    class="<?= $selectClassCompact ?> w-full text-xs font-semibold"
                    style="background-image: <?= $selectChevron ?>;"
                >
                    <option value=""><?= I18n::get('ceo.employee_filter_standort_all') ?></option>
                    <?php foreach ($standortePool as $standortOpt): ?>
                        <option value="<?= (int) $standortOpt['id'] ?>"><?= htmlspecialchars($standortOpt['ort']) ?></option>
                    <?php endforeach; ?>
                    <option value="none"><?= I18n::get('ceo.employee_filter_standort_none') ?></option>
                </select>
            </div>
        <?php endif; ?>

        <label class="sr-only" for="admin-vacation-employee-picker-search"><?= I18n::get('ceo.employee_filter_search_ph') ?></label>
        <input
            id="admin-vacation-employee-picker-search"
            type="search"
            autocomplete="off"
            placeholder="<?= htmlspecialchars(I18n::get('ceo.employee_filter_search_ph')) ?>"
            class="<?= $inputClassCompact ?> mb-3"
        >

        <div id="admin-vacation-employee-picker-list" class="et-scrollbar max-h-56 overflow-y-auto space-y-0.5 pr-1">
            <?php foreach ($pickerEmployees as $empOpt): ?>
                <?php
                $eid = (int) ($empOpt['id'] ?? 0);
                $empName = trim(($empOpt['firstname'] ?? '') . ' ' . ($empOpt['lastname'] ?? ''));
                $standortIds = array_values(array_filter(array_map('intval', $empOpt['standort_ids'] ?? [])));
                ?>
                <button
                    type="button"
                    class="admin-vacation-employee-picker-item w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-emerald-900 hover:bg-lime-50/80 transition-colors"
                    role="option"
                    data-employee-id="<?= $eid ?>"
                    data-employee-name="<?= htmlspecialchars(strtolower($empName)) ?>"
                    data-employee-display-name="<?= htmlspecialchars($empName) ?>"
                    data-standort-ids="<?= htmlspecialchars(implode(',', $standortIds)) ?>"
                ><?= htmlspecialchars($empName) ?></button>
            <?php endforeach; ?>
        </div>

        <p id="admin-vacation-employee-picker-empty" class="hidden py-4 text-center text-xs font-medium text-emerald-600/80"><?= I18n::get('ceo.vacation_employee_pool_empty') ?></p>
    </div>
</div>
