/* MSC Headhunters — Quiet Authority Animation Engine */

document.addEventListener('DOMContentLoaded', () => {

  // === CONTACT FORM SUCCESS MESSAGE ===
  if (new URLSearchParams(window.location.search).get('sent') === '1') {
    const msg = document.getElementById('formSuccess');
    if (msg) { msg.style.display = 'block'; window.scrollTo({top: msg.offsetTop - 120, behavior: 'smooth'}); }
  }

  // === NAV SCROLL EFFECT ===
  const header = document.querySelector('.site-header');
  if (header) {
    let ticking = false;
    window.addEventListener('scroll', () => {
      if (!ticking) {
        requestAnimationFrame(() => {
          header.classList.toggle('scrolled', window.scrollY > 60);
          ticking = false;
        });
        ticking = true;
      }
    });
  }

  // === MOBILE MENU ===
  const toggle = document.querySelector('.nav-mobile-toggle');
  const menu = document.querySelector('.nav-menu');
  if (toggle && menu) {
    toggle.addEventListener('click', () => {
      const isOpen = menu.classList.toggle('active');
      toggle.setAttribute('aria-expanded', isOpen);
      document.body.style.overflow = isOpen ? 'hidden' : '';
      // Animate hamburger
      toggle.classList.toggle('is-open', isOpen);
    });

    // Mobile dropdown toggles
    menu.querySelectorAll('.has-dropdown').forEach(li => {
      const trigger = li.querySelector('.nav-dropdown-trigger') || li.querySelector('.nav-link');
      if (trigger) {
        trigger.addEventListener('click', (e) => {
          if (window.innerWidth <= 768) {
            e.preventDefault();
            li.classList.toggle('dropdown-open');
          }
        });
      }
    });
  }

  // === SCROLL REVEAL ===
  const revealEls = document.querySelectorAll('.reveal, .stagger');
  if (revealEls.length) {
    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          revealObserver.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.08,
      rootMargin: '0px 0px -60px 0px'
    });
    revealEls.forEach(el => revealObserver.observe(el));
  }

  // === HERO TEXT REVEAL ===
  const heroHeadline = document.querySelector('.hero-headline');
  if (heroHeadline) {
    heroHeadline.style.opacity = '0';
    heroHeadline.style.transform = 'translateY(60px)';
    heroHeadline.style.transition = 'opacity 1.2s cubic-bezier(0.16, 1, 0.3, 1), transform 1.2s cubic-bezier(0.16, 1, 0.3, 1)';
    setTimeout(() => {
      heroHeadline.style.opacity = '1';
      heroHeadline.style.transform = 'translateY(0)';
    }, 300);
  }

  const heroBody = document.querySelector('.hero-body');
  if (heroBody) {
    heroBody.style.opacity = '0';
    heroBody.style.transform = 'translateY(40px)';
    heroBody.style.transition = 'opacity 1s cubic-bezier(0.16, 1, 0.3, 1) 0.5s, transform 1s cubic-bezier(0.16, 1, 0.3, 1) 0.5s';
    setTimeout(() => {
      heroBody.style.opacity = '1';
      heroBody.style.transform = 'translateY(0)';
    }, 100);
  }

  const heroCta = document.querySelector('.hero-cta-wrap');
  if (heroCta) {
    heroCta.style.opacity = '0';
    heroCta.style.transition = 'opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.9s';
    setTimeout(() => { heroCta.style.opacity = '1'; }, 100);
  }

  // === PARALLAX ON HERO ===
  const parallaxEls = document.querySelectorAll('[data-parallax]');
  if (parallaxEls.length) {
    let rafId;
    window.addEventListener('scroll', () => {
      if (rafId) cancelAnimationFrame(rafId);
      rafId = requestAnimationFrame(() => {
        const scrollY = window.scrollY;
        parallaxEls.forEach(el => {
          const rate = parseFloat(el.dataset.parallax) || 0.15;
          el.style.transform = `translateY(${scrollY * rate}px)`;
        });
      });
    });
  }

  // === COUNTER ANIMATION (eased) ===
  const counters = document.querySelectorAll('[data-count]');
  if (counters.length) {
    const easeOutQuart = t => 1 - Math.pow(1 - t, 4);
    const countObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const el = entry.target;
          const target = parseInt(el.dataset.count);
          const suffix = el.dataset.suffix || '';
          const prefix = el.dataset.prefix || '';
          const duration = 2000;
          const start = performance.now();
          const animate = (now) => {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const value = Math.round(easeOutQuart(progress) * target);
            el.textContent = prefix + value + suffix;
            if (progress < 1) requestAnimationFrame(animate);
          };
          requestAnimationFrame(animate);
          countObserver.unobserve(el);
        }
      });
    }, { threshold: 0.3 });
    counters.forEach(el => countObserver.observe(el));
  }

  // === FAQ ACCORDION ===
  document.querySelectorAll('.faq-question').forEach(btn => {
    btn.addEventListener('click', () => {
      const item = btn.closest('.faq-item');
      const wasActive = item.classList.contains('active');
      document.querySelectorAll('.faq-item.active').forEach(i => i.classList.remove('active'));
      if (!wasActive) item.classList.add('active');
    });
  });

  // === SMOOTH SCROLL ===
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', (e) => {
      const href = anchor.getAttribute('href');
      if (href === '#') return;
      const target = document.querySelector(href);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Close mobile menu if open
        if (menu && menu.classList.contains('active')) {
          menu.classList.remove('active');
          document.body.style.overflow = '';
        }
      }
    });
  });

});
