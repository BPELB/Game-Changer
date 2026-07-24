// =============================================
// GAME CHANGER — app.js
// =============================================

// --- Light / dark theme toggle ---
const themeToggle = document.getElementById('themeToggle');
const themeOptions = themeToggle.querySelectorAll('.theme-toggle-option');
const root = document.documentElement;

function currentTheme() {
  return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
}

function setTheme(theme) {
  if (theme === 'dark') {
    root.setAttribute('data-theme', 'dark');
  } else {
    root.removeAttribute('data-theme');
  }
  themeOptions.forEach(btn => {
    btn.setAttribute('aria-pressed', btn.dataset.themeOption === theme);
  });
}

setTheme(currentTheme());

themeOptions.forEach(btn => {
  btn.addEventListener('click', () => {
    const next = btn.dataset.themeOption;
    setTheme(next);
    localStorage.setItem('gc-theme', next);
  });
});

// --- Sticky nav shadow on scroll ---
const nav = document.getElementById('nav');
window.addEventListener('scroll', () => {
  nav.classList.toggle('scrolled', window.scrollY > 20);
}, { passive: true });

// --- Mobile hamburger menu ---
const hamburger = document.getElementById('hamburger');
const mobileMenu = document.getElementById('mobileMenu');

hamburger.addEventListener('click', () => {
  mobileMenu.classList.toggle('open');
});

// Close mobile menu on link click
mobileMenu.querySelectorAll('a').forEach(link => {
  link.addEventListener('click', () => mobileMenu.classList.remove('open'));
});

// --- Scroll-triggered fade-in animations ---
const observer = new IntersectionObserver(
  (entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.12 }
);

// Apply fade-in to key elements
const animateTargets = [
  '.problem-card',
  '.why-card',
  '.edition-card',
  '.reason',
  '.value-item',
  '.solution-text',
  '.solution-visual',
  '.contact-left',
  '.contact-form',
  '.hero-stats .stat',
];

animateTargets.forEach(selector => {
  document.querySelectorAll(selector).forEach((el, i) => {
    el.classList.add('fade-in');
    el.style.transitionDelay = `${i * 0.08}s`;
    observer.observe(el);
  });
});

// --- Contact form handler ---
function handleSubmit(e) {
  e.preventDefault();
  const note = document.getElementById('formNote');
  const btn = e.target.querySelector('button[type="submit"]');

  btn.textContent = 'Sending...';
  btn.disabled = true;

  // Simulate async submission (replace with real endpoint)
  setTimeout(() => {
    note.textContent = '♥ Thank you! Brandon will be in touch soon.';
    note.style.color = '#ffffff';
    e.target.reset();
    btn.textContent = 'Send Message ♥';
    btn.disabled = false;
  }, 1200);
}

// --- Smooth active link highlighting on scroll ---
const sections = document.querySelectorAll('section[id]');
const navAnchors = document.querySelectorAll('.nav-links a[href^="#"]');

window.addEventListener('scroll', () => {
  let current = '';
  sections.forEach(section => {
    if (window.scrollY >= section.offsetTop - 120) {
      current = section.getAttribute('id');
    }
  });

  navAnchors.forEach(a => {
    a.style.color = a.getAttribute('href') === `#${current}`
      ? 'var(--accent-text)'
      : '';
  });
}, { passive: true });
