<?php
use App\Core\I18n;

$etToastMessages = [
    'success' => [
        'action_success' => ['text' => I18n::get('msg.action_success'), 'duration' => 4000],
        'employee_created' => ['text' => I18n::get('msg.employee_created'), 'duration' => 4500],
        'created' => ['text' => I18n::get('msg.request_created'), 'duration' => 4000],
        'decided' => ['text' => I18n::get('msg.decided'), 'duration' => 4000],
        'password_reset_requested' => ['text' => I18n::get('msg.password_reset_requested'), 'duration' => 5000],
    ],
    'error' => [
        'invalid_mnr' => ['text' => I18n::get('msg.invalid_mnr'), 'duration' => 6000],
        'blocked_period' => ['text' => I18n::get('msg.blocked_period'), 'duration' => 6000],
        'request_conflict' => ['text' => I18n::get('msg.request_conflict'), 'duration' => 6000],
        'blocked_exists' => ['text' => I18n::get('msg.blocked_exists'), 'duration' => 6000],
        'past_date' => ['text' => I18n::get('msg.past_date'), 'duration' => 6000],
        'coverage_conflict' => ['text' => I18n::get('msg.coverage_conflict'), 'duration' => 6000],
        'coverage_request_denied' => ['text' => I18n::get('msg.coverage_request_denied'), 'duration' => 7000],
        'fenstertage_exceeded' => ['text' => I18n::get('msg.fenstertage_exceeded'), 'duration' => 6000],
        'insufficient_balance' => ['text' => I18n::get('msg.insufficient_balance'), 'duration' => 6000],
        'self_delete_forbidden' => ['text' => I18n::get('msg.self_delete_forbidden'), 'duration' => 6000],
        'invalid_request' => ['text' => I18n::get('msg.invalid_request'), 'duration' => 6000],
        'select_employee' => ['text' => I18n::get('ceo.validation_select_employee'), 'duration' => 6000],
        'select_range' => ['text' => I18n::get('ceo.validation_select_range'), 'duration' => 6000],
        'invalid_range' => ['text' => I18n::get('ceo.validation_invalid_range'), 'duration' => 6000],
        'login_failed' => ['text' => I18n::get('login.invalid_credentials'), 'duration' => 6000],
        'invalid_token' => ['text' => I18n::get('msg.invalid_token'), 'duration' => 6000],
        'employee_failed' => ['text' => I18n::get('msg.employee_failed'), 'duration' => 6000],
        'settings_pool_failed' => ['text' => I18n::get('settings.pool_failed'), 'duration' => 6000],
        'settings_pool_in_use' => ['text' => I18n::get('settings.pool_in_use'), 'duration' => 6000],
        '_default' => ['text' => I18n::get('msg.generic_error'), 'duration' => 6000],
    ],
];
?>
<div id="et-toast-host" class="fixed bottom-6 left-1/2 z-[200] flex w-full max-w-md -translate-x-1/2 flex-col items-center gap-3 px-4 pointer-events-none" aria-live="polite" aria-atomic="true"></div>
<style>
    .et-toast {
        pointer-events: auto;
        width: 100%;
        border-radius: 1rem;
        border-width: 2px;
        padding: 0.875rem 1rem;
        box-shadow: 0 20px 40px rgba(6, 78, 59, 0.12);
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        opacity: 0;
        transform: translateY(0.5rem);
        transition: opacity 0.28s ease, transform 0.28s ease;
    }
    .et-toast.is-visible {
        opacity: 1;
        transform: translateY(0);
    }
    .et-toast.is-leaving {
        opacity: 0;
        transform: translateY(0.35rem);
    }
</style>
<script>
    window.ET_TOAST_MESSAGES = <?= json_encode($etToastMessages, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    window.showEasyTimeToast = function showEasyTimeToast(code, type, messageOverride) {
        const host = document.getElementById('et-toast-host');
        if (!host || (!code && !messageOverride)) return;

        const palette = {
            success: { border: '#16A34A', bg: '#f0fdf4', text: '#14532d', code: '#15803d' },
            error: { border: '#DC2626', bg: '#fef2f2', text: '#7f1d1d', code: '#b91c1c' },
        };
        const colors = palette[type] || palette.error;
        const bucket = (window.ET_TOAST_MESSAGES && window.ET_TOAST_MESSAGES[type]) || {};
        const cfg = bucket[code] || bucket._default || { text: code, duration: type === 'success' ? 4000 : 6000 };
        const duration = cfg.duration || (type === 'success' ? 4000 : 6000);
        const displayText = messageOverride || cfg.text || code;
        const displayCode = code || (type === 'success' ? 'success' : 'error');

        const el = document.createElement('div');
        el.className = 'et-toast';
        el.style.borderColor = colors.border;
        el.style.backgroundColor = colors.bg;
        el.style.color = colors.text;

        const textEl = document.createElement('div');
        textEl.className = 'flex-1 min-w-0';
        const messageEl = document.createElement('div');
        messageEl.className = 'text-sm font-medium leading-snug';
        messageEl.textContent = displayText;
        const codeEl = document.createElement('div');
        codeEl.className = 'mt-1 text-[11px] font-bold uppercase tracking-[0.16em] opacity-80';
        codeEl.style.color = colors.code;
        codeEl.textContent = displayCode;
        textEl.appendChild(messageEl);
        textEl.appendChild(codeEl);

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'shrink-0 text-lg leading-none opacity-60 hover:opacity-100';
        closeBtn.setAttribute('aria-label', 'Close');
        closeBtn.textContent = '×';

        el.appendChild(textEl);
        el.appendChild(closeBtn);
        host.appendChild(el);

        let hideTimer;
        const removeToast = function() {
            clearTimeout(hideTimer);
            el.classList.remove('is-visible');
            el.classList.add('is-leaving');
            window.setTimeout(function() { el.remove(); }, 280);
        };

        closeBtn.addEventListener('click', removeToast);
        window.requestAnimationFrame(function() {
            el.classList.add('is-visible');
        });
        hideTimer = window.setTimeout(removeToast, duration);
    };

    window.showEasyTimeToastMessage = function showEasyTimeToastMessage(message, type, code) {
        showEasyTimeToast(code || '_default', type || 'error', message);
    };

    window.bootstrapEasyTimeToastsFromUrl = function bootstrapEasyTimeToastsFromUrl() {
        const params = new URLSearchParams(window.location.search);
        const error = params.get('error');
        const success = params.get('success');
        let changed = false;

        if (error) {
            showEasyTimeToast(error, 'error');
            params.delete('error');
            changed = true;
        }
        if (success) {
            const type = success === 'employee_failed' ? 'error' : 'success';
            showEasyTimeToast(success, type);
            params.delete('success');
            changed = true;
        }

        if (changed) {
            const query = params.toString();
            const nextUrl = window.location.pathname + (query ? '?' + query : '') + window.location.hash;
            window.history.replaceState({}, '', nextUrl);
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        bootstrapEasyTimeToastsFromUrl();
    });
</script>
