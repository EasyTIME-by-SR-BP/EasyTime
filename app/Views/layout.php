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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                if (window.matchMedia('(min-width: 1024px)').matches && sessionStorage.getItem('et-sidebar-open') === '1') {
                    document.documentElement.classList.add('et-sidebar-pinned');
                }
            } catch (e) {}
        })();
        function easytimeSidebar() {
            const storageKey = 'et-sidebar-open';
            const pin = () => {
                if (window.innerWidth < 1024) return;
                sessionStorage.setItem(storageKey, '1');
                document.documentElement.classList.add('et-sidebar-pinned');
            };
            const unpin = () => {
                if (window.innerWidth < 1024) return;
                sessionStorage.removeItem(storageKey);
                document.documentElement.classList.remove('et-sidebar-pinned');
            };
            return {
                closeTimer: null,
                pin,
                unpin,
                open() {
                    clearTimeout(this.closeTimer);
                    pin();
                },
                close() {
                    clearTimeout(this.closeTimer);
                    this.closeTimer = setTimeout(() => unpin(), 180);
                },
                persistOpen() {
                    clearTimeout(this.closeTimer);
                    pin();
                },
            };
        }
        window.addEventListener('pageshow', function () {
            try {
                if (window.matchMedia('(min-width: 1024px)').matches && sessionStorage.getItem('et-sidebar-open') === '1') {
                    document.documentElement.classList.add('et-sidebar-pinned');
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
        #ceo-calendar.easytime-cross-month-drag { cursor: crosshair; }
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
            .easytime-layout {
                --et-sidebar-w: 4.5rem;
                --et-sidebar-w-hover: 18rem;
            }
            .easytime-sidebar {
                position: fixed;
                top: 4.5rem;
                left: 0;
                bottom: 0;
                width: var(--et-sidebar-w) !important;
                max-width: var(--et-sidebar-w-hover);
                overflow-x: hidden;
                overflow-y: auto;
                overscroll-behavior: contain;
                padding: 1rem 0.5rem;
                z-index: 50;
            }
            html.et-sidebar-pinned .easytime-sidebar,
            .easytime-sidebar:hover {
                width: var(--et-sidebar-w-hover) !important;
                padding: 1.5rem;
                box-shadow: 4px 0 24px rgba(26, 26, 26, 0.12);
            }
            .easytime-main {
                margin-left: var(--et-sidebar-w);
            }
            .easytime-sidebar .sidebar-label,
            .easytime-sidebar .sidebar-section-title,
            .easytime-sidebar .sidebar-badge {
                display: none !important;
            }
            html.et-sidebar-pinned .easytime-sidebar .sidebar-label,
            html.et-sidebar-pinned .easytime-sidebar .sidebar-section-title,
            .easytime-sidebar:hover .sidebar-label,
            .easytime-sidebar:hover .sidebar-section-title {
                display: block !important;
            }
            html.et-sidebar-pinned .easytime-sidebar .sidebar-badge,
            .easytime-sidebar:hover .sidebar-badge {
                display: inline-flex !important;
            }
            .easytime-sidebar .et-sidebar-tab {
                justify-content: center;
                padding-left: 0.75rem;
                padding-right: 0.75rem;
                gap: 0;
            }
            html.et-sidebar-pinned .easytime-sidebar .et-sidebar-tab,
            .easytime-sidebar:hover .et-sidebar-tab {
                justify-content: flex-start;
                gap: 0.75rem;
                padding-left: 1rem;
                padding-right: 1rem;
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
<body class="min-h-screen flex flex-col relative overflow-x-hidden">
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
    <div class="relative z-10 flex min-h-screen w-full flex-col">
        <?php include __DIR__ . '/partials/topbar.php'; ?>

        <div class="easytime-layout flex min-h-0 flex-1 w-full">
        <aside
            class="easytime-sidebar max-lg:w-full shrink-0 border-b border-lime-200/60 bg-white/95 shadow-sm backdrop-blur-lg lg:border-b-0 lg:border-r"
            x-data="easytimeSidebar()"
            @mouseenter="open()"
            @mouseleave="close()"
            @mousedown.capture="if ($event.target.closest('a')) persistOpen()"
        >
            <div class="flex h-full min-h-0 flex-col gap-5 p-4 lg:p-0">
                <?php if ($activeTab === 'inbox'): ?>
                    <?php include __DIR__ . '/partials/sidebar-inbox.php'; ?>
                <?php else: ?>
                <nav class="flex flex-wrap gap-2 max-lg:flex-row lg:flex-col" aria-label="Dashboard Navigation">
                    <?php if (in_array($currentRole, ['CEO', 'Admin'], true)): ?>
                        <a href="/?tab=operations" class="<?= $sidebarTabClass ?> <?= $activeTab === 'operations' ? $sidebarTabActive : $sidebarTabIdle ?>" title="Kalender & Genehmigungen">
                            <?= easytime_nav_icon('calendar') ?>
                            <span class="sidebar-label flex-1">Kalender & Genehmigungen</span>
                        </a>
                        <a href="/?tab=team" class="<?= $sidebarTabClass ?> <?= $activeTab === 'team' ? $sidebarTabActive : $sidebarTabIdle ?>" title="Team">
                            <?= easytime_nav_icon('team') ?>
                            <span class="sidebar-label flex-1">Team</span>
                        </a>
                        <a href="/?tab=settings" class="<?= $sidebarTabClass ?> <?= $activeTab === 'settings' ? $sidebarTabActive : $sidebarTabIdle ?>" title="Globale Einstellungen">
                            <?= easytime_nav_icon('settings') ?>
                            <span class="sidebar-label flex-1">Globale Einstellungen</span>
                        </a>
                    <?php else: ?>
                        <a href="/?tab=calendar" class="<?= $sidebarTabClass ?> <?= $activeTab === 'calendar' ? $sidebarTabActive : $sidebarTabIdle ?>" title="<?= I18n::get('emp.calendar') ?>">
                            <?= easytime_nav_icon('calendar') ?>
                            <span class="sidebar-label flex-1"><?= I18n::get('emp.calendar') ?></span>
                        </a>
                        <a href="/?tab=history" class="<?= $sidebarTabClass ?> <?= $activeTab === 'history' ? $sidebarTabActive : $sidebarTabIdle ?>" title="<?= I18n::get('history.title') ?>">
                            <?= easytime_nav_icon('history') ?>
                            <span class="sidebar-label flex-1"><?= I18n::get('history.title') ?></span>
                        </a>
                    <?php endif; ?>
                </nav>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="easytime-main relative z-10 flex-1 w-full min-w-0 p-4 sm:p-6 lg:p-8 flex flex-col gap-8 overflow-x-hidden">
        
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-lime-50 border border-lime-200 text-lime-800 px-4 py-3 rounded-xl text-sm flex items-center shadow-sm mb-4">
                <svg class="w-5 h-5 mr-3 text-lime-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <?php 
                    if ($_GET['success'] === 'employee_created') echo I18n::get('msg.employee_created');
                    else echo I18n::get('msg.action_success');
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm flex items-center shadow-sm mb-4">
                <svg class="w-5 h-5 mr-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <?php 
                    if ($_GET['error'] === 'invalid_mnr') echo (($_SESSION['lang'] ?? 'de') === 'de' ? 'Personalnummer (MNR) darf nur aus Zahlen bestehen!' : 'Staff number (MNR) must only contain digits!');
                    elseif ($_GET['error'] === 'blocked_period') echo (($_SESSION['lang'] ?? 'de') === 'de' ? 'In diesem Zeitraum ist keine Urlaubsbuchung erlaubt.' : 'Vacation booking is blocked for this selected period.');
                    elseif ($_GET['error'] === 'request_conflict') echo (($_SESSION['lang'] ?? 'de') === 'de' ? 'Dieser Zeitraum überschneidet sich mit einem bestehenden Urlaubsantrag.' : 'This range overlaps with an existing vacation request.');
                    elseif ($_GET['error'] === 'blocked_exists') echo (($_SESSION['lang'] ?? 'de') === 'de' ? 'Dieser Sperrbereich existiert bereits oder überschneidet einen bestehenden.' : 'This blocked period already exists or overlaps an existing blocked period.');
                    elseif ($_GET['error'] === 'past_date') echo (($_SESSION['lang'] ?? 'de') === 'de' ? 'Urlaub kann nicht in der Vergangenheit beantragt werden.' : 'Vacation cannot be requested for past dates.');
                    elseif ($_GET['error'] === 'coverage_conflict') echo (($_SESSION['lang'] ?? 'de') === 'de' ? 'Genehmigung nicht möglich: Mindestbesetzung würde unterschritten.' : 'Approval failed: minimum staffing would be violated.');
                    elseif ($_GET['error'] === 'fenstertage_exceeded') echo (($_SESSION['lang'] ?? 'de') === 'de' ? 'Dein Urlaubsantrag enthält zu viele Fenstertage (Brückentage). Bitte teile den Zeitraum auf.' : 'Your request contains too many window days (bridge days). Please split the period.');
                    elseif ($_GET['error'] === 'insufficient_balance') echo (($_SESSION['lang'] ?? 'de') === 'de' ? 'Nicht genügend Urlaubstage verfügbar.' : 'Not enough vacation days remaining.');
                    elseif ($_GET['error'] === 'self_delete_forbidden') echo (($_SESSION['lang'] ?? 'de') === 'de' ? 'Du kannst deinen eigenen Admin-Account nicht löschen.' : 'You cannot delete your own admin account.');
                    else echo "An error occurred.";
                ?>
            </div>
        <?php endif; ?>

        <?php if ($currentRole === 'Employee' && $activeTab === 'calendar'): ?>
            <div class="space-y-8">
                <div class="bg-white rounded-3xl border border-lime-100 shadow-xl p-6 sm:p-7 relative overflow-hidden">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-lime-100/70 blur-3xl" aria-hidden="true"></div>
                    <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 mb-5 relative"><?= I18n::get('emp.vacation_stats') ?></h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 sm:gap-8 relative">
                        <div>
                            <div class="text-4xl font-bold text-emerald-900 tabular-nums leading-none"><?= (int)($userVacationStats['entitlement'] ?? 0) ?></div>
                            <div class="mt-2 text-sm font-medium text-emerald-600"><?= I18n::get('emp.stats_total') ?></div>
                        </div>
                        <div>
                            <div class="text-4xl font-bold text-emerald-900 tabular-nums leading-none"><?= (int)($userVacationStats['planned'] ?? 0) ?></div>
                            <div class="mt-2 text-sm font-medium text-emerald-600"><?= I18n::get('emp.stats_planned') ?></div>
                        </div>
                        <div>
                            <div class="text-4xl font-bold text-emerald-900 tabular-nums leading-none"><?= (int)($userVacationStats['approved'] ?? 0) ?></div>
                            <div class="mt-2 text-sm font-medium text-emerald-600"><?= I18n::get('emp.stats_taken') ?></div>
                        </div>
                        <div>
                            <div class="text-4xl font-bold text-[#E8007D] tabular-nums leading-none"><?= (int)($userVacationStats['remaining'] ?? 0) ?></div>
                            <div class="mt-2 text-sm font-medium text-emerald-600"><?= I18n::get('emp.stats_remaining') ?></div>
                        </div>
                    </div>
                    <?php if (($maxFenstertage ?? 0) > 0): ?>
                        <p class="mt-6 text-sm leading-relaxed text-emerald-600/90 relative border-t border-lime-100 pt-5">
                            <?= sprintf(I18n::get('emp.max_fenstertage'), (int) $maxFenstertage) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                    <div class="xl:col-span-2 bg-white p-6 sm:p-7 rounded-3xl shadow-xl border border-lime-100">
                        <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 mb-2"><?= I18n::get('emp.calendar') ?></h2>
                        <p class="text-sm text-emerald-600/80 mb-4 leading-relaxed"><?= I18n::get('emp.calendar_hint') ?></p>
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
                        <div class="bg-white p-6 sm:p-7 rounded-3xl shadow-xl border border-lime-100">
                            <section id="employee-calendar-info-panel" class="mb-6">
                                <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 mb-4"><?= I18n::get('emp.panel_info') ?></h3>
                                <div id="employee-calendar-info-empty" class="relative overflow-hidden py-6 text-center">
                                    <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-lime-100/80 blur-2xl" aria-hidden="true"></div>
                                    <div class="relative mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-lime-50 to-emerald-50 text-emerald-500 shadow-inner">
                                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <p class="relative text-base font-bold text-emerald-900"><?= I18n::get('emp.calendar_info_empty_title') ?></p>
                                    <p class="relative mt-2 text-sm leading-relaxed text-emerald-600/80 max-w-[16rem] mx-auto"><?= I18n::get('emp.calendar_info_empty') ?></p>
                                </div>
                                <div id="employee-calendar-info-content" class="hidden">
                                    <div id="employee-calendar-info-body" class="space-y-4"></div>
                                </div>
                            </section>

                            <div class="h-px bg-gradient-to-r from-transparent via-lime-200 to-transparent mb-6" aria-hidden="true"></div>

                            <section>
                                <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-[#E8007D] mb-4"><?= I18n::get('emp.panel_action') ?></h3>
                                <div id="employee-calendar-action-empty" class="relative overflow-hidden py-6 text-center">
                                    <div class="pointer-events-none absolute -left-8 bottom-0 h-24 w-24 rounded-full bg-[#fff0f8]/90 blur-2xl" aria-hidden="true"></div>
                                    <div class="relative mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-[#fff8fc] to-lime-50 text-[#E8007D] shadow-inner">
                                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/>
                                        </svg>
                                    </div>
                                    <p class="relative text-base font-bold text-emerald-900"><?= I18n::get('emp.calendar_action_empty_title') ?></p>
                                    <p class="relative mt-2 text-sm leading-relaxed text-emerald-600/80 max-w-[16rem] mx-auto"><?= I18n::get('emp.calendar_action_empty') ?></p>
                                </div>

                                <div id="employee-calendar-action-range" class="hidden space-y-4">
                                    <form id="employee-request-form" action="/?action=create_request" method="POST" x-data="vacationForm()" class="space-y-4">
                                        <div
                                            x-show="hasInvalidSelection"
                                            x-cloak
                                            class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
                                        >
                                            <p class="font-bold mb-0.5" x-text="invalidSelectionTitle"></p>
                                            <p x-text="invalidSelectionMessage"></p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-emerald-800 mb-1.5"><?= I18n::get('emp.start_date') ?></label>
                                            <input id="employee-start-date" type="date" name="start_date" x-model="start" @change="calculateDays" min="<?= date('Y-m-d') ?>" required class="w-full bg-[#fffdf2] border border-lime-200 rounded-xl px-4 py-3 text-emerald-900 focus:ring-2 focus:ring-lime-400 outline-none transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-emerald-800 mb-1.5"><?= I18n::get('emp.end_date') ?></label>
                                            <input id="employee-end-date" type="date" name="end_date" x-model="end" @change="calculateDays" min="<?= date('Y-m-d') ?>" required class="w-full bg-[#fffdf2] border border-lime-200 rounded-xl px-4 py-3 text-emerald-900 focus:ring-2 focus:ring-lime-400 outline-none transition-all">
                                        </div>
                                        <div class="flex items-center justify-between gap-4 py-1">
                                            <span class="text-sm font-medium text-emerald-700"><?= I18n::get('emp.days_deduct') ?></span>
                                            <span class="text-4xl font-bold text-emerald-900 tabular-nums" x-text="netDays">0</span>
                                            <input type="hidden" name="net_days" x-model="netDays">
                                        </div>
                                        <button type="submit" class="w-full et-btn-primary font-bold py-3 px-4 rounded-xl shadow-lg shadow-lime-400/30 transition-all outline-none disabled:opacity-50 disabled:cursor-not-allowed" :disabled="netDays <= 0 || !start || !end || hasInvalidSelection">
                                            <?= I18n::get('emp.send_request') ?>
                                        </button>
                                    </form>
                                </div>

                                <div id="employee-calendar-action-event" class="hidden space-y-3">
                                    <div id="employee-selected-event-actions" class="space-y-2"></div>
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
            <!-- ADMIN: Kalender & Genehmigungen -->
            <div class="space-y-8">
                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                        <div class="xl:col-span-2 bg-white p-6 rounded-3xl shadow-xl border border-lime-100">
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                                <h2 class="text-2xl font-bold text-emerald-900">Kalender + Requests</h2>
                                <button type="button" onclick="openExportModal(true)" class="et-btn-secondary px-3 py-2 rounded-lg text-sm font-semibold">ICS Export</button>
                            </div>
                            <p class="text-sm text-emerald-700 mb-4">Gesperrte Zeitraeume markieren (fuer Mitarbeiter nicht buchbar). Zeitraum ziehen; am oberen/unteren Rand wechselt der Monat mit. Termine nur in „Kalender Actions“ rechts — ohne Scroll zur Liste.</p>
                            <div id="ceo-calendar"></div>
                        </div>
                        <div class="space-y-4 calendar-side-panel">
                            <div id="calendar-info-panel" class="bg-white p-6 rounded-3xl shadow-xl border border-lime-100">
                                <h3 class="text-xl font-bold text-emerald-900 mb-4">Kalender Infos</h3>
                            <div id="calendar-info-content" class="hidden">
                            <div class="grid grid-cols-3 gap-2 mb-4">
                                <div class="rounded-xl border border-lime-200 bg-lime-50 p-2">
                                    <div class="text-[10px] uppercase text-emerald-700 font-bold">Mitarbeiter</div>
                                    <div class="text-lg font-bold text-emerald-900"><?= (int)($capacitySummary['employees_total'] ?? 0) ?></div>
                                </div>
                                <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-2">
                                    <div class="text-[10px] uppercase text-emerald-700 font-bold">Abwesend</div>
                                    <div class="text-lg font-bold text-emerald-900"><?= (int)($capacitySummary['absent_approved'] ?? 0) ?></div>
                                </div>
                                <div class="rounded-xl border border-emerald-200 bg-white p-2">
                                    <div class="text-[10px] uppercase text-emerald-700 font-bold">Verfuegbar</div>
                                    <div class="text-lg font-bold text-emerald-900"><?= (int)($capacitySummary['available'] ?? 0) ?></div>
                                </div>
                            </div>
                            <div id="calendar-info-meta" class="text-xs text-emerald-700 bg-yellow-50 border border-yellow-200 rounded-xl p-3 mb-4 hidden"></div>
                            </div>
                            </div>

                            <div class="bg-white p-6 rounded-3xl shadow-xl border border-lime-100">
                            <h3 class="text-xl font-bold text-emerald-900 mb-4">Kalender Actions</h3>
                            <div id="calendar-action-empty" class="hidden"></div>
                            <form id="calendar-action-block-form" method="POST" action="/?action=create_blocked_period" class="space-y-3 hidden">
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" id="action-mode-block-btn" class="bg-red-100 text-red-700 border border-red-200 py-2 rounded-xl text-sm font-bold">Sperrbereich</button>
                                    <button type="button" id="action-mode-vacation-btn" class="et-btn-secondary py-2 rounded-xl text-sm font-bold">Urlaubszeit buchen</button>
                                </div>
                                <h4 class="text-sm uppercase tracking-wider font-bold text-emerald-700">Sperrbereich setzen</h4>
                                <div>
                                    <label class="block text-sm font-semibold text-emerald-800 mb-1">Start</label>
                                    <input id="blocked-start-date" type="date" name="start_date" required class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-emerald-800 mb-1">Ende</label>
                                    <input id="blocked-end-date" type="date" name="end_date" required class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-emerald-800 mb-1">Bezeichnung (optional)</label>
                                    <input type="text" name="label" placeholder="z.B. Betriebsurlaub" class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none">
                                </div>
                                <button type="submit" class="w-full bg-red-400 hover:bg-red-500 text-white font-bold py-2.5 rounded-xl">Sperrzeit speichern</button>
                            </form>

                            <form id="calendar-action-vacation-form" method="POST" action="/?action=admin_create_vacation" class="space-y-3 hidden">
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" id="action-mode-block-btn-2" class="et-btn-secondary py-2 rounded-xl text-sm font-bold">Sperrbereich</button>
                                    <button type="button" id="action-mode-vacation-btn-2" class="et-btn-primary py-2 rounded-xl text-sm font-bold">Urlaubszeit buchen</button>
                                </div>
                                <h4 class="text-sm uppercase tracking-wider font-bold text-emerald-700">Urlaubszeit fuer Mitarbeiter buchen</h4>
                                <div>
                                    <label class="block text-sm font-semibold text-emerald-800 mb-1">Mitarbeiter</label>
                                    <select name="user_id" required class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none">
                                        <option value="">Bitte auswaehlen</option>
                                        <?php foreach (($employees ?? []) as $empOpt): ?>
                                            <?php if (($empOpt['role'] ?? '') !== 'Employee') continue; ?>
                                            <option value="<?= $empOpt['id'] ?>"><?= htmlspecialchars($empOpt['firstname'] . ' ' . $empOpt['lastname']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-emerald-800 mb-1">Start</label>
                                    <input id="admin-vacation-start-date" type="date" name="start_date" min="<?= date('Y-m-d') ?>" required class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-emerald-800 mb-1">Ende</label>
                                    <input id="admin-vacation-end-date" type="date" name="end_date" min="<?= date('Y-m-d') ?>" required class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-emerald-800 mb-1">Kommentar im Verlauf (optional)</label>
                                    <input type="text" name="admin_comment" class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none">
                                </div>
                                <button type="submit" class="w-full et-btn-primary font-bold py-2.5 rounded-xl">Urlaubszeit buchen</button>
                            </form>

                            <div id="calendar-action-unblock" class="space-y-3 hidden">
                                <h4 class="text-sm uppercase tracking-wider font-bold text-emerald-700">Sperrbereich aufheben</h4>
                                <div id="calendar-action-unblock-list" class="space-y-2"></div>
                            </div>

                            <div id="calendar-action-event" class="space-y-3 hidden">
                                <h4 class="text-sm uppercase tracking-wider font-bold text-emerald-700">Termin Details</h4>
                                <div id="calendar-selected-event-info" class="text-sm text-emerald-800 bg-yellow-50 border border-yellow-200 rounded-xl p-4"></div>
                                <form method="POST" action="/?action=decide_request" class="space-y-3">
                                    <input type="hidden" id="calendar-selected-request-id" name="request_id" value="">
                                    <input type="hidden" id="calendar-selected-action-decline-value" value="rejected">
                                    <input type="hidden" id="calendar-selected-action-approve-value" value="approved">
                                    <input type="text" name="admin_comment" placeholder="Kommentar zur Entscheidung (optional)" class="w-full bg-white border border-yellow-200 rounded-xl px-4 py-2.5 text-sm text-emerald-900 outline-none">
                                    <div class="grid grid-cols-2 gap-2">
                                        <button type="submit" id="calendar-event-decline-btn" name="status" value="rejected" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 py-2 rounded-xl text-sm font-bold"><?= I18n::get('ceo.decline') ?></button>
                                        <button type="submit" id="calendar-event-approve-btn" name="status" value="approved" class="et-btn-primary py-2 rounded-xl text-sm font-bold"><?= I18n::get('ceo.approve') ?></button>
                                    </div>
                                </form>
                            </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                        <div class="xl:col-span-2">
                            <h2 class="text-3xl font-bold mb-6 text-emerald-900 tracking-tight"><?= I18n::get('ceo.need_approval') ?></h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php 
                        $hasPending = false;
                        foreach ($requests as $req): 
                            if (!in_array($req['status'], ['pending', 'storno_requested'])) continue;
                            $hasPending = true;
                            $isStorno = $req['status'] === 'storno_requested';
                        ?>
                            <div id="request-card-<?= $req['id'] ?>" data-request-id="<?= $req['id'] ?>" class="bg-white rounded-3xl p-6 shadow-xl shadow-lime-900/5 border <?= $isStorno ? 'border-orange-300' : 'border-lime-200' ?> relative overflow-hidden flex flex-col hover:-translate-y-1 transition-transform duration-300">
                                <div class="absolute top-0 right-0 w-24 h-24 <?= $isStorno ? 'bg-orange-100' : 'bg-yellow-100' ?> rounded-bl-full -z-10 mix-blend-multiply opacity-50"></div>
                                <div class="flex justify-between items-start mb-5">
                                    <div>
                                        <h3 class="font-bold text-xl text-emerald-900 flex items-center gap-2">
                                            <?= htmlspecialchars($req['firstname'] . ' ' . $req['lastname']) ?>
                                            <?php if($isStorno): ?>
                                                <span class="bg-orange-500/10 text-orange-600 text-[10px] uppercase font-black px-2 py-0.5 rounded-md">Storno</span>
                                            <?php endif; ?>
                                        </h3>
                                        <p class="text-sm font-medium text-emerald-600"><?= htmlspecialchars($req['email']) ?></p>
                                    </div>
                                    <span class="<?= $isStorno ? 'bg-orange-100 text-orange-800 border-orange-200' : 'bg-lime-100 text-emerald-800 border-lime-200' ?> text-xs px-3 py-1.5 rounded-lg font-bold border whitespace-nowrap">
                                        <?= $req['net_days'] ?> <?= I18n::get('ceo.days') ?>
                                    </span>
                                </div>
                                
                                <div class="bg-yellow-50/50 rounded-2xl p-4 mb-6 flex gap-4 text-center items-center justify-center border border-yellow-100">
                                    <div>
                                        <div class="text-[10px] text-emerald-600/70 font-bold uppercase tracking-widest mb-1"><?= I18n::get('ceo.from') ?></div>
                                        <div class="font-bold text-emerald-800"><?= date('d.m.Y', strtotime($req['start_date'])) ?></div>
                                    </div>
                                    <div class="text-lime-400 font-black">→</div>
                                    <div>
                                        <div class="text-[10px] text-emerald-600/70 font-bold uppercase tracking-widest mb-1"><?= I18n::get('ceo.to') ?></div>
                                        <div class="font-bold text-emerald-800"><?= date('d.m.Y', strtotime($req['end_date'])) ?></div>
                                    </div>
                                </div>

                                <form action="/?action=decide_request" method="POST" class="mt-auto space-y-3 relative z-10">
                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                    <input type="text" name="admin_comment" placeholder="Kommentar zur Entscheidung (optional)" class="w-full bg-white border border-yellow-200 rounded-xl px-4 py-2.5 text-sm text-emerald-900 focus:ring-2 focus:ring-lime-400 outline-none transition-all placeholder:text-emerald-300">
                                    <div class="flex gap-3">
                                        <?php if ($isStorno): ?>
                                            <!-- Approving Storno = Cancelled -->
                                            <!-- Rejecting Storno = Stays Approved -->
                                            <button type="submit" name="status" value="approved" class="flex-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 py-3 rounded-xl text-sm font-bold transition-all">Decline Storno</button>
                                            <button type="submit" name="status" value="cancelled" class="flex-1 bg-orange-400 hover:bg-orange-500 shadow-md shadow-orange-400/20 text-white py-3 rounded-xl text-sm font-bold transition-all">Approve Storno</button>
                                        <?php else: ?>
                                            <button type="submit" name="status" value="rejected" class="flex-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 py-3 rounded-xl text-sm font-bold transition-all"><?= I18n::get('ceo.decline') ?></button>
                                            <button type="submit" name="status" value="approved" class="flex-1 et-btn-primary py-3 rounded-xl text-sm font-bold transition-all"><?= I18n::get('ceo.approve') ?></button>
                                        <?php endif; ?>
                                    </div>
                                </form>

                                <div class="mt-4 border-t border-yellow-100 pt-3 space-y-2">
                                    <div class="text-xs uppercase tracking-wider font-bold text-emerald-700">Kommentare</div>
                                    <?php foreach (($requestCommentsById[$req['id']] ?? []) as $c): ?>
                                        <div class="text-xs bg-yellow-50 border border-yellow-100 rounded-lg p-2">
                                            <span class="font-bold"><?= htmlspecialchars($c['firstname'] . ' ' . $c['lastname']) ?>:</span>
                                            <?= htmlspecialchars($c['comment']) ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($requestCommentsById[$req['id']] ?? [])): ?>
                                        <div class="text-xs text-emerald-600">Noch keine Kommentare. Der optionale Entscheidungskommentar erscheint hier.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (!$hasPending): ?>
                            <div class="col-span-full py-16 text-center text-emerald-600/60 bg-white/50 rounded-3xl border-2 border-dashed border-lime-200">
                                <svg class="w-16 h-16 mx-auto mb-4 text-lime-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p class="text-xl font-bold tracking-tight text-emerald-800"><?= I18n::get('ceo.empty_requests') ?></p>
                                <p class="font-medium text-emerald-600/80"><?= I18n::get('ceo.empty_desc') ?></p>
                            </div>
                        <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- ── URLAUBSSUCHE ─────────────────────────────── -->
                    <div class="bg-white p-6 rounded-3xl shadow-xl border border-lime-100">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                            <h3 class="text-xl font-bold text-emerald-900">Urlaubssuche</h3>
                            <div class="flex gap-2 flex-wrap">
                                <input type="text" id="req-search-name"
                                    placeholder="Mitarbeiter suchen…"
                                    oninput="filterRequests()"
                                    class="bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-2 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400 w-52">
                                <select id="req-search-status" onchange="filterRequests()"
                                    class="bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400">
                                    <option value="">Alle Status</option>
                                    <option value="pending">Ausstehend</option>
                                    <option value="approved">Genehmigt</option>
                                    <option value="rejected">Abgelehnt</option>
                                    <option value="storno_requested">Storno angefragt</option>
                                    <option value="cancelled">Storniert</option>
                                </select>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse min-w-[560px]">
                                <thead>
                                    <tr class="bg-lime-50 border-b border-lime-100 text-xs uppercase text-emerald-700 tracking-wider font-semibold">
                                        <th class="p-3">Mitarbeiter</th>
                                        <th class="p-3">Zeitraum</th>
                                        <th class="p-3">Tage</th>
                                        <th class="p-3">Status</th>
                                        <th class="p-3 text-right">Aktion</th>
                                    </tr>
                                </thead>
                                <tbody id="req-search-tbody" class="divide-y divide-lime-100 text-emerald-800 text-sm font-medium"></tbody>
                            </table>
                            <div id="req-search-empty" class="py-8 text-center text-emerald-600/60 font-medium hidden">Keine Ergebnisse.</div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl shadow-xl border border-lime-100">
                        <h3 class="text-xl font-bold text-emerald-900 mb-4">Audit Log</h3>
                        <div class="space-y-2 max-h-64 overflow-auto pr-1">
                            <?php foreach (($recentAuditLogs ?? []) as $log): ?>
                                <div class="p-3 rounded-xl border border-yellow-100 bg-yellow-50">
                                    <div class="text-xs font-bold text-emerald-800"><?= htmlspecialchars($log['action']) ?></div>
                                    <div class="text-xs text-emerald-700"><?= htmlspecialchars(($log['firstname'] ?? 'System') . ' ' . ($log['lastname'] ?? '')) ?></div>
                                    <div class="text-[10px] text-emerald-500"><?= htmlspecialchars($log['created_at']) ?></div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($recentAuditLogs)): ?>
                                <div class="text-sm text-emerald-600">Noch keine Audit-Eintraege.</div>
                            <?php endif; ?>
                        </div>
                    </div>
            </div>

        <?php elseif (in_array($currentRole, ['CEO', 'Admin'], true) && $activeTab === 'team'): ?>
                    <?php $isTeamDetail = isset($_GET['team_view']) && $_GET['team_view'] === 'detail'; ?>
                    <?php if ($isTeamDetail && isset($selectedTeamUser) && $selectedTeamUser): ?>
                        <div class="bg-white rounded-3xl p-6 shadow-xl shadow-lime-900/5 border border-lime-100">
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                                <a href="/?tab=team" class="text-sm font-bold text-emerald-700 hover:text-emerald-900">← Zurueck zur Team-Uebersicht</a>
                                <span class="text-xs font-bold px-3 py-1 rounded-full <?= $selectedTeamUser['role'] === 'CEO' ? 'bg-blue-100 text-blue-700' : 'bg-lime-100 text-emerald-700' ?>"><?= $selectedTeamUser['role'] === 'CEO' ? 'Admin' : htmlspecialchars($selectedTeamUser['role']) ?></span>
                            </div>
                            <h3 class="text-2xl font-bold text-emerald-900"><?= htmlspecialchars($selectedTeamUser['firstname'] . ' ' . $selectedTeamUser['lastname']) ?></h3>
                            <p class="text-sm text-emerald-600 mb-5"><?= htmlspecialchars($selectedTeamUser['email']) ?> | MNR <?= htmlspecialchars($selectedTeamUser['mnr']) ?></p>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5">
                                <div class="bg-lime-50 border border-lime-200 rounded-xl p-3"><div class="text-xs uppercase font-bold text-emerald-700">Urlaubstage gesamt</div><div class="text-xl font-bold text-emerald-900"><?= (int) $selectedTeamUser['vacation_entitlement_days'] ?></div></div>
                                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-3"><div class="text-xs uppercase font-bold text-emerald-700">Bereits genehmigt</div><div class="text-xl font-bold text-emerald-900"><?= (int) ($selectedTeamUserUsedDays ?? 0) ?></div></div>
                                <div class="bg-white border border-emerald-200 rounded-xl p-3"><div class="text-xs uppercase font-bold text-emerald-700">Resturlaub</div><div class="text-xl font-bold text-emerald-900"><?= max(0, (int)$selectedTeamUser['vacation_entitlement_days'] - (int)($selectedTeamUserUsedDays ?? 0)) ?></div></div>
                            </div>

                            <?php $isOwnAdminAccount = ((int) $selectedTeamUser['id'] === (int) $currentUser['id']) && (($currentUser['role'] ?? '') === 'CEO'); ?>
                            <?php if ($isOwnAdminAccount): ?>
                                <div class="flex justify-end mb-3">
                                    <span class="text-xs font-semibold text-emerald-700 bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-1.5">
                                        <?= (($_SESSION['lang'] ?? 'de') === 'de') ? 'Eigener Admin-Account kann nicht gelöscht werden.' : 'Own admin account cannot be deleted.' ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <form method="POST" action="/?action=delete_employee" onsubmit="return confirm('Ensure you want to delete this employee?');" class="flex justify-end mb-3">
                                    <input type="hidden" name="emp_id" value="<?= $selectedTeamUser['id'] ?>">
                                    <button type="submit" class="text-red-600 font-bold text-sm"><?= I18n::get('ceo.delete') ?></button>
                                </form>
                            <?php endif; ?>

                            <form method="POST" action="/?action=edit_employee" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <input type="hidden" name="emp_id" value="<?= $selectedTeamUser['id'] ?>">
                                <div><label class="block text-xs font-bold text-emerald-700 mb-1">Vorname</label><input type="text" name="firstname" value="<?= htmlspecialchars($selectedTeamUser['firstname']) ?>" class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none"></div>
                                <div><label class="block text-xs font-bold text-emerald-700 mb-1">Nachname</label><input type="text" name="lastname" value="<?= htmlspecialchars($selectedTeamUser['lastname']) ?>" class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none"></div>
                                <div><label class="block text-xs font-bold text-emerald-700 mb-1">E-Mail</label><input type="email" name="email" value="<?= htmlspecialchars($selectedTeamUser['email']) ?>" class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none"></div>
                                <div><label class="block text-xs font-bold text-emerald-700 mb-1">MNR</label><input type="text" name="mnr" value="<?= htmlspecialchars($selectedTeamUser['mnr']) ?>" pattern="[A-Za-z]?[0-9]+" title="MNR, z.B. M002 oder 002" class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none"></div>
                                <div><label class="block text-xs font-bold text-emerald-700 mb-1">Rolle</label><select name="role" class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none">
                                    <option value="Employee" <?= $selectedTeamUser['role'] === 'Employee' ? 'selected' : '' ?>>Employee</option>
                                    <option value="Admin" <?= $selectedTeamUser['role'] === 'CEO' ? 'selected' : '' ?>>Admin</option>
                                </select></div>
                                <div><label class="block text-xs font-bold text-emerald-700 mb-1">Department</label><select name="department_id" class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none">
                                    <option value="">Department</option>
                                    <?php foreach (($departments ?? []) as $dept): ?>
                                        <option value="<?= $dept['id'] ?>" <?= ((string)$selectedTeamUser['department_id'] === (string)$dept['id']) ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                                    <?php endforeach; ?>
                                </select></div>
                                <div><label class="block text-xs font-bold text-emerald-700 mb-1">Urlaubstage</label><input type="number" min="0" name="vacation_entitlement_days" value="<?= (int)$selectedTeamUser['vacation_entitlement_days'] ?>" class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none"></div>
                                <div><label class="block text-xs font-bold text-emerald-700 mb-1">Ueberstunden</label><input type="number" min="0" step="0.5" name="overtime_hours" value="<?= htmlspecialchars($selectedTeamUser['overtime_hours']) ?>" class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none"></div>
                                <div class="md:col-span-2"><label class="block text-xs font-bold text-emerald-700 mb-1">Neues Passwort (optional)</label><input type="password" name="password" id="team-password-field" class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none <?= (isset($_GET['focus']) && $_GET['focus'] === 'password') ? 'ring-2 ring-lime-400 border-lime-400' : '' ?>"></div>
                                <div class="md:col-span-2 flex justify-end items-center mt-1">
                                    <button type="submit" class="et-btn-primary px-4 py-2 rounded-xl font-bold"><?= I18n::get('ceo.save') ?></button>
                                </div>
                            </form>
                        </div>
                        <?php if (isset($_GET['focus']) && $_GET['focus'] === 'password'): ?>
                        <script>
                            document.getElementById('team-password-field')?.focus();
                            document.getElementById('team-password-field')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        </script>
                        <?php endif; ?>

                        <div class="bg-white rounded-3xl p-6 shadow-xl shadow-lime-900/5 border border-lime-100 mt-6">
                            <h4 class="text-xl font-bold text-emerald-900 mb-4">Urlaubsuebersicht dieses Users</h4>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse min-w-[560px]">
                                    <thead>
                                        <tr class="bg-lime-50 border-b border-lime-100 text-xs uppercase text-emerald-700 tracking-wider font-semibold">
                                            <th class="p-3">Zeitraum</th>
                                            <th class="p-3">Tage</th>
                                            <th class="p-3">Status</th>
                                            <th class="p-3">Kommentar</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-lime-100 text-emerald-800 text-sm">
                                        <?php foreach (($selectedTeamUserRequests ?? []) as $req): ?>
                                            <tr>
                                                <td class="p-3"><?= htmlspecialchars($req['start_date']) ?> - <?= htmlspecialchars($req['end_date']) ?></td>
                                                <td class="p-3"><?= (int)$req['net_days'] ?></td>
                                                <td class="p-3"><?= htmlspecialchars($req['status']) ?></td>
                                                <td class="p-3">
                                                    <?php
                                                        $requestCommentList = $requestCommentsById[$req['id']] ?? [];
                                                        $latestComment = !empty($requestCommentList) ? $requestCommentList[count($requestCommentList) - 1] : null;
                                                    ?>
                                                    <?= $latestComment ? htmlspecialchars($latestComment['comment']) : '-' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($selectedTeamUserRequests)): ?>
                                            <tr><td class="p-4 text-emerald-600" colspan="4">Keine Urlaubsantraege vorhanden.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8" x-data="{ teamSearch: '' }">
                            <div class="xl:col-span-2 bg-white rounded-3xl p-6 shadow-xl shadow-lime-900/5 border border-lime-100">
                                <div class="flex items-center justify-between gap-3 mb-4">
                                    <h3 class="text-2xl font-bold text-emerald-900">Alle User</h3>
                                    <input type="text" x-model="teamSearch" placeholder="Suche nach Name, E-Mail, MNR..." class="w-full max-w-md bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none">
                                </div>
                                <div class="space-y-2 max-h-[620px] overflow-auto pr-1">
                                    <?php if (isset($employees)): foreach ($employees as $emp): ?>
                                        <a
                                            href="/?tab=team&team_view=detail&team_user=<?= $emp['id'] ?>"
                                            x-show="'<?= strtolower(htmlspecialchars($emp['firstname'] . ' ' . $emp['lastname'] . ' ' . $emp['email'] . ' ' . $emp['mnr'])) ?>'.includes(teamSearch.toLowerCase())"
                                            class="flex items-center justify-between gap-3 p-4 rounded-xl border border-yellow-100 bg-white hover:bg-yellow-50"
                                        >
                                            <div>
                                                <div class="font-semibold text-emerald-900"><?= htmlspecialchars($emp['firstname'] . ' ' . $emp['lastname']) ?></div>
                                                <div class="text-xs text-emerald-600"><?= htmlspecialchars($emp['email']) ?> | MNR <?= htmlspecialchars($emp['mnr']) ?></div>
                                            </div>
                                            <span class="text-xs font-bold px-2 py-1 rounded-full <?= $emp['role'] === 'CEO' ? 'bg-blue-100 text-blue-700' : 'bg-lime-100 text-emerald-700' ?>"><?= $emp['role'] === 'CEO' ? 'Admin' : htmlspecialchars($emp['role']) ?></span>
                                        </a>
                                    <?php endforeach; endif; ?>
                                </div>
                            </div>

                            <div class="bg-white rounded-3xl p-6 shadow-xl shadow-lime-900/5 border border-lime-100">
                                <h3 class="text-xl font-bold text-emerald-900 mb-4">Neuen User erstellen</h3>
                                <form action="/?action=create_employee" method="POST" class="space-y-3">
                                    <div class="grid grid-cols-2 gap-3">
                                        <input type="text" name="firstname" placeholder="<?= I18n::get('ceo.firstname') ?>" required class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none">
                                        <input type="text" name="lastname" placeholder="<?= I18n::get('ceo.lastname') ?>" required class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none">
                                    </div>
                                    <input type="email" name="email" placeholder="<?= I18n::get('ceo.email') ?>" required class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none">
                                    <input type="text" name="mnr" placeholder="<?= I18n::get('ceo.mnr') ?>" pattern="[A-Za-z]?[0-9]+" title="MNR, z.B. M002 oder 002" required class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none">
                                    <input type="password" id="new_emp_pw" name="password" placeholder="<?= I18n::get('ceo.initial_pw') ?>" required class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none">
                                    <div class="grid grid-cols-2 gap-3">
                                        <select name="role" class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none">
                                            <option value="Employee">Employee</option>
                                            <option value="Admin">Admin</option>
                                        </select>
                                        <select name="department_id" class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none">
                                            <option value="">Department</option>
                                            <?php foreach (($departments ?? []) as $dept): ?>
                                                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <input type="number" min="0" name="vacation_entitlement_days" value="25" placeholder="Urlaubstage" class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none">
                                        <input type="number" min="0" step="0.5" name="overtime_hours" value="0" placeholder="Ueberstunden" class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 text-emerald-900 outline-none">
                                    </div>
                                    <button type="submit" class="w-full et-btn-primary font-bold py-2.5 rounded-xl"><?= I18n::get('ceo.register_btn') ?></button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

        <?php elseif (in_array($currentRole, ['CEO', 'Admin'], true) && $activeTab === 'settings'): ?>
            <div class="bg-white rounded-3xl p-6 shadow-xl shadow-lime-900/5 border border-lime-100 max-w-3xl">
                <h2 class="text-2xl font-bold text-emerald-900 mb-2"><?= I18n::get('settings.title') ?></h2>
                <p class="text-sm text-emerald-600 mb-6"><?= I18n::get('settings.holidays_note') ?></p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <form method="POST" action="/?action=update_min_staff" class="flex items-end gap-2">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-emerald-700 mb-1"><?= I18n::get('settings.min_staff') ?></label>
                            <input type="number" min="0" name="min_staff_available" value="<?= (int)($minStaffAvailable ?? 1) ?>" class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2 text-emerald-900 outline-none">
                        </div>
                        <button type="submit" class="et-btn-primary font-bold px-3 py-2 rounded-xl">✓</button>
                    </form>
                    <form method="POST" action="/?action=update_max_fenstertage" class="flex items-end gap-2">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-emerald-700 mb-1">
                                <?= I18n::get('settings.max_fenstertage') ?>
                                <span class="font-normal text-emerald-500"><?= I18n::get('settings.max_fenstertage_hint') ?></span>
                            </label>
                            <input type="number" min="0" name="max_fenstertage" value="<?= (int)($maxFenstertage ?? 0) ?>" class="w-full bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2 text-emerald-900 outline-none">
                        </div>
                        <button type="submit" class="et-btn-primary font-bold px-3 py-2 rounded-xl">✓</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        </main>
        </div>
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
        let suppressEmpDateClick = false;
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

        function isShowCancelledVacationEnabled() {
            return localStorage.getItem(showCancelledVacationKey) === '1';
        }

        function shouldHideCancelledEvent(calendarId, status) {
            if (status !== 'cancelled') return false;
            if (calendarId === 'ceo-calendar') return true;
            if (calendarId === 'employee-calendar') return !isShowCancelledVacationEnabled();
            return true;
        }

        function applyCancelledVacationVisibility(calendarRootId) {
            if (calendarRootId !== 'employee-calendar') return;
            const show = isShowCancelledVacationEnabled();
            document.getElementById('employee-calendar')?.querySelectorAll('.fc-event[data-status="cancelled"]').forEach((el) => {
                el.style.display = show ? '' : 'none';
            });
        }

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
            .filter((r) => ['pending', 'approved', 'storno_requested'].includes(r.status))
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

        const calendarSelection = {
            'employee-calendar': { type: null, start: null, end: null, requestId: null },
            'ceo-calendar': { type: null, start: null, end: null, requestId: null }
        };
        
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

            function updateEmployeeRangeSelectionUi(startYmd, endExclusiveYmd) {
                const endInclusive = addDaysYmd(endExclusiveYmd, -1);
                const isSingle = startYmd === endInclusive;
                const issue = getEmployeeSelectionIssue(startYmd, endExclusiveYmd);
                const days = eachDayInRangeExclusive(startYmd, endExclusiveYmd).length;

                renderEmployeeInfoPanel({
                    eyebrow: isSingle ? 'Tag' : 'Zeitraum',
                    range: formatEmployeeDateRange(startYmd, endInclusive),
                    statusLabel: issue ? empUnbookableInfo : '',
                    statusClass: issue ? 'bg-red-100 text-red-800 border-red-200' : '',
                    days,
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
                    <div class="text-2xl sm:text-[1.65rem] font-bold text-emerald-900 leading-tight tracking-tight">${range}</div>
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
                const infoBody = document.getElementById('employee-calendar-info-body');
                if (infoBody) infoBody.innerHTML = '';
                const actionBody = document.getElementById('employee-selected-event-actions');
                if (actionBody) actionBody.innerHTML = '';
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

            function handleEmployeeDateClick(dateStr) {
                const endExclusive = addDaysYmd(dateStr, 1);

                const sel = calendarSelection['employee-calendar'];
                if (sel.type === 'range' && sel.start && sel.end && isYmdInRange(dateStr, sel.start, sel.end)) {
                    const endInclusive = addDaysYmd(sel.end, -1);
                    if (dateStr === sel.start && dateStr === endInclusive) {
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

            function attachCrossMonthDrag(calendar, calendarEl, onCommitRange) {
                if (!calendar || !calendarEl) return;
                const EDGE_PX = 52;
                const NAV_COOLDOWN_MS = 380;
                let anchorYmd = null;
                let lastNavAt = 0;
                let active = false;
                let dragMoved = false;

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

                function maybeChangeMonth(e) {
                    const rect = calendarEl.getBoundingClientRect();
                    const now = Date.now();
                    if (now - lastNavAt < NAV_COOLDOWN_MS) return;
                    if (e.clientY < rect.top + EDGE_PX) {
                        calendar.prev();
                        lastNavAt = now;
                    } else if (e.clientY > rect.bottom - EDGE_PX) {
                        calendar.next();
                        lastNavAt = now;
                    }
                }

                function onPointerMove(e) {
                    if (!active || !anchorYmd) return;
                    dragMoved = true;
                    maybeChangeMonth(e);
                    const dayEl = dayElFromPoint(e.clientX, e.clientY);
                    const hoverYmd = dayEl ? dayEl.getAttribute('data-date') : null;
                    if (!hoverYmd) return;
                    const start = compareYmd(anchorYmd, hoverYmd) <= 0 ? anchorYmd : hoverYmd;
                    const endInclusive = compareYmd(anchorYmd, hoverYmd) <= 0 ? hoverYmd : anchorYmd;
                    previewRange(start, endInclusive);
                }

                function endDrag(e) {
                    if (!active) return;
                    active = false;
                    calendarEl.classList.remove('easytime-cross-month-drag');
                    document.removeEventListener('pointermove', onPointerMove);
                    document.removeEventListener('pointerup', endDrag);
                    clearPreview();
                    const dayEl = dayElFromPoint(e.clientX, e.clientY);
                    const hoverYmd = dayEl ? dayEl.getAttribute('data-date') : anchorYmd;
                    if (dragMoved && anchorYmd && hoverYmd && typeof onCommitRange === 'function') {
                        if (calendarEl.id === 'employee-calendar') {
                            suppressEmpDateClick = true;
                        }
                        const start = compareYmd(anchorYmd, hoverYmd) <= 0 ? anchorYmd : hoverYmd;
                        const endInclusive = compareYmd(anchorYmd, hoverYmd) <= 0 ? hoverYmd : anchorYmd;
                        onCommitRange(start, addDaysYmd(endInclusive, 1), e);
                    }
                    anchorYmd = null;
                    dragMoved = false;
                }

                calendarEl.addEventListener('pointerdown', function(e) {
                    if (e.button !== 0) return;
                    if (e.target.closest('.fc-event, .fc-button, a, button, input, select, textarea, label')) return;
                    const dayEl = e.target.closest('.fc-daygrid-day');
                    if (!dayEl) return;
                    const ymd = dayEl.getAttribute('data-date');
                    if (!ymd) return;
                    anchorYmd = ymd;
                    active = true;
                    dragMoved = false;
                    lastNavAt = 0;
                    calendarEl.classList.add('easytime-cross-month-drag');
                    previewRange(ymd, ymd);
                    document.addEventListener('pointermove', onPointerMove);
                    document.addEventListener('pointerup', endDrag);
                });
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

            function setBlockedFormDates(startStr, endExclusiveStr) {
                const startInput = document.getElementById('blocked-start-date');
                const endInput = document.getElementById('blocked-end-date');
                if (!startInput || !endInput) return;

                const endDate = new Date(endExclusiveStr);
                endDate.setDate(endDate.getDate() - 1);
                const localEnd = formatLocalDate(endDate);

                startInput.value = startStr;
                endInput.value = localEnd;
            }

            function setAdminVacationFormDates(startStr, endExclusiveStr) {
                const startInput = document.getElementById('admin-vacation-start-date');
                const endInput = document.getElementById('admin-vacation-end-date');
                if (!startInput || !endInput) return;

                const endDate = new Date(endExclusiveStr);
                endDate.setDate(endDate.getDate() - 1);
                const localEnd = endDate.toISOString().slice(0, 10);

                startInput.value = startStr;
                endInput.value = localEnd;
            }

            function clearCalendarActions() {
                document.getElementById('calendar-info-content')?.classList.add('hidden');
                document.getElementById('calendar-action-empty')?.classList.add('hidden');
                document.getElementById('calendar-action-block-form')?.classList.add('hidden');
                document.getElementById('calendar-action-vacation-form')?.classList.add('hidden');
                document.getElementById('calendar-action-unblock')?.classList.add('hidden');
                document.getElementById('calendar-action-event')?.classList.add('hidden');
            }

            function setCalendarInfo(type, start, end, meta = '') {
                const panel = document.getElementById('calendar-info-panel');
                const metaEl = document.getElementById('calendar-info-meta');
                if (!panel || !metaEl) return;
                if (meta && meta.trim() !== '') {
                    metaEl.textContent = `${type}: ${start} bis ${end} | ${meta}`;
                    metaEl.classList.remove('hidden');
                } else {
                    metaEl.textContent = '';
                    metaEl.classList.add('hidden');
                }
                document.getElementById('calendar-info-content')?.classList.remove('hidden');
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

            function applyCeoSelection(range, syncCalendarSelection = true) {
                if (!range || !range.start || !range.end) return;
                ceoSelectedRange = { start: range.start, end: range.end };
                persistCeoRange(ceoSelectedRange);
                setCalendarRangeSelection('ceo-calendar', range.start, range.end, syncCalendarSelection);
                if (hasBlockedOverlap(ceoSelectedRange.start, ceoSelectedRange.end)) {
                    showActionUnblockSelection(ceoSelectedRange.start, ceoSelectedRange.end);
                } else {
                    showActionBlockedSelection(ceoSelectedRange.start, ceoSelectedRange.end);
                }
            }

            function showActionBlockedSelection(startStr, endExclusiveStr) {
                setBlockedFormDates(startStr, endExclusiveStr);
                const endDate = new Date(endExclusiveStr);
                endDate.setDate(endDate.getDate() - 1);
                setCalendarInfo('Zeitraum', startStr, formatLocalDate(endDate), 'Aktion: Sperrbereich setzen oder auf Urlaubszeit buchen wechseln.');
                document.getElementById('calendar-action-block-form')?.classList.remove('hidden');
                document.getElementById('calendar-action-vacation-form')?.classList.add('hidden');
                document.getElementById('calendar-action-unblock')?.classList.add('hidden');
                document.getElementById('calendar-action-event')?.classList.add('hidden');
            }

            function showActionVacationSelection(startStr, endExclusiveStr) {
                setAdminVacationFormDates(startStr, endExclusiveStr);
                const endDate = new Date(endExclusiveStr);
                endDate.setDate(endDate.getDate() - 1);
                setCalendarInfo('Zeitraum', startStr, formatLocalDate(endDate), 'Aktion: Urlaubszeit für Mitarbeiter buchen.');
                document.getElementById('calendar-action-block-form')?.classList.add('hidden');
                document.getElementById('calendar-action-vacation-form')?.classList.remove('hidden');
                document.getElementById('calendar-action-unblock')?.classList.add('hidden');
                document.getElementById('calendar-action-event')?.classList.add('hidden');
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
                const endDate = new Date(endExclusiveStr);
                endDate.setDate(endDate.getDate() - 1);
                setCalendarInfo('Zeitraum', startStr, formatLocalDate(endDate), `Aktion: ${overlaps.length} Sperrbereich(e) aufheben.`);
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
                document.getElementById('calendar-action-block-form')?.classList.add('hidden');
                document.getElementById('calendar-action-vacation-form')?.classList.add('hidden');
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
                    cancelled: '<?= I18n::get('emp.status_cancelled') ?>'
                };
                return labels[status] || status;
            }

            window.showEmployeeEventDetails = function showEmployeeEventDetails(requestId) {
                const request = requestLookup.find((r) => String(r.id) === String(requestId));
                if (!request) return;
                const actions = document.getElementById('employee-selected-event-actions');
                if (!actions) return;

                renderEmployeeInfoPanel({
                    eyebrow: 'Antrag #' + request.id,
                    range: formatEmployeeDateRange(request.start_date, request.end_date),
                    statusLabel: employeeStatusLabel(request.status),
                    statusClass: employeeStatusBadgeClass(request.status),
                    days: request.net_days
                });

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
                    actionsHtml = `
                        <form method="POST" action="/?action=request_storno">
                            <input type="hidden" name="request_id" value="${request.id}">
                            <input type="hidden" name="return_tab" value="calendar">
                            <button type="submit" class="w-full text-orange-600 hover:text-white hover:bg-orange-500 border border-orange-200 py-3 rounded-xl text-sm font-bold transition-colors">
                                <?= I18n::get('emp.storno') ?>
                            </button>
                        </form>`;
                } else if (request.status === 'storno_requested') {
                    actionsHtml = `
                        <form method="POST" action="/?action=withdraw_storno">
                            <input type="hidden" name="request_id" value="${request.id}">
                            <input type="hidden" name="return_tab" value="calendar">
                            <button type="submit" class="w-full et-btn-secondary py-3 rounded-xl text-sm font-bold transition-colors">
                                <?= I18n::get('emp.cancel_storno') ?>
                            </button>
                        </form>`;
                } else {
                    actionsHtml = '<p class="text-sm text-emerald-600 text-center py-2">Keine Aktionen für diesen Status.</p>';
                }
                actions.innerHTML = actionsHtml;

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
                const info = document.getElementById('calendar-selected-event-info');
                const hiddenId = document.getElementById('calendar-selected-request-id');
                const declineBtn = document.getElementById('calendar-event-decline-btn');
                const approveBtn = document.getElementById('calendar-event-approve-btn');
                if (!info || !hiddenId) return;
                hiddenId.value = request.id;

                if (declineBtn && approveBtn) {
                    if (request.status === 'storno_requested') {
                        declineBtn.value = 'approved';
                        approveBtn.value = 'cancelled';
                        declineBtn.textContent = 'Decline Storno';
                        approveBtn.textContent = 'Approve Storno';
                    } else {
                        declineBtn.value = 'rejected';
                        approveBtn.value = 'approved';
                        declineBtn.textContent = '<?= I18n::get('ceo.decline') ?>';
                        approveBtn.textContent = '<?= I18n::get('ceo.approve') ?>';
                    }
                    const canDecide = !['rejected', 'cancelled'].includes(request.status);
                    declineBtn.disabled = !canDecide;
                    approveBtn.disabled = !canDecide;
                    if (!canDecide) {
                        declineBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        approveBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    } else {
                        declineBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                        approveBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                }

                info.innerHTML = `
                    <div class="font-bold text-base mb-2">${request.firstname} ${request.lastname}</div>
                    <div><span class="font-semibold">Zeitraum:</span> ${request.start_date} bis ${request.end_date}</div>
                    <div><span class="font-semibold">Status:</span> ${request.status}</div>
                    <div><span class="font-semibold">Tage:</span> ${request.net_days}</div>
                    <div><span class="font-semibold">Kontakt:</span> ${request.email}</div>
                `;
                setCalendarInfo('Termin', request.start_date, request.end_date, `Status: ${request.status} | Mitarbeiter: ${request.firstname} ${request.lastname}`);
                document.getElementById('calendar-action-block-form')?.classList.add('hidden');
                document.getElementById('calendar-action-vacation-form')?.classList.add('hidden');
                document.getElementById('calendar-action-unblock')?.classList.add('hidden');
                document.getElementById('calendar-action-event')?.classList.remove('hidden');
            }

            function initFC(elemId) {
                const el = document.getElementById(elemId);
                if (!el) return;
                const calendar = new FullCalendar.Calendar(el, {
                    initialView: 'dayGridMonth',
                    locale: currentLang,
                    events: fcEvents,
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,dayGridWeek'
                    },
                    height: 'auto',
                    firstDay: 1, // Start on Monday
                    weekNumbers: true,
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
                        if (elemId === 'employee-calendar') {
                            handleEmployeeDateClick(info.dateStr);
                            return;
                        }
                        if (elemId === 'ceo-calendar' && (currentRole === 'CEO' || currentRole === 'Admin')) {
                            const start = info.dateStr;
                            const end = addDaysYmd(start, 1);
                            applyCeoSelection({ start, end }, true);
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
                        if (requestId) {
                            arg.el.setAttribute('data-request-id', requestId);
                        }
                        if (status) {
                            arg.el.setAttribute('data-status', status);
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
                    attachCrossMonthDrag(calendar, el, function(startYmd, endExclusive) {
                        applyCeoSelection({ start: startYmd, end: endExclusive }, false);
                    });
                    const persistedRange = loadPersistedCeoRange();
                    applyCeoSelection(persistedRange || getTodayRange());
                }
            }

            if (document.getElementById('employee-calendar')) {
                initFC('employee-calendar');
                const showCancelledCb = document.getElementById('employee-show-cancelled');
                if (showCancelledCb) {
                    showCancelledCb.checked = isShowCancelledVacationEnabled();
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
                    updateEmployeeRangeSelectionUi(start, addDaysYmd(end, 1));
                }
                document.getElementById('employee-start-date')?.addEventListener('change', syncEmployeePastUiFromForm);
                document.getElementById('employee-end-date')?.addEventListener('change', syncEmployeePastUiFromForm);
            }
            if (document.getElementById('ceo-calendar')) {
                initFC('ceo-calendar');
            }

            document.getElementById('action-mode-vacation-btn')?.addEventListener('click', function() {
                const start = document.getElementById('blocked-start-date')?.value;
                const end = document.getElementById('blocked-end-date')?.value;
                if (start && end) {
                    const endPlusOne = new Date(end);
                    endPlusOne.setDate(endPlusOne.getDate() + 1);
                    showActionVacationSelection(start, formatLocalDate(endPlusOne));
                }
            });
            document.getElementById('action-mode-block-btn-2')?.addEventListener('click', function() {
                const start = document.getElementById('admin-vacation-start-date')?.value;
                const end = document.getElementById('admin-vacation-end-date')?.value;
                if (start && end) {
                    const endPlusOne = new Date(end);
                    endPlusOne.setDate(endPlusOne.getDate() + 1);
                    showActionBlockedSelection(start, formatLocalDate(endPlusOne));
                }
            });

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
</body>
</html>
