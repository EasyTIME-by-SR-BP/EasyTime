<?php
/**
 * Reusable hover tooltip wrapper.
 *
 * Usage:
 *   echo easytime_tooltip('Label', '<a href="...">...</a>', 'inline-flex', 'top');
 *   echo easytime_tooltip('Label', '<button>...</button>', 'inline-flex', 'bottom', 'my-tooltip-id');
 *
 * @param 'top'|'bottom' $placement  top = above element, bottom = below (for topbar)
 * @param string|null $tooltipId     optional id on the tooltip span for dynamic label updates
 */
if (!function_exists('easytime_tooltip')) {
    function easytime_tooltip(string $label, string $innerHtml, string $wrapperClass = 'inline-flex', string $placement = 'top', ?string $tooltipId = null): string
    {
        $esc = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $posClass = $placement === 'bottom'
            ? 'top-full left-1/2 mt-2 -translate-x-1/2'
            : 'bottom-full left-1/2 mb-2 -translate-x-1/2';
        $idAttr = $tooltipId !== null && $tooltipId !== ''
            ? ' id="' . htmlspecialchars($tooltipId, ENT_QUOTES, 'UTF-8') . '"'
            : '';

        return <<<HTML
<div class="easytime-tooltip group relative {$wrapperClass}">
    {$innerHtml}
    <span{$idAttr} class="pointer-events-none absolute {$posClass} z-[60] max-w-[16rem] whitespace-normal text-center rounded-lg bg-emerald-900 px-2.5 py-1.5 text-xs font-semibold text-white opacity-0 shadow-lg transition-opacity duration-150 group-hover:opacity-100 group-focus-within:opacity-100" role="tooltip">{$esc}</span>
</div>
HTML;
    }
}
