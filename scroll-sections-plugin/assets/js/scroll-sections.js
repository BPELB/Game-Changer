/**
 * Scroll Sections v2 — Smooth Inertia Scroll Engine
 *
 * Replaces browser scroll-snap with a Lenis-style smooth scroll.
 * - Inertia-based scrolling with configurable lerp
 * - Parallax backgrounds
 * - Staggered scroll-triggered reveal animations
 * - Inline video autoplay on scroll
 * - Navigation dots + keyboard nav
 *
 * Zero dependencies.
 */
(function () {
    'use strict';

    /* ---- Config ---- */
    var LERP        = 0.07;   // smoothing factor (lower = smoother/slower)
    var WHEEL_MULT  = 1.0;    // mouse wheel multiplier
    var TOUCH_MULT  = 1.8;    // touch swipe multiplier
    var REVEAL_AT   = 0.80;   // fraction of viewport — trigger reveal when section top reaches this point

    /* ---- State ---- */
    var wrapper, smooth, sections, navDots, progressBar;
    var targetScroll = 0;
    var currentScroll = 0;
    var maxScroll = 0;
    var isRunning = false;
    var touchStart = 0;
    var raf = null;

    /* ---- Reduced motion check ---- */
    var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ======================================================================
       Init
       ====================================================================== */

    function init() {
        wrapper = document.getElementById('ss-wrapper');
        if (!wrapper) return;

        smooth      = document.getElementById('ss-smooth');
        sections    = wrapper.querySelectorAll('.ss-section');
        navDots     = wrapper.querySelectorAll('.ss-nav-dot');
        progressBar = document.getElementById('ss-progress');

        if (!sections.length) return;

        // Apply transition styles to each stagger element based on section data
        sections.forEach(function (sec) {
            var duration = sec.getAttribute('data-duration') || '1.2';
            var delay    = parseFloat(sec.getAttribute('data-delay') || '0');
            var stagger  = parseFloat(sec.getAttribute('data-stagger') || '0.15');
            var easing   = sec.getAttribute('data-easing') || 'cubic-bezier(0.25,0.46,0.45,0.94)';

            var children = sec.querySelectorAll('.ss-stagger');
            children.forEach(function (child, idx) {
                var d = delay + (idx * stagger);
                child.style.transition =
                    'opacity ' + duration + 's ' + easing + ' ' + d + 's, ' +
                    'transform ' + duration + 's ' + easing + ' ' + d + 's, ' +
                    'filter ' + duration + 's ' + easing + ' ' + d + 's, ' +
                    'clip-path ' + duration + 's ' + easing + ' ' + d + 's';
            });
        });

        if (prefersReducedMotion) {
            // Show everything, skip smooth scroll engine
            sections.forEach(function (s) { s.classList.add('ss-in-view'); });
            return;
        }

        calcMaxScroll();
        bindEvents();
        isRunning = true;
        raf = requestAnimationFrame(loop);

        // Initial reveal check
        updateSections();
    }

    /* ======================================================================
       Layout
       ====================================================================== */

    function calcMaxScroll() {
        maxScroll = smooth.scrollHeight - wrapper.clientHeight;
        if (maxScroll < 0) maxScroll = 0;
        // Clamp current values
        targetScroll = clamp(targetScroll, 0, maxScroll);
        currentScroll = clamp(currentScroll, 0, maxScroll);
    }

    /* ======================================================================
       Scroll Input
       ====================================================================== */

    function onWheel(e) {
        e.preventDefault();
        var delta = e.deltaY;
        // Normalize deltaMode (pixels vs lines vs pages)
        if (e.deltaMode === 1) delta *= 36;
        if (e.deltaMode === 2) delta *= window.innerHeight;
        targetScroll = clamp(targetScroll + delta * WHEEL_MULT, 0, maxScroll);
    }

    function onTouchStart(e) {
        touchStart = e.touches[0].clientY;
    }

    function onTouchMove(e) {
        e.preventDefault();
        var delta = (touchStart - e.touches[0].clientY) * TOUCH_MULT;
        touchStart = e.touches[0].clientY;
        targetScroll = clamp(targetScroll + delta, 0, maxScroll);
    }

    function onKeyDown(e) {
        var tag = (e.target || e.srcElement).tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;

        var jump = wrapper.clientHeight * 0.9;
        switch (e.key) {
            case 'ArrowDown': case 'PageDown':
                e.preventDefault();
                targetScroll = clamp(targetScroll + jump, 0, maxScroll);
                break;
            case 'ArrowUp': case 'PageUp':
                e.preventDefault();
                targetScroll = clamp(targetScroll - jump, 0, maxScroll);
                break;
            case 'Home':
                e.preventDefault();
                targetScroll = 0;
                break;
            case 'End':
                e.preventDefault();
                targetScroll = maxScroll;
                break;
        }
    }

    function onResize() {
        calcMaxScroll();
    }

    function bindEvents() {
        wrapper.addEventListener('wheel', onWheel, { passive: false });
        wrapper.addEventListener('touchstart', onTouchStart, { passive: true });
        wrapper.addEventListener('touchmove', onTouchMove, { passive: false });
        document.addEventListener('keydown', onKeyDown);
        window.addEventListener('resize', onResize);

        // Nav dots
        navDots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                var idx = parseInt(this.getAttribute('data-index'), 10);
                if (sections[idx]) {
                    targetScroll = clamp(sections[idx].offsetTop, 0, maxScroll);
                }
            });
        });
    }

    /* ======================================================================
       Render Loop
       ====================================================================== */

    function loop() {
        if (!isRunning) return;

        // Lerp towards target
        currentScroll += (targetScroll - currentScroll) * LERP;

        // Snap when close enough (< 0.5px)
        if (Math.abs(targetScroll - currentScroll) < 0.5) {
            currentScroll = targetScroll;
        }

        // Translate the smooth container
        smooth.style.transform = 'translate3d(0,' + (-currentScroll) + 'px,0)';

        // Update parallax, reveals, progress
        updateParallax();
        updateSections();
        updateProgress();
        updateNav();

        raf = requestAnimationFrame(loop);
    }

    /* ======================================================================
       Parallax
       ====================================================================== */

    function updateParallax() {
        if (window.innerWidth <= 768) return;

        var viewH = wrapper.clientHeight;

        sections.forEach(function (sec) {
            var bg = sec.querySelector('.ss-bg');
            if (!bg) return;

            var speed = parseFloat(sec.getAttribute('data-parallax') || '0.3');
            if (speed === 0) return;

            var secTop = sec.offsetTop;
            var offset = currentScroll - secTop;
            var translate = offset * speed * 0.4;

            bg.style.transform = 'translate3d(0,' + translate + 'px,0)';
        });
    }

    /* ======================================================================
       Section Reveal & Video
       ====================================================================== */

    function updateSections() {
        var viewH = wrapper.clientHeight;
        var triggerLine = currentScroll + viewH * REVEAL_AT;

        sections.forEach(function (sec) {
            var secTop    = sec.offsetTop;
            var secBottom = secTop + sec.offsetHeight;

            // In view = section top has passed the trigger line AND bottom hasn't fully left
            var inView = (secTop < triggerLine) && (secBottom > currentScroll + viewH * 0.1);

            if (inView && !sec.classList.contains('ss-in-view')) {
                sec.classList.add('ss-in-view');
                handleVideoPlay(sec, true);
            } else if (!inView && sec.classList.contains('ss-in-view')) {
                sec.classList.remove('ss-in-view');
                handleVideoPlay(sec, false);
            }
        });
    }

    function handleVideoPlay(sec, entering) {
        var autoplay = sec.getAttribute('data-video-autoplay');

        // Background video: always play when in view
        var bgVid = sec.querySelector('.ss-bg-video video');
        if (bgVid) {
            if (entering) { try { bgVid.play(); } catch(e){} }
            else { try { bgVid.pause(); } catch(e){} }
        }

        // Inline video
        if (autoplay === 'on_scroll' || autoplay === 'autoplay') {
            var inlineVid = sec.querySelector('.ss-video-player');
            if (inlineVid) {
                if (entering) {
                    inlineVid.muted = true;
                    try { inlineVid.play(); } catch(e){}
                } else {
                    try { inlineVid.pause(); } catch(e){}
                }
            }

            // For iframes (YouTube/Vimeo) — toggle autoplay via postMessage
            var iframe = sec.querySelector('.ss-video-embed iframe');
            if (iframe) {
                if (entering) {
                    // Attempt Vimeo/YouTube API play
                    try {
                        iframe.contentWindow.postMessage('{"method":"play"}', '*');
                        iframe.contentWindow.postMessage('{"event":"command","func":"playVideo","args":""}', '*');
                    } catch(e){}
                } else {
                    try {
                        iframe.contentWindow.postMessage('{"method":"pause"}', '*');
                        iframe.contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*');
                    } catch(e){}
                }
            }
        }
    }

    /* ======================================================================
       Nav & Progress
       ====================================================================== */

    function updateNav() {
        var viewH = wrapper.clientHeight;
        var mid = currentScroll + viewH * 0.5;
        var activeIdx = 0;

        sections.forEach(function (sec, i) {
            if (sec.offsetTop <= mid) {
                activeIdx = i;
            }
        });

        navDots.forEach(function (dot, i) {
            dot.classList.toggle('ss-nav-active', i === activeIdx);
        });
    }

    function updateProgress() {
        if (maxScroll <= 0) return;
        var pct = (currentScroll / maxScroll) * 100;
        progressBar.style.width = pct + '%';
    }

    /* ======================================================================
       Utilities
       ====================================================================== */

    function clamp(val, min, max) {
        return Math.max(min, Math.min(max, val));
    }

    /* ======================================================================
       Boot
       ====================================================================== */

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
