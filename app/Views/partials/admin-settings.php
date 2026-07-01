<?php
use App\Core\I18n;

/** @var list<array<string, mixed>> $licenseClassesPool */
/** @var list<array<string, mixed>> $abteilungenPool */
/** @var list<array<string, mixed>> $standortePool */
/** @var string $settingsStandortView */
/** @var array<string, mixed>|null $selectedSettingsStandort */
/** @var int $minStaffAvailable */
/** @var int $maxFenstertage */

$inputClass = 'w-full bg-[#fffdf2] border border-lime-200 rounded-xl px-4 py-3 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400';
$labelClass = 'block text-sm font-semibold text-emerald-800 mb-1.5';

$formatStandortMeta = static function (array $standort): string {
    $parts = [];
    $street = trim((string) ($standort['strasse'] ?? '') . ' ' . (string) ($standort['hausnummer'] ?? ''));
    if ($street !== '') {
        $parts[] = $street;
    }
    $city = trim((string) ($standort['plz'] ?? '') . ' ' . (string) ($standort['ort'] ?? ''));
    if ($city !== '') {
        $parts[] = $city;
    }
    if (($standort['kostenstelle'] ?? null) !== null && (int) $standort['kostenstelle'] > 0) {
        $parts[] = I18n::get('settings.standort_kostenstelle') . ' ' . (int) $standort['kostenstelle'];
    }
    return implode(' · ', $parts);
};

