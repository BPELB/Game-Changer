/**
 * Scroll Sections - Parallax & Scroll-Triggered Animations
 * Vanilla JS, no dependencies.
 */
(function () {
    'use strict';

    var container;
    var sections;
    var navDots;
    var progressBar;
    var ticking = false;

    function init() {
        container = document.getElementById('ss-container');
        if (!container) return;

        sections = container.querySelectorAll('.ss-section');
        navDots = document.querySelectorAll('.ss-nav-dot');

        // Create progress bar
        progressBar = document.createElement('div');
        progressBar.className = 'ss-progress';
        container.appendChild(progressBar);

        // Bind scroll events
        container.addEventListener('scroll', onScroll, { passive: true });

        // Bind nav dot clicks
        navDots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                var index = parseInt(this.getAttribute('data-index'), 10);
                if (sections[index]) {
                    sections[index].scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        // Keyboard navigation
        document.addEventListener('keydown', onKeyDown);

        // Initial check
        onScroll();
    }

    /**
     * Scroll handler - triggers parallax and reveal animations.
     */
    function onScroll() {
        if (ticking) return;
        ticking = true;

        requestAnimationFrame(function () {
            updateParallax();
            updateActiveSection();
            updateProgress();
            ticking = false;
        });
    }

    /**
     * Parallax: Shift background layer based on scroll position within each section.
     */
    function updateParallax() {
        var scrollTop = container.scrollTop;
        var viewH = container.clientHeight;

        // Skip parallax on small screens
        if (window.innerWidth <= 768) return;

        sections.forEach(function (section) {
            var bg = section.querySelector('.ss-bg');
            if (!bg) return;

            var speed = parseFloat(section.getAttribute('data-parallax-speed')) || 0.3;
            var rect = section.getBoundingClientRect();
            var sectionTop = section.offsetTop;
            var offset = scrollTop - sectionTop;

            // Only apply parallax when section is near the viewport
            if (rect.bottom < -viewH || rect.top > viewH * 2) return;

            var translate = offset * speed * 0.5;
            bg.style.transform = 'translate3d(0,' + translate + 'px,0)';
        });
    }

    /**
     * Determine active section and trigger content reveal animations.
     */
    function updateActiveSection() {
        var scrollTop = container.scrollTop;
        var viewH = container.clientHeight;
        var activeIndex = 0;

        sections.forEach(function (section, i) {
            var sectionTop = section.offsetTop;
            var sectionMid = sectionTop + viewH / 2;

            // Determine active section (whichever section's midpoint we've scrolled past)
            if (scrollTop + viewH / 2 >= sectionTop) {
                activeIndex = i;
            }

            // Reveal animation when section enters viewport
            var anim = section.querySelector('.ss-anim');
            if (!anim) return;

            var triggerPoint = sectionTop - viewH * 0.75;
            var exitPoint = sectionTop + viewH;

            if (scrollTop >= triggerPoint && scrollTop < exitPoint) {
                anim.classList.add('ss-visible');
            } else {
                anim.classList.remove('ss-visible');
            }
        });

        // Update nav dots
        navDots.forEach(function (dot, i) {
            dot.classList.toggle('ss-nav-active', i === activeIndex);
        });
    }

    /**
     * Update progress bar.
     */
    function updateProgress() {
        var scrollTop = container.scrollTop;
        var maxScroll = container.scrollHeight - container.clientHeight;
        if (maxScroll <= 0) return;

        var pct = (scrollTop / maxScroll) * 100;
        progressBar.style.width = pct + '%';
    }

    /**
     * Keyboard navigation: arrow keys and page up/down.
     */
    function onKeyDown(e) {
        if (!container) return;

        // Don't hijack if user is typing in an input
        var tag = (e.target || e.srcElement).tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;

        var current = getCurrentSectionIndex();

        if (e.key === 'ArrowDown' || e.key === 'PageDown') {
            e.preventDefault();
            goToSection(current + 1);
        } else if (e.key === 'ArrowUp' || e.key === 'PageUp') {
            e.preventDefault();
            goToSection(current - 1);
        } else if (e.key === 'Home') {
            e.preventDefault();
            goToSection(0);
        } else if (e.key === 'End') {
            e.preventDefault();
            goToSection(sections.length - 1);
        }
    }

    function getCurrentSectionIndex() {
        var scrollTop = container.scrollTop;
        var viewH = container.clientHeight;
        var best = 0;

        sections.forEach(function (section, i) {
            if (scrollTop + viewH / 2 >= section.offsetTop) {
                best = i;
            }
        });

        return best;
    }

    function goToSection(index) {
        if (index < 0) index = 0;
        if (index >= sections.length) index = sections.length - 1;
        sections[index].scrollIntoView({ behavior: 'smooth' });
    }

    // Boot
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
