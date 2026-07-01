<?php
use App\Core\I18n;

/** @var list<array<string, mixed>> $standortePool */
/** @var list<array<string, mixed>> $abteilungenPool */
/** @var array<int, array<int, int>> $standortCoverageRules */
/** @var array<int, array<int, int>> $abteilungCoverageRules */
/** @var int $minStaffAvailable */
/** @var int $maxFenstertage */

$inputClass = $inputClass ?? 'w-full bg-[#fffdf2] border border-lime-200 rounded-xl px-4 py-3 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400';
$labelClass = $labelClass ?? 'block text-sm font-semibold text-emerald-800 mb-1.5';
$cellInputClass = 'w-11 h-10 bg-white border border-lime-200 rounded-lg px-1 text-center text-sm font-semibold text-emerald-900 tabular-nums outline-none focus:ring-2 focus:ring-lime-400';
$weekdays = [
    1 => I18n::get('coverage.weekday_mon'),
    2 => I18n::get('coverage.weekday_tue'),
    3 => I18n::get('coverage.weekday_wed'),
    4 => I18n::get('coverage.weekday_thu'),
    5 => I18n::get('coverage.weekday_fri'),
    6 => I18n::get('coverage.weekday_sat'),
    7 => I18n::get('coverage.weekday_sun'),
];

$renderCoverageGrid = static function (string $prefix, array $items, array $rules, string $fieldPrefix) use ($weekdays, $cellInputClass): void {
    if ($items === []) {
        return;
    }
    ?>
    <div class="space-y-2">
        <?php foreach ($items as $item): ?>
            <?php
            $id = (int) ($item['id'] ?? 0);
            $name = (string) ($item['name'] ?? $item['ort'] ?? '');
            $itemRules = $rules[$id] ?? [];
            ?>
            <details class="group rounded-xl border border-lime-200 bg-[#fffdf2]/40 open:bg-white transition-colors">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 marker:content-none">
                    <span class="text-sm font-semibold text-emerald-900 truncate"><?= htmlspecialchars($name) ?></span>
                    <svg class="h-4 w-4 shrink-0 text-emerald-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-4 pb-4 pt-1 overflow-x-auto">
                    <div class="inline-flex flex-wrap gap-x-2 gap-y-3 sm:gap-x-3">
                        <?php foreach ($weekdays as $dow => $label): ?>
                            <div class="flex flex-col items-center gap-1.5 shrink-0">
                                <span class="text-[10px] font-bold uppercase tracking-wide text-emerald-500 leading-none"><?= htmlspecialchars($label) ?></span>
                                <input
                                    type="number"
                                    min="0"
                                    max="99"
                                    name="<?= htmlspecialchars($fieldPrefix) ?>[<?= $id ?>][<?= $dow ?>]"
                                    value="<?= (int) ($itemRules[$dow] ?? 0) ?>"
                                    title="<?= htmlspecialchars(I18n::get('coverage.zero_hint')) ?>"
                                    aria-label="<?= htmlspecialchars($name . ' ' . $label) ?>"
                                    class="<?= $cellInputClass ?>"
                                >
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
    <?php
};
?>
<div class="bg-white rounded-3xl p-6 sm:p-7 shadow-xl border border-lime-100 space-y-6">
    <div>
        <h3 class="text-sm font-bold text-emerald-900"><?= I18n::get('settings.rules_title') ?></h3>
        <p class="text-xs text-emerald-600/80 mt-1 leading-relaxed max-w-3xl"><?= I18n::get('settings.coverage_rules_hint') ?></p>
    </div>

    <form method="POST" action="/?action=update_coverage_rules" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="<?= $labelClass ?>" for="settings-min-staff"><?= I18n::get('settings.min_staff') ?></label>
                <input id="settings-min-staff" type="number" min="0" name="min_staff_available" value="<?= (int) ($minStaffAvailable ?? 1) ?>" class="<?= $inputClass ?>">
                <p class="text-[11px] text-emerald-600/75 mt-1.5"><?= I18n::get('coverage.zero_hint') ?></p>
            </div>
            <div>
                <label class="<?= $labelClass ?>" for="settings-max-fenstertage"><?= I18n::get('settings.max_fenstertage') ?></label>
                <input id="settings-max-fenstertage" type="number" min="0" name="max_fenstertage" value="<?= (int) ($maxFenstertage ?? 0) ?>" class="<?= $inputClass ?>">
                <p class="text-[11px] text-emerald-600/75 mt-1.5"><?= I18n::get('settings.max_fenstertage_hint') ?></p>
            </div>
        </div>

        <?php if (($standortePool ?? []) !== []): ?>
            <div class="space-y-3">
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-500"><?= I18n::get('settings.coverage_standorte_title') ?></h4>
                    <p class="text-[11px] text-emerald-600/75 mt-1"><?= I18n::get('settings.coverage_standorte_hint') ?></p>
                </div>
                <?php
                $standortItems = array_map(static fn (array $s): array => ['id' => $s['id'], 'name' => $s['ort']], $standortePool);
                $renderCoverageGrid('standort', $standortItems, $standortCoverageRules ?? [], 'standort_coverage');
                ?>
            </div>
        <?php endif; ?>

        <?php if (($abteilungenPool ?? []) !== []): ?>
            <div class="space-y-3">
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-500"><?= I18n::get('settings.coverage_abteilungen_title') ?></h4>
                    <p class="text-[11px] text-emerald-600/75 mt-1"><?= I18n::get('settings.coverage_abteilungen_hint') ?></p>
                </div>
                <?php $renderCoverageGrid('abteilung', $abteilungenPool, $abteilungCoverageRules ?? [], 'abteilung_coverage'); ?>
            </div>
        <?php endif; ?>

        <div class="flex justify-end pt-2 border-t border-lime-100">
            <button type="submit" class="et-btn-primary font-bold px-6 py-3 rounded-xl text-sm"><?= I18n::get('settings.coverage_save_all') ?></button>
        </div>
    </form>
</div>
