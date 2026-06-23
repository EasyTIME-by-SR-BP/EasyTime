<?php

namespace App\Core;

class TourConfig
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function stepsForRole(string $role): array
    {
        $isAdmin = in_array($role, ['CEO', 'Admin'], true);

        if ($isAdmin) {
            return self::adminSteps();
        }

        return self::employeeSteps();
    }

    /**
     * @return array<string, string>
     */
    public static function uiLabels(string $role): array
    {
        $isAdmin = in_array($role, ['CEO', 'Admin'], true);
        $prefix = $isAdmin ? 'tour.admin' : 'tour.employee';

        return [
            'badge' => I18n::get('tour.badge'),
            'stepOf' => I18n::get('tour.step_of'),
            'back' => I18n::get('tour.back'),
            'next' => I18n::get('tour.next'),
            'finish' => I18n::get('tour.finish'),
            'skip' => I18n::get('tour.skip'),
            'navigateHint' => I18n::get('tour.navigate_hint'),
            'understood' => I18n::get('tour.understood'),
            'restart' => I18n::get('tour.restart'),
            'start' => I18n::get('tour.start'),
            'close' => I18n::get('tour.close'),
            'skipHelpTitle' => I18n::get($prefix . '.skip_help.title'),
            'skipHelpBody' => I18n::get($prefix . '.skip_help.body'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function employeeSteps(): array
    {
        return [
            [
                'id' => 'help-button',
                'tab' => 'calendar',
                'anyTab' => true,
                'target' => '[data-tour="help-button"]',
                'title' => I18n::get('tour.employee.help.title'),
                'body' => I18n::get('tour.employee.help.body'),
                'placement' => 'bottom',
                'startButton' => true,
            ],
            [
                'id' => 'welcome',
                'tab' => 'calendar',
                'center' => true,
                'blur' => true,
                'title' => I18n::get('tour.employee.welcome.title'),
                'body' => I18n::get('tour.employee.welcome.body'),
            ],
            [
                'id' => 'stats',
                'tab' => 'calendar',
                'target' => '[data-tour="vacation-stats"]',
                'title' => I18n::get('tour.employee.stats.title'),
                'body' => I18n::get('tour.employee.stats.body'),
                'placement' => 'bottom',
            ],
            [
                'id' => 'legend',
                'tab' => 'calendar',
                'target' => '[data-tour="calendar-legend"]',
                'title' => I18n::get('tour.employee.legend.title'),
                'body' => I18n::get('tour.employee.legend.body'),
                'placement' => 'bottom',
            ],
            [
                'id' => 'calendar',
                'tab' => 'calendar',
                'target' => '[data-tour="employee-calendar"]',
                'title' => I18n::get('tour.employee.calendar.title'),
                'body' => I18n::get('tour.employee.calendar.body'),
                'placement' => 'top',
            ],
            [
                'id' => 'side-panel',
                'tab' => 'calendar',
                'target' => '[data-tour="calendar-side-panel"]',
                'title' => I18n::get('tour.employee.side_panel.title'),
                'body' => I18n::get('tour.employee.side_panel.body'),
                'placement' => 'left',
            ],
            [
                'id' => 'open-requests',
                'tab' => 'calendar',
                'target' => '[data-tour="open-requests"]',
                'title' => I18n::get('tour.employee.open_requests.title'),
                'body' => I18n::get('tour.employee.open_requests.body'),
                'placement' => 'top',
            ],
            [
                'id' => 'nav-history',
                'tab' => 'calendar',
                'target' => '[data-tour="nav-history"]',
                'title' => I18n::get('tour.employee.nav_history.title'),
                'body' => I18n::get('tour.employee.nav_history.body'),
                'placement' => 'right',
                'navigate' => true,
                'navTab' => 'history',
            ],
            [
                'id' => 'history',
                'tab' => 'history',
                'target' => '[data-tour="history-list"]',
                'title' => I18n::get('tour.employee.history.title'),
                'body' => I18n::get('tour.employee.history.body'),
                'placement' => 'docked',
            ],
            [
                'id' => 'nav-inbox',
                'tab' => 'history',
                'target' => '[data-tour="nav-inbox"]',
                'title' => I18n::get('tour.employee.nav_inbox.title'),
                'body' => I18n::get('tour.employee.nav_inbox.body'),
                'placement' => 'bottom',
                'navigate' => true,
                'navTab' => 'inbox',
            ],
            [
                'id' => 'inbox',
                'tab' => 'inbox',
                'target' => '[data-tour="inbox-content"]',
                'title' => I18n::get('tour.employee.inbox.title'),
                'body' => I18n::get('tour.employee.inbox.body'),
                'placement' => 'docked',
            ],
            [
                'id' => 'finish',
                'tab' => 'inbox',
                'center' => true,
                'blur' => true,
                'title' => I18n::get('tour.employee.finish.title'),
                'body' => I18n::get('tour.employee.finish.body'),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function adminSteps(): array
    {
        return [
            [
                'id' => 'help-button',
                'tab' => 'operations',
                'anyTab' => true,
                'target' => '[data-tour="help-button"]',
                'title' => I18n::get('tour.admin.help.title'),
                'body' => I18n::get('tour.admin.help.body'),
                'placement' => 'bottom',
                'startButton' => true,
            ],
            [
                'id' => 'welcome',
                'tab' => 'operations',
                'center' => true,
                'blur' => true,
                'title' => I18n::get('tour.admin.welcome.title'),
                'body' => I18n::get('tour.admin.welcome.body'),
            ],
            [
                'id' => 'filter',
                'tab' => 'operations',
                'target' => '[data-tour="ceo-employee-filter"]',
                'title' => I18n::get('tour.admin.filter.title'),
                'body' => I18n::get('tour.admin.filter.body'),
                'placement' => 'bottom',
            ],
            [
                'id' => 'calendar',
                'tab' => 'operations',
                'target' => '[data-tour="ceo-calendar"]',
                'title' => I18n::get('tour.admin.calendar.title'),
                'body' => I18n::get('tour.admin.calendar.body'),
                'placement' => 'top',
            ],
            [
                'id' => 'legend',
                'tab' => 'operations',
                'target' => '[data-tour="calendar-legend"]',
                'title' => I18n::get('tour.admin.legend.title'),
                'body' => I18n::get('tour.admin.legend.body'),
                'placement' => 'bottom',
            ],
            [
                'id' => 'side-panel',
                'tab' => 'operations',
                'target' => '[data-tour="admin-side-panel"]',
                'title' => I18n::get('tour.admin.side_panel.title'),
                'body' => I18n::get('tour.admin.side_panel.body'),
                'placement' => 'left',
            ],
            [
                'id' => 'approvals-change',
                'tab' => 'operations',
                'target' => '[data-tour="ceo-section-change"]',
                'title' => I18n::get('tour.admin.approvals_change.title'),
                'body' => I18n::get('tour.admin.approvals_change.body'),
                'placement' => 'top',
            ],
            [
                'id' => 'approvals-vacation',
                'tab' => 'operations',
                'target' => '[data-tour="ceo-section-vacation"]',
                'title' => I18n::get('tour.admin.approvals_vacation.title'),
                'body' => I18n::get('tour.admin.approvals_vacation.body'),
                'placement' => 'top',
            ],
            [
                'id' => 'approvals-storno',
                'tab' => 'operations',
                'target' => '[data-tour="ceo-section-storno"]',
                'title' => I18n::get('tour.admin.approvals_storno.title'),
                'body' => I18n::get('tour.admin.approvals_storno.body'),
                'placement' => 'top',
            ],
            [
                'id' => 'nav-history',
                'tab' => 'operations',
                'target' => '[data-tour="nav-history"]',
                'title' => I18n::get('tour.admin.nav_history.title'),
                'body' => I18n::get('tour.admin.nav_history.body'),
                'placement' => 'right',
                'navigate' => true,
                'navTab' => 'history',
            ],
            [
                'id' => 'history',
                'tab' => 'history',
                'target' => '[data-tour="history-list"]',
                'title' => I18n::get('tour.admin.history.title'),
                'body' => I18n::get('tour.admin.history.body'),
                'placement' => 'docked',
            ],
            [
                'id' => 'nav-team',
                'tab' => 'history',
                'target' => '[data-tour="nav-team"]',
                'title' => I18n::get('tour.admin.nav_team.title'),
                'body' => I18n::get('tour.admin.nav_team.body'),
                'placement' => 'bottom',
                'navigate' => true,
                'navTab' => 'team',
            ],
            [
                'id' => 'team',
                'tab' => 'team',
                'target' => '[data-tour="admin-team"]',
                'title' => I18n::get('tour.admin.team.title'),
                'body' => I18n::get('tour.admin.team.body'),
                'placement' => 'bottom',
            ],
            [
                'id' => 'nav-settings',
                'tab' => 'team',
                'target' => '[data-tour="nav-settings"]',
                'title' => I18n::get('tour.admin.nav_settings.title'),
                'body' => I18n::get('tour.admin.nav_settings.body'),
                'placement' => 'bottom',
                'navigate' => true,
                'navTab' => 'settings',
            ],
            [
                'id' => 'settings',
                'tab' => 'settings',
                'target' => '[data-tour="admin-settings"]',
                'title' => I18n::get('tour.admin.settings.title'),
                'body' => I18n::get('tour.admin.settings.body'),
                'placement' => 'bottom',
            ],
            [
                'id' => 'nav-inbox',
                'tab' => 'settings',
                'target' => '[data-tour="nav-inbox"]',
                'title' => I18n::get('tour.admin.nav_inbox.title'),
                'body' => I18n::get('tour.admin.nav_inbox.body'),
                'placement' => 'bottom',
                'navigate' => true,
                'navTab' => 'inbox',
            ],
            [
                'id' => 'inbox',
                'tab' => 'inbox',
                'target' => '[data-tour="inbox-content"]',
                'title' => I18n::get('tour.admin.inbox.title'),
                'body' => I18n::get('tour.admin.inbox.body'),
                'placement' => 'docked',
            ],
            [
                'id' => 'finish',
                'tab' => 'inbox',
                'center' => true,
                'blur' => true,
                'title' => I18n::get('tour.admin.finish.title'),
                'body' => I18n::get('tour.admin.finish.body'),
            ],
        ];
    }
}