$isStandortCreate = $settingsStandortView === 'create';
$isStandortDetail = $settingsStandortView === 'detail' && $selectedSettingsStandort;
?>
<div class="space-y-8 w-full" data-tour="admin-settings">
    <?php if ($isStandortCreate || $isStandortDetail): ?>
        <div class="bg-white rounded-3xl border border-lime-100 shadow-xl p-6 sm:p-7">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                <a href="/?tab=settings" class="inline-flex items-center text-sm font-bold text-emerald-700 hover:text-emerald-900">← <?= I18n::get('settings.back_overview') ?></a>
            </div>

            <?php if ($isStandortCreate): ?>
                <h2 class="text-2xl font-bold text-emerald-900 tracking-tight mb-1"><?= I18n::get('settings.standort_create_title') ?></h2>
                <p class="text-sm text-emerald-600/80 mb-6"><?= I18n::get('settings.standorte_hint') ?></p>
                <?php $standortForm = ['id' => 0, 'ort' => '', 'kostenstelle' => null, 'strasse' => '', 'hausnummer' => '', 'plz' => null]; ?>
                <?php $standortFormAction = '/?action=create_standort'; ?>
            <?php else: ?>
                <h2 class="text-2xl font-bold text-emerald-900 tracking-tight mb-1"><?= htmlspecialchars($selectedSettingsStandort['ort']) ?></h2>
                <p class="text-sm text-emerald-600/80 mb-6"><?= htmlspecialchars($formatStandortMeta($selectedSettingsStandort)) ?></p>
                <?php $standortForm = $selectedSettingsStandort; ?>
                <?php $standortFormAction = '/?action=update_standort'; ?>
            <?php endif; ?>

            <form method="POST" action="<?= htmlspecialchars($standortFormAction) ?>" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if (!$isStandortCreate): ?>
                    <input type="hidden" name="standort_id" value="<?= (int) $standortForm['id'] ?>">
                <?php endif; ?>
                <div class="md:col-span-2">
                    <label class="<?= $labelClass ?>" for="standort-ort"><?= I18n::get('settings.standort_ort') ?></label>
                    <input id="standort-ort" type="text" name="ort" value="<?= htmlspecialchars($standortForm['ort']) ?>" required class="<?= $inputClass ?>">
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="standort-kosten"><?= I18n::get('settings.standort_kostenstelle') ?></label>
                    <input id="standort-kosten" type="number" name="kostenstelle" value="<?= $standortForm['kostenstelle'] !== null ? (int) $standortForm['kostenstelle'] : '' ?>" class="<?= $inputClass ?>">
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="standort-plz"><?= I18n::get('settings.standort_plz') ?></label>
                    <input id="standort-plz" type="number" name="plz" value="<?= $standortForm['plz'] !== null ? (int) $standortForm['plz'] : '' ?>" class="<?= $inputClass ?>">
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="standort-str"><?= I18n::get('settings.standort_strasse') ?></label>
                    <input id="standort-str" type="text" name="strasse" value="<?= htmlspecialchars($standortForm['strasse']) ?>" class="<?= $inputClass ?>">
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="standort-nr"><?= I18n::get('settings.standort_hausnummer') ?></label>
                    <input id="standort-nr" type="text" name="hausnummer" value="<?= htmlspecialchars($standortForm['hausnummer']) ?>" class="<?= $inputClass ?>">
                </div>
                <div class="md:col-span-2 flex justify-end pt-2">
                    <button type="submit" class="et-btn-primary px-6 py-3 rounded-xl text-sm font-bold"><?= I18n::get('ceo.save') ?></button>
                </div>
            </form>

            <?php if ($isStandortDetail): ?>
                <form method="POST" action="/?action=delete_standort" onsubmit="return confirm('<?= htmlspecialchars(I18n::get('settings.delete_confirm'), ENT_QUOTES) ?>');" class="flex justify-end mt-4 pt-4 border-t border-lime-100">
                    <input type="hidden" name="standort_id" value="<?= (int) $standortForm['id'] ?>">
                    <button type="submit" class="text-sm font-bold text-red-600 hover:text-red-700 border border-red-200 rounded-xl px-4 py-2.5 hover:bg-red-50 transition-colors"><?= I18n::get('ceo.delete') ?></button>
                </form>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 mb-2"><?= I18n::get('ceo.nav_settings') ?></h2>
                <p class="text-sm text-emerald-600/80 leading-relaxed max-w-2xl"><?= I18n::get('settings.holidays_note') ?></p>
            </div>
        </div>

        <?php include __DIR__ . '/admin-coverage-rules.php'; ?>

        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-xl border border-lime-100 space-y-5" data-tour="admin-settings-pools">
                <div>
                    <h3 class="text-sm font-bold text-emerald-900"><?= I18n::get('settings.license_classes_title') ?></h3>
                    <p class="text-xs text-emerald-600/80 mt-1 leading-relaxed"><?= I18n::get('settings.license_classes_hint') ?></p>
                </div>

                <div class="rounded-xl border border-lime-100 overflow-hidden divide-y divide-lime-100">
                    <?php if (($licenseClassesPool ?? []) === []): ?>
                        <p class="text-sm text-emerald-600/80 px-4 py-6 text-center"><?= I18n::get('settings.license_classes_empty') ?></p>
                    <?php else: ?>
                        <?php foreach ($licenseClassesPool as $class): ?>
                            <div class="flex items-center justify-between gap-3 px-4 py-3 bg-[#fffdf2]/30 hover:bg-lime-50/80 transition-colors">
                                <span class="text-sm font-semibold text-emerald-900 truncate"><?= htmlspecialchars($class['name']) ?></span>
                                <form method="POST" action="/?action=delete_license_class" onsubmit="return confirm('<?= htmlspecialchars(I18n::get('settings.delete_confirm'), ENT_QUOTES) ?>');">
                                    <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                                    <button type="submit" class="text-xs font-bold text-red-600 hover:text-red-700 px-2 py-1 rounded-lg hover:bg-red-50 shrink-0"><?= I18n::get('ceo.delete') ?></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form method="POST" action="/?action=create_license_class" class="flex flex-col sm:flex-row gap-3 sm:items-end border-t border-lime-100 pt-4">
                    <div class="flex-1 min-w-0">
                        <label class="<?= $labelClass ?>" for="new-license-class"><?= I18n::get('settings.license_class_add') ?></label>
                        <input id="new-license-class" type="text" name="name" required maxlength="120" placeholder="<?= htmlspecialchars(I18n::get('settings.license_class_ph')) ?>" class="<?= $inputClass ?>">
                    </div>
                    <button type="submit" class="et-btn-primary font-bold px-5 py-3 rounded-xl text-sm shrink-0"><?= I18n::get('settings.add') ?></button>
                </form>

                <div class="h-px bg-gradient-to-r from-transparent via-lime-200 to-transparent" aria-hidden="true"></div>

                <div>
                    <h3 class="text-sm font-bold text-emerald-900"><?= I18n::get('settings.abteilungen_title') ?></h3>
                    <p class="text-xs text-emerald-600/80 mt-1 leading-relaxed"><?= I18n::get('settings.abteilungen_hint') ?></p>
                </div>

                <div class="rounded-xl border border-lime-100 overflow-hidden divide-y divide-lime-100">
                    <?php if (($abteilungenPool ?? []) === []): ?>
                        <p class="text-sm text-emerald-600/80 px-4 py-6 text-center"><?= I18n::get('settings.abteilungen_empty') ?></p>
                    <?php else: ?>
                        <?php foreach ($abteilungenPool as $abteilung): ?>
                            <div class="flex items-center justify-between gap-3 px-4 py-3 bg-[#fffdf2]/30 hover:bg-lime-50/80 transition-colors">
                                <span class="text-sm font-semibold text-emerald-900 truncate"><?= htmlspecialchars($abteilung['name']) ?></span>
                                <form method="POST" action="/?action=delete_abteilung" onsubmit="return confirm('<?= htmlspecialchars(I18n::get('settings.delete_confirm'), ENT_QUOTES) ?>');">
                                    <input type="hidden" name="abteilung_id" value="<?= (int) $abteilung['id'] ?>">
                                    <button type="submit" class="text-xs font-bold text-red-600 hover:text-red-700 px-2 py-1 rounded-lg hover:bg-red-50 shrink-0"><?= I18n::get('ceo.delete') ?></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form method="POST" action="/?action=create_abteilung" class="flex flex-col sm:flex-row gap-3 sm:items-end border-t border-lime-100 pt-4">
                    <div class="flex-1 min-w-0">
                        <label class="<?= $labelClass ?>" for="new-abteilung"><?= I18n::get('settings.abteilung_add') ?></label>
                        <input id="new-abteilung" type="text" name="name" required maxlength="120" placeholder="<?= htmlspecialchars(I18n::get('settings.abteilung_ph')) ?>" class="<?= $inputClass ?>">
                    </div>
                    <button type="submit" class="et-btn-primary font-bold px-5 py-3 rounded-xl text-sm shrink-0"><?= I18n::get('settings.add') ?></button>
                </form>
            </div>

        <div class="bg-white rounded-3xl border border-lime-100 shadow-xl p-6 sm:p-7">
            <div class="flex flex-wrap items-end justify-between gap-4 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-emerald-900"><?= I18n::get('settings.standorte_title') ?></h3>
                    <p class="text-xs text-emerald-600/80 mt-1 leading-relaxed"><?= I18n::get('settings.standorte_hint') ?></p>
                </div>
                <a href="/?tab=settings&standort_view=create" class="inline-flex items-center gap-2 et-btn-primary px-5 py-3 rounded-xl text-sm font-bold shadow-lg shadow-lime-400/20 shrink-0">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                    <?= I18n::get('settings.standort_add') ?>
                </a>
            </div>

            <?php if (($standortePool ?? []) === []): ?>
                <p class="text-sm text-emerald-600/80 py-8 text-center rounded-2xl border border-dashed border-lime-200 bg-[#fffdf2]/40"><?= I18n::get('settings.standorte_empty') ?></p>
            <?php else: ?>
                <div class="rounded-xl border border-lime-100 overflow-hidden divide-y divide-lime-100">
                    <?php foreach ($standortePool as $standort): ?>
                        <a
                            href="/?tab=settings&standort_view=detail&standort_id=<?= (int) $standort['id'] ?>"
                            class="flex items-center gap-3 px-4 py-3 sm:py-3.5 text-sm transition-colors hover:bg-lime-50/80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-lime-400"
                        >
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-emerald-900 truncate"><?= htmlspecialchars($standort['ort']) ?></div>
                                <?php $meta = $formatStandortMeta($standort); ?>
                                <?php if ($meta !== ''): ?>
                                    <div class="text-[11px] text-emerald-600/80 truncate mt-0.5"><?= htmlspecialchars($meta) ?></div>
                                <?php endif; ?>
                            </div>
                            <svg class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
