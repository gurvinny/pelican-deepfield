/* =============================================================
 * Deepfield — canvas starfield, nebula, meteors
 * Runs inside Pelican Panel (Livewire + Turbo)
 * ============================================================= */
(() => {
    'use strict';

    const state = {
        settings: {
            star_density:      'medium',
            nebula_enabled:    true,
            nebula_hue:        'violet',
            crt_bloom:         true,
            reduce_motion:     false,
            terminal_palette:  'cosmic',
            scanline_density:  'normal',
            audio_cues:        false,
            tab_title_suffix:  true,
        },
        rafStar: null,
        rafNeb:  null,
        meteorTimer: null,
        stars: [],
        meteors: [],
        pointer: { x: 0, y: 0, tx: 0, ty: 0 },
        prefersReduced: false,
        starCanvas: null,
        nebCanvas: null,
        starCtx: null,
        nebCtx: null,
        dpr: 1,
        resizeObs: null,
        listeners: [],
        visHandler: null,
        started: false,
    };

    const DENSITY = { off: 0, low: 250, medium: 600, high: 1200 };
    const HUE = {
        violet: [270, 210],   // primary hue, secondary hue
        teal:   [175, 260],
        rose:   [335, 260],
    };
    const PARALLAX_TIERS = [0.010, 0.025, 0.050];

    // ---- Settings ingest -------------------------------------------------
    function readSettings() {
        try {
            const meta = document.querySelector('meta[name="df-settings"]');
            if (meta) Object.assign(state.settings, JSON.parse(meta.content));
        } catch (e) { /* ignore */ }
        state.prefersReduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.body.setAttribute('data-df-crt', state.settings.crt_bloom ? '1' : '0');
    }

    function motionAllowed() {
        return !state.prefersReduced && !state.settings.reduce_motion;
    }

    // ---- Canvas setup ----------------------------------------------------
    function sizeCanvas(canvas) {
        state.dpr = Math.min(window.devicePixelRatio || 1, 2);
        const w = window.innerWidth, h = window.innerHeight;
        canvas.width  = Math.floor(w * state.dpr);
        canvas.height = Math.floor(h * state.dpr);
        canvas.style.width  = w + 'px';
        canvas.style.height = h + 'px';
        const ctx = canvas.getContext('2d');
        ctx.setTransform(state.dpr, 0, 0, state.dpr, 0, 0);
        return ctx;
    }

    // ---- Stars -----------------------------------------------------------
    function buildStars() {
        const total = DENSITY[state.settings.star_density] ?? 600;
        const w = window.innerWidth, h = window.innerHeight;
        const stars = [];
        for (let i = 0; i < total; i++) {
            const tier = i % 3;   // 0 = far, 1 = mid, 2 = near
            stars.push({
                x: Math.random() * w,
                y: Math.random() * h,
                r: tier === 2 ? (0.9 + Math.random() * 1.1)
                   : tier === 1 ? (0.5 + Math.random() * 0.7)
                   : (0.3 + Math.random() * 0.4),
                a: tier === 2 ? (0.7 + Math.random() * 0.3)
                   : tier === 1 ? (0.4 + Math.random() * 0.4)
                   : (0.15 + Math.random() * 0.3),
                twk: Math.random() * Math.PI * 2,
                twkSpeed: 0.4 + Math.random() * 1.2,
                tier,
            });
        }
        return stars;
    }

    function drawStars(ts) {
        const ctx = state.starCtx;
        const w = window.innerWidth, h = window.innerHeight;
        ctx.clearRect(0, 0, w, h);

        // ease pointer toward target
        state.pointer.x += (state.pointer.tx - state.pointer.x) * 0.06;
        state.pointer.y += (state.pointer.ty - state.pointer.y) * 0.06;

        const cx = w / 2, cy = h / 2;

        for (const s of state.stars) {
            const p = PARALLAX_TIERS[s.tier];
            const dx = (state.pointer.x - cx) * p;
            const dy = (state.pointer.y - cy) * p;

            // gentle twinkle for near/mid only
            let alpha = s.a;
            if (motionAllowed() && s.tier > 0) {
                alpha = s.a * (0.75 + 0.25 * Math.sin(ts * 0.0006 * s.twkSpeed + s.twk));
            }

            ctx.beginPath();
            ctx.arc(s.x + dx, s.y + dy, s.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(232, 240, 255, ${alpha.toFixed(3)})`;
            ctx.fill();

            if (s.tier === 2) {
                ctx.beginPath();
                ctx.arc(s.x + dx, s.y + dy, s.r * 2.2, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(56, 225, 255, ${(alpha * 0.10).toFixed(3)})`;
                ctx.fill();
            }
        }

        // meteors
        for (let i = state.meteors.length - 1; i >= 0; i--) {
            const m = state.meteors[i];
            m.progress += 0.008;
            if (m.progress >= 1) { state.meteors.splice(i, 1); continue; }
            const x = m.x0 + (m.x1 - m.x0) * m.progress;
            const y = m.y0 + (m.y1 - m.y0) * m.progress;
            const tailX = x - (m.x1 - m.x0) * 0.18;
            const tailY = y - (m.y1 - m.y0) * 0.18;
            const grad = ctx.createLinearGradient(tailX, tailY, x, y);
            const fade = Math.sin(m.progress * Math.PI);
            grad.addColorStop(0, 'rgba(56, 225, 255, 0)');
            grad.addColorStop(1, `rgba(94, 234, 212, ${(fade * 0.9).toFixed(3)})`);
            ctx.strokeStyle = grad;
            ctx.lineWidth = 1.8;
            ctx.lineCap = 'round';
            ctx.beginPath();
            ctx.moveTo(tailX, tailY);
            ctx.lineTo(x, y);
            ctx.stroke();
        }

        if (state.settings.star_density !== 'off') {
            state.rafStar = requestAnimationFrame(drawStars);
        }
    }

    function spawnMeteor() {
        if (!motionAllowed()) return;
        const w = window.innerWidth, h = window.innerHeight;
        const startX = Math.random() * w * 0.6;
        const startY = -20;
        const angle = (Math.PI / 4) + (Math.random() - 0.5) * 0.4;
        const dist = 500 + Math.random() * 400;
        state.meteors.push({
            x0: startX, y0: startY,
            x1: startX + Math.cos(angle) * dist,
            y1: startY + Math.sin(angle) * dist,
            progress: 0,
        });
        if (state.meteors.length > 6) state.meteors.shift();
    }

    // ---- Nebula ----------------------------------------------------------
    function drawNebula() {
        if (!state.settings.nebula_enabled) {
            state.nebCtx.clearRect(0, 0, window.innerWidth, window.innerHeight);
            return;
        }
        const ctx = state.nebCtx;
        const w = window.innerWidth, h = window.innerHeight;
        ctx.clearRect(0, 0, w, h);

        const t = performance.now() * 0.00003;
        const [hue1, hue2] = HUE[state.settings.nebula_hue] || HUE.violet;

        const drift = motionAllowed() ? t : 0;
        const blobs = [
            { x: w * (0.30 + Math.sin(drift * 1.1) * 0.05), y: h * (0.35 + Math.cos(drift * 0.9) * 0.05), r: Math.max(w, h) * 0.55, hue: hue1, alpha: 0.35 },
            { x: w * (0.75 + Math.cos(drift * 0.8) * 0.06), y: h * (0.70 + Math.sin(drift * 1.2) * 0.05), r: Math.max(w, h) * 0.50, hue: hue2, alpha: 0.28 },
        ];

        for (const b of blobs) {
            const grad = ctx.createRadialGradient(b.x, b.y, 0, b.x, b.y, b.r);
            grad.addColorStop(0.0, `hsla(${b.hue}, 80%, 55%, ${b.alpha})`);
            grad.addColorStop(0.5, `hsla(${b.hue}, 80%, 40%, ${(b.alpha * 0.3).toFixed(3)})`);
            grad.addColorStop(1.0, 'hsla(0, 0%, 0%, 0)');
            ctx.fillStyle = grad;
            ctx.fillRect(0, 0, w, h);
        }

        if (motionAllowed() && state.settings.nebula_enabled) {
            state.rafNeb = requestAnimationFrame(drawNebula);
        }
    }

    // ---- Lifecycle -------------------------------------------------------
    function start() {
        if (state.started) return;
        readSettings();

        state.starCanvas = document.getElementById('df-starfield');
        state.nebCanvas  = document.getElementById('df-nebula');
        if (!state.starCanvas || !state.nebCanvas) return;

        state.starCtx = sizeCanvas(state.starCanvas);
        state.nebCtx  = sizeCanvas(state.nebCanvas);

        state.stars = buildStars();

        // Draw nebula once even if motion disabled
        drawNebula();

        if (state.settings.star_density !== 'off') {
            state.rafStar = requestAnimationFrame(drawStars);
        }

        if (motionAllowed() && state.settings.star_density !== 'off') {
            state.meteorTimer = setInterval(spawnMeteor, 3500 + Math.random() * 2500);
        }

        // Pointer parallax
        const onMove = (e) => {
            state.pointer.tx = e.clientX;
            state.pointer.ty = e.clientY;
        };
        if (motionAllowed()) {
            window.addEventListener('pointermove', onMove, { passive: true });
            state.listeners.push(['pointermove', onMove]);
        }

        // Resize (debounced)
        let resizeTimer;
        const onResize = () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                state.starCtx = sizeCanvas(state.starCanvas);
                state.nebCtx  = sizeCanvas(state.nebCanvas);
                state.stars = buildStars();
                drawNebula();
            }, 150);
        };
        window.addEventListener('resize', onResize, { passive: true });
        state.listeners.push(['resize', onResize]);

        // Visibility — pause when tab hidden
        state.visHandler = () => {
            if (document.hidden) stopLoops();
            else if (!state.rafStar && state.settings.star_density !== 'off') {
                state.rafStar = requestAnimationFrame(drawStars);
                if (motionAllowed()) state.rafNeb = requestAnimationFrame(drawNebula);
            }
        };
        document.addEventListener('visibilitychange', state.visHandler);

        state.started = true;
    }

    function stopLoops() {
        if (state.rafStar) { cancelAnimationFrame(state.rafStar); state.rafStar = null; }
        if (state.rafNeb)  { cancelAnimationFrame(state.rafNeb);  state.rafNeb  = null; }
    }

    function teardown() {
        stopLoops();
        if (state.meteorTimer) { clearInterval(state.meteorTimer); state.meteorTimer = null; }
        for (const [ev, fn] of state.listeners) window.removeEventListener(ev, fn);
        state.listeners = [];
        if (state.visHandler) {
            document.removeEventListener('visibilitychange', state.visHandler);
            state.visHandler = null;
        }
        state.meteors = [];
        state.started = false;
    }

    // SPA re-init — Pelican Panel uses Livewire; may also see Turbo
    const reinit = () => { teardown(); start(); };
    document.addEventListener('livewire:navigated', reinit);
    document.addEventListener('turbo:load',    reinit);
    document.addEventListener('turbo:render',  reinit);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }

    // ------------------------------------------------------------------
    // Tab title suffix — append "· Deepfield" if not present
    // ------------------------------------------------------------------
    function initTabTitle() {
        if (!state.settings.tab_title_suffix) return;
        const suffix = ' · Deepfield';
        const apply = () => {
            const t = document.title || '';
            if (t && !t.endsWith(suffix)) document.title = t + suffix;
        };
        apply();
        const titleEl = document.querySelector('title');
        if (titleEl && !titleEl.__dfObserved) {
            titleEl.__dfObserved = true;
            new MutationObserver(apply).observe(titleEl, { childList: true });
        }
    }

    // ------------------------------------------------------------------
    // Audio cues on server-state change (opt-in, default off)
    // Generates tones via WebAudio — no audio files shipped.
    // ------------------------------------------------------------------
    function initAudioCues() {
        if (!state.settings.audio_cues) return;
        let ctx;
        const getCtx = () => (ctx = ctx || new (window.AudioContext || window.webkitAudioContext)());
        const tone = (freq, dur, type = 'sine', gain = 0.06) => {
            try {
                const c = getCtx();
                const o = c.createOscillator();
                const g = c.createGain();
                o.type = type;
                o.frequency.value = freq;
                g.gain.value = 0;
                o.connect(g).connect(c.destination);
                const t = c.currentTime;
                g.gain.linearRampToValueAtTime(gain, t + 0.02);
                g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
                o.start(t);
                o.stop(t + dur);
            } catch (e) {}
        };
        const SOUNDS = {
            running:  () => { tone(440, 0.15); setTimeout(() => tone(660, 0.18), 90); setTimeout(() => tone(880, 0.25), 200); },
            starting: () => { tone(520, 0.12, 'triangle', 0.05); },
            offline:  () => { tone(300, 0.18, 'sawtooth', 0.05); setTimeout(() => tone(220, 0.22, 'sawtooth', 0.05), 120); },
        };
        // Watch state badge mutations
        const seen = new WeakMap();
        const onMut = (mutations) => {
            for (const m of mutations) {
                const target = m.target;
                if (!target || !target.textContent) continue;
                const txt = target.textContent.trim().toLowerCase();
                const key = ['running', 'online', 'starting', 'stopping', 'offline'].find(k => txt.includes(k));
                if (!key) continue;
                const prev = seen.get(target);
                if (prev === key) continue;
                seen.set(target, key);
                if (prev == null) continue;   // skip first observation
                const map = { online: 'running', running: 'running', starting: 'starting', stopping: 'offline', offline: 'offline' };
                (SOUNDS[map[key]] || (() => {}))();
            }
        };
        const badges = document.querySelectorAll('.fi-badge, [class*="badge"]');
        const obs = new MutationObserver(onMut);
        badges.forEach(b => obs.observe(b, { childList: true, characterData: true, subtree: true }));
    }

    // ---- Clamp any dropdown panel that Alpine floating-ui has placed
    // off-screen (happens with the profile popup when the admin sidebar
    // is collapsed to icon-width — trigger is at x~24, popup gets positioned
    // to x=-200 with negative left). Watch for panels appearing and slide
    // them back into the viewport.
    function watchDropdownClamps() {
        const clamp = (panel) => {
            const r = panel.getBoundingClientRect();
            if (r.width < 20) return;
            const vw = window.innerWidth;
            const vh = window.innerHeight;
            let dx = 0, dy = 0;
            if (r.left < 8)             dx = 8 - r.left;
            if (r.right > vw - 8)       dx = (vw - 8) - r.right;
            if (r.top < 8)              dy = 8 - r.top;
            if (r.bottom > vh - 8)      dy = (vh - 8) - r.bottom;
            if (dx || dy) {
                const curLeft = parseFloat(panel.style.left) || 0;
                const curTop  = parseFloat(panel.style.top)  || 0;
                panel.style.setProperty('left', (curLeft + dx) + 'px', 'important');
                panel.style.setProperty('top',  (curTop + dy)  + 'px', 'important');
            }
        };
        // Watch all dropdown panels for style changes (Alpine mutates style
        // when floating-ui recomputes position)
        const wire = (panel) => {
            if (panel.__dfClamp) return;
            panel.__dfClamp = true;
            new MutationObserver(() => {
                if (getComputedStyle(panel).display !== 'none') clamp(panel);
            }).observe(panel, { attributes: true, attributeFilter: ['style'] });
        };
        document.querySelectorAll('.fi-dropdown-panel').forEach(wire);
        // Also wire new panels as they arrive
        new MutationObserver(muts => {
            for (const m of muts) for (const n of m.addedNodes) {
                if (n.nodeType === 1) {
                    if (n.matches?.('.fi-dropdown-panel')) wire(n);
                    n.querySelectorAll?.('.fi-dropdown-panel').forEach(wire);
                }
            }
        }).observe(document.body, { childList: true, subtree: true });
    }

    // ---- Narrow-viewport sidebar toggle — Filament's built-in button only
    // OPENS the sidebar (close is handled by tap-outside on an overlay).
    // Make it toggle both ways so the button visibly closes what it opened.
    function wireSidebarToggle() {
        const btn = document.querySelector('.fi-layout-sidebar-toggle-btn');
        if (!btn || btn.__dfWired) return;
        btn.__dfWired = true;
        btn.addEventListener('click', () => {
            // Alpine store is exposed on `window.Alpine.store('sidebar')`
            try {
                const store = window.Alpine?.store?.('sidebar');
                if (store && store.isOpen) {
                    // Close on next tick so we don't fight Alpine's own open() call
                    requestAnimationFrame(() => store.close());
                }
            } catch (e) {}
        }, { capture: true });
    }

    // ---- Terminal refit — Pelican encapsulates the xterm instance in a
    // closure (not reachable via Alpine.$data or Livewire snapshot). But
    // Pelican DOES have a debounced window-resize listener that calls
    // fitAddon.fit() internally. So: watch the terminal wrapper for size
    // changes (sidebar toggle, window resize, font load) and re-emit a
    // window resize event so Pelican refits. The CSS wrapper also has
    // overflow-y:auto as a safety net — nothing gets clipped either way.
    function wireTerminalRefit() {
        const wrap = document.querySelector('#terminal');
        if (!wrap || wrap.__dfRefitWired) return;
        wrap.__dfRefitWired = true;
        const kick = () => { try { window.dispatchEvent(new Event('resize')); } catch (e) {} };
        if (window.ResizeObserver) new ResizeObserver(kick).observe(wrap);
        document.addEventListener('livewire:navigated', () => setTimeout(kick, 60));
    }

    // Wire up once on first ready + reinit on navigation
    const initExtras = () => { initTabTitle(); initAudioCues(); watchDropdownClamps(); wireSidebarToggle(); wireTerminalRefit(); };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initExtras);
    else initExtras();
    document.addEventListener('livewire:navigated', initExtras);
})();
