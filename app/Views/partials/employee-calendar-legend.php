<?php
use App\Core\I18n;

$statusItems = [
    ['color' => '#16A34A', 'label' => I18n::get('emp.status_approved')],
    ['color' => '#F59E0B', 'label' => I18n::get('emp.status_pending')],
    ['color' => '#EA580C', 'label' => I18n::get('emp.status_storno_requested')],
    ['color' => '#7C3AED', 'label' => I18n::get('emp.status_change_requested')],
    ['color' => '#DC2626', 'label' => I18n::get('emp.status_rejected')],
    ['color' => '#9CA3AF', 'label' => I18n::get('emp.status_cancelled')],
];
?>
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-6 sm:gap-y-3" data-tour="calendar-legend" role="list" aria-label="<?= htmlspecialchars(I18n::get('emp.legend_title')) ?>">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2.5" role="list">
        <?php foreach ($statusItems as $item): ?>
            <span class="inline-flex items-center gap-2.5 text-sm font-medium text-emerald-800" role="listitem">
                <span
                    class="inline-block h-2.5 w-8 shrink-0 rounded-md shadow-sm"
                    style="background-color: <?= htmlspecialchars($item['color']) ?>;"
                    aria-hidden="true"
                ></span>
                <?= htmlspecialchars($item['label']) ?>
            </span>
        <?php endforeach; ?>
    </div>

    <div class="hidden sm:block h-8 w-px bg-lime-200/90 shrink-0" aria-hidden="true"></div>

    <div class="flex flex-wrap items-center gap-x-4 gap-y-2.5" role="list">
        <span class="inline-flex items-center gap-2.5 text-sm font-medium text-emerald-800" role="listitem">
            <span
                class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[rgba(232,0,125,0.1)] text-[11px] font-bold text-[#E8007D] shadow-[inset_0_0_0_2px_rgba(232,0,125,0.55)]"
                aria-hidden="true"
            >·</span>
            <?= I18n::get('emp.legend_today') ?>
        </span>
        <span class="inline-flex items-center gap-2.5 text-sm font-medium text-emerald-800" role="listitem">
            <span
                class="inline-block h-6 w-8 shrink-0 rounded-md bg-[#FFF8E7] shadow-[inset_0_0_0_2px_rgba(232,0,125,0.55)]"
                aria-hidden="true"
            ></span>
            <?= I18n::get('emp.legend_selection') ?>
        </span>
        <span class="inline-flex items-center gap-2.5 text-sm font-medium text-emerald-800" role="listitem">
            <span
                class="inline-block h-6 w-8 shrink-0 rounded-md bg-red-100/90 shadow-[inset_0_0_0_2px_rgba(220,38,38,0.55)]"
                aria-hidden="true"
            ></span>
            <?= I18n::get('emp.legend_blocked') ?>
        </span>
    </div>
</div>
