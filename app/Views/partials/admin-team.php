<?php
use App\Core\I18n;

/** @var list<array<string, mixed>> $employees */
/** @var list<array<string, mixed>> $departments */
/** @var array<string, mixed>|null $selectedTeamUser */
/** @var array<string, int> $selectedTeamUserStats */
/** @var list<array<string, mixed>> $selectedTeamUserRequests */
/** @var array<int, list<array<string, mixed>>> $requestCommentsById */
/** @var array<string, mixed> $currentUser */

$isTeamDetail = isset($_GET['team_view']) && $_GET['team_view'] === 'detail';
$inputClass = 'w-full bg-[#fffdf2] border border-lime-200 rounded-xl px-4 py-3 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400';
$labelClass = 'block text-sm font-semibold text-emerald-800 mb-1.5';
?>
<div class="space-y-8" x-data="{ showCreateModal: <?= isset($_GET['create']) ? 'true' : 'false' ?> }">
    <?php if ($isTeamDetail && $selectedTeamUser): ?>
        <div class="bg-white rounded-3xl border border-lime-100 shadow-xl p-6 sm:p-7">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                <a href="/?tab=team" class="inline-flex items-center text-sm font-bold text-emerald-700 hover:text-emerald-900">← <?= I18n::get('ceo.back_team') ?></a>
                <span class="inline-flex px-3 py-1.5 rounded-full text-xs font-bold border <?= $selectedTeamUser['role'] === 'CEO' ? 'bg-blue-50 text-blue-800 border-blue-200' : 'bg-lime-50 text-emerald-800 border-lime-200' ?>">
                    <?= $selectedTeamUser['role'] === 'CEO' ? 'Admin' : htmlspecialchars($selectedTeamUser['role']) ?>
                </span>
            </div>

            <h2 class="text-2xl font-bold text-emerald-900 tracking-tight"><?= htmlspecialchars($selectedTeamUser['firstname'] . ' ' . $selectedTeamUser['lastname']) ?></h2>
            <p class="text-sm text-emerald-600/80 mt-1 mb-6"><?= htmlspecialchars($selectedTeamUser['email']) ?> · <?= I18n::get('ceo.mnr') ?> <?= htmlspecialchars($selectedTeamUser['mnr']) ?></p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div class="rounded-2xl border border-lime-100 bg-[#fffdf2]/50 p-4">
                    <div class="text-xs font-bold uppercase tracking-[0.15em] text-emerald-500 mb-1"><?= I18n::get('ceo.vacation_total') ?></div>
                    <div class="text-3xl font-bold text-emerald-900 tabular-nums"><?= (int) $selectedTeamUser['vacation_entitlement_days'] ?></div>
                </div>
                <div class="rounded-2xl border border-lime-100 bg-[#fffdf2]/50 p-4">
                    <div class="text-xs font-bold uppercase tracking-[0.15em] text-emerald-500 mb-1"><?= I18n::get('ceo.vacation_used') ?></div>
                    <div class="text-3xl font-bold text-emerald-900 tabular-nums"><?= (int) ($selectedTeamUserStats['approved'] ?? $selectedTeamUserUsedDays) ?></div>
                </div>
                <div class="rounded-2xl border border-[#E8007D]/20 bg-[#fff8fc]/40 p-4">
                    <div class="text-xs font-bold uppercase tracking-[0.15em] text-[#E8007D] mb-1"><?= I18n::get('ceo.vacation_remaining') ?></div>
                    <div class="text-3xl font-bold text-emerald-900 tabular-nums"><?= (int) ($selectedTeamUserStats['remaining'] ?? 0) ?></div>
                </div>
            </div>

            <?php $isOwnAdminAccount = ((int) $selectedTeamUser['id'] === (int) $currentUser['id']) && (($currentUser['role'] ?? '') === 'CEO'); ?>
            <?php if (!$isOwnAdminAccount): ?>
                <form method="POST" action="/?action=delete_employee" onsubmit="return confirm('<?= htmlspecialchars(I18n::get('ceo.delete_confirm'), ENT_QUOTES) ?>');" class="flex justify-end mb-4">
                    <input type="hidden" name="emp_id" value="<?= (int) $selectedTeamUser['id'] ?>">
                    <button type="submit" class="text-sm font-bold text-red-600 hover:text-red-700 border border-red-200 rounded-xl px-4 py-2 hover:bg-red-50 transition-colors"><?= I18n::get('ceo.delete') ?></button>
                </form>
            <?php endif; ?>

            <form method="POST" action="/?action=edit_employee" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="emp_id" value="<?= (int) $selectedTeamUser['id'] ?>">
                <div>
                    <label class="<?= $labelClass ?>" for="team-firstname"><?= I18n::get('ceo.firstname') ?></label>
                    <input id="team-firstname" type="text" name="firstname" value="<?= htmlspecialchars($selectedTeamUser['firstname']) ?>" required class="<?= $inputClass ?>">
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="team-lastname"><?= I18n::get('ceo.lastname') ?></label>
                    <input id="team-lastname" type="text" name="lastname" value="<?= htmlspecialchars($selectedTeamUser['lastname']) ?>" required class="<?= $inputClass ?>">
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="team-email"><?= I18n::get('ceo.email') ?></label>
                    <input id="team-email" type="email" name="email" value="<?= htmlspecialchars($selectedTeamUser['email']) ?>" required class="<?= $inputClass ?>">
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="team-mnr"><?= I18n::get('ceo.mnr') ?></label>
                    <input id="team-mnr" type="text" name="mnr" value="<?= htmlspecialchars($selectedTeamUser['mnr']) ?>" pattern="[A-Za-z]?[0-9]+" required class="<?= $inputClass ?>">
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="team-role"><?= I18n::get('ceo.role') ?></label>
                    <select id="team-role" name="role" class="<?= $inputClass ?>">
                        <option value="Employee" <?= $selectedTeamUser['role'] === 'Employee' ? 'selected' : '' ?>>Mitarbeiter</option>
                        <option value="Admin" <?= $selectedTeamUser['role'] === 'CEO' ? 'selected' : '' ?>>Administrator</option>
                    </select>
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="team-dept"><?= I18n::get('ceo.department') ?></label>
                    <select id="team-dept" name="department_id" class="<?= $inputClass ?>">
                        <option value=""><?= I18n::get('ceo.department_none') ?></option>
                        <?php foreach (($departments ?? []) as $dept): ?>
                            <option value="<?= (int) $dept['id'] ?>" <?= ((string) $selectedTeamUser['department_id'] === (string) $dept['id']) ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="team-vacation"><?= I18n::get('ceo.vacation_days') ?></label>
                    <input id="team-vacation" type="number" min="0" name="vacation_entitlement_days" value="<?= (int) $selectedTeamUser['vacation_entitlement_days'] ?>" class="<?= $inputClass ?>">
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="team-overtime"><?= I18n::get('ceo.overtime_hours') ?></label>
                    <input id="team-overtime" type="number" min="0" step="0.5" name="overtime_hours" value="<?= htmlspecialchars((string) $selectedTeamUser['overtime_hours']) ?>" class="<?= $inputClass ?>">
                </div>
                <div class="md:col-span-2">
                    <label class="<?= $labelClass ?>" for="team-password-field"><?= I18n::get('ceo.new_password') ?></label>
                    <input type="password" name="password" id="team-password-field" autocomplete="new-password" class="<?= $inputClass ?> <?= (isset($_GET['focus']) && $_GET['focus'] === 'password') ? 'ring-2 ring-lime-400 border-lime-400' : '' ?>" placeholder="<?= I18n::get('ceo.new_password_hint') ?>">
                </div>
                <div class="md:col-span-2 flex justify-end pt-2">
                    <button type="submit" class="et-btn-primary px-6 py-3 rounded-xl text-sm font-bold"><?= I18n::get('ceo.save') ?></button>
                </div>
            </form>
        </div>
        <?php if (isset($_GET['focus']) && $_GET['focus'] === 'password'): ?>
        <script>
            document.getElementById('team-password-field')?.focus();
            document.getElementById('team-password-field')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        </script>
        <?php endif; ?>

    <?php else: ?>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-500 mb-2"><?= I18n::get('ceo.team') ?></h2>
                <p class="text-sm text-emerald-600/80 leading-relaxed"><?= I18n::get('ceo.team_hint') ?></p>
            </div>
            <button type="button" @click="showCreateModal = true" class="inline-flex items-center gap-2 et-btn-primary px-5 py-3 rounded-xl text-sm font-bold shadow-lg shadow-lime-400/20">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                <?= I18n::get('ceo.add_user') ?>
            </button>
        </div>

        <div class="bg-white rounded-3xl border border-lime-100 shadow-xl p-6 sm:p-7" x-data="{ teamSearch: '' }">
            <div class="flex flex-col sm:flex-row sm:items-end gap-3 mb-4">
                <div class="flex-1 min-w-0">
                    <label class="block text-[11px] font-bold uppercase tracking-[0.15em] text-emerald-500 mb-1.5" for="team-search"><?= I18n::get('ceo.search_users') ?></label>
                    <input id="team-search" type="search" x-model="teamSearch" placeholder="<?= htmlspecialchars(I18n::get('ceo.search_users_ph')) ?>" class="w-full sm:max-w-md bg-[#fffdf2] border border-lime-200 rounded-xl px-3 py-2 text-sm text-emerald-900 outline-none focus:ring-2 focus:ring-lime-400">
                </div>
            </div>

            <div class="rounded-xl border border-lime-100 overflow-hidden divide-y divide-lime-100">
                <?php foreach (($employees ?? []) as $emp): ?>
                    <?php
                    $searchHay = strtolower($emp['firstname'] . ' ' . $emp['lastname'] . ' ' . $emp['email'] . ' ' . $emp['mnr']);
                    $roleLabel = $emp['role'] === 'CEO' ? 'Admin' : 'Mitarbeiter';
                    $roleClass = $emp['role'] === 'CEO'
                        ? 'bg-blue-50 text-blue-800 border-blue-200'
                        : 'bg-lime-50 text-emerald-800 border-lime-200';
                    ?>
                    <a
                        href="/?tab=team&team_view=detail&team_user=<?= (int) $emp['id'] ?>"
                        x-show="'<?= htmlspecialchars($searchHay, ENT_QUOTES) ?>'.includes(teamSearch.toLowerCase())"
                        class="flex items-center gap-2 sm:gap-3 px-3 py-2 sm:py-2.5 text-sm transition-colors hover:bg-lime-50/80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-lime-400"
                    >
                        <div class="flex-1 min-w-0">
                            <div class="truncate font-semibold text-emerald-900"><?= htmlspecialchars($emp['firstname'] . ' ' . $emp['lastname']) ?></div>
                            <div class="truncate text-[11px] text-emerald-600 mt-0.5"><?= htmlspecialchars($emp['email']) ?> · <?= htmlspecialchars($emp['mnr']) ?></div>
                        </div>
                        <span class="shrink-0 hidden sm:inline-flex max-w-[6.5rem] truncate px-2 py-0.5 rounded-full text-[10px] font-bold border <?= $roleClass ?>"><?= htmlspecialchars($roleLabel) ?></span>
                        <span class="shrink-0 sm:hidden inline-flex px-1.5 py-0.5 rounded-full text-[10px] font-bold border <?= $roleClass ?>"><?= $emp['role'] === 'CEO' ? 'A' : 'M' ?></span>
                        <svg class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div
        x-show="showCreateModal"
        x-cloak
        @keydown.escape.window="showCreateModal = false"
        class="fixed inset-0 z-[120] flex items-center justify-center p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="team-create-title"
    >
        <div class="absolute inset-0 bg-emerald-950/40 backdrop-blur-md" @click="showCreateModal = false"></div>
        <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-white rounded-3xl border border-lime-100 shadow-2xl p-6 sm:p-8">
            <div class="flex items-start justify-between gap-4 mb-6">
                <div>
                    <h3 id="team-create-title" class="text-xl font-bold text-emerald-900"><?= I18n::get('ceo.create_user_title') ?></h3>
                    <p class="text-sm text-emerald-600/80 mt-1"><?= I18n::get('ceo.create_user_hint') ?></p>
                </div>
                <button type="button" @click="showCreateModal = false" class="text-emerald-500 hover:text-emerald-900 font-bold text-xl leading-none" aria-label="<?= I18n::get('ceo.cancel') ?>">×</button>
            </div>

            <form action="/?action=create_employee" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="<?= $labelClass ?>" for="create-firstname"><?= I18n::get('ceo.firstname') ?></label>
                    <input id="create-firstname" type="text" name="firstname" required class="<?= $inputClass ?>">
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="create-lastname"><?= I18n::get('ceo.lastname') ?></label>
                    <input id="create-lastname" type="text" name="lastname" required class="<?= $inputClass ?>">
                </div>
                <div class="sm:col-span-2">
                    <label class="<?= $labelClass ?>" for="create-email"><?= I18n::get('ceo.email') ?></label>
                    <input id="create-email" type="email" name="email" required class="<?= $inputClass ?>">
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="create-mnr"><?= I18n::get('ceo.mnr') ?></label>
                    <input id="create-mnr" type="text" name="mnr" pattern="[A-Za-z]?[0-9]+" required class="<?= $inputClass ?>" placeholder="M011">
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="create-password"><?= I18n::get('ceo.initial_pw') ?></label>
                    <input id="create-password" type="password" name="password" required autocomplete="new-password" class="<?= $inputClass ?>">
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="create-role"><?= I18n::get('ceo.role') ?></label>
                    <select id="create-role" name="role" class="<?= $inputClass ?>">
                        <option value="Employee">Mitarbeiter</option>
                        <option value="Admin">Administrator</option>
                    </select>
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="create-dept"><?= I18n::get('ceo.department') ?></label>
                    <select id="create-dept" name="department_id" class="<?= $inputClass ?>">
                        <option value=""><?= I18n::get('ceo.department_none') ?></option>
                        <?php foreach (($departments ?? []) as $dept): ?>
                            <option value="<?= (int) $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="create-vacation"><?= I18n::get('ceo.vacation_days') ?></label>
                    <input id="create-vacation" type="number" min="0" name="vacation_entitlement_days" value="25" class="<?= $inputClass ?>">
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="create-overtime"><?= I18n::get('ceo.overtime_hours') ?></label>
                    <input id="create-overtime" type="number" min="0" step="0.5" name="overtime_hours" value="0" class="<?= $inputClass ?>">
                </div>
                <div class="sm:col-span-2">
                    <label class="et-checkbox mt-1" for="create-must-change">
                        <input type="checkbox" id="create-must-change" name="must_change_password" value="1" class="et-checkbox__input" checked>
                        <span class="et-checkbox__box" aria-hidden="true"></span>
                        <span><?= I18n::get('ceo.must_change_password') ?></span>
                    </label>
                    <p class="text-xs text-emerald-600/80 mt-2 ml-7"><?= I18n::get('ceo.must_change_password_hint') ?></p>
                </div>
                <div class="sm:col-span-2 flex flex-wrap justify-end gap-3 pt-2">
                    <button type="button" @click="showCreateModal = false" class="et-btn-secondary px-5 py-3 rounded-xl text-sm font-bold"><?= I18n::get('ceo.cancel') ?></button>
                    <button type="submit" class="et-btn-primary px-5 py-3 rounded-xl text-sm font-bold"><?= I18n::get('ceo.register_btn') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
