<?php
use App\Core\I18n;

/** @var list<array<string, mixed>> $employees */
/** @var list<array<string, mixed>> $standortePool */

$inputClassCompact = $inputClassCompact ?? 'w-full bg-[#fffdf2] border border-lime-200 rounded-lg px-2.5 py-2 text-xs text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400';
$selectClassCompact = $inputClassCompact . ' appearance-none pr-8 bg-[length:10px] bg-[right_0.65rem_center] bg-no-repeat';
$selectChevron = "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23166534'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E\")";

$filterEmployees = array_values(array_filter(
    $employees ?? [],
    static fn (array $e): bool => ($e['role'] ?? '') === 'Employee'
));
usort($filterEmployees, static function (array $a, array $b): int {
    $primaryA = (int) ($a['primary_standort_id'] ?? 0);
    $primaryB = (int) ($b['primary_standort_id'] ?? 0);
    if ($primaryA !== $primaryB) {
        if ($primaryA === 0) return 1;
        if ($primaryB === 0) return -1;
    }
    $na = strtolower(trim(($a['lastname'] ?? '') . ' ' . ($a['firstname'] ?? '')));
    $nb = strtolower(trim(($b['lastname'] ?? '') . ' ' . ($b['firstname'] ?? '')));
    return $na <=> $nb;
});

if ($filterEmployees === []) {
    return;
}

