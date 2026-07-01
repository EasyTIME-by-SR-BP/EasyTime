<?php
use App\Core\I18n;
?>
<style>
.et-time-range { user-select: none; touch-action: none; }
.et-time-range__labels { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.35rem; }
.et-time-range__input {
    width: 3.25rem; padding: 0.1rem 0.2rem; text-align: center; font-size: 11px; font-weight: 600;
    font-variant-numeric: tabular-nums; color: #065f46; background: transparent; border: none;
    border-bottom: 1px solid transparent; outline: none;
}
.et-time-range__input:focus { border-bottom-color: #84cc16; background: #fffdf2; border-radius: 4px; }
.et-time-range__track {
    position: relative; height: 0.75rem; border-radius: 9999px; background: #ecfccb; cursor: pointer;
}
.et-time-range__fill {
    position: absolute; top: 0; bottom: 0; border-radius: 9999px; background: rgba(16, 185, 129, 0.75);
    pointer-events: none;
}
.et-time-range__handle {
    position: absolute; top: 50%; width: 14px; height: 14px; margin-top: -7px; margin-left: -7px;
    border-radius: 9999px; background: #fff; border: 2px solid #059669; box-shadow: 0 1px 3px rgba(0,0,0,.12);
    cursor: ew-resize; z-index: 2;
}
.et-time-range__handle:active { transform: scale(1.1); }
.et-schedule-row { padding: 0.625rem 0; border-bottom: 1px solid #ecfccb; }
.et-schedule-row:last-child { border-bottom: none; }
.et-schedule-total { padding-top: 0.5rem; margin-top: 0.25rem; border-top: 1px solid #ecfccb;
    font-size: 11px; font-weight: 700; color: #065f46; text-align: right; }
</style>
<script>
window.EasyTimeSchedule = (function () {
    const WORK_START = '08:00';
    const DAY_MINUTES = 24 * 60;
    const SNAP = 15;

    const labels = {
        from: <?= json_encode(I18n::get('schedule.from'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
        to: <?= json_encode(I18n::get('schedule.to'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
        total: <?= json_encode(I18n::get('schedule.total_hours'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
        hoursShort: <?= json_encode(I18n::get('schedule.hours_short'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
        noWorkdays: <?= json_encode(I18n::get('schedule.no_workdays'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
    };

    function escapeHtml(text) {
        return String(text ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function formatDayLabel(ymd) {
        if (!ymd || ymd.length < 10) return ymd;
        const parts = ymd.split('-');
        const d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        const wd = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'][d.getDay()];
        return wd + ' ' + parts[2] + '.' + parts[1] + '.';
    }

    function formatHours(minutes) {
        const h = (Number(minutes) || 0) / 60;
        if (Math.abs(h - Math.round(h)) < 0.05) return String(Math.round(h));
        return h.toFixed(1).replace('.', ',');
    }

    function totalMinutes(segments) {
        return (segments || []).reduce((sum, s) => sum + (Number(s.minutes) || 0), 0);
    }

    function timeToMinutes(timeStr) {
        if (!timeStr || !String(timeStr).includes(':')) return 0;
        const [h, m] = String(timeStr).trim().split(':').map(Number);
        return Math.max(0, Math.min(DAY_MINUTES, h * 60 + (m || 0)));
    }

    function minutesToTime(minutes) {
        minutes = Math.max(0, Math.min(DAY_MINUTES, Math.round(minutes)));
        if (minutes >= DAY_MINUTES) return '24:00';
        return String(Math.floor(minutes / 60)).padStart(2, '0') + ':' + String(minutes % 60).padStart(2, '0');
    }

    function snapMinutes(m) {
        return Math.round(m / SNAP) * SNAP;
    }

    function workWindow(dailyMinutes) {
        const start = timeToMinutes(WORK_START);
        const end = Math.min(DAY_MINUTES, start + (dailyMinutes || 480));
        return { start, end };
    }

    function isFullDayWindow(fromM, toM, dailyMinutes) {
        const w = workWindow(dailyMinutes);
        return fromM === w.start && toM === w.end;
    }

    function segmentFromMinutes(fromM, toM, dailyMinutes) {
        const from = minutesToTime(fromM);
        const to = minutesToTime(toM);
        const full_day = isFullDayWindow(fromM, toM, dailyMinutes);
        const minutes = Math.max(0, toM - fromM);
        return { from, to, full_day, minutes };
    }

    function defaultSegment(date, dailyMinutes) {
        const w = workWindow(dailyMinutes);
        const seg = segmentFromMinutes(w.start, w.end, dailyMinutes);
        return { date, ...seg };
    }

    function resolveSegment(seg, dailyMinutes) {
        if (seg.full_day && seg.from && seg.to) {
            const fromM = timeToMinutes(seg.from);
            const toM = timeToMinutes(seg.to);
            return { fromM, toM, ...segmentFromMinutes(fromM, toM, dailyMinutes) };
        }
        if (seg.full_day) {
            const w = workWindow(dailyMinutes);
            return { date: seg.date, ...segmentFromMinutes(w.start, w.end, dailyMinutes) };
        }
        const fromM = timeToMinutes(seg.from || WORK_START);
        const toM = timeToMinutes(seg.to || minutesToTime(workWindow(dailyMinutes).end));
        return segmentFromMinutes(fromM, toM, dailyMinutes);
    }

    function formatTimeLabel(from, to) {
        return escapeHtml(from) + ' – ' + escapeHtml(to);
    }

    function renderSliderHtml(fromM, toM) {
        const left = (fromM / DAY_MINUTES) * 100;
        const width = Math.max(1, ((toM - fromM) / DAY_MINUTES) * 100);
        const from = minutesToTime(fromM);
        const to = minutesToTime(toM);
        return `
            <div class="et-time-range" data-from-m="${fromM}" data-to-m="${toM}">
                <div class="et-time-range__labels">
                    <input type="text" class="et-time-range__input" data-time-from value="${from}" inputmode="numeric" maxlength="5" aria-label="${escapeHtml(labels.from)}">
                    <input type="text" class="et-time-range__input" data-time-to value="${to}" inputmode="numeric" maxlength="5" aria-label="${escapeHtml(labels.to)}">
                </div>
                <div class="et-time-range__track" data-time-track>
                    <div class="et-time-range__fill" style="left:${left}%;width:${width}%"></div>
                    <div class="et-time-range__handle" data-handle="from" style="left:${left}%"></div>
                    <div class="et-time-range__handle" data-handle="to" style="left:${left + width}%"></div>
                </div>
            </div>`;
    }

    function updateSliderVisual(rangeEl, fromM, toM) {
        fromM = snapMinutes(Math.max(0, Math.min(toM - SNAP, fromM)));
        toM = snapMinutes(Math.min(DAY_MINUTES, Math.max(fromM + SNAP, toM)));
        rangeEl.dataset.fromM = String(fromM);
        rangeEl.dataset.toM = String(toM);
        const left = (fromM / DAY_MINUTES) * 100;
        const width = ((toM - fromM) / DAY_MINUTES) * 100;
        rangeEl.querySelector('[data-time-from]').value = minutesToTime(fromM);
        rangeEl.querySelector('[data-time-to]').value = minutesToTime(toM);
        rangeEl.querySelector('.et-time-range__fill').style.left = left + '%';
        rangeEl.querySelector('.et-time-range__fill').style.width = width + '%';
        rangeEl.querySelector('[data-handle="from"]').style.left = left + '%';
        rangeEl.querySelector('[data-handle="to"]').style.left = (left + width) + '%';
        return { fromM, toM };
    }

    function parseTimeInput(raw) {
        const s = String(raw || '').trim();
        if (!s) return null;
        if (/^\d{1,2}:\d{2}$/.test(s)) return snapMinutes(timeToMinutes(s));
        if (/^\d{1,2}$/.test(s)) return snapMinutes(Number(s) * 60);
        if (/^\d{3,4}$/.test(s)) {
            const padded = s.padStart(4, '0');
            return snapMinutes(timeToMinutes(padded.slice(0, 2) + ':' + padded.slice(2)));
        }
        return null;
    }

    function wireSlider(rangeEl, dailyMinutes, onChange) {
        const track = rangeEl.querySelector('[data-time-track]');
        const fromInput = rangeEl.querySelector('[data-time-from]');
        const toInput = rangeEl.querySelector('[data-time-to]');
        let drag = null;

        function notify() {
            if (typeof onChange === 'function') onChange();
        }

        function posToMinutes(clientX) {
            const rect = track.getBoundingClientRect();
            const ratio = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width));
            return snapMinutes(ratio * DAY_MINUTES);
        }

        function apply(fromM, toM) {
            updateSliderVisual(rangeEl, fromM, toM);
            notify();
        }

        function onPointerMove(e) {
            if (!drag) return;
            const m = posToMinutes(e.clientX);
            let fromM = Number(rangeEl.dataset.fromM);
            let toM = Number(rangeEl.dataset.toM);
            if (drag === 'from') apply(Math.min(m, toM - SNAP), toM);
            else if (drag === 'to') apply(fromM, Math.max(m, fromM + SNAP));
            else {
                const half = (toM - fromM) / 2;
                let nf = snapMinutes(m - half);
                let nt = nf + (toM - fromM);
                if (nf < 0) { nf = 0; nt = toM - fromM; }
                if (nt > DAY_MINUTES) { nt = DAY_MINUTES; nf = nt - (toM - fromM); }
                apply(nf, nt);
            }
        }

        function onPointerUp() {
            drag = null;
            document.removeEventListener('pointermove', onPointerMove);
            document.removeEventListener('pointerup', onPointerUp);
        }

        rangeEl.querySelectorAll('[data-handle]').forEach((handle) => {
            handle.addEventListener('pointerdown', (e) => {
                e.preventDefault();
                drag = handle.dataset.handle;
                document.addEventListener('pointermove', onPointerMove);
                document.addEventListener('pointerup', onPointerUp);
            });
        });

        track.addEventListener('pointerdown', (e) => {
            if (e.target.closest('[data-handle]')) return;
            e.preventDefault();
            drag = 'move';
            document.addEventListener('pointermove', onPointerMove);
            document.addEventListener('pointerup', onPointerUp);
        });

        fromInput.addEventListener('change', () => {
            const m = parseTimeInput(fromInput.value);
            if (m === null) { fromInput.value = minutesToTime(Number(rangeEl.dataset.fromM)); return; }
            apply(m, Number(rangeEl.dataset.toM));
        });
        toInput.addEventListener('change', () => {
            const m = parseTimeInput(toInput.value);
            if (m === null) { toInput.value = minutesToTime(Number(rangeEl.dataset.toM)); return; }
            apply(Number(rangeEl.dataset.fromM), m);
        });
    }

    function renderEditor(container, segments, dailyMinutes, onChange) {
        if (!container) return;
        if (!segments || segments.length === 0) {
            container.innerHTML = `<p class="text-xs text-emerald-600/80 py-1">${escapeHtml(labels.noWorkdays)}</p>`;
            return;
        }
        let html = `<div class="easytime-schedule-editor space-y-0" data-daily-minutes="${dailyMinutes || 480}">`;
        segments.forEach((seg) => {
            const resolved = resolveSegment(seg, dailyMinutes);
            const fromM = timeToMinutes(resolved.from);
            const toM = timeToMinutes(resolved.to);
            html += `
                <div class="et-schedule-row" data-schedule-row data-date="${escapeHtml(seg.date)}">
                    <div class="flex items-center justify-between gap-2 mb-1.5">
                        <span class="text-xs font-bold text-emerald-900">${escapeHtml(formatDayLabel(seg.date))}</span>
                        <span class="text-[11px] text-emerald-600 tabular-nums" data-row-hours>${formatHours(resolved.minutes)} ${labels.hoursShort}</span>
                    </div>
                    ${renderSliderHtml(fromM, toM)}
                </div>`;
        });
        html += `<div class="et-schedule-total" data-schedule-total>${escapeHtml(labels.total)}: ${formatHours(totalMinutes(segments))} ${labels.hoursShort}</div></div>`;
        container.innerHTML = html;

        const notify = () => {
            updateEditorTotals(container, dailyMinutes);
            if (typeof onChange === 'function') onChange(readEditor(container));
        };

        container.querySelectorAll('.et-time-range').forEach((rangeEl) => {
            wireSlider(rangeEl, dailyMinutes, notify);
        });
    }

    function updateEditorTotals(container, dailyMinutes) {
        const segs = readEditor(container);
        const totalEl = container.querySelector('[data-schedule-total]');
        if (totalEl) totalEl.textContent = labels.total + ': ' + formatHours(totalMinutes(segs)) + ' ' + labels.hoursShort;
        container.querySelectorAll('[data-schedule-row]').forEach((row, i) => {
            const hoursEl = row.querySelector('[data-row-hours]');
            if (hoursEl && segs[i]) hoursEl.textContent = formatHours(segs[i].minutes) + ' ' + labels.hoursShort;
        });
    }

    function readEditor(container) {
        if (!container) return [];
        const daily = Number(container.querySelector('.easytime-schedule-editor')?.dataset.dailyMinutes || 480);
        const out = [];
        container.querySelectorAll('[data-schedule-row]').forEach((row) => {
            const date = row.dataset.date;
            const rangeEl = row.querySelector('.et-time-range');
            if (!date || !rangeEl) return;
            const fromM = Number(rangeEl.dataset.fromM);
            const toM = Number(rangeEl.dataset.toM);
            const seg = segmentFromMinutes(fromM, toM, daily);
            if (seg.minutes > 0) out.push({ date, ...seg });
        });
        return out;
    }

    function renderTimelineReadonly(container, segments, dailyMinutes) {
        if (!container) return;
        const daily = dailyMinutes || 480;
        if (!segments || segments.length === 0) {
            container.innerHTML = `<p class="text-xs text-emerald-600/80 text-center py-2">${escapeHtml(labels.noWorkdays)}</p>`;
            return;
        }
        const total = totalMinutes(segments);
        let html = `<div class="space-y-0">`;
        segments.forEach((seg) => {
            const resolved = resolveSegment(seg, daily);
            const fromM = timeToMinutes(resolved.from);
            const toM = timeToMinutes(resolved.to);
            const left = (fromM / DAY_MINUTES) * 100;
            const width = ((toM - fromM) / DAY_MINUTES) * 100;
            html += `
                <div class="et-schedule-row">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <span class="text-xs font-semibold text-emerald-900">${escapeHtml(formatDayLabel(seg.date))}</span>
                        <span class="text-[11px] text-emerald-700 tabular-nums">${formatTimeLabel(resolved.from, resolved.to)} · ${formatHours(resolved.minutes)} ${labels.hoursShort}</span>
                    </div>
                    <div class="et-time-range__track"><div class="et-time-range__fill" style="left:${left}%;width:${width}%"></div></div>
                </div>`;
        });
        html += `<div class="et-schedule-total">${escapeHtml(labels.total)}: ${formatHours(total)} ${labels.hoursShort}</div></div>`;
        container.innerHTML = html;
    }

    function fetchPreview(userId, start, end) {
        const params = new URLSearchParams({ action: 'schedule_preview', start, end });
        if (userId) params.set('user_id', String(userId));
        return fetch('/?' + params.toString(), { headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .catch(() => ({ segments: [], daily_minutes: 480 }));
    }

    function syncHiddenFields(segments, partialInput, jsonInput) {
        const allFull = segments.length > 0 && segments.every((s) => s.full_day);
        if (partialInput) partialInput.value = allFull ? '0' : '1';
        if (jsonInput) jsonInput.value = JSON.stringify(allFull ? [] : segments);
    }

    function bindBookingForm(config) {
        const { formId, partialInputId, jsonInputId, wrapId, editorContainerId, getUserId, getStart, getEnd } = config;
        const form = document.getElementById(formId);
        const partialInput = document.getElementById(partialInputId);
        const jsonInput = document.getElementById(jsonInputId);
        const wrapEl = wrapId ? document.getElementById(wrapId) : null;
        const editorContainer = document.getElementById(editorContainerId);
        if (!form || !partialInput || !jsonInput) return;

        let currentSegments = [];
        let dailyMinutes = 480;

        function syncHidden() {
            if (editorContainer) currentSegments = readEditor(editorContainer);
            syncHiddenFields(currentSegments, partialInput, jsonInput);
        }

        function renderSurface() {
            if (!editorContainer) return;
            renderEditor(editorContainer, currentSegments, dailyMinutes, (arg) => {
                currentSegments = arg;
                syncHidden();
            });
        }

        function hideBookingSchedule() {
            currentSegments = [];
            if (editorContainer) editorContainer.innerHTML = '';
            if (wrapEl) wrapEl.classList.add('hidden');
            syncHidden();
        }

        function reloadEditor() {
            const start = getStart();
            const end = getEnd();
            if (!start || !end || typeof compareYmd === 'function' && compareYmd(start, end) > 0) {
                hideBookingSchedule();
                return;
            }
            if (wrapEl) wrapEl.classList.remove('hidden');
            fetchPreview(getUserId ? getUserId() : null, start, end).then((data) => {
                currentSegments = data.segments || [];
                dailyMinutes = data.daily_minutes || 480;
                renderSurface();
                syncHidden();
            });
        }

        form.addEventListener('submit', syncHidden);
        return { reloadEditor, syncHidden, hide: hideBookingSchedule };
    }

    function getFormDateInputs(form) {
        return {
            start: form.querySelector('[name="approved_start_date"], [name="start_date"], [name="new_start_date"]'),
            end: form.querySelector('[name="approved_end_date"], [name="end_date"], [name="new_end_date"]'),
        };
    }

    function mountFormSchedule(form, options = {}) {
        if (!form || form.dataset.scheduleMounted === '1') return form._easytimeSchedule || null;
        form.dataset.scheduleMounted = '1';

        let partialInput = form.querySelector('[name="partial_schedule"]');
        let jsonInput = form.querySelector('[name="schedule_json"]');
        if (!partialInput) {
            partialInput = document.createElement('input');
            partialInput.type = 'hidden';
            partialInput.name = 'partial_schedule';
            partialInput.value = '0';
            form.appendChild(partialInput);
        }
        if (!jsonInput) {
            jsonInput = document.createElement('input');
            jsonInput.type = 'hidden';
            jsonInput.name = 'schedule_json';
            jsonInput.value = '[]';
            form.appendChild(jsonInput);
        }

        const mount = form.querySelector('.easytime-form-schedule-mount');
        let editorContainer = form.querySelector('.easytime-form-schedule-editor');
        if (!editorContainer) {
            editorContainer = document.createElement('div');
            editorContainer.className = 'easytime-form-schedule-editor mt-3 max-h-48 overflow-y-auto';
            const dates = getFormDateInputs(form);
            const anchor = dates.end?.closest('.grid') || dates.end?.parentElement || form.firstElementChild;
            if (anchor?.nextSibling) anchor.parentNode.insertBefore(editorContainer, anchor.nextSibling);
            else form.insertBefore(editorContainer, form.querySelector('button[type="submit"]'));
        }

        const userId = options.userId || mount?.dataset.scheduleUserId || null;
        if (!options.initialSegments?.length && mount?.dataset.initialSchedule) {
            try {
                options.initialSegments = JSON.parse(decodeURIComponent(mount.dataset.initialSchedule));
            } catch (e) {
                options.initialSegments = [];
            }
        }
        if (!options.initialStart && mount?.dataset.initialStart) {
            options.initialStart = mount.dataset.initialStart;
        }
        if (!options.initialEnd && mount?.dataset.initialEnd) {
            options.initialEnd = mount.dataset.initialEnd;
        }

        let currentSegments = options.initialSegments || [];
        let dailyMinutes = 480;

        function syncHidden() {
            if (editorContainer.innerHTML) currentSegments = readEditor(editorContainer);
            syncHiddenFields(currentSegments, partialInput, jsonInput);
        }

        function renderSurface() {
            renderEditor(editorContainer, currentSegments, dailyMinutes, (arg) => {
                currentSegments = arg;
                syncHidden();
            });
        }

        function reloadEditor() {
            const inputs = getFormDateInputs(form);
            const start = inputs.start?.value || '';
            const end = inputs.end?.value || '';
            if (!start || !end || (typeof compareYmd === 'function' && compareYmd(start, end) > 0)) {
                editorContainer.innerHTML = '';
                currentSegments = [];
                syncHidden();
                return;
            }
            const uid = userId || null;
            fetchPreview(uid, start, end).then((data) => {
                dailyMinutes = data.daily_minutes || 480;
                const fresh = data.segments || [];
                if (options.initialSegments?.length && start === options.initialStart && end === options.initialEnd) {
                    currentSegments = options.initialSegments;
                } else {
                    currentSegments = fresh;
                }
                options.initialSegments = null;
                renderSurface();
                syncHidden();
            });
        }

        const inputs = getFormDateInputs(form);
        const onDateChange = () => reloadEditor();
        inputs.start?.addEventListener('change', onDateChange);
        inputs.end?.addEventListener('change', onDateChange);
        form.addEventListener('submit', syncHidden);

        const api = { reloadEditor, syncHidden };
        form._easytimeSchedule = api;
        reloadEditor();
        return api;
    }

    function wireFormSchedules(root) {
        (root || document).querySelectorAll('form.admin-dates-form, form.employee-dates-form').forEach((form) => {
            if (form.dataset.scheduleMounted === '1') return;
            const mount = form.querySelector('.easytime-form-schedule-mount');
            if (!mount) return;
            let initial = [];
            try {
                initial = JSON.parse(decodeURIComponent(mount.dataset.initialSchedule || '%5B%5D'));
            } catch (e) {
                initial = [];
            }
            mountFormSchedule(form, {
                userId: mount.dataset.scheduleUserId,
                initialSegments: initial,
                initialStart: mount.dataset.initialStart || '',
                initialEnd: mount.dataset.initialEnd || '',
            });
        });
    }

    function hideEventSchedule(wrapId, bodyId) {
        const wrap = document.getElementById(wrapId);
        const body = bodyId ? document.getElementById(bodyId) : null;
        if (body) body.innerHTML = '';
        if (wrap) wrap.classList.add('hidden');
    }

    function showEventSchedule(wrapId, bodyId, request) {
        const wrap = document.getElementById(wrapId);
        const body = document.getElementById(bodyId);
        if (!wrap || !body) return;
        const segments = request?.schedule || [];
        if (!segments.length) {
            hideEventSchedule(wrapId, bodyId);
            return;
        }
        const daily = request?.minuten_abwesend && request?.net_days
            ? Math.round((request.minuten_abwesend / Math.max(1, request.net_days)))
            : 480;
        renderTimelineReadonly(body, segments, daily);
        wrap.classList.remove('hidden');
    }

    function buildFormScheduleMountHtml(request) {
        const scheduleEnc = encodeURIComponent(JSON.stringify(request?.schedule || []));
        return `<div class="easytime-form-schedule-mount hidden" data-schedule-user-id="${Number(request.user_id || 0)}"
            data-initial-start="${escapeHtml(request.start_date || '')}" data-initial-end="${escapeHtml(request.end_date || '')}"
            data-initial-schedule="${scheduleEnc}"></div>`;
    }

    return {
        labels, formatDayLabel, formatHours, totalMinutes, renderTimelineReadonly, renderEditor, readEditor,
        fetchPreview, bindBookingForm, mountFormSchedule, wireFormSchedules, showEventSchedule, hideEventSchedule,
        buildFormScheduleMountHtml,
    };
})();
</script>
