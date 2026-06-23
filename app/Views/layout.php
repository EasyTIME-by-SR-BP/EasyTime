<?php
use App\Core\I18n;
require_once __DIR__ . '/partials/tooltip.php';
require_once __DIR__ . '/partials/nav-icons.php';
if (!isset($currentRole)) exit;
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'de' ?>" class="antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>EasyTime | Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="/assets/icons/urlaubsplaner_icon.svg">
    <script>
        window.easytimeTailwindConfig = {
            theme: {
                extend: {
                    colors: {
                        emerald: {
                            50: '#fffdf2',
                            100: '#fff7cc',
                            200: '#fff0a3',
                            300: '#ffe866',
                            400: '#FFD600',
                            500: '#E8007D',
                            600: '#4a4a4a',
                            700: '#2d2d2d',
                            800: '#1f1f1f',
                            900: '#1a1a1a',
                            950: '#111111'
                        },
                        lime: {
                            50: '#fff0f7',
                            100: '#ffd6eb',
                            200: '#ffadd8',
                            300: '#ff73bd',
                            400: '#E8007D',
                            500: '#c8006c',
                            600: '#a60059',
                            700: '#7d0044',
                            800: '#56002f',
                            900: '#33001c'
                        },
                        yellow: {
                            50: '#fffdf2',
                            100: '#fff7cc',
                            200: '#fff0a3',
                            300: '#ffe866',
                            400: '#FFD600',
                            500: '#e6c100',
                            600: '#b89600',
                            700: '#806900',
                            800: '#4d3f00',
                            900: '#1a1a1a'
                        },
                        green: {
                            50:  '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534'
                        },
                        red: {
                            50:  '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b'
                        },
                        orange: {
                            50:  '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412'
                        },
                        blue: {
                            50:  '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8'
                        },
                        pink: {
                            50: '#fff0f7',
                            100: '#ffd6eb',
                            200: '#ffadd8',
                            300: '#ff73bd',
                            400: '#f52b95',
                            500: '#E8007D',
                            600: '#c8006c'
                        }
                    }
                }
            }
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        window.tailwind.config = window.easytimeTailwindConfig;
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        (function () {
            try {
                if (!window.matchMedia('(min-width: 1024px)').matches) return;
                const legacy = sessionStorage.getItem('et-sidebar-open');
                if (sessionStorage.getItem('et-sidebar-expanded') === null && legacy === '1') {
                    sessionStorage.setItem('et-sidebar-expanded', '1');
                }
                if (sessionStorage.getItem('et-sidebar-expanded') === '1') {
                    document.documentElement.classList.add('et-sidebar-expanded');
                }
            } catch (e) {}
        })();
        function easytimeSidebar() {
            const storageKey = 'et-sidebar-expanded';
            const expandLabel = <?= json_encode(I18n::get('nav.sidebar_expand'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
            const collapseLabel = <?= json_encode(I18n::get('nav.sidebar_collapse'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
            const syncDom = (expanded) => {
                if (window.innerWidth < 1024) {
                    document.documentElement.classList.remove('et-sidebar-expanded');
                    return;
                }
                document.documentElement.classList.toggle('et-sidebar-expanded', expanded);
                window.setTimeout(function () {
                    window.applyCeoEmployeeFilterPinLayout?.();
                }, 210);
            };
            return {
                expanded: (function () {
                    try {
                        return window.matchMedia('(min-width: 1024px)').matches
                            && (document.documentElement.classList.contains('et-sidebar-expanded')
                                || sessionStorage.getItem(storageKey) === '1');
                    } catch (e) {
                        return false;
                    }
                })(),
                expandLabel,
                collapseLabel,
                init() {
                    try {
                        this.expanded = window.innerWidth >= 1024 && sessionStorage.getItem(storageKey) === '1';
                    } catch (e) {
                        this.expanded = false;
                    }
                    syncDom(this.expanded);
                    window.addEventListener('resize', () => syncDom(this.expanded));
                },
                toggle() {
                    if (window.innerWidth < 1024) return;
                    this.expanded = !this.expanded;
                    try {
                        sessionStorage.setItem(storageKey, this.expanded ? '1' : '0');
                    } catch (e) {}
                    syncDom(this.expanded);
                },
                expand() {
                    if (window.innerWidth < 1024 || this.expanded) return;
                    this.expanded = true;
                    try {
                        sessionStorage.setItem(storageKey, '1');
                    } catch (e) {}
                    syncDom(this.expanded);
                },
                onSidebarClick(event) {
                    if (window.innerWidth < 1024 || this.expanded) return;
                    if (event.target.closest('a, button, input, select, textarea, label')) return;
                    this.expand();
                },
            };
        }
        window.addEventListener('pageshow', function () {
            try {
                if (!window.matchMedia('(min-width: 1024px)').matches) return;
                if (sessionStorage.getItem('et-sidebar-expanded') === '1') {
                    document.documentElement.classList.add('et-sidebar-expanded');
                } else {
                    document.documentElement.classList.remove('et-sidebar-expanded');
                }
            } catch (e) {}
        });
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
    <style>
        :root {
            --et-accent: #E8007D;
            --et-accent-hover: #c8006c;
            --et-accent-text: #fff8fc;
            --et-info-bg: #fffdf2;
            --et-info-border: #fff0a3;
            --et-text: #1a1a1a;
        }
        body { font-family: 'Outfit', sans-serif; background-color: var(--et-info-bg); color: var(--et-text); }
        .et-btn-primary {
            background-color: var(--et-accent) !important;
            color: var(--et-accent-text) !important;
            border-color: var(--et-accent) !important;
        }
        .et-btn-primary:hover {
            background-color: var(--et-accent-hover) !important;
            border-color: var(--et-accent-hover) !important;
        }
        .et-avatar {
            background-color: var(--et-accent);
            color: var(--et-accent-text);
        }
        .et-btn-secondary {
            background-color: #fff !important;
            color: var(--et-text) !important;
            border: 1px solid rgba(232, 0, 125, 0.2) !important;
        }
        .et-btn-secondary:hover {
            background-color: #fff8fc !important;
            border-color: rgba(232, 0, 125, 0.35) !important;
        }
        .et-btn-secondary.is-active {
            background-color: var(--et-accent) !important;
            color: var(--et-accent-text) !important;
            border-color: var(--et-accent) !important;
        }
        .et-topbar-inbox-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(232, 0, 125, 0.2);
            background-color: #fff;
            color: #1a1a1a;
            transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }
        .et-topbar-inbox-btn:hover {
            background-color: #fff8fc;
            border-color: rgba(232, 0, 125, 0.35);
        }
        .et-topbar-inbox-btn.is-active,
        .et-topbar-inbox-btn[aria-current="page"] {
            background-color: var(--et-accent) !important;
            border-color: var(--et-accent) !important;
            color: #fff !important;
        }
        .et-topbar-inbox-btn.is-active:hover,
        .et-topbar-inbox-btn[aria-current="page"]:hover {
            background-color: var(--et-accent-hover) !important;
            border-color: var(--et-accent-hover) !important;
            color: #fff !important;
        }
        .et-info-surface {
            background-color: var(--et-info-bg);
            border-color: var(--et-info-border);
        }
        .et-checkbox {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            cursor: pointer;
            user-select: none;
            padding-left: 1.625rem;
            min-height: 1.375rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--et-text);
        }
        .et-checkbox__input {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 1.125rem;
            height: 1.125rem;
            margin: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 1;
        }
        .et-checkbox__box {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 1.125rem;
            height: 1.125rem;
            flex-shrink: 0;
            border-radius: 0.375rem;
            border: 2px solid rgba(232, 0, 125, 0.32);
            background: #fff;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.6);
            transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
            pointer-events: none;
        }
        .et-checkbox__input:focus-visible + .et-checkbox__box {
            outline: 2px solid rgba(232, 0, 125, 0.28);
            outline-offset: 2px;
        }
        .et-checkbox__input:checked + .et-checkbox__box {
            background: var(--et-accent);
            border-color: var(--et-accent);
            box-shadow: 0 1px 2px rgba(232, 0, 125, 0.22);
        }
        .et-checkbox__input:checked + .et-checkbox__box::after {
            content: '';
            position: absolute;
            left: 0.28rem;
            top: 0.1rem;
            width: 0.35rem;
            height: 0.62rem;
            border: solid #fff;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        .et-checkbox:hover .et-checkbox__input:not(:checked) + .et-checkbox__box {
            border-color: rgba(232, 0, 125, 0.5);
            background: #fff8fc;
        }
        .et-checkbox:hover .et-checkbox__input:checked + .et-checkbox__box {
            background: var(--et-accent-hover);
            border-color: var(--et-accent-hover);
        }
        .ceo-employee-filter-item.et-checkbox {
            display: flex;
            width: 100%;
            min-width: 0;
            padding-left: 1.75rem;
            padding-right: 0.375rem;
        }
        .ceo-employee-filter-item.et-checkbox.hidden {
            display: none !important;
        }
        .ceo-employee-filter-item.et-checkbox > span:last-child {
            min-width: 0;
            flex: 1 1 auto;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        #ceo-employee-filter.is-pinned {
            position: fixed;
            top: var(--et-ceo-filter-top);
            left: var(--et-ceo-filter-left, 0);
            width: var(--et-ceo-filter-width, 100%);
            z-index: 35;
            box-shadow: 0 12px 28px rgba(26, 26, 26, 0.1);
        }
        #ceo-employee-filter-pin-btn.is-active {
            background-color: var(--et-accent) !important;
            color: var(--et-accent-text) !important;
            border-color: var(--et-accent) !important;
        }
        .et-sidebar-tab {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            border-radius: 0.75rem;
            border: 1px solid rgba(232, 0, 125, 0.12);
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 700;
            transition: all 0.15s ease;
        }
        .et-sidebar-tab--active {
            background-color: var(--et-accent);
            border-color: var(--et-accent);
            color: var(--et-accent-text);
            box-shadow: 0 1px 2px rgba(232, 0, 125, 0.2);
        }
        .et-sidebar-tab--idle {
            background-color: #fff;
            color: var(--et-text);
        }
        .et-sidebar-tab--idle:hover {
            background-color: #fff8fc;
            border-color: rgba(232, 0, 125, 0.28);
        }
        .glass { background: rgba(255, 255, 255, 0.72); backdrop-filter: blur(12px); border: 1px solid rgba(232, 0, 125, 0.26); }
        .fc-toolbar-title { font-weight: 700 !important; color: #1a1a1a; font-family: 'Outfit', sans-serif;}
        .fc-button-primary,
        .fc-button-primary:not(:disabled):active,
        .fc-button-primary:not(:disabled).fc-button-active {
            background-color: var(--et-accent) !important;
            border-color: var(--et-accent) !important;
            color: var(--et-accent-text) !important;
            font-weight: bold !important;
            text-transform: capitalize;
        }
        .fc-button-primary:hover {
            background-color: var(--et-accent-hover) !important;
            border-color: var(--et-accent-hover) !important;
            color: var(--et-accent-text) !important;
        }
        .fc-day-today {
            background-color: transparent !important;
        }
        .fc-day-today .fc-daygrid-day-number {
            color: #E8007D !important;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.65rem;
            min-height: 1.65rem;
            border-radius: 9999px;
            background: rgba(232, 0, 125, 0.1);
            box-shadow: inset 0 0 0 2px rgba(232, 0, 125, 0.55);
        }
        #employee-calendar .fc-day-today.easytime-day-selected .fc-daygrid-day-number,
        #ceo-calendar .fc-day-today.easytime-day-selected .fc-daygrid-day-number {
            background: #fff8fc !important;
            color: #E8007D !important;
            box-shadow: inset 0 0 0 2px #E8007D, 0 0 0 2px #fffdf2 !important;
        }
        .fc-col-header-cell-cushion { color: #1a1a1a !important; }
        .fc-daygrid-day-number { color: #1a1a1a !important; }
        .fc-event { border: none !important; border-radius: 4px; padding: 2px 4px; font-weight: 600; font-size: 0.75rem; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;}
        #employee-calendar .fc-event[data-status="approved"],
        #ceo-calendar .fc-event[data-status="approved"] {
            background-color: #16A34A !important;
            border-color: #16A34A !important;
            color: #ffffff !important;
        }
        #employee-calendar .fc-event[data-status="pending"],
        #ceo-calendar .fc-event[data-status="pending"] {
            background-color: #F59E0B !important;
            border-color: #F59E0B !important;
            color: #1a1a1a !important;
        }
        #employee-calendar .fc-event[data-status="storno_requested"],
        #ceo-calendar .fc-event[data-status="storno_requested"] {
            background-color: #EA580C !important;
            border-color: #EA580C !important;
            color: #ffffff !important;
        }
        #employee-calendar .fc-event[data-status="rejected"],
        #ceo-calendar .fc-event[data-status="rejected"] {
            background-color: #DC2626 !important;
            border-color: #DC2626 !important;
            color: #ffffff !important;
        }
        #employee-calendar .fc-event[data-status="cancelled"],
        #ceo-calendar .fc-event[data-status="cancelled"] {
            background-color: #9CA3AF !important;
            border-color: #9CA3AF !important;
            color: #ffffff !important;
            opacity: 0.92;
        }
        #employee-calendar .fc-event[data-status="cancelled"] .fc-event-title {
            text-decoration: line-through;
            text-decoration-color: rgba(255, 255, 255, 0.85);
        }
        /* KW-Anzeige */
        .fc-daygrid-week-number { font-size: 0.6rem !important; font-weight: 700 !important; color: #E8007D !important; background: rgba(232,0,125,0.08); border-radius: 4px; padding: 1px 5px !important; min-width: 2.2rem; text-align: center; }
        #employee-calendar.easytime-cross-month-drag,
        #ceo-calendar.easytime-cross-month-drag {
            cursor: crosshair;
            user-select: none;
        }
        #employee-calendar .fc-day-other:not(.easytime-day-selected):not(.easytime-drag-preview) .fc-daygrid-day-number,
        #ceo-calendar .fc-day-other:not(.easytime-day-selected):not(.easytime-drag-preview) .fc-daygrid-day-number {
            opacity: 0.45;
        }
        #employee-calendar .fc-daygrid-day.easytime-drag-preview,
        #ceo-calendar .fc-daygrid-day.easytime-drag-preview { background-color: rgba(255, 248, 231, 0.85) !important; }
        #employee-calendar .fc-daygrid-day.easytime-day-selected,
        #ceo-calendar .fc-daygrid-day.easytime-day-selected {
            background-color: #FFF8E7 !important;
            box-shadow: inset 0 0 0 2px rgba(232, 0, 125, 0.55);
        }
        #employee-calendar .fc-daygrid-day.easytime-day-selected .fc-daygrid-day-number,
        #ceo-calendar .fc-daygrid-day.easytime-day-selected .fc-daygrid-day-number {
            font-weight: 700 !important;
            color: #E8007D !important;
        }
        #employee-calendar .fc-daygrid-day.easytime-day-selected.easytime-day-unbookable-selected,
        #employee-calendar .fc-daygrid-day.easytime-day-selected.easytime-day-past-selected {
            background-color: rgba(254, 226, 226, 0.85) !important;
            box-shadow: inset 0 0 0 2px rgba(220, 38, 38, 0.55);
        }
        #employee-calendar .fc-daygrid-day.easytime-day-selected.easytime-day-unbookable-selected .fc-daygrid-day-number,
        #employee-calendar .fc-daygrid-day.easytime-day-selected.easytime-day-past-selected .fc-daygrid-day-number {
            color: #DC2626 !important;
        }
        #employee-calendar .fc-daygrid-day.easytime-drag-preview.easytime-day-unbookable-selected,
        #employee-calendar .fc-daygrid-day.easytime-drag-preview.easytime-day-past-selected {
            background-color: rgba(254, 226, 226, 0.72) !important;
        }
        #employee-calendar .fc-event.easytime-event-selected {
            outline: 3px solid #E8007D !important;
            outline-offset: 1px;
            box-shadow: 0 0 0 2px #fffdf2 !important;
            z-index: 8 !important;
        }
        #ceo-calendar .fc-event.easytime-event-selected {
            outline: 3px solid #E8007D !important;
            outline-offset: 2px;
            box-shadow: 0 0 0 2px #fffdf2 !important;
            z-index: 8 !important;
        }
        @media (min-width: 1024px) {
            :root {
                --et-sidebar-w-collapsed: 4.5rem;
                --et-sidebar-w-expanded: 18rem;
                --et-sidebar-w: var(--et-sidebar-w-collapsed);
            }
            html.et-sidebar-expanded {
                --et-sidebar-w: var(--et-sidebar-w-expanded);
            }
            .easytime-sidebar {
                position: fixed;
                top: var(--et-topbar-h);
                left: 0;
                align-self: flex-start;
                height: calc(100vh - var(--et-topbar-h));
                width: var(--et-sidebar-w);
                flex-shrink: 0;
                overflow-x: hidden;
                overflow-y: hidden;
                overscroll-behavior: contain;
                padding: 0.75rem 0.5rem;
                z-index: 20;
                transition: width 0.2s ease;
            }
            html.et-sidebar-expanded .easytime-sidebar {
                padding: 0.75rem 1rem 1rem;
            }
            .easytime-main {
                flex: 1 1 auto;
                min-width: 0;
                margin-left: var(--et-sidebar-w);
                transition: margin-left 0.2s ease;
            }
            html:not(.et-sidebar-expanded) .easytime-sidebar .sidebar-label,
            html:not(.et-sidebar-expanded) .easytime-sidebar .sidebar-section-title,
            html:not(.et-sidebar-expanded) .easytime-sidebar .sidebar-badge {
                display: none !important;
                width: 0 !important;
                overflow: hidden !important;
            }
            html.et-sidebar-expanded .easytime-sidebar .sidebar-label,
            html.et-sidebar-expanded .easytime-sidebar .sidebar-section-title {
                display: block !important;
                width: auto !important;
            }
            html.et-sidebar-expanded .easytime-sidebar .sidebar-badge {
                display: inline-flex !important;
            }
            .easytime-sidebar .et-sidebar-tab {
                justify-content: center;
                padding-left: 0.75rem;
                padding-right: 0.75rem;
                gap: 0;
            }
            html:not(.et-sidebar-expanded) .easytime-sidebar .et-sidebar-tab {
                min-height: 2.75rem;
            }
            .et-sidebar-nav-group {
                display: flex;
                flex-direction: column;
                flex-wrap: wrap;
                gap: 0.5rem;
                width: 100%;
            }
            html.et-sidebar-expanded .easytime-sidebar .et-sidebar-tab {
                justify-content: flex-start;
                gap: 0.75rem;
                padding-left: 1rem;
                padding-right: 1rem;
            }
            .et-sidebar-header {
                display: flex;
                align-items: center;
                flex-shrink: 0;
                min-height: 2rem;
                padding: 0 0.125rem;
            }
            html:not(.et-sidebar-expanded) .et-sidebar-header {
                justify-content: center;
            }
            html.et-sidebar-expanded .et-sidebar-header {
                justify-content: flex-end;
                padding-right: 0.25rem;
            }
            .et-sidebar-toggle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 2rem;
                height: 2rem;
                border-radius: 0.5rem;
                border: none;
                background: transparent;
                color: var(--et-text);
                padding: 0;
                flex-shrink: 0;
                transition: background-color 0.15s ease, color 0.15s ease;
            }
            .et-sidebar-toggle:hover {
                background: rgba(232, 0, 125, 0.08);
                color: var(--et-accent);
            }
            .et-sidebar-toggle:focus-visible {
                outline: 2px solid rgba(232, 0, 125, 0.28);
                outline-offset: 2px;
            }
            html:not(.et-sidebar-expanded) .easytime-sidebar {
                cursor: pointer;
            }
            .history-detail-panel {
                left: var(--et-sidebar-w);
                top: var(--et-topbar-h);
                transition: left 0.2s ease;
            }
        }
        :root {
            --et-topbar-h: 3.5rem;
            --et-mobile-nav-h: 4.5rem;
        }
        @media (min-width: 640px) {
            :root {
                --et-topbar-h: 4.5rem;
            }
        }
        @media (max-width: 1023px) {
            html {
                overflow: hidden;
                width: 100%;
                max-width: 100%;
            }
            body {
                overflow: hidden;
                width: 100%;
                max-width: 100%;
                height: 100%;
                height: 100dvh;
                height: 100svh;
            }
            body > .absolute[class*="blur-3xl"] {
                display: none;
            }
            .easytime-app {
                display: flex !important;
                flex-direction: column !important;
                width: 100%;
                max-width: 100%;
                min-height: 0 !important;
                height: 100dvh !important;
                height: 100svh !important;
                max-height: 100dvh !important;
                max-height: 100svh !important;
                overflow: hidden !important;
            }
            .easytime-scroll-region {
                flex: 1 1 auto !important;
                min-height: 0 !important;
                width: 100%;
                max-width: 100%;
                overflow-x: hidden !important;
                overflow-y: auto !important;
                overscroll-behavior-y: contain;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 0.5rem !important;
            }
            .easytime-topbar {
                flex-shrink: 0;
                width: 100%;
                max-width: 100%;
                z-index: 51;
            }
            #ceo-employee-filter,
            #ceo-employee-filter-anchor {
                max-width: 100%;
                min-width: 0;
            }
            #ceo-employee-filter.is-pinned {
                top: 0.5rem;
            }
            .easytime-layout {
                width: 100%;
                max-width: 100%;
                min-width: 0;
            }
            .easytime-main {
                padding-bottom: 0.5rem !important;
                width: 100%;
                max-width: 100%;
                min-width: 0;
                overflow-x: hidden;
            }
            .easytime-main .bg-white.rounded-3xl {
                max-width: 100%;
                overflow-x: hidden;
            }
            .et-mobile-nav {
                flex-shrink: 0;
                width: 100%;
                max-width: 100%;
                z-index: 50;
            }
            .et-mobile-nav__item--active {
                background: rgba(232, 0, 125, 0.08);
            }
            .et-mobile-nav__item {
                min-width: 0;
                padding-left: 0.125rem;
                padding-right: 0.125rem;
            }
            .et-mobile-nav__item span:last-child {
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .et-mobile-nav__item svg {
                height: 1.125rem;
                width: 1.125rem;
            }
            #employee-calendar,
            #ceo-calendar {
                width: 100%;
                max-width: 100%;
                overflow-x: hidden;
            }
            #employee-calendar .fc-view-harness,
            #ceo-calendar .fc-view-harness {
                width: 100% !important;
                max-width: 100% !important;
                overflow-x: hidden !important;
            }
            #employee-calendar .fc-scrollgrid,
            #ceo-calendar .fc-scrollgrid,
            #employee-calendar .fc-scrollgrid table,
            #ceo-calendar .fc-scrollgrid table,
            #employee-calendar .fc-scrollgrid-sync-table,
            #ceo-calendar .fc-scrollgrid-sync-table {
                width: 100% !important;
                max-width: 100% !important;
                table-layout: fixed !important;
            }
            #employee-calendar .fc-scroller-harness,
            #ceo-calendar .fc-scroller-harness,
            #employee-calendar .fc-scroller,
            #ceo-calendar .fc-scroller {
                overflow-x: hidden !important;
            }
            #employee-calendar .fc-scrollgrid-section > td,
            #ceo-calendar .fc-scrollgrid-section > td,
            #employee-calendar .fc-scrollgrid-section > th,
            #ceo-calendar .fc-scrollgrid-section > th {
                overflow: hidden;
            }
            #employee-calendar .fc-col-header-cell,
            #ceo-calendar .fc-col-header-cell,
            #employee-calendar .fc-daygrid-day,
            #ceo-calendar .fc-daygrid-day {
                min-width: 0 !important;
            }
            #employee-calendar .fc-header-toolbar,
            #ceo-calendar .fc-header-toolbar {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 0.5rem !important;
            }
            #employee-calendar .fc-toolbar-chunk,
            #ceo-calendar .fc-toolbar-chunk {
                display: flex !important;
                justify-content: center !important;
                width: 100% !important;
            }
            .history-detail-panel {
                left: 0;
                top: var(--et-topbar-h);
            }
            #employee-calendar .fc-toolbar,
            #ceo-calendar .fc-toolbar {
                gap: 0.375rem;
                margin-bottom: 0.75rem !important;
            }
            #employee-calendar .fc-toolbar-title,
            #ceo-calendar .fc-toolbar-title {
                font-size: 1rem !important;
            }
            #employee-calendar .fc-button,
            #ceo-calendar .fc-button {
                padding: 0.25rem 0.5rem !important;
                font-size: 0.75rem !important;
            }
            #employee-calendar .fc-daygrid-week-number,
            #ceo-calendar .fc-daygrid-week-number {
                display: none !important;
            }
            #employee-calendar .fc-col-header-cell-cushion,
            #ceo-calendar .fc-col-header-cell-cushion {
                font-size: 0.65rem !important;
                padding: 2px !important;
            }
            #employee-calendar .fc-daygrid-day-number,
            #ceo-calendar .fc-daygrid-day-number {
                font-size: 0.75rem !important;
                padding: 2px 4px !important;
            }
            #employee-calendar .fc-event,
            #ceo-calendar .fc-event {
                font-size: 0.625rem !important;
            }
            #ceo-employee-filter .flex.items-center.gap-2,
            #ceo-employee-filter .flex.min-w-0.w-full {
                min-width: 0;
            }
            @media (max-width: 639px) {
                .easytime-main .mb-5.flex.flex-col[aria-label] {
                    gap: 0.5rem;
                }
                .easytime-main .mb-5.flex.flex-col[aria-label] .text-sm {
                    font-size: 0.6875rem;
                }
                .easytime-main .mb-5.flex.flex-col[aria-label] .gap-x-4 {
                    column-gap: 0.625rem;
                    row-gap: 0.375rem;
                }
            }
            .scrollbar-none {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }
            .scrollbar-none::-webkit-scrollbar {
                display: none;
            }
            .et-btn-primary,
            .et-btn-secondary,
            .et-mobile-nav__item,
            .fc-button {
                touch-action: manipulation;
            }
        }
        @media (max-width: 639px) {
            #employee-calendar .fc-header-toolbar .fc-toolbar-chunk:last-child,
            #ceo-calendar .fc-header-toolbar .fc-toolbar-chunk:last-child {
                display: none;
            }
        }
        [x-cloak] { display: none !important; }
        .easytime-tooltip:focus-within > a,
        .easytime-tooltip:focus-within > button {
            outline: 2px solid rgba(232, 0, 125, 0.35);
            outline-offset: 2px;
        }
    </style>
