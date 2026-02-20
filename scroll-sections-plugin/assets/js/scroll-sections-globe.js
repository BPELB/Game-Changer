/**
 * Scroll Sections — 3D Globe Background
 *
 * Three.js wireframe icosahedron with scroll-driven rotation,
 * particle ring, per-section color transitions, and camera movement.
 *
 * Requires Three.js r128+.
 */
(function () {
    'use strict';

    if (typeof THREE === 'undefined') return;

    /* ---- Reduced motion ---- */
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    /* ---- DOM refs ---- */
    var wrap = document.getElementById('ss-wrap');
    if (!wrap) return;
    if (wrap.getAttribute('data-globe') === 'no') return;

    var canvas = document.getElementById('ss-globe-canvas');
    if (!canvas) return;

    /* ==================================================================
       Renderer / Scene / Camera
       ================================================================== */

    var renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

    var scene  = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(55, window.innerWidth / window.innerHeight, 0.1, 100);
    camera.position.z = 5;

    /* ==================================================================
       Geometry — Wireframe Icosahedron (Globe)
       ================================================================== */

    var defaultHex = 0xc8ff00;

    // Read the first section's globe color as the initial color
    var firstSec = wrap.querySelector('.ss-sec[data-globe-color]');
    if (firstSec) {
        var fc = firstSec.getAttribute('data-globe-color');
        if (fc) defaultHex = fc;
    }

    var geo = new THREE.IcosahedronGeometry(1.8, 2);
    var mat = new THREE.MeshStandardMaterial({
        color: defaultHex,
        wireframe: true,
        transparent: true,
        opacity: 0.7
    });
    var mesh = new THREE.Mesh(geo, mat);
    scene.add(mesh);

    // Inner solid mesh for glow
    var innerMat = new THREE.MeshStandardMaterial({
        color: defaultHex,
        transparent: true,
        opacity: 0.18,
        emissive: defaultHex,
        emissiveIntensity: 0.8
    });
    var innerMesh = new THREE.Mesh(new THREE.IcosahedronGeometry(1.5, 2), innerMat);
    scene.add(innerMesh);

    /* ==================================================================
       Particle Ring
       ================================================================== */

    var particleCount = 200;
    var particleGeo   = new THREE.BufferGeometry();
    var positions     = new Float32Array(particleCount * 3);

    for (var i = 0; i < particleCount; i++) {
        var angle  = (i / particleCount) * Math.PI * 2;
        var radius = 2.5 + (Math.random() - 0.5) * 0.8;
        positions[i * 3]     = Math.cos(angle) * radius;
        positions[i * 3 + 1] = (Math.random() - 0.5) * 1.5;
        positions[i * 3 + 2] = Math.sin(angle) * radius;
    }

    particleGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));

    var particleMat = new THREE.PointsMaterial({
        color: defaultHex,
        size: 0.05,
        transparent: true,
        opacity: 0.8
    });
    var particles = new THREE.Points(particleGeo, particleMat);
    scene.add(particles);

    /* ==================================================================
       Lights
       ================================================================== */

    scene.add(new THREE.AmbientLight(0xffffff, 0.7));

    var pointLight = new THREE.PointLight(defaultHex, 3.0, 20);
    pointLight.position.set(3, 3, 3);
    scene.add(pointLight);

    var backLight = new THREE.PointLight(0x6366f1, 1.5, 15);
    backLight.position.set(-3, -2, -3);
    scene.add(backLight);

    /* ==================================================================
       Scroll State & Color Targets
       ================================================================== */

    var scrollY      = window.pageYOffset || 0;
    var targetColor  = new THREE.Color(defaultHex);
    var currentColor = new THREE.Color(defaultHex);
    var globeVisible = false;

    window.addEventListener('scroll', function () {
        scrollY = window.pageYOffset || document.documentElement.scrollTop;
    }, { passive: true });

    /* ==================================================================
       Section Observer — color transitions
       ================================================================== */

    var sections = wrap.querySelectorAll('.ss-sec');

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                var color = entry.target.getAttribute('data-globe-color');
                if (color) {
                    targetColor = new THREE.Color(color);
                }
            }
        });
    }, { threshold: 0.2 });

    sections.forEach(function (s) { observer.observe(s); });

    /* ==================================================================
       Visibility — show/hide + clip canvas to wrap bounds
       ================================================================== */

    var wrapObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            globeVisible = entry.isIntersecting;
            canvas.style.opacity = globeVisible ? '1' : '0';
        });
    }, { threshold: 0 });

    wrapObserver.observe(wrap);

    /* ==================================================================
       Animation Loop
       ================================================================== */

    function animate() {
        requestAnimationFrame(animate);

        // Skip rendering when not visible (performance)
        if (!globeVisible) return;

        var t = performance.now() * 0.001;

        // Calculate scroll progress relative to the wrapper
        var wrapRect    = wrap.getBoundingClientRect();
        var wrapTop     = scrollY + wrapRect.top;
        var wrapH       = wrap.offsetHeight;
        var wrapMax     = wrapH - window.innerHeight;
        var localScroll = scrollY - wrapTop;
        var scrollProgress = wrapMax > 0 ? Math.max(0, Math.min(1, localScroll / wrapMax)) : 0;

        // Clip canvas to wrap bounds so it doesn't bleed into header/footer
        var clipTop    = Math.max(0, wrapRect.top);
        var clipBottom = Math.max(0, window.innerHeight - wrapRect.bottom);
        canvas.style.clipPath = 'inset(' + clipTop + 'px 0 ' + clipBottom + 'px 0)';

        // --- Rotate based on scroll + time ---
        mesh.rotation.x = scrollProgress * Math.PI * 2 + t * 0.1;
        mesh.rotation.y = scrollProgress * Math.PI * 1.5 + t * 0.15;

        innerMesh.rotation.x = mesh.rotation.x * 0.8;
        innerMesh.rotation.y = mesh.rotation.y * 0.8;

        // --- Scale pulse ---
        var scale = 1 + Math.sin(t * 0.5) * 0.05 + scrollProgress * 0.3;
        mesh.scale.setScalar(scale);
        innerMesh.scale.setScalar(scale * 0.9);

        // --- Particles orbit ---
        particles.rotation.y = t * 0.08 + scrollProgress * Math.PI;
        particles.rotation.x = Math.sin(t * 0.1) * 0.2;

        // --- Color lerp ---
        currentColor.lerp(targetColor, 0.03);
        mat.color.copy(currentColor);
        innerMat.color.copy(currentColor);
        innerMat.emissive.copy(currentColor);
        particleMat.color.copy(currentColor);
        pointLight.color.copy(currentColor);

        // --- Wireframe opacity based on scroll ---
        mat.opacity      = 0.6 + scrollProgress * 0.3;
        innerMat.opacity  = 0.15 + scrollProgress * 0.15;

        // --- Camera subtle movement ---
        camera.position.x = Math.sin(t * 0.2) * 0.3;
        camera.position.y = Math.cos(t * 0.15) * 0.2 - scrollProgress * 1.5;
        camera.lookAt(0, -scrollProgress * 1.5, 0);

        renderer.render(scene, camera);
    }

    animate();

    /* ==================================================================
       Resize
       ================================================================== */

    window.addEventListener('resize', function () {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });

})();
