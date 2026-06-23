<?php
use App\Core\I18n;

/** @var string $sectionOpen */
/** @var string $sectionPlanned */
/** @var string $sectionPast */
/** @var bool $showEmployeeName */

$showEmployeeName = $showEmployeeName ?? false;
$listWrapClass = 'rounded-xl border border-lime-100 overflow-hidden divide-y divide-lime-100';
$rowClass = 'w-full flex items-center gap-2 sm:gap-3 px-3 py-2 sm:py-2.5 text-left text-sm transition-colors hover:bg-lime-50/80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-lime-400';
?>
<template x-if="openItems.length > 0">
    <div class="mb-5">
        <h3 class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#E8007D] mb-2 px-0.5"><?= htmlspecialchars($sectionOpen) ?></h3>
        <div class="<?= $listWrapClass ?>">
            <template x-for="item in openItems" :key="'open-' + item.id">
                <button type="button" @click="openDetail(item.id)" class="<?= $rowClass ?>">
                    <span class="shrink-0 w-9 tabular-nums text-[11px] font-bold text-emerald-500" x-text="'#' + item.id"></span>
                    <?php if ($showEmployeeName): ?>
                    <span class="hidden sm:block shrink-0 w-28 truncate text-xs font-medium text-emerald-700" x-text="item.employee_name"></span>
                    <?php endif; ?>
                    <span class="flex-1 min-w-0">
                        <span class="block truncate font-semibold text-emerald-900 tabular-nums" x-text="formatRange(item)"></span>
                        <?php if ($showEmployeeName): ?>
                        <span class="sm:hidden block truncate text-[11px] text-emerald-600 mt-0.5" x-text="item.employee_name"></span>
                        <?php endif; ?>
                    </span>
                    <span class="shrink-0 tabular-nums text-xs text-emerald-600 whitespace-nowrap">
                        <span class="font-bold text-emerald-900" x-text="item.net_days"></span>
                        <span class="hidden sm:inline"><?= I18n::get('emp.days') ?></span>
                        <span class="sm:hidden">T</span>
                    </span>
                    <span class="shrink-0 inline-flex max-w-[7.5rem] truncate px-2 py-0.5 rounded-full text-[10px] font-bold border" :class="statusBadgeClass(item.status)" x-text="item.status_label"></span>
                    <svg class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </template>
        </div>
    </div>
</template>

<template x-if="plannedItems.length > 0">
    <div class="mb-5">
        <h3 class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-500 mb-2 px-0.5"><?= htmlspecialchars($sectionPlanned) ?></h3>
        <div class="<?= $listWrapClass ?> border-green-100 divide-green-50">
            <template x-for="item in plannedItems" :key="'planned-' + item.id">
                <button type="button" @click="openDetail(item.id)" class="<?= $rowClass ?> hover:bg-green-50/60">
                    <span class="shrink-0 w-9 tabular-nums text-[11px] font-bold text-emerald-500" x-text="'#' + item.id"></span>
                    <?php if ($showEmployeeName): ?>
                    <span class="hidden sm:block shrink-0 w-28 truncate text-xs font-medium text-emerald-700" x-text="item.employee_name"></span>
                    <?php endif; ?>
                    <span class="flex-1 min-w-0">
                        <span class="block truncate font-semibold text-emerald-900 tabular-nums" x-text="formatRange(item)"></span>
                        <?php if ($showEmployeeName): ?>
                        <span class="sm:hidden block truncate text-[11px] text-emerald-600 mt-0.5" x-text="item.employee_name"></span>
                        <?php endif; ?>
                    </span>
                    <span class="shrink-0 tabular-nums text-xs text-emerald-600 whitespace-nowrap">
                        <span class="font-bold text-emerald-900" x-text="item.net_days"></span>
                        <span class="hidden sm:inline"><?= I18n::get('emp.days') ?></span>
                        <span class="sm:hidden">T</span>
                    </span>
                    <span class="shrink-0 inline-flex max-w-[7.5rem] truncate px-2 py-0.5 rounded-full text-[10px] font-bold border" :class="statusBadgeClass(item.status)" x-text="item.status_label"></span>
                    <svg class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </template>
        </div>
    </div>
</template>

<template x-if="pastItems.length > 0">
    <div>
        <h3 class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-500 mb-2 px-0.5" x-show="openItems.length > 0 || plannedItems.length > 0"><?= htmlspecialchars($sectionPast) ?></h3>
        <div class="<?= $listWrapClass ?>">
            <template x-for="item in pastItems" :key="'past-' + item.id">
                <button type="button" @click="openDetail(item.id)" class="<?= $rowClass ?>">
                    <span class="shrink-0 w-9 tabular-nums text-[11px] font-bold text-emerald-500" x-text="'#' + item.id"></span>
                    <?php if ($showEmployeeName): ?>
                    <span class="hidden sm:block shrink-0 w-28 truncate text-xs font-medium text-emerald-700" x-text="item.employee_name"></span>
                    <?php endif; ?>
                    <span class="flex-1 min-w-0">
                        <span class="block truncate font-semibold text-emerald-900 tabular-nums" x-text="formatRange(item)"></span>
                        <?php if ($showEmployeeName): ?>
                        <span class="sm:hidden block truncate text-[11px] text-emerald-600 mt-0.5" x-text="item.employee_name"></span>
                        <?php endif; ?>
                    </span>
                    <span class="shrink-0 tabular-nums text-xs text-emerald-600 whitespace-nowrap">
                        <span class="font-bold text-emerald-900" x-text="item.net_days"></span>
                        <span class="hidden sm:inline"><?= I18n::get('emp.days') ?></span>
                        <span class="sm:hidden">T</span>
                    </span>
                    <span class="shrink-0 inline-flex max-w-[7.5rem] truncate px-2 py-0.5 rounded-full text-[10px] font-bold border" :class="statusBadgeClass(item.status)" x-text="item.status_label"></span>
                    <svg class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </template>
        </div>
    </div>
</template>

<div x-show="openItems.length === 0 && plannedItems.length === 0 && pastItems.length === 0" class="py-10 text-center">
    <p class="text-sm font-semibold text-emerald-700"><?= I18n::get('history.empty') ?></p>
</div>
