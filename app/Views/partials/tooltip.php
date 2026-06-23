<?php
/**
 * Reusable hover tooltip wrapper.
 *
 * Usage:
 *   echo easytime_tooltip('Label', '<a href="...">...</a>', 'inline-flex', 'bottom');
 *
 * @param 'top'|'bottom' $placement  top = above element, bottom = below (for topbar)
 */
if (!function_exists('easytime_tooltip')) {
    function easytime_tooltip(string $label, string $innerHtml, string $wrapperClass = 'inline-flex', string $placement = 'top'): string
    {
        $esc = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $posClass = $placement === 'bottom'
            ? 'top-full left-1/2 mt-2 -translate-x-1/2'
            : 'bottom-full left-1/2 mb-2 -translate-x-1/2';

        return <<<HTML
<div class="easytime-tooltip group relative {$wrapperClass}">
    {$innerHtml}
    <span class="pointer-events-none absolute {$posClass} z-[60] whitespace-nowrap rounded-lg bg-emerald-900 px-2.5 py-1.5 text-xs font-semibold text-white opacity-0 shadow-lg transition-opacity duration-150 group-hover:opacity-100 group-focus-within:opacity-100" role="tooltip">{$esc}</span>
</div>
HTML;
    }
}
