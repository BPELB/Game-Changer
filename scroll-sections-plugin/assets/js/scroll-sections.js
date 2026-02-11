/**
 * Scroll Sections v2.1 — Native Scroll with Smooth Parallax
 *
 * Uses native browser scroll so the page header/footer remain visible.
 * Applies lerp-based smooth parallax and scroll-triggered reveals.
 *
 * Zero dependencies.
 */
(function () {
    'use strict';

    /* ---- Config ---- */
    var PARALLAX_LERP = 0.08;  // smoothing for parallax (lower = smoother)
    var REVEAL_AT     = 0.82;  // reveal when section top reaches this % of viewport

    /* ---- State ---- */
    var wrapper, sections, navDots, nav, progressBar;
    var parallaxTargets = [];  // { el, speed, currentY, targetY }
    var ticking = false;

    /* ---- Reduced motion check ---- */
    var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ======================================================================
       Init
       ====================================================================== */

    function init() {
        wrapper = document.getElementById('ss-wrapper');
        if (!wrapper) return;

        sections    = wrapper.querySelectorAll('.ss-section');
        nav         = document.getElementById('ss-nav');
        navDots     = wrapper.querySelectorAll('.ss-nav-dot');
        progressBar = document.getElementById('ss-progress');

        if (!sections.length) return;

        // Apply transition styles to each stagger element
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

            // Build parallax target list
            var bg = sec.querySelector('.ss-bg');
            if (bg) {
                var speed = parseFloat(sec.getAttribute('data-parallax') || '0.3');
                parallaxTargets.push({ el: bg, section: sec, speed: speed, currentY: 0, targetY: 0 });
            }
        });

        if (prefersReducedMotion) {
            sections.forEach(function (s) { s.classList.add('ss-in-view'); });
            return;
        }

        // Smooth scroll feel via CSS
        document.documentElement.style.scrollBehavior = 'smooth';

        bindEvents();

        // Initial update
        onScroll();

        // Start parallax lerp loop
        requestAnimationFrame(parallaxLoop);
    }

    /* ======================================================================
       Events
       ====================================================================== */

    function bindEvents() {
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });

        // Nav dots — smooth-scroll to section
        navDots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                var idx = parseInt(this.getAttribute('data-index'), 10);
                if (sections[idx]) {
                    sections[idx].scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    }

    function onScroll() {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(function () {
            update();
            ticking = false;
        });
    }

    /* ======================================================================
       Main Update (runs on scroll)
       ====================================================================== */

    function update() {
        var scrollY = window.pageYOffset || document.documentElement.scrollTop;
        var viewH   = window.innerHeight;

        updateReveals(scrollY, viewH);
        updateParallaxTargets(scrollY, viewH);
        updateNav(scrollY, viewH);
        updateProgress(scrollY);
        updateNavVisibility(scrollY, viewH);
    }

    /* ======================================================================
       Reveals
       ====================================================================== */

    function updateReveals(scrollY, viewH) {
        var triggerLine = scrollY + viewH * REVEAL_AT;

        sections.forEach(function (sec) {
            var rect      = sec.getBoundingClientRect();
            var secTop    = scrollY + rect.top;
            var secBottom = secTop + rect.height;

            var inView = (secTop < triggerLine) && (secBottom > scrollY + viewH * 0.1);

            if (inView && !sec.classList.contains('ss-in-view')) {
                sec.classList.add('ss-in-view');
                handleVideoPlay(sec, true);
            } else if (!inView && sec.classList.contains('ss-in-view')) {
                sec.classList.remove('ss-in-view');
                handleVideoPlay(sec, false);
            }
        });
    }

    /* ======================================================================
       Parallax (lerp-smoothed via rAF loop)
       ====================================================================== */

    function updateParallaxTargets(scrollY, viewH) {
        if (window.innerWidth <= 768) return;

        parallaxTargets.forEach(function (p) {
            if (p.speed === 0) return;
            var rect = p.section.getBoundingClientRect();
            var secTop = scrollY + rect.top;
            var offset = scrollY - secTop;
            p.targetY = offset * p.speed * 0.4;
        });
    }

    function parallaxLoop() {
        var moved = false;

        parallaxTargets.forEach(function (p) {
            if (p.speed === 0) return;
            p.currentY += (p.targetY - p.currentY) * PARALLAX_LERP;

            if (Math.abs(p.targetY - p.currentY) < 0.1) {
                p.currentY = p.targetY;
            } else {
                moved = true;
            }

            p.el.style.transform = 'translate3d(0,' + p.currentY + 'px,0)';
        });

        requestAnimationFrame(parallaxLoop);
    }

    /* ======================================================================
       Video Play/Pause
       ====================================================================== */

    function handleVideoPlay(sec, entering) {
        var autoplay = sec.getAttribute('data-video-autoplay');

        // Background video
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

            var iframe = sec.querySelector('.ss-video-embed iframe');
            if (iframe) {
                if (entering) {
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

    function updateNav(scrollY, viewH) {
        var mid = scrollY + viewH * 0.5;
        var activeIdx = 0;

        sections.forEach(function (sec, i) {
            var rect = sec.getBoundingClientRect();
            var secTop = scrollY + rect.top;
            if (secTop <= mid) {
                activeIdx = i;
            }
        });

        navDots.forEach(function (dot, i) {
            dot.classList.toggle('ss-nav-active', i === activeIdx);
        });
    }

    function updateProgress(scrollY) {
        if (!progressBar) return;
        var wrapperRect = wrapper.getBoundingClientRect();
        var wrapperTop  = scrollY + wrapperRect.top;
        var wrapperH    = wrapper.offsetHeight;
        var viewH       = window.innerHeight;
        var maxScroll   = wrapperH - viewH;

        if (maxScroll <= 0) return;

        var localScroll = scrollY - wrapperTop;
        var pct = Math.max(0, Math.min(100, (localScroll / maxScroll) * 100));
        progressBar.style.width = pct + '%';
    }

    /** Show nav dots and progress bar only when the wrapper is in the viewport */
    function updateNavVisibility(scrollY, viewH) {
        var rect = wrapper.getBoundingClientRect();
        var visible = (rect.bottom > 0) && (rect.top < viewH);

        if (nav) {
            nav.classList.toggle('ss-nav-visible', visible);
        }
        if (progressBar) {
            progressBar.classList.toggle('ss-progress-visible', visible);
        }
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
