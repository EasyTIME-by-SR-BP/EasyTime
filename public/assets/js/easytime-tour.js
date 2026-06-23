(function () {
    'use strict';

    const STORAGE_KEY = 'et_tour_state';
    const TOUR_DONE_VERSION = 2;
    const PADDING = 10;
    const RING_PADDING = 4;
    const POPOVER_AVOID_EXTRA = 28;

    let runtime = null;
    let rootEl = null;
    let shadeTop = null;
    let shadeLeft = null;
    let shadeRight = null;
    let shadeBottom = null;
    let ringEl = null;
    let popoverEl = null;
    let resizeObserver = null;
    let observedTarget = null;
    let submitBlocker = null;
    let navigateClickHandler = null;
    let skipExitMode = false;
    let lastPopoverPlacement = 'bottom';

    function doneStorageKey() {
        const userId = runtime?.userId || 0;
        return 'et_tour_v' + TOUR_DONE_VERSION + '_' + runtime.tourId + '_done_' + userId;
    }

    function formatStepOf(template, current, total) {
        return String(template)
            .replace('%d', String(current))
            .replace('%d', String(total));
    }

    function readState() {
        try {
            const raw = sessionStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function writeState(state) {
        if (!state) {
            sessionStorage.removeItem(STORAGE_KEY);
            return;
        }
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    }

    function findTourTarget(selector) {
        if (!selector) return null;
        const nodes = document.querySelectorAll(selector);
        for (let i = 0; i < nodes.length; i++) {
            const el = nodes[i];
            const rect = el.getBoundingClientRect();
            if (rect.width > 0 && rect.height > 0 && rect.bottom > 0 && rect.right > 0) {
                return el;
            }
        }
        return nodes[0] || null;
    }

    function waitForElement(selector, timeoutMs) {
        const deadline = Date.now() + (timeoutMs || 8000);
        return new Promise(function (resolve) {
            function tick() {
                const el = selector ? findTourTarget(selector) : document.body;
                if (el && (!selector || el.getClientRects().length > 0 || el === document.body)) {
                    resolve(el);
                    return;
                }
                if (Date.now() >= deadline) {
                    resolve(el || null);
                    return;
                }
                requestAnimationFrame(tick);
            }
            tick();
        });
    }

    function unbindNavigateLinks() {
        if (navigateClickHandler) {
            document.removeEventListener('click', navigateClickHandler, true);
            navigateClickHandler = null;
        }
    }

    function bindNavigateLinks(step, index) {
        unbindNavigateLinks();
        if (!step?.navigate || !step.navTab) return;

        navigateClickHandler = function (e) {
            const link = e.target.closest('a[href]');
            if (!link) return;
            let tab = null;
            try {
                tab = new URL(link.href, window.location.origin).searchParams.get('tab');
            } catch (err) {
                return;
            }
            if (tab !== step.navTab) return;
            writeState({ active: true, tourId: runtime.tourId, stepIndex: index + 1 });
        };
        document.addEventListener('click', navigateClickHandler, true);
    }

    function ensureDom() {
        if (rootEl) return;

        rootEl = document.createElement('div');
        rootEl.id = 'et-tour-root';
        rootEl.className = 'et-tour-root';
        rootEl.setAttribute('role', 'dialog');
        rootEl.setAttribute('aria-modal', 'true');
        rootEl.innerHTML = [
            '<div class="et-tour-shade et-tour-shade--top" data-shade="top"></div>',
            '<div class="et-tour-shade et-tour-shade--left" data-shade="left"></div>',
            '<div class="et-tour-shade et-tour-shade--right" data-shade="right"></div>',
            '<div class="et-tour-shade et-tour-shade--bottom" data-shade="bottom"></div>',
            '<div class="et-tour-ring" aria-hidden="true"></div>',
            '<div class="et-tour-popover" role="document">',
            '  <div class="et-tour-popover__glow" aria-hidden="true"></div>',
            '  <div class="et-tour-popover__inner">',
            '    <div class="et-tour-popover__header">',
            '      <span class="et-tour-popover__badge"></span>',
            '      <span class="et-tour-popover__progress-text"></span>',
            '    </div>',
            '    <div class="et-tour-popover__progress"><div class="et-tour-popover__progress-bar"></div></div>',
            '    <h2 class="et-tour-popover__title"></h2>',
            '    <p class="et-tour-popover__body"></p>',
            '    <p class="et-tour-popover__nav-hint hidden"></p>',
            '    <div class="et-tour-popover__actions">',
            '      <button type="button" class="et-tour-btn et-tour-btn--ghost" data-action="skip"></button>',
            '      <div class="et-tour-popover__actions-main">',
            '        <button type="button" class="et-tour-btn et-tour-btn--secondary" data-action="back"></button>',
            '        <button type="button" class="et-tour-btn et-tour-btn--primary" data-action="next"></button>',
            '      </div>',
            '    </div>',
            '  </div>',
            '</div>',
        ].join('');
        document.body.appendChild(rootEl);

        shadeTop = rootEl.querySelector('[data-shade="top"]');
        shadeLeft = rootEl.querySelector('[data-shade="left"]');
        shadeRight = rootEl.querySelector('[data-shade="right"]');
        shadeBottom = rootEl.querySelector('[data-shade="bottom"]');
        ringEl = rootEl.querySelector('.et-tour-ring');
        popoverEl = rootEl.querySelector('.et-tour-popover');

        rootEl.querySelector('.et-tour-popover__badge').textContent = runtime.labels.badge;
        rootEl.querySelector('[data-action="skip"]').textContent = runtime.labels.skip;
        rootEl.querySelector('[data-action="back"]').textContent = runtime.labels.back;
        rootEl.querySelector('[data-action="next"]').textContent = runtime.labels.next;

        rootEl.querySelector('[data-action="skip"]').addEventListener('click', skipTour);
        rootEl.querySelector('[data-action="back"]').addEventListener('click', goBack);
        rootEl.querySelector('[data-action="next"]').addEventListener('click', goNext);

        document.addEventListener('keydown', onKeydown);
    }

    function onKeydown(e) {
        if (!runtime?.state?.active) return;
        if (e.key === 'Escape') {
            e.preventDefault();
            skipTour();
        } else if (e.key === 'ArrowRight' && !skipExitMode && !currentStep()?.navigate) {
            e.preventDefault();
            goNext();
        } else if (e.key === 'ArrowLeft' && !skipExitMode) {
            e.preventDefault();
            goBack();
        }
    }

    function currentStep() {
        if (!runtime) return null;
        return runtime.steps[runtime.state.stepIndex] || null;
    }

    function setBodyTourClass(active) {
        document.body.classList.toggle('et-tour-active', !!active);
    }

    function attachSubmitBlocker() {
        if (submitBlocker) return;
        submitBlocker = function (e) {
            if (runtime?.state?.active && !skipExitMode) {
                e.preventDefault();
                e.stopPropagation();
            }
        };
        document.addEventListener('submit', submitBlocker, true);
    }

    function detachSubmitBlocker() {
        if (!submitBlocker) return;
        document.removeEventListener('submit', submitBlocker, true);
        submitBlocker = null;
    }

    function clearSpotlight() {
        unbindNavigateLinks();
        [shadeTop, shadeLeft, shadeRight, shadeBottom].forEach(function (el) {
            if (el) el.style.cssText = '';
        });
        if (ringEl) ringEl.style.cssText = 'display:none;';
        if (popoverEl) popoverEl.style.cssText = '';
        if (resizeObserver && observedTarget) {
            resizeObserver.unobserve(observedTarget);
        }
        observedTarget = null;
        document.querySelectorAll('.et-tour-highlight-target').forEach(function (el) {
            el.classList.remove('et-tour-highlight-target');
        });
    }

    function setBackdropMode(step) {
        if (!rootEl) return;
        rootEl.classList.toggle('et-tour-root--blur', !!(step && step.blur));
    }

    function layoutSpotlight(target, placement, center) {
        if (!rootEl) return;

        document.querySelectorAll('.et-tour-highlight-target').forEach(function (el) {
            el.classList.remove('et-tour-highlight-target');
        });

        if (center || !target) {
            setBackdropMode(currentStep());
            [shadeTop, shadeLeft, shadeRight, shadeBottom].forEach(function (el) {
                el.style.top = '0';
                el.style.left = '0';
                el.style.width = '100%';
                el.style.height = '100%';
            });
            ringEl.style.display = 'none';
            layoutPopover(null, 'center');
            return;
        }

        setBackdropMode(null);
        target.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
        target.classList.add('et-tour-highlight-target');

        const rect = target.getBoundingClientRect();
        const x = Math.max(0, rect.left - PADDING);
        const y = Math.max(0, rect.top - PADDING);
        const w = Math.min(window.innerWidth - x, rect.width + PADDING * 2);
        const h = Math.min(window.innerHeight - y, rect.height + PADDING * 2);

        shadeTop.style.cssText = 'top:0;left:0;width:100%;height:' + y + 'px;';
        shadeLeft.style.cssText = 'top:' + y + 'px;left:0;width:' + x + 'px;height:' + h + 'px;';
        shadeRight.style.cssText = 'top:' + y + 'px;left:' + (x + w) + 'px;width:' + Math.max(0, window.innerWidth - x - w) + 'px;height:' + h + 'px;';
        shadeBottom.style.cssText = 'top:' + (y + h) + 'px;left:0;width:100%;height:' + Math.max(0, window.innerHeight - y - h) + 'px;';

        ringEl.style.display = 'block';
        ringEl.style.top = (y - RING_PADDING) + 'px';
        ringEl.style.left = (x - RING_PADDING) + 'px';
        ringEl.style.width = (w + RING_PADDING * 2) + 'px';
        ringEl.style.height = (h + RING_PADDING * 2) + 'px';

        layoutPopover({ x, y, w, h }, placement || 'bottom');

        if (typeof ResizeObserver !== 'undefined') {
            if (!resizeObserver) {
                resizeObserver = new ResizeObserver(function () {
                    if (observedTarget && runtime?.state?.active) {
                        const step = currentStep();
                        layoutSpotlight(observedTarget, step?.placement, false);
                    }
                });
            }
            if (observedTarget !== target) {
                if (observedTarget) resizeObserver.unobserve(observedTarget);
                observedTarget = target;
                resizeObserver.observe(target);
            }
        }
    }

    function resetPopoverPositionStyles() {
        if (!popoverEl) return;
        popoverEl.classList.remove('et-tour-popover--docked');
        popoverEl.style.transform = '';
        popoverEl.style.bottom = 'auto';
        popoverEl.style.top = '';
        popoverEl.style.left = '';
        popoverEl.style.right = 'auto';
    }

    function isLargeHighlight(rect, vw, vh) {
        return rect.h / vh > 0.4 || rect.w / vw > 0.82;
    }

    function layoutPopoverDocked() {
        if (!popoverEl) return;
        resetPopoverPositionStyles();
        popoverEl.style.display = 'block';
        popoverEl.style.maxWidth = Math.min(420, window.innerWidth - 32) + 'px';
        popoverEl.style.visibility = 'visible';
        popoverEl.classList.add('et-tour-popover--docked');
    }

    function showPopoverChrome(index, step, options) {
        ensureDom();
        updatePopoverChrome(index, step, options);
        layoutPopoverDocked();
    }

    function popoverBox(left, top, width, height) {
        return { left: left, top: top, right: left + width, bottom: top + height, width: width, height: height };
    }

    function boxesOverlap(a, b, gap) {
        gap = gap || 0;
        return !(
            a.right + gap <= b.left ||
            b.right + gap <= a.left ||
            a.bottom + gap <= b.top ||
            b.bottom + gap <= a.top
        );
    }

    function layoutPopover(rect, placement) {
        if (!popoverEl) return;

        resetPopoverPositionStyles();
        popoverEl.style.visibility = 'hidden';
        popoverEl.style.display = 'block';
        popoverEl.style.maxWidth = Math.min(420, window.innerWidth - 32) + 'px';

        const margin = 16;
        const gap = 24;
        const vw = window.innerWidth;
        const vh = window.innerHeight;
        const popW = popoverEl.offsetWidth;
        const popH = popoverEl.offsetHeight;

        if (!rect || placement === 'center') {
            const top = Math.max(margin, (vh - popH) / 2);
            const left = Math.max(margin, (vw - popW) / 2);
            popoverEl.style.top = top + 'px';
            popoverEl.style.left = left + 'px';
            popoverEl.style.visibility = 'visible';
            return;
        }

        if (isLargeHighlight(rect, vw, vh) || placement === 'docked') {
            layoutPopoverDocked();
            return;
        }

        lastPopoverPlacement = placement || 'bottom';

        const avoid = popoverBox(
            rect.x - RING_PADDING - POPOVER_AVOID_EXTRA,
            rect.y - RING_PADDING - POPOVER_AVOID_EXTRA,
            rect.w + (RING_PADDING + POPOVER_AVOID_EXTRA) * 2,
            rect.h + (RING_PADDING + POPOVER_AVOID_EXTRA) * 2
        );

        const order = ['bottom', 'top', 'right', 'left'];
        const preferred = placement || 'bottom';
        order.sort(function (a, b) {
            if (a === preferred) return -1;
            if (b === preferred) return 1;
            return 0;
        });

        function candidateFor(side) {
            let top;
            let left;
            if (side === 'top') {
                top = avoid.top - gap - popH;
                left = avoid.left + avoid.width / 2 - popW / 2;
            } else if (side === 'left') {
                top = avoid.top + avoid.height / 2 - popH / 2;
                left = avoid.left - gap - popW;
            } else if (side === 'right') {
                top = avoid.top + avoid.height / 2 - popH / 2;
                left = avoid.right + gap;
            } else {
                top = avoid.bottom + gap;
                left = avoid.left + avoid.width / 2 - popW / 2;
            }
            return { top: top, left: left, side: side };
        }

        function clampPos(pos) {
            return {
                top: Math.max(margin, Math.min(pos.top, vh - popH - margin)),
                left: Math.max(margin, Math.min(pos.left, vw - popW - margin)),
                side: pos.side,
            };
        }

        function score(pos) {
            const box = popoverBox(pos.left, pos.top, popW, popH);
            const overlap = boxesOverlap(box, avoid, 0);
            const offScreen =
                pos.top < margin - 1 ||
                pos.left < margin - 1 ||
                pos.top + popH > vh - margin + 1 ||
                pos.left + popW > vw - margin + 1;
            const clamped = boxesOverlap(box, avoid, gap);
            const sideBonus = pos.side === preferred ? 0 : 1;
            return (overlap ? 1000 : 0) + (clamped ? 500 : 0) + (offScreen ? 200 : 0) + sideBonus;
        }

        let best = null;
        let bestScore = Infinity;

        order.forEach(function (side) {
            const raw = candidateFor(side);
            const clamped = clampPos(raw);
            const s = score(clamped);
            if (s < bestScore) {
                bestScore = s;
                best = clamped;
            }
        });

        if (!best || bestScore >= 500) {
            const below = clampPos(candidateFor('bottom'));
            const above = clampPos(candidateFor('top'));
            const spaceBelow = vh - avoid.bottom - margin;
            const spaceAbove = avoid.top - margin;

            if (spaceBelow >= spaceAbove && spaceBelow >= popH + gap) {
                best = { top: avoid.bottom + gap, left: Math.max(margin, Math.min(vw - popW - margin, avoid.left + avoid.width / 2 - popW / 2)), side: 'bottom' };
            } else if (spaceAbove >= popH + gap) {
                best = { top: avoid.top - gap - popH, left: Math.max(margin, Math.min(vw - popW - margin, avoid.left + avoid.width / 2 - popW / 2)), side: 'top' };
            } else {
                layoutPopoverDocked();
                return;
            }

            const fallbackBox = popoverBox(best.left, best.top, popW, popH);
            if (boxesOverlap(fallbackBox, avoid, gap)) {
                best = { top: Math.min(vh - popH - margin, avoid.bottom + gap), left: Math.max(margin, Math.min(vw - popW - margin, best.left)), side: 'bottom' };
                const box2 = popoverBox(best.left, best.top, popW, popH);
                if (boxesOverlap(box2, avoid, gap)) {
                    best = { top: margin, left: Math.max(margin, (vw - popW) / 2), side: 'top' };
                }
            }
        }

        if (avoid.bottom <= vh * 0.18) {
            const minTop = Math.max(avoid.bottom + gap, vh * 0.3);
            const shifted = popoverBox(best.left, minTop, popW, popH);
            if (!boxesOverlap(shifted, avoid, gap)) {
                best.top = minTop;
            }
        }

        const finalBox = popoverBox(best.left, best.top, popW, popH);
        if (
            boxesOverlap(finalBox, avoid, gap) ||
            best.top + popH > vh - margin ||
            best.top < margin
        ) {
            layoutPopoverDocked();
            return;
        }

        popoverEl.style.top = best.top + 'px';
        popoverEl.style.left = best.left + 'px';
        popoverEl.style.visibility = 'visible';
    }

    function relayoutPopoverForTarget(target, placement) {
        if (!target || !popoverEl) return;
        const rect = target.getBoundingClientRect();
        const x = Math.max(0, rect.left - PADDING);
        const y = Math.max(0, rect.top - PADDING);
        const w = Math.min(window.innerWidth - x, rect.width + PADDING * 2);
        const h = Math.min(window.innerHeight - y, rect.height + PADDING * 2);
        layoutPopover({ x: x, y: y, w: w, h: h }, placement || lastPopoverPlacement);
    }

    function updatePopoverChrome(index, step, options) {
        const opts = options || {};
        const total = runtime.steps.length;
        const isLast = index >= total - 1;
        const isNavigate = !!step.navigate && !skipExitMode;
        const useStartLabel = !!step.startButton && !skipExitMode;

        rootEl.querySelector('.et-tour-popover__progress-text').textContent = skipExitMode
            ? runtime.labels.badge
            : formatStepOf(runtime.labels.stepOf, index + 1, total);
        rootEl.querySelector('.et-tour-popover__progress-bar').style.width = skipExitMode
            ? '100%'
            : (((index + 1) / total) * 100) + '%';
        rootEl.querySelector('.et-tour-popover__title').textContent = opts.title || step.title;
        rootEl.querySelector('.et-tour-popover__body').textContent = opts.body || step.body;

        const navHint = rootEl.querySelector('.et-tour-popover__nav-hint');
        navHint.textContent = runtime.labels.navigateHint;
        navHint.classList.toggle('hidden', !isNavigate);

        const skipBtn = rootEl.querySelector('[data-action="skip"]');
        const backBtn = rootEl.querySelector('[data-action="back"]');
        const nextBtn = rootEl.querySelector('[data-action="next"]');

        skipBtn.classList.toggle('hidden', !!skipExitMode);
        backBtn.disabled = skipExitMode || index === 0;
        backBtn.style.visibility = (skipExitMode || index === 0) ? 'hidden' : 'visible';
        nextBtn.textContent = skipExitMode
            ? runtime.labels.understood
            : (useStartLabel ? runtime.labels.start : (isLast ? runtime.labels.finish : runtime.labels.next));
        nextBtn.classList.toggle('hidden', isNavigate);
    }

    function renderStep(index) {
        const step = runtime.steps[index];
        if (!step) {
            finishTour();
            return;
        }

        ensureDom();
        setBodyTourClass(true);
        attachSubmitBlocker();
        rootEl.classList.add('et-tour-root--visible');

        updatePopoverChrome(index, step);

        runtime.state.stepIndex = index;
        writeState(runtime.state);

        bindNavigateLinks(step, index);

        if (step.center) {
            layoutSpotlight(null, 'center', true);
            return;
        }

        layoutPopoverDocked();

        waitForElement(step.target, 10000).then(function (target) {
            if (!runtime?.state?.active || runtime.state.stepIndex !== index || skipExitMode) return;
            if (!target) {
                layoutSpotlight(null, 'center', true);
                return;
            }
            layoutSpotlight(target, step.placement || 'bottom', false);
        });
    }

    function renderSkipHelpStep() {
        skipExitMode = true;
        ensureDom();
        setBodyTourClass(true);
        rootEl.classList.add('et-tour-root--visible');
        unbindNavigateLinks();

        updatePopoverChrome(0, runtime.steps[0], {
            title: runtime.labels.skipHelpTitle,
            body: runtime.labels.skipHelpBody,
        });

        waitForElement('[data-tour="help-button"]', 5000).then(function (target) {
            if (!skipExitMode) return;
            if (target) {
                layoutSpotlight(target, 'bottom', false);
            } else {
                layoutSpotlight(null, 'center', true);
            }
        });
    }

    function goNext() {
        if (skipExitMode) {
            finishTour();
            return;
        }
        const step = currentStep();
        if (!step) return;
        if (runtime.state.stepIndex >= runtime.steps.length - 1) {
            finishTour();
            return;
        }
        showStepAt(runtime.state.stepIndex + 1);
    }

    function goBack() {
        if (skipExitMode) return;
        if (runtime.state.stepIndex <= 0) return;
        showStepAt(runtime.state.stepIndex - 1);
    }

    function stepNeedsTabRedirect(step) {
        if (step.anyTab) return false;
        return step.tab !== runtime.activeTab;
    }

    function showStepAt(index) {
        skipExitMode = false;
        const step = runtime.steps[index];
        if (!step) return;

        if (stepNeedsTabRedirect(step)) {
            writeState({ active: true, tourId: runtime.tourId, stepIndex: index });
            window.location.href = '/?tab=' + encodeURIComponent(step.tab);
            return;
        }

        renderStep(index);
    }

    function markTourSeen() {
        try {
            localStorage.setItem(doneStorageKey(), '1');
        } catch (e) {}
    }

    function finishTour() {
        markTourSeen();
        writeState(null);
        skipExitMode = false;
        setBodyTourClass(false);
        detachSubmitBlocker();
        unbindNavigateLinks();
        if (rootEl) {
            rootEl.classList.remove('et-tour-root--visible');
            rootEl.classList.remove('et-tour-root--blur');
        }
        clearSpotlight();
        if (runtime) {
            runtime.state = { active: false, tourId: runtime.tourId, stepIndex: 0 };
        }
    }

    function skipTour() {
        writeState({ active: true, tourId: runtime.tourId, stepIndex: runtime.state.stepIndex });
        renderSkipHelpStep();
    }

    function handleResume() {
        const state = readState();
        if (!state?.active) return;

        let index = state.stepIndex || 0;
        const steps = runtime.steps;
        let step = steps[index];

        if (!step) {
            finishTour();
            return;
        }

        if (step.navigate && step.navTab === runtime.activeTab && runtime.activeTab !== step.tab) {
            index += 1;
            step = steps[index];
            writeState({ active: true, tourId: runtime.tourId, stepIndex: index });
        }

        if (!step) {
            finishTour();
            return;
        }

        if (stepNeedsTabRedirect(step)) {
            window.location.href = '/?tab=' + encodeURIComponent(step.tab);
            return;
        }

        runtime.state = { active: true, tourId: runtime.tourId, stepIndex: index };
        renderStep(index);
    }

    function startTour() {
        skipExitMode = false;
        writeState({ active: true, tourId: runtime.tourId, stepIndex: 0 });
        runtime.state = { active: true, tourId: runtime.tourId, stepIndex: 0 };
        showStepAt(0);
    }

    function init(config) {
        runtime = {
            tourId: config.tourId,
            userId: config.userId || 0,
            activeTab: config.activeTab,
            steps: config.steps || [],
            labels: Object.assign({
                badge: 'Tutorial',
                stepOf: 'Step %d of %d',
                back: 'Back',
                next: 'Next',
                finish: 'Finish',
                skip: 'Skip',
                understood: 'Got it',
                navigateHint: 'Click the highlighted area to continue',
                skipHelpTitle: 'Tutorial skipped',
                skipHelpBody: 'Restart anytime via the ? button.',
            }, config.labels || {}),
            state: readState() || { active: false, tourId: config.tourId, stepIndex: 0 },
        };

        window.addEventListener('resize', function () {
            if (!runtime?.state?.active) return;
            if (skipExitMode) {
                const target = findTourTarget('[data-tour="help-button"]');
                if (target) layoutSpotlight(target, 'bottom', false);
                return;
            }
            const step = currentStep();
            if (!step || step.center) return;
            const target = step.target ? findTourTarget(step.target) : null;
            if (target) layoutSpotlight(target, step.placement, false);
        });

        window.addEventListener('scroll', function () {
            if (!runtime?.state?.active) return;
            if (skipExitMode) {
                const target = findTourTarget('[data-tour="help-button"]');
                if (target) layoutSpotlight(target, 'bottom', false);
                return;
            }
            const step = currentStep();
            if (!step || step.center) return;
            const target = step.target ? findTourTarget(step.target) : null;
            if (target) layoutSpotlight(target, step.placement, false);
        }, true);

        if (runtime.state.active) {
            setTimeout(handleResume, 450);
        } else if (config.autoStart && runtime.userId > 0 && !localStorage.getItem(doneStorageKey())) {
            setTimeout(startTour, 800);
        }
    }

    window.EasyTimeTour = {
        init: init,
        start: function () {
            if (!runtime) return;
            startTour();
        },
        finish: finishTour,
        isActive: function () {
            return !!runtime?.state?.active || skipExitMode;
        },
    };
})();