</head>
<body class="min-h-screen max-lg:overflow-hidden lg:overflow-x-hidden relative">
    <!-- Sunny accents -->
    <div class="absolute top-[-10%] right-[-10%] w-96 h-96 bg-yellow-300 rounded-full mix-blend-multiply opacity-20 blur-3xl z-[-1]"></div>
    <div class="absolute bottom-[-10%] left-[-10%] w-96 h-96 bg-lime-300 rounded-full mix-blend-multiply opacity-20 blur-3xl z-[-1]"></div>

    <!-- Force Password Reset Modal -->
    <?php if (isset($requirePasswordChange) && $requirePasswordChange): ?>
    <div class="fixed inset-0 z-[100] flex items-center justify-center bg-emerald-950/40 backdrop-blur-md p-4">
        <div class="max-w-md w-full bg-white p-10 rounded-3xl shadow-2xl border border-yellow-200">
            <h2 class="text-2xl font-bold text-emerald-900 mb-2"><?= I18n::get('force.title') ?></h2>
            <p class="text-emerald-700 mb-6 text-sm"><?= I18n::get('force.info') ?></p>
            <form action="/?action=change_password" method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-emerald-800 mb-1.5"><?= I18n::get('newpw.password') ?></label>
                    <div class="relative">
                        <input type="password" name="password" id="force_pw" required class="appearance-none block w-full px-4 py-3 text-emerald-900 border border-yellow-200 bg-yellow-50/50 rounded-xl focus:outline-none focus:ring-2 focus:ring-lime-400 font-medium transition-all">
                        <button type="button" onclick="togglePw('force_pw')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-emerald-500 hover:text-lime-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="w-full et-btn-primary font-bold py-3.5 rounded-xl transition-all shadow-lg shadow-lime-400/40">
                    <?= I18n::get('force.submit') ?>
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php
        $activeTab = $activeTab ?? ($currentRole === 'Employee' ? 'calendar' : 'operations');
        $sidebarTabClass = 'et-sidebar-tab lg:w-full';
        $sidebarTabActive = 'et-sidebar-tab--active';
        $sidebarTabIdle = 'et-sidebar-tab--idle';
    ?>
    <div class="easytime-app relative z-10 flex w-full max-w-full flex-col min-h-screen lg:min-h-screen">
        <?php include __DIR__ . '/partials/topbar.php'; ?>

        <div class="easytime-scroll-region flex min-h-0 flex-1 w-full max-w-full flex-col lg:contents">
        <div class="easytime-layout flex min-h-0 flex-1 w-full max-w-full min-w-0">
        <aside
            class="easytime-sidebar max-lg:hidden shrink-0 border-b border-lime-200/60 bg-white/95 shadow-sm backdrop-blur-lg lg:border-b-0 lg:border-r"
            x-data="easytimeSidebar()"
            x-init="init()"
            @click="onSidebarClick($event)"
        >
            <div class="flex h-full min-h-0 flex-col p-4 lg:p-0 lg:h-full lg:gap-2">
                <div class="et-sidebar-header hidden lg:flex">
                    <button
                        type="button"
                        class="et-sidebar-toggle"
                        @click.stop="toggle()"
                        :aria-expanded="expanded.toString()"
                        :title="expanded ? collapseLabel : expandLabel"
                        :aria-label="expanded ? collapseLabel : expandLabel"
                    >
                        <svg x-show="!expanded" x-cloak class="h-[1.125rem] w-[1.125rem]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 4v16"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 9l3 3-3 3"/>
                        </svg>
                        <svg x-show="expanded" x-cloak class="h-[1.125rem] w-[1.125rem]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 4v16"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M14 9l-3 3 3 3"/>
                        </svg>
                    </button>
                </div>
                <nav class="flex flex-col flex-1 min-h-0 h-full gap-2 max-lg:gap-5" aria-label="Dashboard Navigation">
                    <?php if (in_array($currentRole, ['CEO', 'Admin'], true)): ?>
                        <div class="et-sidebar-nav-group lg:flex-1 lg:min-h-0 lg:overflow-y-auto">
                            <a href="/?tab=operations" class="<?= $sidebarTabClass ?> <?= $activeTab === 'operations' ? $sidebarTabActive : $sidebarTabIdle ?>" title="<?= I18n::get('ceo.nav_operations') ?>">
                                <?= easytime_nav_icon('calendar') ?>
                                <span class="sidebar-label flex-1"><?= I18n::get('ceo.nav_operations') ?></span>
                            </a>
                            <a href="/?tab=history" class="<?= $sidebarTabClass ?> <?= $activeTab === 'history' ? $sidebarTabActive : $sidebarTabIdle ?>" title="<?= I18n::get('ceo.nav_history') ?>">
                                <?= easytime_nav_icon('history') ?>
                                <span class="sidebar-label flex-1"><?= I18n::get('ceo.nav_history') ?></span>
                            </a>
                        </div>
                        <div class="et-sidebar-nav-group w-full border-t border-lime-200/80 pt-2 shrink-0">
                            <a href="/?tab=team" class="<?= $sidebarTabClass ?> <?= $activeTab === 'team' ? $sidebarTabActive : $sidebarTabIdle ?>" title="<?= I18n::get('ceo.team') ?>">
                                <?= easytime_nav_icon('team') ?>
                                <span class="sidebar-label flex-1"><?= I18n::get('ceo.team') ?></span>
                            </a>
                            <a href="/?tab=settings" class="<?= $sidebarTabClass ?> <?= $activeTab === 'settings' ? $sidebarTabActive : $sidebarTabIdle ?>" title="<?= I18n::get('ceo.nav_settings') ?>">
                                <?= easytime_nav_icon('settings') ?>
                                <span class="sidebar-label flex-1"><?= I18n::get('ceo.nav_settings') ?></span>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="et-sidebar-nav-group lg:flex-1 lg:min-h-0 lg:overflow-y-auto">
                        <a href="/?tab=calendar" class="<?= $sidebarTabClass ?> <?= $activeTab === 'calendar' ? $sidebarTabActive : $sidebarTabIdle ?>" title="<?= I18n::get('emp.calendar') ?>">
                            <?= easytime_nav_icon('calendar') ?>
                            <span class="sidebar-label flex-1"><?= I18n::get('emp.calendar') ?></span>
                        </a>
                        <a href="/?tab=history" class="<?= $sidebarTabClass ?> <?= $activeTab === 'history' ? $sidebarTabActive : $sidebarTabIdle ?>" title="<?= I18n::get('history.title') ?>">
                            <?= easytime_nav_icon('history') ?>
                            <span class="sidebar-label flex-1"><?= I18n::get('history.title') ?></span>
                        </a>
                        </div>
                    <?php endif; ?>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="easytime-main relative z-10 flex-1 w-full min-w-0 p-3 sm:p-6 lg:p-8 flex flex-col gap-6 sm:gap-8 overflow-x-hidden">

        <?php if ($currentRole === 'Employee' && $activeTab === 'calendar'): ?>
            <div class="space-y-6 sm:space-y-8 min-w-0">
                <div class="bg-white rounded-3xl border border-lime-100 shadow-xl p-4 sm:p-7 relative overflow-hidden min-w-0">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-lime-100/70 blur-3xl" aria-hidden="true"></div>
                    <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 mb-5 relative"><?= I18n::get('emp.vacation_stats') ?></h3>
                    <div class="grid grid-cols-4 gap-3 sm:gap-8 relative">
                        <div>
                            <div class="text-2xl sm:text-4xl font-bold text-emerald-900 tabular-nums leading-none"><?= (int)($userVacationStats['entitlement'] ?? 0) ?></div>
                            <div class="mt-1.5 sm:mt-2 text-[11px] sm:text-sm font-medium text-emerald-600 leading-tight"><?= I18n::get('emp.stats_total') ?></div>
                        </div>
                        <div>
                            <div class="text-2xl sm:text-4xl font-bold text-emerald-900 tabular-nums leading-none"><?= (int)($userVacationStats['planned'] ?? 0) ?></div>
                            <div class="mt-1.5 sm:mt-2 text-[11px] sm:text-sm font-medium text-emerald-600 leading-tight"><?= I18n::get('emp.stats_planned') ?></div>
                        </div>
                        <div>
                            <div class="text-2xl sm:text-4xl font-bold text-emerald-900 tabular-nums leading-none"><?= (int)($userVacationStats['approved'] ?? 0) ?></div>
                            <div class="mt-1.5 sm:mt-2 text-[11px] sm:text-sm font-medium text-emerald-600 leading-tight"><?= I18n::get('emp.stats_taken') ?></div>
                        </div>
                        <div>
                            <div class="text-2xl sm:text-4xl font-bold text-[#E8007D] tabular-nums leading-none"><?= (int)($userVacationStats['remaining'] ?? 0) ?></div>
                            <div class="mt-1.5 sm:mt-2 text-[11px] sm:text-sm font-medium text-emerald-600 leading-tight"><?= I18n::get('emp.stats_remaining') ?></div>
                        </div>
                    </div>
                    <?php if (($maxFenstertage ?? 0) > 0): ?>
                        <p class="mt-6 text-sm leading-relaxed text-emerald-600/90 relative border-t border-lime-100 pt-5">
                            <?= sprintf(I18n::get('emp.max_fenstertage'), (int) $maxFenstertage) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 sm:gap-8 min-w-0">
                    <div class="xl:col-span-2 bg-white p-4 sm:p-7 rounded-3xl shadow-xl border border-lime-100 overflow-hidden min-w-0">
                        <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 mb-2"><?= I18n::get('emp.calendar') ?></h2>
                        <p class="text-sm text-emerald-600/80 mb-4 leading-relaxed lg:hidden"><?= I18n::get('emp.calendar_hint_mobile') ?></p>
                        <p class="hidden lg:block text-sm text-emerald-600/80 mb-4 leading-relaxed"><?= I18n::get('emp.calendar_hint') ?></p>
                        <?php include __DIR__ . '/partials/employee-calendar-legend.php'; ?>
                        <label class="et-checkbox mb-4" for="employee-show-cancelled">
                            <input
                                type="checkbox"
                                id="employee-show-cancelled"
                                class="et-checkbox__input"
                            >
                            <span class="et-checkbox__box" aria-hidden="true"></span>
                            <span><?= I18n::get('emp.show_cancelled') ?></span>
                        </label>
                        <div id="employee-calendar"></div>
                    </div>

                    <div class="calendar-side-panel">
                        <div class="bg-white p-6 sm:p-7 rounded-3xl shadow-xl border border-lime-100" x-data="vacationForm()">
                            <section id="employee-calendar-range-section" class="mb-6">
                                <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-[#E8007D] mb-4"><?= I18n::get('emp.panel_period') ?></h3>
                                <div id="employee-calendar-range-empty" class="relative overflow-hidden py-5 text-center">
                                    <p class="relative text-base font-bold text-emerald-900"><?= I18n::get('emp.calendar_range_empty_title') ?></p>
                                    <p class="relative mt-2 text-sm leading-relaxed text-emerald-600/80 max-w-[16rem] mx-auto"><?= I18n::get('emp.calendar_range_empty') ?></p>
                                </div>
                                <div id="employee-calendar-range-content" class="hidden space-y-4">
                                    <div id="employee-calendar-range-summary" class="text-xl font-bold text-emerald-900 leading-tight tabular-nums"></div>
                                    <div id="employee-calendar-range-inputs" class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-semibold text-emerald-800 mb-1.5" for="employee-start-date"><?= I18n::get('emp.start_date') ?></label>
                                            <input id="employee-start-date" form="employee-request-form" type="date" name="start_date" x-model="start" @change="calculateDays" min="<?= date('Y-m-d') ?>" required class="w-full bg-[#fffdf2] border border-lime-200 rounded-xl px-4 py-3 text-emerald-900 focus:ring-2 focus:ring-lime-400 outline-none transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-emerald-800 mb-1.5" for="employee-end-date"><?= I18n::get('emp.end_date') ?></label>
                                            <input id="employee-end-date" form="employee-request-form" type="date" name="end_date" x-model="end" @change="calculateDays" min="<?= date('Y-m-d') ?>" required class="w-full bg-[#fffdf2] border border-lime-200 rounded-xl px-4 py-3 text-emerald-900 focus:ring-2 focus:ring-lime-400 outline-none transition-all">
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <div class="h-px bg-gradient-to-r from-transparent via-lime-200 to-transparent mb-6" aria-hidden="true"></div>

                            <section id="employee-calendar-info-panel" class="mb-6">
                                <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 mb-4"><?= I18n::get('emp.panel_info') ?></h3>
                                <div id="employee-calendar-info-empty" class="relative overflow-hidden py-5 text-center">
                                    <p class="relative text-base font-bold text-emerald-900"><?= I18n::get('emp.calendar_info_empty_title') ?></p>
                                    <p class="relative mt-2 text-sm leading-relaxed text-emerald-600/80 max-w-[16rem] mx-auto"><?= I18n::get('emp.calendar_info_empty') ?></p>
                                </div>
                                <div id="employee-calendar-info-content" class="hidden">
                                    <div id="employee-calendar-info-body" class="space-y-4"></div>
                                </div>
                            </section>

                            <div class="h-px bg-gradient-to-r from-transparent via-lime-200 to-transparent mb-6" aria-hidden="true"></div>

                            <section>
                                <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 mb-4"><?= I18n::get('emp.panel_action') ?></h3>
                                <div id="employee-calendar-action-empty" class="relative overflow-hidden py-5 text-center">
                                    <p class="relative text-base font-bold text-emerald-900"><?= I18n::get('emp.calendar_action_empty_title') ?></p>
                                    <p class="relative mt-2 text-sm leading-relaxed text-emerald-600/80 max-w-[16rem] mx-auto"><?= I18n::get('emp.calendar_action_empty') ?></p>
                                </div>

                                <div id="employee-calendar-action-range" class="hidden space-y-4">
                                    <form id="employee-request-form" action="/?action=create_request" method="POST" class="space-y-4" @submit="handleEmployeeRequestSubmit($event)">
                                        <div class="flex items-center justify-between gap-4 py-1">
                                            <span class="text-sm font-medium text-emerald-700"><?= I18n::get('emp.days_deduct') ?></span>
                                            <span class="text-4xl font-bold text-emerald-900 tabular-nums" x-text="netDays">0</span>
                                            <input type="hidden" name="net_days" x-model="netDays">
                                        </div>
                                        <button type="submit" class="w-full et-btn-primary font-bold py-3 px-4 rounded-xl shadow-lg shadow-lime-400/30 transition-all outline-none disabled:opacity-50 disabled:cursor-not-allowed" :disabled="netDays <= 0 || !start || !end">
                                            <?= I18n::get('emp.send_request') ?>
                                        </button>
                                    </form>
                                </div>

                                <div id="employee-calendar-action-event" class="hidden space-y-4">
                                    <div id="employee-selected-event-actions" class="space-y-4"></div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>

                <?php include __DIR__ . '/partials/employee-open-requests.php'; ?>
            </div>

        <?php elseif ($currentRole === 'Employee' && $activeTab === 'history'): ?>
            <?php include __DIR__ . '/partials/employee-history.php'; ?>

        <?php elseif ($activeTab === 'inbox'): ?>
            <?php include __DIR__ . '/partials/inbox.php'; ?>

        <?php elseif (in_array($currentRole, ['CEO', 'Admin'], true) && $activeTab === 'operations'): ?>
            <?php include __DIR__ . '/partials/admin-operations.php'; ?>

        <?php elseif (in_array($currentRole, ['CEO', 'Admin'], true) && $activeTab === 'history'): ?>
            <?php include __DIR__ . '/partials/admin-history.php'; ?>

        <?php elseif (in_array($currentRole, ['CEO', 'Admin'], true) && $activeTab === 'team'): ?>
            <?php include __DIR__ . '/partials/admin-team.php'; ?>

        <?php elseif (in_array($currentRole, ['CEO', 'Admin'], true) && $activeTab === 'settings'): ?>
            <div class="space-y-6 max-w-3xl">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 mb-2"><?= I18n::get('ceo.nav_settings') ?></h2>
                    <p class="text-sm text-emerald-600/80 leading-relaxed"><?= I18n::get('settings.holidays_note') ?></p>
                </div>
                <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-xl border border-lime-100 space-y-6">
                    <form method="POST" action="/?action=update_min_staff" class="space-y-2">
                        <label class="block text-sm font-semibold text-emerald-800" for="settings-min-staff"><?= I18n::get('settings.min_staff') ?></label>
                        <div class="flex items-end gap-3">
                            <input id="settings-min-staff" type="number" min="0" name="min_staff_available" value="<?= (int)($minStaffAvailable ?? 1) ?>" class="flex-1 w-full bg-[#fffdf2] border border-lime-200 rounded-xl px-4 py-3 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400">
                            <button type="submit" class="et-btn-primary font-bold px-5 py-3 rounded-xl text-sm shrink-0"><?= I18n::get('ceo.save') ?></button>
                        </div>
                    </form>
                    <div class="h-px bg-gradient-to-r from-transparent via-lime-200 to-transparent" aria-hidden="true"></div>
                    <form method="POST" action="/?action=update_max_fenstertage" class="space-y-2">
                        <label class="block text-sm font-semibold text-emerald-800" for="settings-max-fenstertage">
                            <?= I18n::get('settings.max_fenstertage') ?>
                            <span class="block text-xs font-normal text-emerald-600/80 mt-1"><?= I18n::get('settings.max_fenstertage_hint') ?></span>
                        </label>
                        <div class="flex items-end gap-3">
                            <input id="settings-max-fenstertage" type="number" min="0" name="max_fenstertage" value="<?= (int)($maxFenstertage ?? 0) ?>" class="flex-1 w-full bg-[#fffdf2] border border-lime-200 rounded-xl px-4 py-3 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400">
                            <button type="submit" class="et-btn-primary font-bold px-5 py-3 rounded-xl text-sm shrink-0"><?= I18n::get('ceo.save') ?></button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        </main>
        </div>
        </div>
        <?php include __DIR__ . '/partials/mobile-bottom-nav.php'; ?>
    </div>

    <div id="export-modal" class="fixed inset-0 z-[120] hidden items-center justify-center bg-emerald-950/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-lime-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-emerald-900">Kalender Export</h3>
                <button type="button" onclick="closeExportModal()" class="text-emerald-600 hover:text-emerald-900 font-bold">✕</button>
            </div>
            <form action="/" method="GET" class="space-y-4">
                <input type="hidden" name="action" value="calendar_ics">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-emerald-700 mb-1">Von</label>
                        <input type="date" name="export_start" class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2 text-emerald-900 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-emerald-700 mb-1">Bis</label>
                        <input type="date" name="export_end" class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2 text-emerald-900 outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-1 text-sm text-emerald-800">
                    <label><input type="checkbox" name="include_approved" value="1" checked class="mr-2">Genehmigt</label>
                    <label><input type="checkbox" name="include_pending" value="1" checked class="mr-2">Ausstehend</label>
                    <label><input type="checkbox" name="include_storno" value="1" checked class="mr-2">Storno angefragt</label>
                    <label id="export-include-blocked-row" class="hidden"><input type="checkbox" name="include_blocked" value="1" class="mr-2">Sperrzeiten</label>
                </div>
                <button type="submit" class="w-full et-btn-primary font-bold py-2.5 rounded-xl">Exportieren</button>
            </form>
        </div>
    </div>

    <div id="admin-past-confirm-modal" class="fixed inset-0 z-[130] hidden items-center justify-center bg-emerald-950/40 backdrop-blur-md p-4" role="dialog" aria-modal="true" aria-labelledby="admin-past-confirm-title">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl border border-amber-200 p-6 sm:p-8">
            <h3 id="admin-past-confirm-title" class="text-xl font-bold text-emerald-900 mb-2"><?= I18n::get('ceo.past_confirm_title') ?></h3>
            <p class="text-sm text-emerald-700 leading-relaxed mb-6"><?= I18n::get('ceo.past_confirm_message') ?></p>
            <div class="flex flex-wrap justify-end gap-3">
                <button type="button" onclick="closeAdminPastConfirmModal()" class="et-btn-secondary px-5 py-3 rounded-xl text-sm font-bold"><?= I18n::get('ceo.past_confirm_cancel') ?></button>
                <button type="button" onclick="confirmAdminPastAction()" class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-3 rounded-xl text-sm font-bold transition-colors"><?= I18n::get('ceo.past_confirm_proceed') ?></button>
            </div>
        </div>
    </div>

    <div id="admin-dates-confirm-modal" class="fixed inset-0 z-[130] hidden items-center justify-center bg-emerald-950/40 backdrop-blur-md p-4" role="dialog" aria-modal="true" aria-labelledby="admin-dates-confirm-title">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl border border-amber-200 p-6 sm:p-8">
            <h3 id="admin-dates-confirm-title" class="text-xl font-bold text-emerald-900 mb-2"><?= I18n::get('ceo.dates_changed_confirm_title') ?></h3>
            <p id="admin-dates-confirm-message" class="text-sm text-emerald-700 leading-relaxed mb-6"></p>
            <div class="flex flex-wrap justify-end gap-3">
                <button type="button" onclick="closeAdminDatesConfirmModal()" class="et-btn-secondary px-5 py-3 rounded-xl text-sm font-bold"><?= I18n::get('ceo.past_confirm_cancel') ?></button>
                <button type="button" onclick="confirmAdminDatesAction()" class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-3 rounded-xl text-sm font-bold transition-colors"><?= I18n::get('ceo.past_confirm_proceed') ?></button>
            </div>
        </div>
    </div>

    <script>
        const fcEvents = <?= isset($fcEvents) ? json_encode($fcEvents) : '[]' ?>;
        const currentLang = '<?= $_SESSION['lang'] ?? 'de' ?>';
        const currentRole = '<?= $currentRole ?>';
        const requestLookup = <?= isset($requests) ? json_encode($requests) : '[]' ?>;
        const blockedPeriodLookup = <?= isset($blockedPeriods) ? json_encode($blockedPeriods) : '[]' ?>;
        const blockedRanges = fcEvents
            .filter((e) => e.extendedProps && e.extendedProps.isBlocked)
            .map((e) => ({ start: e.start, end: e.end }));
        let ceoCalendarInstance = null;
        let employeeCalendarInstance = null;
        let ceoSelectedRange = null;
        const ceoSelectionStorageKey = 'easytime_ceo_calendar_selection';
        const ceoActionModeStorageKey = 'easytime_ceo_calendar_action_mode';
        let ceoActionMode = 'vacation';
        let suppressEmpDateClick = false;
        let suppressCeoDateClick = false;
        let employeeSelectionSetAt = 0;
        let ceoSelectionSetAt = 0;
        let adminPastConfirmForm = null;
        let adminDatesConfirmForm = null;
        let adminDatesConfirmSubmitter = null;
        const ceoDatesChangedPanelMsg = <?= json_encode(I18n::get('ceo.dates_changed_panel'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const ceoDatesChangedPanelWunschMsg = <?= json_encode(I18n::get('ceo.dates_changed_panel_wunsch'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

        function isMobileLayout() {
            return window.matchMedia('(max-width: 1023px)').matches;
        }

        function formatYmdDisplay(ymd) {
            if (!ymd || ymd.length < 10) return ymd || '';
            const parts = ymd.split('-');
            if (parts.length !== 3) return ymd;
            return parts[2] + '.' + parts[1] + '.' + parts[0];
        }

        window.addEventListener('resize', function () {
            if (isMobileLayout()) {
                const root = document.getElementById('ceo-employee-filter');
                if (root?.classList.contains('is-pinned')) {
                    syncCeoEmployeeFilterPinUi(root, false, null);
                }
            } else {
                loadCeoEmployeeFilterPinState();
            }
        });
        const ceoDatesChangedConfirmTitle = <?= json_encode(I18n::get('ceo.dates_changed_confirm_title'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const ceoDatesChangedConfirmMsg = <?= json_encode(I18n::get('ceo.dates_changed_confirm_message'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const empDatesChangedPanelMsg = <?= json_encode(I18n::get('emp.dates_changed_panel'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const empDatesChangedConfirmTitle = <?= json_encode(I18n::get('emp.dates_changed_confirm_title'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const empDatesChangedConfirmMsg = <?= json_encode(I18n::get('emp.dates_changed_confirm_message'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const ceoActionSectionAdjust = <?= json_encode(I18n::get('ceo.action_section_adjust'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const ceoActionSectionCancel = <?= json_encode(I18n::get('ceo.action_section_cancel'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const ceoActionSectionDecide = <?= json_encode(I18n::get('ceo.action_section_decide'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const ceoActionSectionChange = <?= json_encode(I18n::get('ceo.action_section_change'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const ceoActionSectionStorno = <?= json_encode(I18n::get('ceo.action_section_storno'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const empActionSectionChange = <?= json_encode(I18n::get('emp.action_section_change'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const empActionSectionStorno = <?= json_encode(I18n::get('emp.action_section_storno'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const empActionSectionRetract = <?= json_encode(I18n::get('emp.action_section_retract'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const empActionSectionWithdrawStorno = <?= json_encode(I18n::get('emp.action_section_withdraw_storno'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const empActionSectionWithdrawChange = <?= json_encode(I18n::get('emp.action_section_withdraw_change'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const ceoEmployeeFilterStorageKey = 'easytime_ceo_employee_filter';
        const ceoEmployeeFilterPinStorageKey = 'easytime_ceo_employee_filter_pinned';
        const ceoEmployeeFilterPinLabel = <?= json_encode(I18n::get('ceo.employee_filter_pin'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const ceoEmployeeFilterUnpinLabel = <?= json_encode(I18n::get('ceo.employee_filter_unpin'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const ceoEmployeeFilterSummaryAll = <?= json_encode(I18n::get('ceo.employee_filter_summary_all'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const ceoEmployeeFilterSummaryCount = <?= json_encode(I18n::get('ceo.employee_filter_summary_count'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const ceoEmployeeFilterBadgeAll = <?= json_encode(I18n::get('ceo.employee_filter_badge_all'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const ceoEmployeeFilterBadgeCount = <?= json_encode(I18n::get('ceo.employee_filter_badge_count'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        function formatAdminDateRange(startYmd, endYmd) {
            if (!startYmd || !endYmd) return '';
            const fmt = function(ymd) {
                const p = ymd.split('-');
                return p.length === 3 ? (p[2] + '.' + p[1] + '.' + p[0]) : ymd;
            };
            return fmt(startYmd) + ' – ' + fmt(endYmd);
        }

        function getDatesFormInputs(form) {
            return {
                start: form.querySelector('[name="approved_start_date"], [name="start_date"], [name="new_start_date"]'),
                end: form.querySelector('[name="approved_end_date"], [name="end_date"], [name="new_end_date"]'),
            };
        }

        function datesFormChanged(form) {
            const originalStart = form.dataset.originalStart || '';
            const originalEnd = form.dataset.originalEnd || '';
            const inputs = getDatesFormInputs(form);
            if (!inputs.start || !inputs.end) return false;
            return inputs.start.value !== originalStart || inputs.end.value !== originalEnd;
        }

        function updateDatesFormWarning(form) {
            const warningEl = form.querySelector('.admin-dates-warning, .employee-dates-warning');
            if (!warningEl) return;
            const changed = datesFormChanged(form);
            warningEl.classList.toggle('hidden', !changed);
        }

        function needsDatesConfirmOnSubmit(form, submitter) {
            if (form.action.includes('admin_modify_vacation')) return true;
            if (form.action.includes('request_change')) return true;
            if (!submitter) return false;
            const value = submitter.value || '';
            if (form.action.includes('decide_change_request')) return value === 'approve';
            if (form.action.includes('decide_request')) return value === 'approved';
            return false;
        }

        function wireDatesChangeForm(form) {
            if (form.dataset.datesWired === '1') return;
            form.dataset.datesWired = '1';
            const inputs = getDatesFormInputs(form);
            const onChange = function() { updateDatesFormWarning(form); };
            inputs.start?.addEventListener('change', onChange);
            inputs.end?.addEventListener('change', onChange);
            updateDatesFormWarning(form);
            form.addEventListener('submit', function(e) {
                const submitter = e.submitter;
                if (!needsDatesConfirmOnSubmit(form, submitter)) return;
                if (!datesFormChanged(form)) return;
                if (form.dataset.confirmed === '1') {
                    form.dataset.confirmed = '';
                    return;
                }
                e.preventDefault();
                openDatesConfirmModal(form, submitter);
            });
        }

        function wireDatesChangeForms(root) {
            (root || document).querySelectorAll('.admin-dates-form, .employee-dates-form').forEach(wireDatesChangeForm);
        }
        window.wireDatesChangeForm = wireDatesChangeForm;
        window.wireDatesChangeForms = wireDatesChangeForms;

        function openDatesConfirmModal(form, submitter) {
            adminDatesConfirmForm = form;
            adminDatesConfirmSubmitter = submitter || null;
            const modal = document.getElementById('admin-dates-confirm-modal');
            const titleEl = document.getElementById('admin-dates-confirm-title');
            const messageEl = document.getElementById('admin-dates-confirm-message');
            const isEmployeeForm = form.classList.contains('employee-dates-form') || form.action.includes('request_change');
            const defaultTitle = isEmployeeForm ? empDatesChangedConfirmTitle : ceoDatesChangedConfirmTitle;
            const defaultMessage = isEmployeeForm ? empDatesChangedConfirmMsg : ceoDatesChangedConfirmMsg;
            if (titleEl) {
                titleEl.textContent = form.dataset.confirmTitle || defaultTitle;
            }
            if (messageEl) {
                const inputs = getDatesFormInputs(form);
                const original = formatAdminDateRange(form.dataset.originalStart || '', form.dataset.originalEnd || '');
                const approved = formatAdminDateRange(inputs.start?.value || '', inputs.end?.value || '');
                const template = form.dataset.confirmMessage || defaultMessage;
                messageEl.textContent = template
                    .replace('{original}', original)
                    .replace('{approved}', approved);
            }
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeAdminDatesConfirmModal() {
            adminDatesConfirmForm = null;
            adminDatesConfirmSubmitter = null;
            const modal = document.getElementById('admin-dates-confirm-modal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function confirmAdminDatesAction() {
            if (!adminDatesConfirmForm) return;
            adminDatesConfirmForm.dataset.confirmed = '1';
            if (adminDatesConfirmSubmitter && typeof adminDatesConfirmForm.requestSubmit === 'function') {
                adminDatesConfirmForm.requestSubmit(adminDatesConfirmSubmitter);
            } else {
                adminDatesConfirmForm.submit();
            }
            closeAdminDatesConfirmModal();
        }

        function buildActionPickerPanel(actions, defaultId) {
            if (!actions || actions.length === 0) return '';
            if (actions.length === 1) {
                return actions[0].bodyHtml;
            }

            const activeId = defaultId || actions[0].id;
            const pickerButtons = actions.map(function(action) {
                const isActive = action.id === activeId;
                const btnClass = isActive ? 'et-btn-primary' : 'et-btn-secondary';
                return `<button type="button" class="et-action-picker-btn ${btnClass} py-2 rounded-xl text-sm font-bold" data-action-id="${action.id}">${action.label}</button>`;
            }).join('');

            const panels = actions.map(function(action) {
                const hidden = action.id !== activeId ? ' hidden' : '';
                return `<div class="et-action-panel space-y-4${hidden}" data-action-id="${action.id}">${action.bodyHtml}</div>`;
            }).join('');

            return `<div class="et-action-picker space-y-4">
                <div class="grid grid-cols-2 gap-2 calendar-action-mode-picker">${pickerButtons}</div>
                ${panels}
            </div>`;
        }

        function wireActionPicker(root) {
            (root || document).querySelectorAll('.et-action-picker').forEach(function(picker) {
                if (picker.dataset.wired === '1') return;
                picker.dataset.wired = '1';
                picker.querySelectorAll('.et-action-picker-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const id = btn.dataset.actionId;
                        picker.querySelectorAll('.et-action-picker-btn').forEach(function(b) {
                            const active = b === btn;
                            b.classList.toggle('et-btn-primary', active);
                            b.classList.toggle('et-btn-secondary', !active);
                        });
                        picker.querySelectorAll('.et-action-panel').forEach(function(panel) {
                            panel.classList.toggle('hidden', panel.dataset.actionId !== id);
                        });
                        wireDatesChangeForms(picker);
                    });
                });
            });
        }

        function buildAdminDatesWarningHtml(message) {
            return `<div class="admin-dates-warning hidden rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium leading-snug text-amber-900" role="alert">${message}</div>`;
        }

        function buildEmployeeDatesWarningHtml(message) {
            return `<div class="employee-dates-warning hidden rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium leading-snug text-amber-900" role="alert">${message}</div>`;
        }

        function buildAdminDateFields(startVal, endVal, startName, endName, fromLabel, toLabel) {
            return `
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-emerald-800 mb-1">${fromLabel}</label>
                        <input type="date" name="${startName}" value="${startVal}" class="w-full bg-[#fffdf2] border border-lime-200 rounded-xl px-3 py-2 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-emerald-800 mb-1">${toLabel}</label>
                        <input type="date" name="${endName}" value="${endVal}" class="w-full bg-[#fffdf2] border border-lime-200 rounded-xl px-3 py-2 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400">
                    </div>
                </div>`;
        }

        const ceoPastRangeInfo = <?= json_encode(I18n::get('ceo.past_range_info'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const ceoValidationSelectEmployee = <?= json_encode(I18n::get('ceo.validation_select_employee'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const ceoValidationSelectRange = <?= json_encode(I18n::get('ceo.validation_select_range'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const ceoValidationInvalidRange = <?= json_encode(I18n::get('ceo.validation_invalid_range'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const msgBlockedPeriod = <?= json_encode(I18n::get('msg.blocked_period'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const msgRequestConflict = <?= json_encode(I18n::get('msg.request_conflict'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        let employeeRangeAnchor = null;
        const todayYmd = '<?= date('Y-m-d') ?>';
        const employeeVacationRemaining = <?= ($currentRole === 'Employee') ? (int)($userVacationStats['remaining'] ?? 0) : 0 ?>;
        const empPastWarningFull = <?= json_encode(I18n::get('emp.past_date_warning_full'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const empPastWarningPartial = <?= json_encode(I18n::get('emp.past_date_warning_partial'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const empPastInfo = <?= json_encode(I18n::get('emp.past_date_info'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const empUnbookableInfo = <?= json_encode(I18n::get('emp.unbookable_date_info'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const empBlockedWarning = <?= json_encode(I18n::get('emp.blocked_date_warning'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const empOccupiedWarning = <?= json_encode(I18n::get('emp.occupied_date_warning'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const empNoBalanceZero = <?= json_encode(I18n::get('emp.no_balance_zero'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const empNoBalanceExceeded = <?= json_encode(I18n::get('emp.no_balance_exceeded'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const showCancelledVacationKey = 'easytime_employee_show_cancelled';
        const showCancelledCeoKey = 'easytime_ceo_show_cancelled';

        function isShowCancelledVacationEnabled(calendarId) {
            const key = calendarId === 'ceo-calendar' ? showCancelledCeoKey : showCancelledVacationKey;
            return localStorage.getItem(key) === '1';
        }

        function shouldHideCancelledEvent(calendarId, status) {
            if (status !== 'cancelled') return false;
            return !isShowCancelledVacationEnabled(calendarId);
        }

        function applyCancelledVacationVisibility(calendarRootId) {
            const show = isShowCancelledVacationEnabled(calendarRootId);
            document.getElementById(calendarRootId)?.querySelectorAll('.fc-event[data-status="cancelled"]').forEach((el) => {
                let visible = show;
                if (calendarRootId === 'ceo-calendar') {
                    const userId = el.getAttribute('data-user-id');
                    if (userId && !isCeoUserVisible(userId)) {
                        visible = false;
                    }
                }
                el.style.display = visible ? '' : 'none';
            });
        }

        function measureCeoEmployeeFilterAnchor() {
            const root = document.getElementById('ceo-employee-filter');
            const anchor = document.getElementById('ceo-employee-filter-anchor');
            if (!root || !anchor) return null;
            const rect = anchor.getBoundingClientRect();
            return {
                top: rect.top,
                left: rect.left,
                width: rect.width,
                height: root.offsetHeight,
            };
        }

        function applyCeoEmployeeFilterPinLayout(pinGeometry) {
            const root = document.getElementById('ceo-employee-filter');
            const anchor = document.getElementById('ceo-employee-filter-anchor');
            if (!root || !anchor) return;

            if (!root.classList.contains('is-pinned')) {
                anchor.style.minHeight = '';
                root.style.removeProperty('--et-ceo-filter-left');
                root.style.removeProperty('--et-ceo-filter-width');
                root.style.removeProperty('--et-ceo-filter-top');
                return;
            }

            if (pinGeometry) {
                anchor.style.minHeight = pinGeometry.height + 'px';
                root.style.setProperty('--et-ceo-filter-top', pinGeometry.top + 'px');
                root.style.setProperty('--et-ceo-filter-left', pinGeometry.left + 'px');
                root.style.setProperty('--et-ceo-filter-width', pinGeometry.width + 'px');
                return;
            }

            const existingTop = root.style.getPropertyValue('--et-ceo-filter-top');
            if (!existingTop) {
                root.classList.remove('is-pinned');
                anchor.style.minHeight = '';
                const measured = measureCeoEmployeeFilterAnchor();
                root.classList.add('is-pinned');
                if (!measured) return;
                anchor.style.minHeight = measured.height + 'px';
                root.style.setProperty('--et-ceo-filter-top', measured.top + 'px');
                root.style.setProperty('--et-ceo-filter-left', measured.left + 'px');
                root.style.setProperty('--et-ceo-filter-width', measured.width + 'px');
                return;
            }

            const rect = anchor.getBoundingClientRect();
            const height = parseInt(anchor.style.minHeight, 10) || root.offsetHeight || 0;
            anchor.style.minHeight = height + 'px';
            root.style.setProperty('--et-ceo-filter-left', rect.left + 'px');
            root.style.setProperty('--et-ceo-filter-width', rect.width + 'px');
        }

        function syncCeoEmployeeFilterPinUi(root, pinned, pinGeometry) {
            const pinBtn = document.getElementById('ceo-employee-filter-pin-btn');
            if (!root) return;
            root.classList.toggle('is-pinned', pinned);
            if (pinBtn) {
                pinBtn.classList.toggle('is-active', pinned);
                pinBtn.setAttribute('aria-pressed', pinned ? 'true' : 'false');
                pinBtn.title = pinned ? ceoEmployeeFilterUnpinLabel : ceoEmployeeFilterPinLabel;
                pinBtn.setAttribute('aria-label', pinned ? ceoEmployeeFilterUnpinLabel : ceoEmployeeFilterPinLabel);
            }
            applyCeoEmployeeFilterPinLayout(pinGeometry);
        }

        function loadCeoEmployeeFilterPinState() {
            const root = document.getElementById('ceo-employee-filter');
            if (!root) return;
            let pinned = false;
            if (!isMobileLayout()) {
                try {
                    pinned = localStorage.getItem(ceoEmployeeFilterPinStorageKey) === '1';
                } catch (e) {}
            }
            const pinGeometry = pinned ? measureCeoEmployeeFilterAnchor() : null;
            syncCeoEmployeeFilterPinUi(root, pinned, pinGeometry);
        }

        function toggleCeoEmployeeFilterPin() {
            if (isMobileLayout()) return;
            const root = document.getElementById('ceo-employee-filter');
            if (!root) return;
            const pinned = !root.classList.contains('is-pinned');
            const pinGeometry = pinned ? measureCeoEmployeeFilterAnchor() : null;
            try {
                localStorage.setItem(ceoEmployeeFilterPinStorageKey, pinned ? '1' : '0');
            } catch (e) {}
            syncCeoEmployeeFilterPinUi(root, pinned, pinGeometry);
        }

        function getCeoEmployeeFilterSelectedIds() {
            const boxes = document.querySelectorAll('.ceo-employee-filter-cb:checked');
            if (!boxes.length) return null;
            return new Set(Array.from(boxes, (cb) => String(cb.value)));
        }

        function isCeoUserVisible(userId) {
            const selected = getCeoEmployeeFilterSelectedIds();
            if (!selected) return true;
            return selected.has(String(userId));
        }

        function saveCeoEmployeeFilterSelection() {
            const selected = getCeoEmployeeFilterSelectedIds();
            if (!selected) {
                localStorage.removeItem(ceoEmployeeFilterStorageKey);
                return;
            }
            localStorage.setItem(ceoEmployeeFilterStorageKey, JSON.stringify(Array.from(selected)));
        }

        function loadCeoEmployeeFilterSelection() {
            const raw = localStorage.getItem(ceoEmployeeFilterStorageKey);
            if (!raw) return;
            try {
                const ids = JSON.parse(raw);
                if (!Array.isArray(ids)) return;
                const idSet = new Set(ids.map(String));
                document.querySelectorAll('.ceo-employee-filter-cb').forEach((cb) => {
                    cb.checked = idSet.has(String(cb.value));
                });
            } catch (e) {
                localStorage.removeItem(ceoEmployeeFilterStorageKey);
            }
        }

        function updateCeoEmployeeFilterSummary() {
            const summary = document.getElementById('ceo-employee-filter-summary');
            if (!summary) return;
            const total = document.querySelectorAll('.ceo-employee-filter-cb').length;
            const selected = getCeoEmployeeFilterSelectedIds();
            if (!selected) {
                summary.textContent = ceoEmployeeFilterBadgeAll;
                summary.title = ceoEmployeeFilterSummaryAll;
                return;
            }
            summary.textContent = ceoEmployeeFilterBadgeCount.replace('{count}', String(selected.size));
            summary.title = ceoEmployeeFilterSummaryCount
                .replace('{count}', String(selected.size))
                .replace('{total}', String(total));
        }

        function setCeoEmployeeFilterDropdownOpen(open) {
            const panel = document.getElementById('ceo-employee-filter-dropdown');
            const toggle = document.getElementById('ceo-employee-filter-toggle');
            if (!panel || !toggle) return;
            panel.classList.toggle('hidden', !open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function toggleCeoEmployeeFilterDropdown() {
            const panel = document.getElementById('ceo-employee-filter-dropdown');
            if (!panel) return;
            const willOpen = panel.classList.contains('hidden');
            setCeoEmployeeFilterDropdownOpen(willOpen);
            if (willOpen) {
                filterCeoEmployeeFilterList();
                window.setTimeout(function() {
                    document.getElementById('ceo-employee-filter-search')?.focus();
                }, 0);
            }
        }

        function filterCeoEmployeeFilterList() {
            const q = (document.getElementById('ceo-employee-filter-search')?.value ?? '').trim().toLowerCase();
            const terms = q ? q.split(/\s+/).filter(Boolean) : [];
            document.querySelectorAll('.ceo-employee-filter-item').forEach((item) => {
                const name = item.dataset.employeeName || '';
                const visible = terms.length === 0 || terms.every((term) => name.includes(term));
                item.classList.toggle('hidden', !visible);
            });
        }

        function syncCeoVacationEmployeeDropdown() {
            const select = document.getElementById('admin-vacation-user');
            if (!select) return;
            const selected = getCeoEmployeeFilterSelectedIds();
            let firstVisible = '';
            Array.from(select.options).forEach((opt) => {
                if (!opt.value) return;
                const show = !selected || selected.has(String(opt.value));
                opt.hidden = !show;
                opt.disabled = !show;
                if (show && !firstVisible) firstVisible = opt.value;
            });
            if (select.value && select.options[select.selectedIndex]?.disabled) {
                select.value = firstVisible;
            } else if (selected && selected.size === 1) {
                select.value = Array.from(selected)[0];
            }
        }

        function applyCeoEmployeeFilterToCalendar() {
            if (!ceoCalendarInstance) return;
            ceoCalendarInstance.getEvents().forEach((ev) => {
                if (ev.extendedProps?.isBlocked) return;
                const userId = ev.extendedProps?.userId;
                if (userId == null) return;
                const show = isCeoUserVisible(userId);
                ev.setProp('display', show ? 'auto' : 'none');
                const requestId = ev.extendedProps?.requestId;
                if (requestId) {
                    const el = document.querySelector('#ceo-calendar .fc-event[data-request-id="' + requestId + '"]');
                    if (el && ev.extendedProps.status !== 'cancelled') {
                        el.style.display = show ? '' : 'none';
                    }
                }
            });
            applyCancelledVacationVisibility('ceo-calendar');
        }

        function applyCeoEmployeeFilterToCards() {
            [
                { grid: 'ceo-grid-change', empty: 'ceo-filter-empty-change' },
                { grid: 'ceo-grid-vacation', empty: 'ceo-filter-empty-vacation' },
                { grid: 'ceo-grid-storno', empty: 'ceo-filter-empty-storno' },
            ].forEach(({ grid, empty }) => {
                const gridEl = document.getElementById(grid);
                if (!gridEl) return;
                let visibleCount = 0;
                gridEl.querySelectorAll('.ceo-filterable-card').forEach((card) => {
                    const show = isCeoUserVisible(card.dataset.userId);
                    card.classList.toggle('hidden', !show);
                    if (show) visibleCount++;
                });
                const emptyEl = document.getElementById(empty);
                if (emptyEl) {
                    emptyEl.classList.toggle('hidden', visibleCount > 0);
                    gridEl.classList.toggle('hidden', visibleCount === 0);
                }
            });
        }

        function applyCeoEmployeeFilter() {
            updateCeoEmployeeFilterSummary();
            filterCeoEmployeeFilterList();
            applyCeoEmployeeFilterToCalendar();
            applyCeoEmployeeFilterToCards();
            syncCeoVacationEmployeeDropdown();
            if (document.getElementById('req-search-tbody')) {
                filterRequests();
            }
            window.dispatchEvent(new CustomEvent('easytime:ceo-employee-filter-changed'));
        }

        let ceoEmployeeFilterInitialized = false;

        function resetCeoEmployeeFilter() {
            document.querySelectorAll('.ceo-employee-filter-cb').forEach((cb) => { cb.checked = false; });
            const search = document.getElementById('ceo-employee-filter-search');
            if (search) search.value = '';
            saveCeoEmployeeFilterSelection();
            setCeoEmployeeFilterDropdownOpen(false);
            applyCeoEmployeeFilter();
        }

        function initCeoEmployeeFilter() {
            const root = document.getElementById('ceo-employee-filter');
            if (!root) return;
            if (ceoEmployeeFilterInitialized) {
                loadCeoEmployeeFilterSelection();
                loadCeoEmployeeFilterPinState();
                applyCeoEmployeeFilter();
                return;
            }
            ceoEmployeeFilterInitialized = true;
            loadCeoEmployeeFilterSelection();
            loadCeoEmployeeFilterPinState();
            window.addEventListener('resize', applyCeoEmployeeFilterPinLayout);
            document.getElementById('ceo-employee-filter-pin-btn')?.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleCeoEmployeeFilterPin();
            });
            document.getElementById('ceo-employee-filter-reset-btn')?.addEventListener('click', resetCeoEmployeeFilter);
            document.getElementById('ceo-employee-filter-toggle')?.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleCeoEmployeeFilterDropdown();
            });
            document.getElementById('ceo-employee-filter-search')?.addEventListener('input', filterCeoEmployeeFilterList);
            document.getElementById('ceo-employee-filter-search')?.addEventListener('search', filterCeoEmployeeFilterList);
            document.addEventListener('click', function(e) {
                const panel = document.getElementById('ceo-employee-filter-dropdown');
                if (!panel || panel.classList.contains('hidden')) return;
                if (!root.contains(e.target)) {
                    setCeoEmployeeFilterDropdownOpen(false);
                }
            });
            root.querySelectorAll('.ceo-employee-filter-cb').forEach((cb) => {
                cb.addEventListener('change', function() {
                    saveCeoEmployeeFilterSelection();
                    applyCeoEmployeeFilter();
                });
            });
            applyCeoEmployeeFilter();
        }

        window.applyCeoEmployeeFilterPinLayout = applyCeoEmployeeFilterPinLayout;

        window.getCeoEmployeeFilterSelectedIds = getCeoEmployeeFilterSelectedIds;

        function addDaysYmd(ymd, days) {
            const d = new Date(ymd + 'T12:00:00');
            d.setDate(d.getDate() + days);
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function compareYmd(a, b) {
            return a < b ? -1 : a > b ? 1 : 0;
        }

        function eachDayInRangeExclusive(startYmd, endExclusiveYmd) {
            const days = [];
            for (let cur = startYmd; cur < endExclusiveYmd; cur = addDaysYmd(cur, 1)) {
                days.push(cur);
            }
            return days;
        }

        function hasBlockedOverlap(startStr, endExclusiveStr) {
            const start = new Date(startStr);
            const endInclusive = new Date(endExclusiveStr);
            endInclusive.setDate(endInclusive.getDate() - 1);
            return blockedRanges.some((r) => {
                const blockStart = new Date(r.start);
                const blockEnd = new Date(r.end);
                blockEnd.setDate(blockEnd.getDate() - 1);
                return start <= blockEnd && endInclusive >= blockStart;
            });
        }

        function isYmdBeforeToday(ymd) {
            return compareYmd(ymd, todayYmd) < 0;
        }

        function isYmdBlocked(ymd) {
            return hasBlockedOverlap(ymd, addDaysYmd(ymd, 1));
        }

        const employeeOccupiedRanges = requestLookup
            .filter((r) => ['pending', 'approved', 'storno_requested', 'change_requested'].includes(r.status))
            .map((r) => ({ start: r.start_date, end: addDaysYmd(r.end_date, 1) }));

        function isYmdInOccupiedRange(ymd) {
            return employeeOccupiedRanges.some((r) => ymd >= r.start && ymd < r.end);
        }

        function getEmployeeSelectionIssue(startYmd, endExclusiveYmd) {
            const endInclusive = addDaysYmd(endExclusiveYmd, -1);
            const days = eachDayInRangeExclusive(startYmd, endExclusiveYmd);

            if (compareYmd(endInclusive, todayYmd) < 0) {
                return { type: 'past_full', message: empPastWarningFull };
            }
            if (compareYmd(startYmd, todayYmd) < 0) {
                return { type: 'past_partial', message: empPastWarningPartial };
            }
            if (days.some((ymd) => isYmdBlocked(ymd))) {
                return { type: 'blocked', message: empBlockedWarning };
            }
            if (days.some((ymd) => isYmdInOccupiedRange(ymd))) {
                return { type: 'occupied', message: empOccupiedWarning };
            }
            if (employeeVacationRemaining <= 0) {
                return { type: 'no_balance', message: empNoBalanceZero };
            }
            if (days.length > employeeVacationRemaining) {
                return { type: 'no_balance', message: empNoBalanceExceeded };
            }
            return null;
        }

        function shouldMarkEmployeeDayUnbookable(ymd, startYmd, endExclusiveYmd, issue) {
            if (isYmdBeforeToday(ymd) || isYmdBlocked(ymd) || isYmdInOccupiedRange(ymd)) {
                return true;
            }
            return !!(issue && issue.type === 'no_balance');
        }

        function rangesOverlapExclusive(startA, endExclusiveA, startB, endExclusiveB) {
            return startA < endExclusiveB && endExclusiveA > startB;
        }

        function getAdminVacationRangeValues() {
            const start = document.getElementById('admin-range-start-date')?.value
                || document.getElementById('vacation-form-start-date')?.value
                || '';
            const end = document.getElementById('admin-range-end-date')?.value
                || document.getElementById('vacation-form-end-date')?.value
                || '';
            return { start, end };
        }

        function getAdminVacationValidationIssue() {
            const userId = document.getElementById('admin-vacation-user')?.value || '';
            const { start, end } = getAdminVacationRangeValues();

            if (!start || !end) {
                return { code: 'select_range', message: ceoValidationSelectRange };
            }
            if (compareYmd(start, end) > 0) {
                return { code: 'invalid_range', message: ceoValidationInvalidRange };
            }
            if (!userId) {
                return { code: 'select_employee', message: ceoValidationSelectEmployee };
            }

            const endExclusive = addDaysYmd(end, 1);
            if (hasBlockedOverlap(start, endExclusive)) {
                return { code: 'blocked_period', message: msgBlockedPeriod };
            }

            const hasConflict = requestLookup.some(function(r) {
                if (String(r.user_id) !== String(userId)) return false;
                if (!['pending', 'approved', 'storno_requested', 'change_requested'].includes(r.status)) return false;
                return rangesOverlapExclusive(
                    start,
                    endExclusive,
                    r.start_date,
                    addDaysYmd(r.end_date, 1)
                );
            });
            if (hasConflict) {
                return { code: 'request_conflict', message: msgRequestConflict };
            }
            return null;
        }

        function updateAdminVacationValidation() {
            const form = document.getElementById('calendar-action-vacation-form');
            const submitBtn = document.getElementById('admin-vacation-submit-btn');
            if (!form || !submitBtn || form.classList.contains('hidden')) return;

            const issue = getAdminVacationValidationIssue();
            submitBtn.disabled = !!issue;
            submitBtn.classList.toggle('opacity-50', !!issue);
            submitBtn.classList.toggle('cursor-not-allowed', !!issue);
        }

        const calendarSelection = {
            'employee-calendar': { type: null, start: null, end: null, requestId: null },
            'ceo-calendar': { type: null, start: null, end: null, requestId: null }
        };
        const crossMonthDragByCalendarId = {};
        
        document.addEventListener('DOMContentLoaded', function() {
            function formatLocalDate(dateObj) {
                const year = dateObj.getFullYear();
                const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                const day = String(dateObj.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function eachDayInclusive(startYmd, endInclusiveYmd) {
                const out = [];
                const start = compareYmd(startYmd, endInclusiveYmd) <= 0 ? startYmd : endInclusiveYmd;
                const end = compareYmd(startYmd, endInclusiveYmd) <= 0 ? endInclusiveYmd : startYmd;
                for (let cur = start; compareYmd(cur, end) <= 0; cur = addDaysYmd(cur, 1)) {
                    out.push(cur);
                }
                return out;
            }

            function dayElFromPoint(x, y) {
                const hit = document.elementFromPoint(x, y);
                return hit ? hit.closest('.fc-daygrid-day') : null;
            }

            function getCalendarInstanceById(calendarId) {
                return calendarId === 'employee-calendar' ? employeeCalendarInstance : ceoCalendarInstance;
            }

            function isYmdInRange(ymd, startYmd, endExclusiveYmd) {
                return ymd >= startYmd && ymd < endExclusiveYmd;
            }

            function clearRangeVisual(calendarId) {
                const root = document.getElementById(calendarId);
                root?.querySelectorAll('.easytime-day-selected').forEach((el) => {
                    el.classList.remove('easytime-day-selected');
                });
                root?.querySelectorAll('.easytime-day-unbookable-selected, .easytime-day-past-selected').forEach((el) => {
                    el.classList.remove('easytime-day-unbookable-selected', 'easytime-day-past-selected');
                });
                root?.querySelectorAll('.easytime-drag-preview').forEach((el) => {
                    el.classList.remove('easytime-drag-preview');
                });
                getCalendarInstanceById(calendarId)?.unselect();
            }

            function applyEmployeeUnbookableDayMarkers(startYmd, endExclusiveYmd) {
                const root = document.getElementById('employee-calendar');
                if (!root) return;
                const issue = getEmployeeSelectionIssue(startYmd, endExclusiveYmd);
                root.querySelectorAll('.easytime-day-unbookable-selected, .easytime-day-past-selected').forEach((el) => {
                    el.classList.remove('easytime-day-unbookable-selected', 'easytime-day-past-selected');
                });
                eachDayInRangeExclusive(startYmd, endExclusiveYmd).forEach((ymd) => {
                    if (!shouldMarkEmployeeDayUnbookable(ymd, startYmd, endExclusiveYmd, issue)) return;
                    root.querySelector('.fc-daygrid-day[data-date="' + ymd + '"]')
                        ?.classList.add('easytime-day-unbookable-selected');
                });
            }

            function formatPanelDateRange(startYmd, endInclusiveYmd) {
                function fmt(ymd) {
                    const p = ymd.split('-');
                    return p.length === 3 ? `${p[2]}.${p[1]}.${p[0]}` : ymd;
                }
                return fmt(startYmd) + ' – ' + fmt(endInclusiveYmd);
            }

            function showEmployeeRangeSection(startYmd, endInclusiveYmd, options = {}) {
                const { readonly = false } = options;
                const summary = document.getElementById('employee-calendar-range-summary');
                if (summary) {
                    summary.textContent = formatPanelDateRange(startYmd, endInclusiveYmd);
                }
                document.getElementById('employee-calendar-range-empty')?.classList.add('hidden');
                document.getElementById('employee-calendar-range-content')?.classList.remove('hidden');
                const inputs = document.getElementById('employee-calendar-range-inputs');
                if (inputs) {
                    inputs.classList.toggle('hidden', readonly);
                }
            }

            function clearEmployeeRangeSection() {
                const summary = document.getElementById('employee-calendar-range-summary');
                if (summary) summary.textContent = '';
                document.getElementById('employee-calendar-range-empty')?.classList.remove('hidden');
                document.getElementById('employee-calendar-range-content')?.classList.add('hidden');
                document.getElementById('employee-calendar-range-inputs')?.classList.remove('hidden');
            }

            function updateEmployeeRangeSelectionUi(startYmd, endExclusiveYmd) {
                const endInclusive = addDaysYmd(endExclusiveYmd, -1);
                const issue = getEmployeeSelectionIssue(startYmd, endExclusiveYmd);
                const days = eachDayInRangeExclusive(startYmd, endExclusiveYmd).length;

                showEmployeeRangeSection(startYmd, endInclusive);

                renderEmployeeInfoPanel({
                    eyebrow: issue ? '' : '<?= I18n::get('emp.days_deduct') ?>',
                    statusLabel: issue ? empUnbookableInfo : '',
                    statusClass: issue ? 'bg-red-100 text-red-800 border-red-200' : '',
                    days: issue ? null : days,
                    note: issue ? issue.message : '',
                    isWarning: !!issue
                });

                applyEmployeeUnbookableDayMarkers(startYmd, endExclusiveYmd);
            }

            function clearEventVisual(calendarId) {
                document.getElementById(calendarId)?.querySelectorAll('.easytime-event-selected').forEach((el) => {
                    el.classList.remove('easytime-event-selected');
                });
            }

            function applyRangeVisual(calendarId, startYmd, endExclusiveYmd, syncFcSelect) {
                const root = document.getElementById(calendarId);
                if (!root) return;
                eachDayInRangeExclusive(startYmd, endExclusiveYmd).forEach((ymd) => {
                    root.querySelector('.fc-daygrid-day[data-date="' + ymd + '"]')
                        ?.classList.add('easytime-day-selected');
                });
                if (calendarId === 'employee-calendar') {
                    applyEmployeeUnbookableDayMarkers(startYmd, endExclusiveYmd);
                }
                if (syncFcSelect && calendarId !== 'employee-calendar') {
                    getCalendarInstanceById(calendarId)?.select(startYmd, endExclusiveYmd);
                }
            }

            function applyEventVisual(calendarId, requestId, fcEventEl) {
                clearEventVisual(calendarId);
                if (!requestId) return;
                document.querySelectorAll(
                    '#' + calendarId + ' .fc-event[data-request-id="' + requestId + '"]'
                ).forEach((el) => {
                    el.classList.add('easytime-event-selected');
                });
            }

            function reapplySelectionVisuals(calendarId) {
                const sel = calendarSelection[calendarId];
                if (!sel || !sel.type) return;
                if (sel.type === 'range' && sel.start && sel.end) {
                    clearRangeVisual(calendarId);
                    applyRangeVisual(calendarId, sel.start, sel.end, false);
                    if (calendarId === 'employee-calendar') {
                        applyEmployeeUnbookableDayMarkers(sel.start, sel.end);
                    }
                } else if (sel.type === 'event' && sel.requestId) {
                    applyEventVisual(calendarId, sel.requestId, null);
                }
            }

            function showEmployeeRangePanel(startYmd, endExclusiveYmd) {
                document.getElementById('employee-calendar-action-empty')?.classList.add('hidden');
                document.getElementById('employee-calendar-action-event')?.classList.add('hidden');
                document.getElementById('employee-calendar-action-range')?.classList.remove('hidden');
                updateEmployeeRangeSelectionUi(startYmd, endExclusiveYmd);
            }

            function employeeStatusBadgeClass(status) {
                const map = {
                    approved: 'bg-green-100 text-green-800 border-green-200',
                    pending: 'bg-amber-100 text-amber-900 border-amber-200',
                    storno_requested: 'bg-orange-100 text-orange-900 border-orange-300',
                    change_requested: 'bg-violet-100 text-violet-900 border-violet-300',
                    rejected: 'bg-red-100 text-red-800 border-red-200',
                    cancelled: 'bg-gray-100 text-gray-600 border-gray-200'
                };
                return map[status] || 'bg-lime-50 text-emerald-800 border-lime-200';
            }

            function formatEmployeeDateRange(start, end) {
                return start + ' – ' + end;
            }

            function renderEmployeeInfoPanel(options) {
                const body = document.getElementById('employee-calendar-info-body');
                if (!body) return;
                const {
                    eyebrow = '',
                    range = '',
                    statusLabel = '',
                    statusClass = '',
                    days = null,
                    note = '',
                    isWarning = false
                } = options;

                const daysHtml = days !== null
                    ? `<div class="flex items-baseline gap-2 pt-1">
                            <span class="text-4xl font-bold text-emerald-900 tabular-nums leading-none">${days}</span>
                            <span class="text-sm font-medium text-emerald-600"><?= I18n::get('emp.days') ?></span>
                       </div>`
                    : '';

                body.innerHTML = `
                    ${eyebrow ? `<div class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500">${eyebrow}</div>` : ''}
                    ${range ? `<div class="text-2xl sm:text-[1.65rem] font-bold text-emerald-900 leading-tight tracking-tight">${range}</div>` : ''}
                    ${statusLabel ? `<span class="inline-flex px-3 py-1.5 rounded-full text-sm font-bold border ${statusClass}">${statusLabel}</span>` : ''}
                    ${daysHtml}
                    ${note ? `<p class="text-sm leading-relaxed ${isWarning ? 'text-red-700' : 'text-emerald-600'}">${note}</p>` : ''}
                `;

                document.getElementById('employee-calendar-info-empty')?.classList.add('hidden');
                document.getElementById('employee-calendar-info-content')?.classList.remove('hidden');
            }

            function setEmployeeCalendarInfo(type, start, end, meta = '') {
                renderEmployeeInfoPanel({
                    eyebrow: type,
                    range: formatEmployeeDateRange(start, end),
                    note: meta
                });
            }

            function clearEmployeeCalendarSelection() {
                calendarSelection['employee-calendar'] = { type: null, start: null, end: null, requestId: null };
                clearRangeVisual('employee-calendar');
                clearEventVisual('employee-calendar');
                getCalendarInstanceById('employee-calendar')?.unselect();
                const infoBody = document.getElementById('employee-calendar-info-body');
                if (infoBody) infoBody.innerHTML = '';
                const actionBody = document.getElementById('employee-selected-event-actions');
                if (actionBody) actionBody.innerHTML = '';
                clearEmployeeRangeSection();
                document.getElementById('employee-calendar-info-empty')?.classList.remove('hidden');
                document.getElementById('employee-calendar-info-content')?.classList.add('hidden');
                document.getElementById('employee-calendar-action-empty')?.classList.remove('hidden');
                document.getElementById('employee-calendar-action-range')?.classList.add('hidden');
                document.getElementById('employee-calendar-action-event')?.classList.add('hidden');

                const startInput = document.getElementById('employee-start-date');
                const endInput = document.getElementById('employee-end-date');
                if (startInput) {
                    startInput.value = '';
                    startInput.dispatchEvent(new Event('input', { bubbles: true }));
                    startInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
                if (endInput) {
                    endInput.value = '';
                    endInput.dispatchEvent(new Event('input', { bubbles: true }));
                    endInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            function clearCeoCalendarSelection() {
                ceoSelectedRange = null;
                try {
                    localStorage.removeItem(ceoSelectionStorageKey);
                } catch (e) {
                    // ignore storage errors
                }
                calendarSelection['ceo-calendar'] = { type: null, start: null, end: null, requestId: null };
                clearRangeVisual('ceo-calendar');
                clearEventVisual('ceo-calendar');
                getCalendarInstanceById('ceo-calendar')?.unselect();

                const rangeSummary = document.getElementById('calendar-range-summary');
                if (rangeSummary) {
                    rangeSummary.textContent = '';
                    rangeSummary.classList.add('hidden');
                }
                document.getElementById('calendar-range-hint')?.classList.remove('hidden');
                document.getElementById('calendar-range-inputs')?.classList.remove('hidden');

                const adminStart = document.getElementById('admin-range-start-date');
                const adminEnd = document.getElementById('admin-range-end-date');
                if (adminStart) adminStart.value = '';
                if (adminEnd) adminEnd.value = '';

                const eventInfo = document.getElementById('calendar-info-event-body');
                if (eventInfo) {
                    eventInfo.innerHTML = '';
                    eventInfo.classList.add('hidden');
                }
                const eventActions = document.getElementById('calendar-event-actions');
                if (eventActions) eventActions.innerHTML = '';
                document.getElementById('calendar-info-empty')?.classList.remove('hidden');
                document.getElementById('calendar-info-content')?.classList.add('hidden');

                document.getElementById('calendar-action-empty')?.classList.remove('hidden');
                document.getElementById('calendar-action-range-wrapper')?.classList.add('hidden');
                document.getElementById('calendar-action-block-form')?.classList.add('hidden');
                document.getElementById('calendar-action-vacation-form')?.classList.add('hidden');
                document.getElementById('calendar-action-unblock')?.classList.add('hidden');
                document.getElementById('calendar-action-event')?.classList.add('hidden');
                syncAdminRangeToForms();
            }

            function handleCeoDateClick(dateStr) {
                const endExclusive = addDaysYmd(dateStr, 1);
                const sel = calendarSelection['ceo-calendar'];

                if (sel.type === 'range' && sel.start && sel.end && isYmdInRange(dateStr, sel.start, sel.end)) {
                    const endInclusive = addDaysYmd(sel.end, -1);
                    if (dateStr === sel.start && dateStr === endInclusive) {
                        if (Date.now() - ceoSelectionSetAt < 400) return;
                        clearCeoCalendarSelection();
                        return;
                    }
                    if (dateStr === sel.start) {
                        const newStart = addDaysYmd(sel.start, 1);
                        if (newStart >= sel.end) {
                            clearCeoCalendarSelection();
                            return;
                        }
                        applyCeoSelection({ start: newStart, end: sel.end }, true);
                        return;
                    }
                    if (dateStr === endInclusive) {
                        const newEndExclusive = dateStr;
                        if (sel.start >= newEndExclusive) {
                            clearCeoCalendarSelection();
                            return;
                        }
                        applyCeoSelection({ start: sel.start, end: newEndExclusive }, true);
                        return;
                    }
                    applyCeoSelection({ start: dateStr, end: endExclusive }, true);
                    return;
                }

                applyCeoSelection({ start: dateStr, end: endExclusive }, true);
            }

            function handleEmployeeDateClick(dateStr) {
                const endExclusive = addDaysYmd(dateStr, 1);

                const sel = calendarSelection['employee-calendar'];
                if (sel.type === 'range' && sel.start && sel.end && isYmdInRange(dateStr, sel.start, sel.end)) {
                    const endInclusive = addDaysYmd(sel.end, -1);
                    if (dateStr === sel.start && dateStr === endInclusive) {
                        if (Date.now() - employeeSelectionSetAt < 400) return;
                        clearEmployeeCalendarSelection();
                        return;
                    }
                    if (dateStr === sel.start) {
                        const newStart = addDaysYmd(sel.start, 1);
                        if (newStart >= sel.end) {
                            clearEmployeeCalendarSelection();
                            return;
                        }
                        setCalendarRangeSelection('employee-calendar', newStart, sel.end, true);
                        setEmployeeFormDates(newStart, sel.end);
                        return;
                    }
                    if (dateStr === endInclusive) {
                        const newEndExclusive = dateStr;
                        if (sel.start >= newEndExclusive) {
                            clearEmployeeCalendarSelection();
                            return;
                        }
                        setCalendarRangeSelection('employee-calendar', sel.start, newEndExclusive, true);
                        setEmployeeFormDates(sel.start, newEndExclusive);
                        return;
                    }
                    setCalendarRangeSelection('employee-calendar', dateStr, endExclusive, true);
                    setEmployeeFormDates(dateStr, endExclusive);
                    return;
                }

                setCalendarRangeSelection('employee-calendar', dateStr, endExclusive, true);
                setEmployeeFormDates(dateStr, endExclusive);
            }

            function setCalendarRangeSelection(calendarId, startYmd, endExclusiveYmd, syncFcSelect) {
                calendarSelection[calendarId] = {
                    type: 'range',
                    start: startYmd,
                    end: endExclusiveYmd,
                    requestId: null
                };
                clearEventVisual(calendarId);
                clearRangeVisual(calendarId);
                applyRangeVisual(calendarId, startYmd, endExclusiveYmd, syncFcSelect !== false);
                if (calendarId === 'employee-calendar') {
                    employeeSelectionSetAt = Date.now();
                    showEmployeeRangePanel(startYmd, endExclusiveYmd);
                }
            }

            function setCalendarEventSelection(calendarId, requestId, fcEventEl) {
                calendarSelection[calendarId] = {
                    type: 'event',
                    start: null,
                    end: null,
                    requestId: String(requestId)
                };
                clearRangeVisual(calendarId);
                applyEventVisual(calendarId, requestId, fcEventEl);
                if (calendarId === 'employee-calendar') {
                    showEmployeeEventDetails(requestId);
                } else {
                    renderCeoEventDetails(requestId);
                }
            }

            function getViewMonthBoundsYmd(calendar) {
                const d = calendar.getDate();
                const y = d.getFullYear();
                const m = d.getMonth();
                return {
                    start: formatLocalDate(new Date(y, m, 1)),
                    end: formatLocalDate(new Date(y, m + 1, 0)),
                };
            }

            function attachCrossMonthDrag(calendar, calendarEl, onCommitRange) {
                if (!calendar || !calendarEl) return;
                const REARM_INSET_PX = 56;
                const NAV_COOLDOWN_MS = 90;
                const session = {
                    active: false,
                    anchorYmd: null,
                    extentYmd: null,
                    dragMoved: false,
                    lastNavAt: 0,
                    lastPointerEvent: null,
                    lastNavDirection: null,
                    navArmedNext: true,
                    navArmedPrev: true,
                };

                function getDayGridRect() {
                    const body = calendarEl.querySelector('.fc-daygrid-body');
                    return (body || calendarEl).getBoundingClientRect();
                }

                function clearPreview() {
                    calendarEl.querySelectorAll('.easytime-drag-preview').forEach((el) => {
                        el.classList.remove('easytime-drag-preview');
                    });
                    calendarEl.querySelectorAll('.easytime-day-unbookable-selected, .easytime-day-past-selected').forEach((el) => {
                        if (!el.classList.contains('easytime-day-selected')) {
                            el.classList.remove('easytime-day-unbookable-selected', 'easytime-day-past-selected');
                        }
                    });
                }

                function previewRange(startYmd, endInclusiveYmd) {
                    clearPreview();
                    const endExclusive = addDaysYmd(endInclusiveYmd, 1);
                    const issue = calendarEl.id === 'employee-calendar'
                        ? getEmployeeSelectionIssue(startYmd, endExclusive)
                        : null;
                    eachDayInclusive(startYmd, endInclusiveYmd).forEach((ymd) => {
                        const cell = calendarEl.querySelector('.fc-daygrid-day[data-date="' + ymd + '"]');
                        if (!cell) return;
                        cell.classList.add('easytime-drag-preview');
                        if (calendarEl.id === 'employee-calendar' && shouldMarkEmployeeDayUnbookable(ymd, startYmd, endExclusive, issue)) {
                            cell.classList.add('easytime-day-unbookable-selected');
                        }
                    });
                }

                function previewFromExtent(extYmd) {
                    if (!session.anchorYmd || !extYmd) return;
                    session.extentYmd = extYmd;
                    const start = compareYmd(session.anchorYmd, extYmd) <= 0 ? session.anchorYmd : extYmd;
                    const endInclusive = compareYmd(session.anchorYmd, extYmd) <= 0 ? extYmd : session.anchorYmd;
                    previewRange(start, endInclusive);
                }

                function updateNavArming(e) {
                    const grid = getDayGridRect();
                    if (e.clientY <= grid.bottom - REARM_INSET_PX) {
                        session.navArmedNext = true;
                    }
                    if (e.clientY >= grid.top + REARM_INSET_PX) {
                        session.navArmedPrev = true;
                    }
                }

                function continueAfterMonthChange(e) {
                    if (!session.active || !session.anchorYmd) return;
                    const bounds = getViewMonthBoundsYmd(calendar);

                    if (session.lastNavDirection === 'next') {
                        previewFromExtent(bounds.start);
                        session.lastNavDirection = null;
                        return;
                    }
                    if (session.lastNavDirection === 'prev') {
                        previewFromExtent(bounds.end);
                        session.lastNavDirection = null;
                        return;
                    }

                    if (!e) return;
                    const grid = getDayGridRect();
                    const inGridVertically = e.clientY >= grid.top && e.clientY <= grid.bottom;
                    if (!inGridVertically) return;
                    const dayEl = dayElFromPoint(e.clientX, e.clientY);
                    if (dayEl && calendarEl.contains(dayEl)) {
                        previewFromExtent(dayEl.getAttribute('data-date'));
                    }
                }

                function isPastBottomEdge(e, grid) {
                    return e.clientY > grid.bottom - 1;
                }

                function isPastTopEdge(e, grid) {
                    return e.clientY < grid.top + 1;
                }

                function maybeChangeMonthOnEdge(e) {
                    const now = Date.now();
                    if (now - session.lastNavAt < NAV_COOLDOWN_MS) return false;

                    const grid = getDayGridRect();
                    updateNavArming(e);

                    if (session.navArmedNext && isPastBottomEdge(e, grid)) {
                        session.navArmedNext = false;
                        session.navArmedPrev = true;
                        session.lastNavDirection = 'next';
                        session.lastNavAt = now;
                        calendar.next();
                        continueAfterMonthChange(e);
                        return true;
                    }
                    if (session.navArmedPrev && isPastTopEdge(e, grid)) {
                        session.navArmedPrev = false;
                        session.navArmedNext = true;
                        session.lastNavDirection = 'prev';
                        session.lastNavAt = now;
                        calendar.prev();
                        continueAfterMonthChange(e);
                        return true;
                    }
                    return false;
                }

                function onPointerMove(e) {
                    if (!session.active || !session.anchorYmd) return;
                    session.dragMoved = true;
                    session.lastPointerEvent = e;

                    const grid = getDayGridRect();
                    const dayEl = dayElFromPoint(e.clientX, e.clientY);
                    if (dayEl && calendarEl.contains(dayEl) && e.clientY >= grid.top && e.clientY <= grid.bottom) {
                        const hoverYmd = dayEl.getAttribute('data-date');
                        if (hoverYmd) {
                            updateNavArming(e);
                            previewFromExtent(hoverYmd);
                        }
                    }

                    if (maybeChangeMonthOnEdge(e)) {
                        requestAnimationFrame(function() {
                            continueAfterMonthChange(e);
                        });
                    }
                }

                function endDrag(e) {
                    if (!session.active) return;
                    session.active = false;
                    calendarEl.classList.remove('easytime-cross-month-drag');
                    document.removeEventListener('pointermove', onPointerMove);
                    document.removeEventListener('pointerup', endDrag);
                    clearPreview();

                    const dayEl = dayElFromPoint(e.clientX, e.clientY);
                    let hoverYmd = session.extentYmd || session.anchorYmd;
                    if (dayEl && calendarEl.contains(dayEl)) {
                        hoverYmd = dayEl.getAttribute('data-date') || hoverYmd;
                    }

                    if (session.dragMoved && session.anchorYmd && hoverYmd && typeof onCommitRange === 'function') {
                        if (calendarEl.id === 'employee-calendar') {
                            suppressEmpDateClick = true;
                        } else if (calendarEl.id === 'ceo-calendar') {
                            suppressCeoDateClick = true;
                        }
                        const start = compareYmd(session.anchorYmd, hoverYmd) <= 0 ? session.anchorYmd : hoverYmd;
                        const endInclusive = compareYmd(session.anchorYmd, hoverYmd) <= 0 ? hoverYmd : session.anchorYmd;
                        onCommitRange(start, addDaysYmd(endInclusive, 1), e);
                    }

                    session.anchorYmd = null;
                    session.extentYmd = null;
                    session.dragMoved = false;
                    session.lastPointerEvent = null;
                    session.lastNavDirection = null;
                    session.navArmedNext = true;
                    session.navArmedPrev = true;
                }

                session.continueAfterMonthChange = continueAfterMonthChange;

                calendarEl.addEventListener('pointerdown', function(e) {
                    if (e.button !== 0) return;
                    if (e.target.closest('.fc-event, .fc-button, a, button, input, select, textarea, label')) return;
                    const dayEl = e.target.closest('.fc-daygrid-day');
                    if (!dayEl) return;
                    const ymd = dayEl.getAttribute('data-date');
                    if (!ymd) return;
                    session.anchorYmd = ymd;
                    session.extentYmd = ymd;
                    session.active = true;
                    session.dragMoved = false;
                    session.lastNavAt = 0;
                    session.lastNavDirection = null;
                    session.lastPointerEvent = e;
                    session.navArmedNext = true;
                    session.navArmedPrev = true;
                    calendarEl.classList.add('easytime-cross-month-drag');
                    previewFromExtent(ymd);
                    document.addEventListener('pointermove', onPointerMove);
                    document.addEventListener('pointerup', endDrag);
                });

                crossMonthDragByCalendarId[calendarEl.id] = session;
            }

            function setEmployeeFormDates(startStr, endExclusiveStr) {
                const startInput = document.getElementById('employee-start-date');
                const endInput = document.getElementById('employee-end-date');
                if (!startInput || !endInput) return;

                const endDate = new Date(endExclusiveStr);
                endDate.setDate(endDate.getDate() - 1);
                const localEnd = formatLocalDate(endDate);

                startInput.value = startStr;
                endInput.value = localEnd;

                startInput.dispatchEvent(new Event('input', { bubbles: true }));
                startInput.dispatchEvent(new Event('change', { bubbles: true }));
                endInput.dispatchEvent(new Event('input', { bubbles: true }));
                endInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            function syncAdminRangeToForms() {
                const start = document.getElementById('admin-range-start-date')?.value || '';
                const end = document.getElementById('admin-range-end-date')?.value || '';
                const blockStart = document.getElementById('block-form-start-date');
                const blockEnd = document.getElementById('block-form-end-date');
                const vacStart = document.getElementById('vacation-form-start-date');
                const vacEnd = document.getElementById('vacation-form-end-date');
                if (blockStart) blockStart.value = start;
                if (blockEnd) blockEnd.value = end;
                if (vacStart) vacStart.value = start;
                if (vacEnd) vacEnd.value = end;
            }

            function updateAdminRangeHintVisibility() {
                const start = document.getElementById('admin-range-start-date')?.value || '';
                const end = document.getElementById('admin-range-end-date')?.value || '';
                const summary = document.getElementById('calendar-range-summary');
                const hint = document.getElementById('calendar-range-hint');
                const hasValidRange = !!(start && end && compareYmd(start, end) <= 0);

                if (hasValidRange) {
                    if (summary) {
                        summary.textContent = formatPanelDateRange(start, end);
                        summary.classList.remove('hidden');
                    }
                    hint?.classList.add('hidden');
                } else {
                    if (summary) {
                        summary.textContent = '';
                        summary.classList.add('hidden');
                    }
                    hint?.classList.remove('hidden');
                }
            }

            function resetAdminRangePanels() {
                ceoSelectedRange = null;
                try {
                    localStorage.removeItem(ceoSelectionStorageKey);
                } catch (e) {
                    // ignore storage errors
                }
                calendarSelection['ceo-calendar'] = { type: null, start: null, end: null, requestId: null };
                clearRangeVisual('ceo-calendar');
                document.getElementById('calendar-info-event-body')?.classList.add('hidden');
                document.getElementById('calendar-event-actions') && (document.getElementById('calendar-event-actions').innerHTML = '');
                clearCalendarActions();
                document.getElementById('calendar-action-range-wrapper')?.classList.add('hidden');
                syncAdminRangeToForms();
                updateAdminVacationValidation();
            }

            function showAdminRangeSection(startYmd, endInclusiveYmd, options = {}) {
                const { readonly = false } = options;
                const summary = document.getElementById('calendar-range-summary');
                if (summary) {
                    summary.textContent = formatPanelDateRange(startYmd, endInclusiveYmd);
                    summary.classList.remove('hidden');
                }
                document.getElementById('calendar-range-hint')?.classList.add('hidden');
                const inputs = document.getElementById('calendar-range-inputs');
                if (inputs) {
                    inputs.classList.toggle('hidden', readonly);
                }
            }

            function setAdminRangeDates(startStr, endExclusiveStr, options = {}) {
                const startInput = document.getElementById('admin-range-start-date');
                const endInput = document.getElementById('admin-range-end-date');
                if (!startInput || !endInput) return;

                const endDate = new Date(endExclusiveStr);
                endDate.setDate(endDate.getDate() - 1);
                const localEnd = formatLocalDate(endDate);

                startInput.value = startStr;
                endInput.value = localEnd;
                showAdminRangeSection(startStr, localEnd, options);
                syncAdminRangeToForms();
            }

            function applyAdminRangeFromInputs() {
                const start = document.getElementById('admin-range-start-date')?.value;
                const end = document.getElementById('admin-range-end-date')?.value;
                updateAdminRangeHintVisibility();

                if (!start || !end) {
                    if (ceoSelectedRange) {
                        resetAdminRangePanels();
                    } else {
                        syncAdminRangeToForms();
                        updateAdminVacationValidation();
                    }
                    return;
                }

                const endExclusive = addDaysYmd(end, 1);
                if (compareYmd(start, end) > 0) {
                    if (ceoSelectedRange) {
                        resetAdminRangePanels();
                    } else {
                        syncAdminRangeToForms();
                        updateAdminVacationValidation();
                    }
                    return;
                }
                ceoSelectedRange = { start, end: endExclusive };
                ceoSelectionSetAt = Date.now();
                persistCeoRange(ceoSelectedRange);
                setCalendarRangeSelection('ceo-calendar', start, endExclusive, true);
                syncAdminRangeToForms();
                updateAdminRangeHintVisibility();
                document.getElementById('calendar-info-event-body')?.classList.add('hidden');
                if (hasBlockedOverlap(start, endExclusive)) {
                    showActionUnblockSelection(start, endExclusive);
                    setCalendarInfo('Zeitraum', start, end, buildAdminInfoMeta(start, end, 'Aktion: Sperrbereich(e) aufheben.'));
                } else {
                    showActionForRange(start, endExclusive);
                    const actionText = ceoActionMode === 'vacation'
                        ? 'Aktion: Urlaubszeit für Mitarbeiter buchen.'
                        : 'Aktion: Sperrbereich setzen.';
                    setCalendarInfo('Zeitraum', start, end, buildAdminInfoMeta(start, end, actionText));
                }
                updateAdminVacationValidation();
            }

            function clearCalendarActions() {
                document.getElementById('calendar-info-content')?.classList.add('hidden');
                document.getElementById('calendar-info-empty')?.classList.remove('hidden');
                document.getElementById('calendar-action-empty')?.classList.remove('hidden');
                document.getElementById('calendar-action-block-form')?.classList.add('hidden');
                document.getElementById('calendar-action-vacation-form')?.classList.add('hidden');
                document.getElementById('calendar-action-unblock')?.classList.add('hidden');
                document.getElementById('calendar-action-event')?.classList.add('hidden');
            }

            function setCalendarInfo(type, start, end, meta = '') {
                const metaEl = document.getElementById('calendar-info-meta');
                if (!metaEl) return;
                document.getElementById('calendar-info-empty')?.classList.add('hidden');
                document.getElementById('calendar-info-content')?.classList.remove('hidden');
                if (meta && meta.trim() !== '') {
                    metaEl.textContent = meta;
                    metaEl.classList.remove('hidden');
                } else {
                    metaEl.textContent = '';
                    metaEl.classList.add('hidden');
                }
            }

            function getTodayRange() {
                const now = new Date();
                const start = formatLocalDate(now);
                const end = new Date(now);
                end.setDate(end.getDate() + 1);
                return { start, end: formatLocalDate(end) };
            }

            function loadPersistedCeoRange() {
                try {
                    const raw = localStorage.getItem(ceoSelectionStorageKey);
                    if (!raw) return null;
                    const parsed = JSON.parse(raw);
                    if (!parsed || !parsed.start || !parsed.end) return null;
                    return parsed;
                } catch (e) {
                    return null;
                }
            }

            function persistCeoRange(range) {
                try {
                    localStorage.setItem(ceoSelectionStorageKey, JSON.stringify(range));
                } catch (e) {
                    // ignore storage errors
                }
            }

            function loadCeoActionMode() {
                try {
                    const stored = localStorage.getItem(ceoActionModeStorageKey);
                    return stored === 'block' ? 'block' : 'vacation';
                } catch (e) {
                    return 'vacation';
                }
            }

            function setCeoActionMode(mode) {
                ceoActionMode = mode === 'block' ? 'block' : 'vacation';
                try {
                    localStorage.setItem(ceoActionModeStorageKey, ceoActionMode);
                } catch (e) {
                    // ignore storage errors
                }
                updateCeoModeButtonStyles();
            }

            function updateCeoModeButtonStyles() {
                const isVacation = ceoActionMode === 'vacation';
                const vacPrimary = 'et-btn-primary py-2 rounded-xl text-sm font-bold';
                const vacSecondary = 'et-btn-secondary py-2 rounded-xl text-sm font-bold';
                const blockActive = 'bg-red-100 text-red-700 border border-red-200 py-2 rounded-xl text-sm font-bold';
                const blockIdle = 'et-btn-secondary py-2 rounded-xl text-sm font-bold';
                const vacBtn = document.getElementById('action-mode-vacation-btn');
                const blockBtn = document.getElementById('action-mode-block-btn');
                if (vacBtn) vacBtn.className = isVacation ? vacPrimary : vacSecondary;
                if (blockBtn) blockBtn.className = isVacation ? blockIdle : blockActive;
            }

            function showAdminRangeActions() {
                document.getElementById('calendar-action-empty')?.classList.add('hidden');
                document.getElementById('calendar-action-range-wrapper')?.classList.remove('hidden');
                document.getElementById('calendar-action-unblock')?.classList.add('hidden');
                document.getElementById('calendar-action-event')?.classList.add('hidden');
            }

            function showActionForRange(startStr, endExclusiveStr) {
                if (ceoActionMode === 'vacation') {
                    showActionVacationSelection(startStr, endExclusiveStr);
                } else {
                    showActionBlockedSelection(startStr, endExclusiveStr);
                }
                updateCeoModeButtonStyles();
            }

            function switchCeoActionMode(mode) {
                setCeoActionMode(mode);
                if (!ceoSelectedRange) return;
                const endInclusive = addDaysYmd(ceoSelectedRange.end, -1);
                if (hasBlockedOverlap(ceoSelectedRange.start, ceoSelectedRange.end)) {
                    setCalendarInfo('Zeitraum', ceoSelectedRange.start, endInclusive, buildAdminInfoMeta(ceoSelectedRange.start, endInclusive, 'Aktion: Sperrbereich(e) aufheben.'));
                    showActionUnblockSelection(ceoSelectedRange.start, ceoSelectedRange.end);
                    return;
                }
                const actionText = mode === 'vacation'
                    ? 'Aktion: Urlaubszeit für Mitarbeiter buchen.'
                    : 'Aktion: Sperrbereich setzen.';
                setCalendarInfo('Zeitraum', ceoSelectedRange.start, endInclusive, buildAdminInfoMeta(ceoSelectedRange.start, endInclusive, actionText));
                showActionForRange(ceoSelectedRange.start, ceoSelectedRange.end);
            }

            function isAdminRangePast(startYmd, endInclusiveYmd) {
                return compareYmd(startYmd, todayYmd) < 0 || compareYmd(endInclusiveYmd, todayYmd) < 0;
            }

            function buildAdminInfoMeta(startYmd, endInclusiveYmd, actionText) {
                let meta = actionText;
                if (isAdminRangePast(startYmd, endInclusiveYmd)) {
                    meta += ' ' + ceoPastRangeInfo;
                }
                return meta;
            }

            function applyCeoSelection(range, syncCalendarSelection = true) {
                if (!range || !range.start || !range.end) return;
                ceoSelectedRange = { start: range.start, end: range.end };
                ceoSelectionSetAt = Date.now();
                persistCeoRange(ceoSelectedRange);
                setCalendarRangeSelection('ceo-calendar', range.start, range.end, syncCalendarSelection);
                const endInclusive = addDaysYmd(range.end, -1);
                setAdminRangeDates(range.start, range.end);
                document.getElementById('calendar-info-event-body')?.classList.add('hidden');
                if (hasBlockedOverlap(ceoSelectedRange.start, ceoSelectedRange.end)) {
                    setCalendarInfo('Zeitraum', range.start, endInclusive, buildAdminInfoMeta(range.start, endInclusive, 'Aktion: Sperrbereich(e) aufheben.'));
                    showActionUnblockSelection(ceoSelectedRange.start, ceoSelectedRange.end);
                } else {
                    const actionText = ceoActionMode === 'vacation'
                        ? 'Aktion: Urlaubszeit für Mitarbeiter buchen.'
                        : 'Aktion: Sperrbereich setzen.';
                    setCalendarInfo('Zeitraum', range.start, endInclusive, buildAdminInfoMeta(range.start, endInclusive, actionText));
                    showActionForRange(ceoSelectedRange.start, ceoSelectedRange.end);
                }
                updateAdminVacationValidation();
            }

            function showActionBlockedSelection(startStr, endExclusiveStr) {
                showAdminRangeActions();
                document.getElementById('calendar-action-block-form')?.classList.remove('hidden');
                document.getElementById('calendar-action-vacation-form')?.classList.add('hidden');
                updateCeoModeButtonStyles();
            }

            function showActionVacationSelection(startStr, endExclusiveStr) {
                showAdminRangeActions();
                document.getElementById('calendar-action-block-form')?.classList.add('hidden');
                document.getElementById('calendar-action-vacation-form')?.classList.remove('hidden');
                updateCeoModeButtonStyles();
                updateAdminVacationValidation();
            }

            function getBlockedOverlaps(startStr, endExclusiveStr) {
                const start = new Date(startStr);
                const endInclusive = new Date(endExclusiveStr);
                endInclusive.setDate(endInclusive.getDate() - 1);
                return blockedPeriodLookup.filter((b) => {
                    const bStart = new Date(b.start_date);
                    const bEnd = new Date(b.end_date);
                    return start <= bEnd && endInclusive >= bStart;
                });
            }

            function showActionUnblockSelection(startStr, endExclusiveStr) {
                const list = document.getElementById('calendar-action-unblock-list');
                if (!list) return;
                const overlaps = getBlockedOverlaps(startStr, endExclusiveStr);
                list.innerHTML = '';
                overlaps.forEach((b) => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/?action=delete_blocked_period';
                    form.className = 'flex items-center justify-between gap-3 bg-red-50 border border-red-100 rounded-xl p-3';
                    form.innerHTML = `
                        <div class="text-xs text-emerald-800">
                            <div class="font-bold">${(b.label || 'Blocked')}</div>
                            <div>${b.start_date} - ${b.end_date}</div>
                        </div>
                        <input type="hidden" name="blocked_id" value="${b.id}">
                        <button type="submit" class="text-red-600 hover:text-red-800 font-bold text-xs">Aufheben</button>
                    `;
                    list.appendChild(form);
                });
                document.getElementById('calendar-action-empty')?.classList.add('hidden');
                document.getElementById('calendar-action-range-wrapper')?.classList.add('hidden');
                document.getElementById('calendar-action-unblock')?.classList.remove('hidden');
                document.getElementById('calendar-action-event')?.classList.add('hidden');
            }

            function clearEmployeeCalendarActions() {
                clearEmployeeCalendarSelection();
            }

            function employeeStatusLabel(status) {
                const labels = {
                    approved: '<?= I18n::get('emp.status_approved') ?>',
                    rejected: '<?= I18n::get('emp.status_rejected') ?>',
                    pending: '<?= I18n::get('emp.status_pending') ?>',
                    storno_requested: '<?= I18n::get('emp.status_storno_requested') ?>',
                    change_requested: '<?= I18n::get('emp.status_change_requested') ?>',
                    cancelled: '<?= I18n::get('emp.status_cancelled') ?>'
                };
                return labels[status] || status;
            }

            window.showEmployeeEventDetails = function showEmployeeEventDetails(requestId) {
                const request = requestLookup.find((r) => String(r.id) === String(requestId));
                if (!request) return;
                const actions = document.getElementById('employee-selected-event-actions');
                if (!actions) return;

                showEmployeeRangeSection(request.start_date, request.end_date, { readonly: true });

                renderEmployeeInfoPanel({
                    eyebrow: 'Antrag #' + request.id,
                    statusLabel: employeeStatusLabel(request.status),
                    statusClass: employeeStatusBadgeClass(request.status),
                    days: request.net_days
                });

                const employeeDatesWarning = buildEmployeeDatesWarningHtml(empDatesChangedPanelMsg);

                let actionsHtml = '';
                if (request.status === 'pending') {
                    actionsHtml = `
                        <form method="POST" action="/?action=withdraw_request">
                            <input type="hidden" name="request_id" value="${request.id}">
                            <input type="hidden" name="return_tab" value="calendar">
                            <button type="submit" class="w-full text-red-600 hover:text-white hover:bg-red-500 border border-red-200 py-3 rounded-xl text-sm font-bold transition-colors">
                                <?= I18n::get('emp.retract') ?>
                            </button>
                        </form>`;
                } else if (request.status === 'approved') {
                    actionsHtml = buildActionPickerPanel([
                        {
                            id: 'change',
                            label: empActionSectionChange,
                            bodyHtml: `
                                <form method="POST" action="/?action=request_change" class="employee-dates-form space-y-4"
                                    data-original-start="${request.start_date}" data-original-end="${request.end_date}">
                                    <input type="hidden" name="request_id" value="${request.id}">
                                    <input type="hidden" name="return_tab" value="calendar">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-semibold text-emerald-800 mb-1.5"><?= I18n::get('emp.start_date') ?></label>
                                            <input type="date" name="new_start_date" value="${request.start_date}" required class="w-full bg-[#fffdf2] border border-lime-200 rounded-xl px-4 py-3 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-emerald-800 mb-1.5"><?= I18n::get('emp.end_date') ?></label>
                                            <input type="date" name="new_end_date" value="${request.end_date}" required class="w-full bg-[#fffdf2] border border-lime-200 rounded-xl px-4 py-3 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400">
                                        </div>
                                    </div>
                                    ${employeeDatesWarning}
                                    <button type="submit" class="w-full et-btn-primary font-bold py-3 rounded-xl"><?= I18n::get('emp.request_change') ?></button>
                                </form>`
                        },
                        {
                            id: 'storno',
                            label: empActionSectionStorno,
                            bodyHtml: `
                                <form method="POST" action="/?action=request_storno" class="space-y-4">
                                    <input type="hidden" name="request_id" value="${request.id}">
                                    <input type="hidden" name="return_tab" value="calendar">
                                    <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl transition-colors">
                                        <?= I18n::get('emp.storno') ?>
                                    </button>
                                </form>`
                        }
                    ], 'change');
                } else if (request.status === 'storno_requested') {
                    actionsHtml = `
                        <form method="POST" action="/?action=withdraw_storno">
                            <input type="hidden" name="request_id" value="${request.id}">
                            <input type="hidden" name="return_tab" value="calendar">
                            <button type="submit" class="w-full et-btn-secondary py-3 rounded-xl text-sm font-bold transition-colors">
                                <?= I18n::get('emp.cancel_storno') ?>
                            </button>
                        </form>`;
                } else if (request.status === 'change_requested') {
                    actionsHtml = `
                        <form method="POST" action="/?action=withdraw_change">
                            <input type="hidden" name="request_id" value="${request.id}">
                            <input type="hidden" name="return_tab" value="calendar">
                            <button type="submit" class="w-full et-btn-secondary py-3 rounded-xl text-sm font-bold transition-colors">
                                <?= I18n::get('emp.cancel_change') ?>
                            </button>
                        </form>`;
                } else {
                    actionsHtml = '<p class="text-sm text-emerald-600 text-center py-2">Keine Aktionen für diesen Status.</p>';
                }
                actions.innerHTML = actionsHtml;
                wireActionPicker(actions);
                wireDatesChangeForms(actions);

                document.getElementById('employee-calendar-action-empty')?.classList.add('hidden');
                document.getElementById('employee-calendar-action-range')?.classList.add('hidden');
                document.getElementById('employee-calendar-action-event')?.classList.remove('hidden');
            };

            window.focusEmployeeCalendarRequest = function focusEmployeeCalendarRequest(requestId) {
                const request = requestLookup.find((r) => String(r.id) === String(requestId));
                if (!request) return;
                if (employeeCalendarInstance && request.start_date) {
                    employeeCalendarInstance.gotoDate(request.start_date);
                }
                window.setTimeout(function() {
                    const el = document.querySelector('#employee-calendar .fc-event[data-request-id="' + requestId + '"]');
                    if (el) {
                        employeeRangeAnchor = null;
                        setCalendarEventSelection('employee-calendar', requestId, el);
                    }
                    document.getElementById('employee-calendar')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 80);
            };

            window.showActionEventDetails = function showActionEventDetails(requestId) {
                setCalendarEventSelection('ceo-calendar', requestId, null);
            };

            function renderCeoEventDetails(requestId) {
                const request = requestLookup.find((r) => String(r.id) === String(requestId));
                if (!request) return;
                const actions = document.getElementById('calendar-event-actions');
                const eventInfo = document.getElementById('calendar-info-event-body');
                if (!actions || !eventInfo) return;

                const commentLabel = <?= json_encode(I18n::get('ceo.decision_comment'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
                const fromLabel = <?= json_encode(I18n::get('ceo.from'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
                const toLabel = <?= json_encode(I18n::get('ceo.to'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
                const modifyLabel = <?= json_encode(I18n::get('ceo.modify_vacation'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
                const approveDatesLabel = <?= json_encode(I18n::get('ceo.approve_with_dates'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
                const commentField = `
                    <div>
                        <label class="block text-sm font-semibold text-emerald-800 mb-1.5">${commentLabel}</label>
                        <input type="text" name="admin_comment" class="w-full bg-[#fffdf2] border border-lime-200 rounded-xl px-4 py-3 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400">
                    </div>`;
                const dateFields = (startVal, endVal, startName, endName) => `
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-semibold text-emerald-800 mb-1.5">${fromLabel}</label>
                            <input type="date" name="${startName}" value="${startVal}" class="w-full bg-[#fffdf2] border border-lime-200 rounded-xl px-4 py-3 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-emerald-800 mb-1.5">${toLabel}</label>
                            <input type="date" name="${endName}" value="${endVal}" class="w-full bg-[#fffdf2] border border-lime-200 rounded-xl px-4 py-3 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400">
                        </div>
                    </div>`;

                const datesWarningPending = buildAdminDatesWarningHtml(ceoDatesChangedPanelMsg);
                const datesWarningWunsch = buildAdminDatesWarningHtml(ceoDatesChangedPanelWunschMsg);

                let actionsHtml = '';
                if (request.status === 'pending') {
                    actionsHtml = `
                        <form method="POST" action="/?action=decide_request" class="admin-dates-form space-y-4"
                            data-original-start="${request.start_date}" data-original-end="${request.end_date}"
                            data-warning-msg="${ceoDatesChangedPanelMsg.replace(/"/g, '&quot;')}">
                            <input type="hidden" name="request_id" value="${request.id}">
                            ${dateFields(request.start_date, request.end_date, 'approved_start_date', 'approved_end_date')}
                            ${datesWarningPending}
                            ${commentField}
                            <div class="grid grid-cols-2 gap-2">
                                <button type="submit" name="status" value="rejected" class="text-red-600 hover:text-white hover:bg-red-500 border border-red-200 py-2.5 rounded-xl text-sm font-bold transition-colors"><?= I18n::get('ceo.decline') ?></button>
                                <button type="submit" name="status" value="approved" class="et-btn-primary py-2.5 rounded-xl text-sm font-bold">${approveDatesLabel}</button>
                            </div>
                        </form>`;
                } else if (request.status === 'storno_requested') {
                    actionsHtml = `
                        <form method="POST" action="/?action=decide_request" class="space-y-4">
                            <input type="hidden" name="request_id" value="${request.id}">
                            ${commentField}
                            <div class="grid grid-cols-2 gap-2">
                                <button type="submit" name="status" value="approved" class="text-red-600 hover:text-white hover:bg-red-500 border border-red-200 py-2.5 rounded-xl text-sm font-bold transition-colors"><?= I18n::get('ceo.decline_storno') ?></button>
                                <button type="submit" name="status" value="cancelled" class="bg-orange-500 hover:bg-orange-600 text-white py-2.5 rounded-xl text-sm font-bold transition-colors"><?= I18n::get('ceo.approve_storno') ?></button>
                            </div>
                        </form>`;
                } else if (request.status === 'change_requested') {
                    const wStart = request.wunsch_start_date || request.start_date;
                    const wEnd = request.wunsch_end_date || request.end_date;
                    actionsHtml = `
                        <form method="POST" action="/?action=decide_change_request" class="admin-dates-form space-y-4"
                            data-original-start="${wStart}" data-original-end="${wEnd}"
                            data-warning-msg="${ceoDatesChangedPanelWunschMsg.replace(/"/g, '&quot;')}">
                            <input type="hidden" name="request_id" value="${request.id}">
                            ${dateFields(wStart, wEnd, 'approved_start_date', 'approved_end_date')}
                            ${datesWarningWunsch}
                            ${commentField}
                            <div class="grid grid-cols-2 gap-2">
                                <button type="submit" name="decision" value="reject" class="text-red-600 hover:text-white hover:bg-red-500 border border-red-200 py-2.5 rounded-xl text-sm font-bold transition-colors"><?= I18n::get('ceo.reject_change') ?></button>
                                <button type="submit" name="decision" value="approve" class="bg-violet-600 hover:bg-violet-700 text-white py-2.5 rounded-xl text-sm font-bold transition-colors"><?= I18n::get('ceo.approve_change') ?></button>
                            </div>
                        </form>`;
                } else if (request.status === 'approved') {
                    actionsHtml = buildActionPickerPanel([
                        {
                            id: 'adjust',
                            label: ceoActionSectionAdjust,
                            bodyHtml: `
                                <form method="POST" action="/?action=admin_modify_vacation" class="admin-dates-form space-y-4"
                                    data-original-start="${request.start_date}" data-original-end="${request.end_date}"
                                    data-warning-msg="${ceoDatesChangedPanelMsg.replace(/"/g, '&quot;')}">
                                    <input type="hidden" name="request_id" value="${request.id}">
                                    ${dateFields(request.start_date, request.end_date, 'start_date', 'end_date')}
                                    ${datesWarningPending}
                                    ${commentField}
                                    <button type="submit" class="w-full et-btn-primary font-bold py-3 rounded-xl">${modifyLabel}</button>
                                </form>`
                        },
                        {
                            id: 'cancel',
                            label: ceoActionSectionCancel,
                            bodyHtml: `
                                <form method="POST" action="/?action=decide_request" class="space-y-4">
                                    <input type="hidden" name="request_id" value="${request.id}">
                                    ${commentField}
                                    <button type="submit" name="status" value="cancelled" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl transition-colors"><?= I18n::get('ceo.admin_cancel_vacation') ?></button>
                                </form>`
                        }
                    ], 'adjust');
                } else {
                    actionsHtml = '<p class="text-sm text-emerald-600 text-center py-2"><?= I18n::get('ceo.no_event_actions') ?></p>';
                }
                actions.innerHTML = actionsHtml;
                wireActionPicker(actions);
                wireDatesChangeForms(actions);

                setAdminRangeDates(request.start_date, addDaysYmd(request.end_date, 1), { readonly: true });

                eventInfo.innerHTML = `
                    <div class="font-bold text-base mb-2">${request.firstname} ${request.lastname}</div>
                    <div><span class="font-semibold">Status:</span> ${employeeStatusLabel(request.status)}</div>
                    <div><span class="font-semibold">Tage:</span> ${request.net_days}</div>
                    ${request.status === 'change_requested' && request.wunsch_start_date ? `<div><span class="font-semibold"><?= I18n::get('ceo.proposed_dates') ?>:</span> ${request.wunsch_start_date} – ${request.wunsch_end_date} (${request.wunsch_net_days || ''} Tage)</div>` : ''}
                    <div><span class="font-semibold">Kontakt:</span> ${request.email}</div>
                `;
                eventInfo.classList.remove('hidden');
                setCalendarInfo('Termin', request.start_date, request.end_date, `Antrag #${request.id}`);
                document.getElementById('calendar-action-empty')?.classList.add('hidden');
                document.getElementById('calendar-action-range-wrapper')?.classList.add('hidden');
                document.getElementById('calendar-action-unblock')?.classList.add('hidden');
                document.getElementById('calendar-action-event')?.classList.remove('hidden');
            }

            function initFC(elemId) {
                const el = document.getElementById(elemId);
                if (!el) return;
                const compactToolbar = window.matchMedia('(max-width: 639px)').matches;
                const isMobile = window.matchMedia('(max-width: 1023px)').matches;
                const calendar = new FullCalendar.Calendar(el, {
                    initialView: 'dayGridMonth',
                    locale: currentLang,
                    events: fcEvents,
                    fixedWeekCount: false,
                    showNonCurrentDates: true,
                    headerToolbar: compactToolbar ? {
                        left: 'prev,next',
                        center: 'title',
                        right: 'today'
                    } : {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,dayGridWeek'
                    },
                    height: 'auto',
                    firstDay: 1, // Start on Monday
                    weekNumbers: !isMobile,
                    weekNumberContent: function(arg) { return 'KW ' + arg.num; },
                    eventDisplay: 'block',
                    unselectAuto: false,
                    unselectCancel: '.calendar-side-panel',
                    selectable: false,
                    dateClick: function(info) {
                        if (elemId === 'employee-calendar' && suppressEmpDateClick) {
                            suppressEmpDateClick = false;
                            return;
                        }
                        if (elemId === 'ceo-calendar' && suppressCeoDateClick) {
                            suppressCeoDateClick = false;
                            return;
                        }
                        if (elemId === 'employee-calendar') {
                            handleEmployeeDateClick(info.dateStr);
                            return;
                        }
                        if (elemId === 'ceo-calendar' && (currentRole === 'CEO' || currentRole === 'Admin')) {
                            handleCeoDateClick(info.dateStr);
                        }
                    },
                    dayCellDidMount: function(arg) {
                        const ymd = formatLocalDate(arg.date);
                        const sel = calendarSelection[elemId];
                        if (sel && sel.type === 'range' && sel.start && sel.end && isYmdInRange(ymd, sel.start, sel.end)) {
                            arg.el.classList.add('easytime-day-selected');
                            if (elemId === 'employee-calendar') {
                                const issue = getEmployeeSelectionIssue(sel.start, sel.end);
                                if (shouldMarkEmployeeDayUnbookable(ymd, sel.start, sel.end, issue)) {
                                    arg.el.classList.add('easytime-day-unbookable-selected');
                                }
                            }
                        }
                    },
                    eventDidMount: function(arg) {
                        const requestId = arg.event.extendedProps && arg.event.extendedProps.requestId;
                        const status = arg.event.extendedProps && arg.event.extendedProps.status;
                        const userId = arg.event.extendedProps && arg.event.extendedProps.userId;
                        if (requestId) {
                            arg.el.setAttribute('data-request-id', requestId);
                        }
                        if (status) {
                            arg.el.setAttribute('data-status', status);
                        }
                        if (userId != null && elemId === 'ceo-calendar') {
                            arg.el.setAttribute('data-user-id', String(userId));
                            if (!isCeoUserVisible(userId)) {
                                arg.el.style.display = 'none';
                            }
                        }
                        if (shouldHideCancelledEvent(elemId, status)) {
                            arg.el.style.display = 'none';
                        }
                        const sel = calendarSelection[elemId];
                        if (sel && sel.type === 'event' && String(sel.requestId) === String(requestId)) {
                            arg.el.classList.add('easytime-event-selected');
                        }
                    },
                    datesSet: function() {
                        reapplySelectionVisuals(elemId);
                        const drag = crossMonthDragByCalendarId[elemId];
                        if (drag && drag.active && drag.lastPointerEvent) {
                            drag.continueAfterMonthChange(drag.lastPointerEvent);
                        }
                    },
                    eventClick: function(info) {
                        if (info.event.extendedProps && info.event.extendedProps.isBlocked) {
                            return;
                        }
                        const requestId = info.event.extendedProps && info.event.extendedProps.requestId;
                        if (!requestId) return;
                        info.jsEvent.preventDefault();
                        if (elemId === 'employee-calendar') {
                            const sel = calendarSelection['employee-calendar'];
                            if (sel.type === 'event' && String(sel.requestId) === String(requestId)) {
                                clearEmployeeCalendarSelection();
                                return;
                            }
                        }
                        if (elemId === 'ceo-calendar') {
                            const sel = calendarSelection['ceo-calendar'];
                            if (sel.type === 'event' && String(sel.requestId) === String(requestId)) {
                                clearCeoCalendarSelection();
                                return;
                            }
                        }
                        setCalendarEventSelection(elemId, requestId, info.el);
                    }
                });
                calendar.render();
                if (elemId === 'employee-calendar') {
                    employeeCalendarInstance = calendar;
                    attachCrossMonthDrag(calendar, el, function(startYmd, endExclusive) {
                        setCalendarRangeSelection('employee-calendar', startYmd, endExclusive, true);
                        setEmployeeFormDates(startYmd, endExclusive);
                    });
                }
                if (elemId === 'ceo-calendar') {
                    ceoCalendarInstance = calendar;
                    ceoActionMode = loadCeoActionMode();
                    attachCrossMonthDrag(calendar, el, function(startYmd, endExclusive) {
                        applyCeoSelection({ start: startYmd, end: endExclusive }, false);
                    });
                    const persistedRange = loadPersistedCeoRange();
                    if (persistedRange) {
                        applyCeoSelection(persistedRange);
                    } else if (window.matchMedia('(min-width: 1024px)').matches) {
                        applyCeoSelection(getTodayRange());
                    }
                }
            }

            if (document.getElementById('employee-calendar')) {
                initFC('employee-calendar');
                const showCancelledCb = document.getElementById('employee-show-cancelled');
                if (showCancelledCb) {
                    showCancelledCb.checked = isShowCancelledVacationEnabled('employee-calendar');
                    showCancelledCb.addEventListener('change', function() {
                        localStorage.setItem(showCancelledVacationKey, showCancelledCb.checked ? '1' : '0');
                        applyCancelledVacationVisibility('employee-calendar');
                    });
                    applyCancelledVacationVisibility('employee-calendar');
                }
                function syncEmployeePastUiFromForm() {
                    const start = document.getElementById('employee-start-date')?.value;
                    const end = document.getElementById('employee-end-date')?.value;
                    if (!start || !end) return;
                    const endExclusive = addDaysYmd(end, 1);
                    if (start >= endExclusive) return;
                    setCalendarRangeSelection('employee-calendar', start, endExclusive, true);
                }
                document.getElementById('employee-start-date')?.addEventListener('change', syncEmployeePastUiFromForm);
                document.getElementById('employee-end-date')?.addEventListener('change', syncEmployeePastUiFromForm);
            }
            if (document.getElementById('ceo-calendar')) {
                initFC('ceo-calendar');
                const showCancelledCeoCb = document.getElementById('ceo-show-cancelled');
                if (showCancelledCeoCb) {
                    showCancelledCeoCb.checked = isShowCancelledVacationEnabled('ceo-calendar');
                    showCancelledCeoCb.addEventListener('change', function() {
                        localStorage.setItem(showCancelledCeoKey, showCancelledCeoCb.checked ? '1' : '0');
                        applyCancelledVacationVisibility('ceo-calendar');
                    });
                    applyCancelledVacationVisibility('ceo-calendar');
                }
            }
            if (document.getElementById('ceo-employee-filter')) {
                initCeoEmployeeFilter();
            }
            wireDatesChangeForms();

            document.getElementById('action-mode-vacation-btn')?.addEventListener('click', function() {
                switchCeoActionMode('vacation');
            });
            document.getElementById('action-mode-block-btn')?.addEventListener('click', function() {
                switchCeoActionMode('block');
            });
            document.getElementById('admin-range-start-date')?.addEventListener('change', applyAdminRangeFromInputs);
            document.getElementById('admin-range-end-date')?.addEventListener('change', applyAdminRangeFromInputs);
            updateAdminRangeHintVisibility();

            function adminRangeIsPast() {
                const start = document.getElementById('admin-range-start-date')?.value;
                const end = document.getElementById('admin-range-end-date')?.value;
                if (!start || !end) return false;
                return isAdminRangePast(start, end);
            }

            function handleAdminActionFormSubmit(e) {
                syncAdminRangeToForms();
                const confirmInput = e.target.querySelector('input[name="confirm_past"]');
                if (confirmInput) confirmInput.value = '';

                if (e.target.id === 'calendar-action-vacation-form') {
                    const issue = getAdminVacationValidationIssue();
                    if (issue) {
                        e.preventDefault();
                        if (window.showEasyTimeToast) {
                            window.showEasyTimeToast(issue.code, 'error', issue.message);
                        }
                        return;
                    }
                }

                if (adminRangeIsPast()) {
                    e.preventDefault();
                    openAdminPastConfirmModal(e.target);
                }
            }

            document.getElementById('admin-vacation-user')?.addEventListener('change', updateAdminVacationValidation);
            document.getElementById('calendar-action-vacation-form')?.addEventListener('submit', handleAdminActionFormSubmit);
            document.getElementById('calendar-action-block-form')?.addEventListener('submit', handleAdminActionFormSubmit);

            // Admin calendar keeps a persistent selection by design.

            // Auto-populate the Urlaubssuche table on first load
            if (document.getElementById('req-search-tbody')) {
                filterRequests();
            }
        });

        /* ── URLAUBSSUCHE filterRequests() ─────────────────────────── */
        function filterRequests() {
            const nameRaw  = (document.getElementById('req-search-name')?.value ?? '').toLowerCase().trim();
            const statusF  = document.getElementById('req-search-status')?.value ?? '';
            const tbody    = document.getElementById('req-search-tbody');
            const emptyEl  = document.getElementById('req-search-empty');
            if (!tbody) return;

            const filtered = requestLookup.filter(function(r) {
                const name = (r.firstname + ' ' + r.lastname).toLowerCase();
                if (!isCeoUserVisible(r.user_id)) return false;
                return (!nameRaw || name.includes(nameRaw)) && (!statusF || r.status === statusF);
            });

            tbody.innerHTML = '';

            const badges = {
                pending:          '<span class="inline-flex px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 text-xs font-bold">Ausstehend</span>',
                approved:         '<span class="inline-flex px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-bold">Genehmigt</span>',
                rejected:         '<span class="inline-flex px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-xs font-bold">Abgelehnt</span>',
                storno_requested: '<span class="inline-flex px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 text-xs font-bold">Storno</span>',
                cancelled:        '<span class="inline-flex px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs font-bold">Storniert</span>',
            };

            if (filtered.length === 0) {
                emptyEl?.classList.remove('hidden');
                return;
            }
            emptyEl?.classList.add('hidden');

            // Sort: newest first by start_date
            filtered.sort((a, b) => (b.start_date > a.start_date ? 1 : -1));

            filtered.forEach(function(r) {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-yellow-50/50 transition-colors';
                tr.innerHTML =
                    '<td class="p-3 font-semibold">' + r.firstname + ' ' + r.lastname + '</td>' +
                    '<td class="p-3 text-emerald-700">' + r.start_date + ' → ' + r.end_date + '</td>' +
                    '<td class="p-3">' + r.net_days + ' T</td>' +
                    '<td class="p-3">' + (badges[r.status] ?? r.status) + '</td>' +
                    '<td class="p-3 text-right">' +
                        '<button onclick="showActionEventDetails(' + r.id + ')" ' +
                            'class="text-xs font-bold text-lime-600 hover:text-emerald-900 border border-lime-200 px-3 py-1 rounded-lg hover:bg-lime-50 transition-colors">' +
                            'Details' +
                        '</button>' +
                    '</td>';
                tbody.appendChild(tr);
            });
        }

        function openExportModal(isAdminExport) {
            const modal = document.getElementById('export-modal');
            const blockedRow = document.getElementById('export-include-blocked-row');
            if (!modal || !blockedRow) return;
            blockedRow.classList.toggle('hidden', !isAdminExport);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeExportModal() {
            const modal = document.getElementById('export-modal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openAdminPastConfirmModal(form) {
            adminPastConfirmForm = form;
            const modal = document.getElementById('admin-past-confirm-modal');
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeAdminPastConfirmModal() {
            adminPastConfirmForm = null;
            const modal = document.getElementById('admin-past-confirm-modal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function confirmAdminPastAction() {
            if (!adminPastConfirmForm) return;
            let confirmInput = adminPastConfirmForm.querySelector('input[name="confirm_past"]');
            if (!confirmInput) {
                confirmInput = document.createElement('input');
                confirmInput.type = 'hidden';
                confirmInput.name = 'confirm_past';
                adminPastConfirmForm.appendChild(confirmInput);
            }
            confirmInput.value = '1';
            adminPastConfirmForm.submit();
            closeAdminPastConfirmModal();
        }

        function vacationForm() {
            return {
                start: '',
                end: '',
                netDays: 0,
                todayMin: todayYmd,
                unbookableTitle: empUnbookableInfo,
                get hasInvalidSelection() {
                    if (!this.start || !this.end) return false;
                    return getEmployeeSelectionIssue(this.start, addDaysYmd(this.end, 1)) !== null;
                },
                get invalidSelectionMessage() {
                    if (!this.start || !this.end) return '';
                    const issue = getEmployeeSelectionIssue(this.start, addDaysYmd(this.end, 1));
                    return issue ? issue.message : '';
                },
                get invalidSelectionTitle() {
                    return this.unbookableTitle;
                },
                calculateDays() {
                    if (this.start && this.end) {
                        const s = new Date(this.start);
                        const e = new Date(this.end);
                        if (e >= s) {
                            const diffTime = Math.abs(e - s);
                            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                            this.netDays = diffDays;
                        } else {
                            this.netDays = 0;
                        }
                    }
                },
                handleEmployeeRequestSubmit(e) {
                    if (this.netDays <= 0 || !this.start || !this.end) {
                        e.preventDefault();
                        return;
                    }
                    const issue = getEmployeeSelectionIssue(this.start, addDaysYmd(this.end, 1));
                    if (issue) {
                        e.preventDefault();
                        if (window.showEasyTimeToastMessage) {
                            window.showEasyTimeToastMessage(issue.message, 'error', issue.type);
                        }
                    }
                }
            }
        }

        function togglePw(id) {
            const el = document.getElementById(id);
            if (el.type === 'password') {
                el.type = 'text';
            } else {
                el.type = 'password';
            }
        }

    </script>
    <?php include __DIR__ . '/partials/toast.php'; ?>
</body>
</html>
