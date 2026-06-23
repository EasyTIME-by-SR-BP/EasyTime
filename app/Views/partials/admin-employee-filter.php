<?php
use App\Core\I18n;

/** @var list<array<string, mixed>> $employees */

$inputClassCompact = $inputClassCompact ?? 'w-full bg-[#fffdf2] border border-lime-200 rounded-lg px-2.5 py-2 text-xs text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400';

$filterEmployees = array_values(array_filter(
    $employees ?? [],
    static fn (array $e): bool => ($e['role'] ?? '') === 'Employee'
));
usort($filterEmployees, static function (array $a, array $b): int {
    $na = strtolower(trim(($a['lastname'] ?? '') . ' ' . ($a['firstname'] ?? '')));
    $nb = strtolower(trim(($b['lastname'] ?? '') . ' ' . ($b['firstname'] ?? '')));
    return $na <=> $nb;
});

if ($filterEmployees === []) {
    return;
}
?>
<div id="ceo-employee-filter-anchor" class="w-full min-w-0 max-w-full">
<div id="ceo-employee-filter" class="relative w-full min-w-0 max-w-full bg-white rounded-3xl shadow-xl border border-lime-100 px-4 sm:px-7 py-3">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
        <span class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 shrink-0"><?= I18n::get('ceo.employee_filter') ?></span>
        <div class="flex min-w-0 w-full items-center gap-2">
            <button
                type="button"
                id="ceo-employee-filter-toggle"
                class="flex min-w-0 flex-1 items-center justify-between gap-2 rounded-xl border border-lime-200 bg-[#fffdf2] px-3 py-2 text-left text-sm font-semibold text-emerald-900 hover:border-lime-300 transition-colors"
                aria-expanded="false"
                aria-controls="ceo-employee-filter-dropdown"
            >
                <span id="ceo-employee-filter-summary" class="truncate"><?= I18n::get('ceo.employee_filter_badge_all') ?></span>
                <span class="shrink-0 text-[10px] font-bold uppercase tracking-wider text-emerald-500" aria-hidden="true">▼</span>
            </button>
            <button type="button" id="ceo-employee-filter-reset-btn" class="et-btn-secondary shrink-0 px-2.5 sm:px-3 py-2 rounded-xl text-xs font-bold" title="<?= htmlspecialchars(I18n::get('ceo.employee_filter_show_all')) ?>">
                <span class="hidden sm:inline"><?= I18n::get('ceo.employee_filter_show_all') ?></span>
                <span class="sm:hidden" aria-hidden="true">↺</span>
            </button>
            <button
                type="button"
                id="ceo-employee-filter-pin-btn"
                class="et-btn-secondary shrink-0 hidden lg:inline-flex h-7 w-7 items-center justify-center rounded-lg p-0"
                aria-pressed="false"
                title="<?= htmlspecialchars(I18n::get('ceo.employee_filter_pin')) ?>"
                aria-label="<?= htmlspecialchars(I18n::get('ceo.employee_filter_pin')) ?>"
            >
                <svg class="h-3.5 w-3.5 ceo-employee-filter-pin-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 4v6l-2 2v8h10v-8l-2-2V4M9 4h6"/>
                </svg>
            </button>
        </div>
    </div>
    <div
        id="ceo-employee-filter-dropdown"
        class="hidden absolute left-0 right-0 top-full z-30 mt-2 rounded-2xl border border-lime-200 bg-white p-3 shadow-xl"
    >
        <label class="sr-only" for="ceo-employee-filter-search"><?= I18n::get('ceo.employee_filter_search_ph') ?></label>
        <input
            id="ceo-employee-filter-search"
            type="search"
            autocomplete="off"
            placeholder="<?= htmlspecialchars(I18n::get('ceo.employee_filter_search_ph')) ?>"
            class="<?= $inputClassCompact ?> mb-2"
        >
        <div id="ceo-employee-filter-list" class="max-h-52 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-0.5 pr-1">
            <?php foreach ($filterEmployees as $empOpt): ?>
                <?php
                $eid = (int) ($empOpt['id'] ?? 0);
                $empName = trim(($empOpt['firstname'] ?? '') . ' ' . ($empOpt['lastname'] ?? ''));
                ?>
                <label
                    class="ceo-employee-filter-item et-checkbox cursor-pointer rounded-lg py-1.5 hover:bg-lime-50/80"
                    data-employee-name="<?= htmlspecialchars(strtolower($empName)) ?>"
                >
                    <input type="checkbox" class="et-checkbox__input ceo-employee-filter-cb" value="<?= $eid ?>">
                    <span class="et-checkbox__box" aria-hidden="true"></span>
                    <span class="text-xs font-medium text-emerald-900"><?= htmlspecialchars($empName) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</div>
