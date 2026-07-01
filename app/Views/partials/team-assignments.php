<?php
use App\Core\I18n;

/** @var list<array<string, mixed>> $licenseClassesPool */
/** @var list<array<string, mixed>> $abteilungenPool */
/** @var list<array<string, mixed>> $standortePool */
/** @var array<string, mixed>|null $selectedTeamUser */
/** @var string $assignmentFormPrefix */

$prefix = $assignmentFormPrefix ?? 'team';
$selectedClassIds = array_map('strval', $selectedTeamUser['license_class_ids'] ?? []);
$selectedAbteilungIds = array_map('strval', $selectedTeamUser['abteilung_ids'] ?? []);
$selectedStandortIds = array_map('strval', $selectedTeamUser['standort_ids'] ?? []);
$primaryStandortId = (string) ($selectedTeamUser['primary_standort_id'] ?? '');
$checkboxBoxClass = 'max-h-44 overflow-y-auto rounded-xl border border-lime-200 bg-[#fffdf2] p-3 space-y-2';
?>
<div class="md:col-span-2">
    <label class="block text-sm font-semibold text-emerald-800 mb-1.5"><?= I18n::get('ceo.abteilungen') ?></label>
    <?php if (($abteilungenPool ?? []) === []): ?>
        <p class="text-xs text-emerald-600/80"><?= I18n::get('ceo.abteilungen_empty') ?></p>
    <?php else: ?>
        <div class="<?= $checkboxBoxClass ?>">
            <?php foreach ($abteilungenPool as $abteilung): ?>
                <?php $aid = (string) (int) $abteilung['id']; ?>
                <label class="et-checkbox text-sm">
                    <input type="checkbox" name="abteilung_ids[]" value="<?= (int) $abteilung['id'] ?>" class="et-checkbox__input" <?= in_array($aid, $selectedAbteilungIds, true) ? 'checked' : '' ?>>
                    <span class="et-checkbox__box" aria-hidden="true"></span>
                    <span><?= htmlspecialchars($abteilung['name']) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="md:col-span-2">
    <label class="block text-sm font-semibold text-emerald-800 mb-1.5"><?= I18n::get('ceo.license_classes') ?></label>
    <?php if (($licenseClassesPool ?? []) === []): ?>
        <p class="text-xs text-emerald-600/80"><?= I18n::get('ceo.license_classes_empty') ?></p>
    <?php else: ?>
        <div class="<?= $checkboxBoxClass ?>">
            <?php foreach ($licenseClassesPool as $class): ?>
                <?php $cid = (string) (int) $class['id']; ?>
                <label class="et-checkbox text-sm">
                    <input type="checkbox" name="license_class_ids[]" value="<?= (int) $class['id'] ?>" class="et-checkbox__input" <?= in_array($cid, $selectedClassIds, true) ? 'checked' : '' ?>>
                    <span class="et-checkbox__box" aria-hidden="true"></span>
                    <span><?= htmlspecialchars($class['name']) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="md:col-span-2">
    <label class="block text-sm font-semibold text-emerald-800 mb-1.5"><?= I18n::get('ceo.standorte') ?></label>
    <?php if (($standortePool ?? []) === []): ?>
        <p class="text-xs text-emerald-600/80"><?= I18n::get('ceo.standorte_empty') ?></p>
    <?php else: ?>
        <div class="<?= $checkboxBoxClass ?>" id="<?= htmlspecialchars($prefix) ?>-standorte-box">
            <?php foreach ($standortePool as $standort): ?>
                <?php $sid = (string) (int) $standort['id']; ?>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <label class="et-checkbox text-sm flex-1 min-w-0">
                        <input type="checkbox" name="standort_ids[]" value="<?= (int) $standort['id'] ?>" class="et-checkbox__input <?= htmlspecialchars($prefix) ?>-standort-cb" data-standort-id="<?= (int) $standort['id'] ?>" <?= in_array($sid, $selectedStandortIds, true) ? 'checked' : '' ?>>
                        <span class="et-checkbox__box" aria-hidden="true"></span>
                        <span class="truncate"><?= htmlspecialchars($standort['ort']) ?></span>
                    </label>
                    <label class="inline-flex items-center gap-1.5 text-xs text-emerald-700 shrink-0">
                        <input type="radio" name="primary_standort_id" value="<?= (int) $standort['id'] ?>" class="accent-lime-500 <?= htmlspecialchars($prefix) ?>-standort-primary" <?= $primaryStandortId === $sid ? 'checked' : '' ?> <?= in_array($sid, $selectedStandortIds, true) ? '' : 'disabled' ?>>
                        <span><?= I18n::get('ceo.standort_primary') ?></span>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="text-xs text-emerald-600/80 mt-2"><?= I18n::get('ceo.standort_primary_hint') ?></p>
    <?php endif; ?>
</div>

<script>
(function () {
    const box = document.getElementById(<?= json_encode($prefix . '-standorte-box') ?>);
    if (!box) return;
    const sync = () => {
        box.querySelectorAll('.<?= htmlspecialchars($prefix) ?>-standort-cb').forEach((cb) => {
            const radio = box.querySelector('.<?= htmlspecialchars($prefix) ?>-standort-primary[value="' + cb.value + '"]');
            if (!radio) return;
            radio.disabled = !cb.checked;
            if (!cb.checked && radio.checked) radio.checked = false;
        });
        const enabled = box.querySelectorAll('.<?= htmlspecialchars($prefix) ?>-standort-primary:not(:disabled)');
        if (enabled.length && !box.querySelector('.<?= htmlspecialchars($prefix) ?>-standort-primary:checked:not(:disabled)')) {
            enabled[0].checked = true;
        }
    };
    box.addEventListener('change', sync);
    sync();
})();
</script>
