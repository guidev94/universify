(function () {
  'use strict';

  var hasGsap = typeof gsap !== 'undefined';
  if (hasGsap && typeof ScrollTrigger !== 'undefined') {
    gsap.registerPlugin(ScrollTrigger);
  }

  var EASE_FLUID = 'power3.out';
  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ═══════════════ HERO ENTRANCE ═══════════════
  function initHeroAnimation() {
    var heroEls = document.querySelectorAll('.hero-eyebrow, .hero-word, .hero-sub, .hero-ctas');

    if (reducedMotion) {
      heroEls.forEach(function (el) {
        el.style.opacity = '1';
        el.style.transform = 'none';
      });
      return;
    }

    var tl = gsap.timeline({ defaults: { ease: EASE_FLUID } });

    gsap.set('.hero-eyebrow', { opacity: 0, y: 20 });
    gsap.set('.hero-word', { opacity: 0, y: 50, rotateX: 20 });
    gsap.set('.hero-sub', { opacity: 0, y: 30 });
    gsap.set('.hero-ctas', { opacity: 0, y: 20 });

    tl.to('.hero-eyebrow', { opacity: 1, y: 0, duration: 0.8 }, 0.3)
      .to('.hero-word', { opacity: 1, y: 0, rotateX: 0, duration: 0.9, stagger: 0.08 }, 0.5)
      .to('.hero-sub', { opacity: 1, y: 0, duration: 0.8 }, 1.2)
      .to('.hero-ctas', { opacity: 1, y: 0, duration: 0.6 }, 1.5);
  }

  // ═══════════════ SCROLL REVEALS ═══════════════
  function revealElements(selector, options) {
    var defaults = { y: 40, x: 0, scale: 1, stagger: 0.1, duration: 0.8, start: 'top 90%' };
    var opts = Object.assign({}, defaults, options);

    document.querySelectorAll(selector).forEach(function (el, i) {
      gsap.set(el, { opacity: 0, y: opts.y, x: opts.x, scale: opts.scale });

      ScrollTrigger.create({
        trigger: el,
        start: opts.start,
        once: true,
        onEnter: function () {
          gsap.to(el, {
            opacity: 1, y: 0, x: 0, scale: 1,
            duration: opts.duration,
            delay: i * opts.stagger,
            ease: EASE_FLUID
          });
        }
      });
    });
  }

  function initScrollReveals() {
    if (reducedMotion) return;

    revealElements('.stat-card', { y: 30, stagger: 0.08 });
    revealElements('.service-card', { y: 40, stagger: 0.12, duration: 0.9 });
    revealElements('.how-step', { y: 35, stagger: 0.1 });
    revealElements('.about-text', { x: -30, y: 0, stagger: 0.08 });
    revealElements('.about-badge', { y: 20, scale: 0.95, stagger: 0.06 });
    revealElements('.coverage-region', { y: 30, stagger: 0.08 });
    revealElements('.pricing-card', { y: 30, scale: 0.98, stagger: 0 });
    revealElements('.faq-item', { y: 25, stagger: 0.06 });
    revealElements('.section-header', { y: 25, stagger: 0 });
    revealElements('.form-field', { y: 20, stagger: 0.05, duration: 0.6 });
  }

  // ═══════════════ FALLBACK VISIBILITY ═══════════════
  function initFallbackVisibility() {
    setTimeout(function () {
      var selectors = '.stat-card, .service-card, .how-step, .about-text, .about-badge, .coverage-region, .pricing-card, .faq-item, .section-header, .form-field';
      document.querySelectorAll(selectors).forEach(function (el) {
        if (parseFloat(getComputedStyle(el).opacity) < 0.1) {
          el.style.opacity = '1';
          el.style.transform = 'none';
        }
      });
    }, 4000);
  }

  // ═══════════════ COUNTERS ═══════════════
  function initCounters() {
    document.querySelectorAll('[data-count]').forEach(function (counter) {
      var target = parseInt(counter.dataset.count, 10);
      ScrollTrigger.create({
        trigger: counter,
        start: 'top 90%',
        once: true,
        onEnter: function () {
          gsap.to(counter, {
            innerText: target,
            duration: 2,
            ease: 'power2.out',
            snap: { innerText: 1 },
            onUpdate: function () {
              var val = Math.round(parseFloat(counter.innerText));
              counter.innerText = val >= 1000 ? val.toLocaleString('pt-BR') : val;
            }
          });
        }
      });
    });
  }

  // ═══════════════ NAVBAR ═══════════════
  function initNavbar() {
    var navbar = document.getElementById('navbar');
    if (!navbar) return;
    var lastScroll = 0;
    var ticking = false;

    function onScroll() {
      var currentScroll = window.scrollY;
      if (currentScroll > 100) {
        if (currentScroll > lastScroll && currentScroll > 200) {
          navbar.style.transform = 'translateY(-120%)';
        } else {
          navbar.style.transform = 'translateY(0)';
        }
      } else {
        navbar.style.transform = 'translateY(0)';
      }
      lastScroll = currentScroll;
      ticking = false;
    }

    window.addEventListener('scroll', function () {
      if (!ticking) {
        requestAnimationFrame(onScroll);
        ticking = true;
      }
    }, { passive: true });
  }

  // ═══════════════ MOBILE MENU ═══════════════
  function initMobileMenu() {
    var toggle = document.getElementById('menu-toggle');
    var menu = document.getElementById('mobile-menu');
    var close = document.getElementById('menu-close');
    if (!toggle || !menu) return;

    var links = menu.querySelectorAll('.mobile-nav-link');
    var lines = toggle.querySelectorAll('.menu-line');

    lines[0].style.transform = 'translateY(-4px)';
    lines[1].style.transform = 'translateY(4px)';

    function openMenu() {
      menu.style.pointerEvents = 'auto';
      menu.style.opacity = '1';
      document.body.style.overflow = 'hidden';
      lines[0].style.transform = 'rotate(45deg)';
      lines[1].style.transform = 'rotate(-45deg)';

      links.forEach(function (link, i) {
        setTimeout(function () {
          link.style.transition = 'all 0.5s cubic-bezier(0.32, 0.72, 0, 1)';
          link.style.transform = 'translateY(0)';
          link.style.opacity = '1';
        }, 100 + i * 70);
      });
    }

    function closeMenu() {
      menu.style.opacity = '0';
      document.body.style.overflow = '';
      lines[0].style.transform = 'translateY(-4px)';
      lines[1].style.transform = 'translateY(4px)';

      links.forEach(function (link) {
        link.style.transform = 'translateY(32px)';
        link.style.opacity = '0';
      });

      setTimeout(function () { menu.style.pointerEvents = 'none'; }, 500);
    }

    toggle.addEventListener('click', openMenu);
    if (close) close.addEventListener('click', closeMenu);
    links.forEach(function (link) { link.addEventListener('click', closeMenu); });
  }

  // ═══════════════ SMOOTH SCROLL ═══════════════
  function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
      anchor.addEventListener('click', function (e) {
        var target = document.querySelector(this.getAttribute('href'));
        if (target) {
          e.preventDefault();
          var top = target.getBoundingClientRect().top + window.scrollY - 80;
          window.scrollTo({ top: top, behavior: 'smooth' });
        }
      });
    });
  }

  // ═══════════════ BACK TO TOP ═══════════════
  function initBackToTop() {
    var btn = document.getElementById('back-to-top');
    if (!btn) return;

    window.addEventListener('scroll', function () {
      if (window.scrollY > window.innerHeight * 0.5) {
        btn.style.opacity = '1';
        btn.style.pointerEvents = 'auto';
        btn.style.transform = 'translateY(0)';
      } else {
        btn.style.opacity = '0';
        btn.style.pointerEvents = 'none';
        btn.style.transform = 'translateY(16px)';
      }
    }, { passive: true });

    btn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // ═══════════════ FORM VALIDATION + WHATSAPP ═══════════════
  function initForm() {
    var forms = document.querySelectorAll('[data-form-whatsapp]');

    forms.forEach(function (form) {
      if (form.tagName !== 'FORM') return;

      var phoneInputs = form.querySelectorAll('input[type="tel"]');
      var allInputs = form.querySelectorAll('input, select, textarea');

      // Phone mask
      phoneInputs.forEach(function (input) {
        input.addEventListener('input', function () {
          var value = this.value.replace(/\D/g, '');
          if (value.length > 11) value = value.slice(0, 11);
          if (value.length > 6) {
            this.value = '(' + value.slice(0, 2) + ') ' + value.slice(2, 7) + '-' + value.slice(7);
          } else if (value.length > 2) {
            this.value = '(' + value.slice(0, 2) + ') ' + value.slice(2);
          } else if (value.length > 0) {
            this.value = '(' + value;
          }
        });
      });

      // Real-time validation on blur
      allInputs.forEach(function (input) {
        input.addEventListener('blur', function () { validateField(input); });
        input.addEventListener('input', function () {
          if (input.classList.contains('border-emergency')) validateField(input);
        });
      });

      function validateField(input) {
        var errorEl = input.parentElement.querySelector('.field-error');
        var isValid = true;

        if (input.required && !input.value.trim()) {
          isValid = false;
        } else if (input.type === 'email' && input.value) {
          isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value);
        } else if (input.type === 'tel' && input.value) {
          var digits = input.value.replace(/\D/g, '');
          isValid = digits.length >= 10 && digits.length <= 11;
        } else if (input.minLength && input.value.length > 0 && input.value.length < input.minLength) {
          isValid = false;
        } else if (input.tagName === 'SELECT' && input.required && !input.value) {
          isValid = false;
        }

        if (isValid) {
          input.classList.remove('border-emergency', 'ring-emergency/20');
          input.classList.add('border-primary', 'ring-primary/20');
          if (errorEl) errorEl.classList.add('hidden');
        } else {
          input.classList.remove('border-primary', 'ring-primary/20');
          input.classList.add('border-emergency', 'ring-emergency/20');
          if (errorEl) errorEl.classList.remove('hidden');
        }

        return isValid;
      }

      // Submit handler
      form.addEventListener('submit', function (e) {
        e.preventDefault();

        var allValid = true;
        form.querySelectorAll('input[required], select[required]').forEach(function (input) {
          if (!validateField(input)) allValid = false;
        });

        if (!allValid) return;

        var formData = new FormData(form);
        var parts = ['Olá! Gostaria de solicitar um orçamento.\n'];

        formData.forEach(function (value, key) {
          if (value.trim()) {
            parts.push(key.charAt(0).toUpperCase() + key.slice(1) + ': ' + value);
          }
        });

        var message = encodeURIComponent(parts.join('\n'));

        // Open WhatsApp immediately
        window.open('https://wa.me/5511971674912?text=' + message, '_blank');

        // Send copy via AJAX (email + save to admin)
        if (typeof betel_ajax !== 'undefined' && betel_ajax.url) {
          var ajaxData = new URLSearchParams();
          ajaxData.append('action', 'betel_form_ajax');
          ajaxData.append('pagina', window.location.pathname);
          formData.forEach(function(value, key) { ajaxData.append(key, value); });
          fetch(betel_ajax.url, { method: 'POST', body: ajaxData }).catch(function(){});
        }

        if (window.dataLayer) {
          window.dataLayer.push({ event: 'form_submit', form_name: form.id || 'orcamento' });
        }
      });
    });
  }

  // ═══════════════ FAQ ACCORDION ═══════════════
  function initFAQ() {
    document.querySelectorAll('.faq-item').forEach(function (item) {
      var trigger = item.querySelector('.faq-trigger');
      var content = item.querySelector('.faq-content');
      var icon = item.querySelector('.faq-icon');
      if (!trigger || !content) return;

      trigger.addEventListener('click', function () {
        var isOpen = content.style.maxHeight && content.style.maxHeight !== '0px';

        if (isOpen) {
          content.style.maxHeight = '0px';
          content.style.opacity = '0';
          if (icon) icon.style.transform = 'rotate(0deg)';
        } else {
          content.style.maxHeight = content.scrollHeight + 'px';
          content.style.opacity = '1';
          if (icon) icon.style.transform = 'rotate(45deg)';
        }
      });
    });
  }

  // ═══════════════ COOKIES ═══════════════
  function initCookies() {
    var banner = document.getElementById('cookie-banner');
    var accept = document.getElementById('cookie-accept');
    if (!banner || localStorage.getItem('cookies_accepted')) return;

    setTimeout(function () { banner.style.transform = 'translateY(0)'; }, 2500);

    accept.addEventListener('click', function () {
      localStorage.setItem('cookies_accepted', 'true');
      banner.style.transform = 'translateY(100%)';
    });
  }

  // ═══════════════ PARALLAX ═══════════════
  function initParallax() {
    if (reducedMotion) return;
    var heroVideo = document.querySelector('.hero-video-wrap');
    if (heroVideo) {
      gsap.to(heroVideo, {
        scrollTrigger: { trigger: '#inicio', start: 'top top', end: 'bottom top', scrub: true },
        y: 60,
        ease: 'none'
      });
    }
  }

  // ═══════════════ MAGNETIC BUTTONS (throttled with rAF) ═══════════════
  function initMagneticButtons() {
    if (reducedMotion || window.innerWidth < 768) return;

    document.querySelectorAll('[data-magnetic]').forEach(function (btn) {
      var rafId = null;

      btn.addEventListener('mousemove', function (e) {
        if (rafId) return;
        rafId = requestAnimationFrame(function () {
          var rect = btn.getBoundingClientRect();
          var x = e.clientX - rect.left - rect.width / 2;
          var y = e.clientY - rect.top - rect.height / 2;
          btn.style.transform = 'translate(' + (x / rect.width) * 4 + 'px, ' + (y / rect.height) * 4 + 'px)';
          rafId = null;
        });
      });

      btn.addEventListener('mouseleave', function () {
        if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
        btn.style.transform = '';
      });
    });
  }

  // ═══════════════ ACTIVE NAV ═══════════════
  function initActiveNav() {
    var sections = document.querySelectorAll('section[id]');
    var navLinks = document.querySelectorAll('.nav-link');

    sections.forEach(function (section) {
      ScrollTrigger.create({
        trigger: section,
        start: 'top center',
        end: 'bottom center',
        onEnter: function () { setActive(section.id); },
        onEnterBack: function () { setActive(section.id); }
      });
    });

    function setActive(id) {
      navLinks.forEach(function (link) {
        var isActive = link.getAttribute('href') === '#' + id;
        link.classList.toggle('text-primary', isActive);
        link.classList.toggle('text-muted', !isActive);
      });
    }
  }

  // ═══════════════ INIT ═══════════════
  document.addEventListener('DOMContentLoaded', function () {
    // GSAP-dependent (only run if GSAP loaded — front page)
    if (hasGsap) {
      initHeroAnimation();
      initScrollReveals();
      initCounters();
      initParallax();
      initMagneticButtons();
    }
    initFallbackVisibility();
    initNavbar();
    initMobileMenu();
    initSmoothScroll();
    initBackToTop();
    initForm();
    initFAQ();
    initCookies();
    initActiveNav();

    window.addEventListener('load', function () {
      ScrollTrigger.refresh();
    });
  });

})();