$hasStandorte = !empty($standortePool);
?>
<div id="ceo-employee-filter-anchor" class="w-full min-w-0 max-w-full" data-tour="ceo-employee-filter">
<div id="ceo-employee-filter" class="relative w-full min-w-0 max-w-full bg-white rounded-3xl shadow-xl border border-lime-100 px-4 sm:px-7 py-3">
    <div class="flex items-center gap-2 sm:gap-3">
        <span class="hidden sm:inline text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 shrink-0"><?= I18n::get('ceo.employee_filter') ?></span>
        <button
            type="button"
            id="ceo-employee-filter-toggle"
            class="flex min-w-0 flex-1 items-center justify-between gap-3 rounded-xl border border-lime-200 bg-[#fffdf2] px-3 sm:px-4 py-2.5 text-left hover:border-lime-300 transition-colors"
            aria-expanded="false"
            aria-controls="ceo-employee-filter-dropdown"
        >
            <span id="ceo-employee-filter-summary" class="block min-w-0 truncate text-sm font-semibold text-emerald-900"><?= I18n::get('ceo.employee_filter_badge_all') ?></span>
            <span class="shrink-0 flex h-7 w-7 items-center justify-center rounded-lg bg-white border border-lime-100 text-emerald-500" aria-hidden="true">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </span>
        </button>
        <?= easytime_tooltip(
            I18n::get('ceo.employee_filter_show_all'),
            '<button type="button" id="ceo-employee-filter-reset-btn" class="et-btn-secondary shrink-0 inline-flex h-10 w-10 items-center justify-center rounded-xl p-0" aria-label="' . htmlspecialchars(I18n::get('ceo.employee_filter_show_all'), ENT_QUOTES, 'UTF-8') . '"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></button>',
            'inline-flex shrink-0'
        ) ?>
        <?= easytime_tooltip(
            I18n::get('ceo.employee_filter_pin'),
            '<button type="button" id="ceo-employee-filter-pin-btn" class="et-btn-secondary shrink-0 hidden lg:inline-flex h-10 w-10 items-center justify-center rounded-xl p-0" aria-pressed="false" aria-label="' . htmlspecialchars(I18n::get('ceo.employee_filter_pin'), ENT_QUOTES, 'UTF-8') . '"><svg class="h-4 w-4 ceo-employee-filter-pin-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 4v6l-2 2v8h10v-8l-2-2V4M9 4h6"/></svg></button>',
            'inline-flex shrink-0 hidden lg:inline-flex',
            'top',
            'ceo-employee-filter-pin-tooltip'
        ) ?>
    </div>

    <div
        id="ceo-employee-filter-dropdown"
        class="hidden absolute left-0 right-0 top-full z-30 mt-2 rounded-2xl border border-lime-200 bg-white p-3 sm:p-4 shadow-xl"
    >
        <?php if ($hasStandorte): ?>
            <div class="mb-3 pb-3 border-b border-lime-100">
                <label class="block text-[11px] font-bold uppercase tracking-[0.12em] text-emerald-500 mb-1" for="ceo-employee-filter-standort"><?= I18n::get('ceo.employee_filter_standort_label') ?></label>
                <div class="flex items-stretch gap-2">
                    <select
                        id="ceo-employee-filter-standort"
                        class="<?= $selectClassCompact ?> flex-1 min-w-0 text-xs font-semibold"
                        style="background-image: <?= $selectChevron ?>;"
                    >
                        <option value=""><?= I18n::get('ceo.employee_filter_standort_all') ?></option>
                        <?php foreach ($standortePool as $standortOpt): ?>
                            <option value="<?= (int) $standortOpt['id'] ?>"><?= htmlspecialchars($standortOpt['ort']) ?></option>
                        <?php endforeach; ?>
                        <option value="none"><?= I18n::get('ceo.employee_filter_standort_none') ?></option>
                    </select>
                    <div id="ceo-employee-filter-select-standort-wrap" class="hidden shrink-0 self-stretch">
                        <?= easytime_tooltip(
                            I18n::get('ceo.employee_filter_select_standort_all'),
                            '<button type="button" id="ceo-employee-filter-select-standort-btn" class="et-btn-secondary inline-flex h-full min-h-[2.25rem] w-10 items-center justify-center rounded-lg p-0" aria-label="' . htmlspecialchars(I18n::get('ceo.employee_filter_select_standort_all'), ENT_QUOTES, 'UTF-8') . '"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"/></svg></button>',
                            'inline-flex h-full',
                            'bottom',
                            'ceo-employee-filter-select-standort-tooltip'
                        ) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <label class="sr-only" for="ceo-employee-filter-search"><?= I18n::get('ceo.employee_filter_search_ph') ?></label>
        <input
            id="ceo-employee-filter-search"
            type="search"
            autocomplete="off"
            placeholder="<?= htmlspecialchars(I18n::get('ceo.employee_filter_search_ph')) ?>"
            class="<?= $inputClassCompact ?> mb-3"
        >
        <div id="ceo-employee-filter-list" class="max-h-56 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-0.5 pr-1">
            <?php foreach ($filterEmployees as $empOpt): ?>
                <?php
                $eid = (int) ($empOpt['id'] ?? 0);
                $empName = trim(($empOpt['firstname'] ?? '') . ' ' . ($empOpt['lastname'] ?? ''));
                $standortIds = array_values(array_filter(array_map('intval', $empOpt['standort_ids'] ?? [])));
                $primaryStandortId = (int) ($empOpt['primary_standort_id'] ?? 0);
                ?>
                <label
                    class="ceo-employee-filter-item et-checkbox cursor-pointer rounded-lg py-1.5 hover:bg-lime-50/80"
                    data-employee-name="<?= htmlspecialchars(strtolower($empName)) ?>"
                    data-employee-display-name="<?= htmlspecialchars($empName) ?>"
                    data-standort-ids="<?= htmlspecialchars(implode(',', $standortIds)) ?>"
                    data-primary-standort-id="<?= $primaryStandortId ?>"
                >
                    <input type="checkbox" class="et-checkbox__input ceo-employee-filter-cb" value="<?= $eid ?>">
                    <span class="et-checkbox__box" aria-hidden="true"></span>
                    <span class="min-w-0">
                        <span class="block text-xs font-medium text-emerald-900 truncate"><?= htmlspecialchars($empName) ?></span>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</div>
