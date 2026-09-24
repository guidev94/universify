document.addEventListener('DOMContentLoaded', function() {

    // 1. BARRA DE COOKIES
        const privacyBar = document.querySelector('.privacy-bar-clean');
        const acceptBtn = document.getElementById('btn-accept-privacy');
        const jaAceitou = localStorage.getItem('privacyAccepted');

        if (!jaAceitou) {
            setTimeout(() => {
                if(privacyBar) privacyBar.classList.add('show-bar');
            }, 1000); 
        }

        if(acceptBtn && privacyBar) {
            acceptBtn.addEventListener('click', function() {
                privacyBar.classList.remove('show-bar');
                localStorage.setItem('privacyAccepted', 'true');
            });
        }

    // 2. MENU MOBILE
        const menuToggle = document.getElementById('mobile-menu-toggle');
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                document.body.classList.toggle('menu-aberto');
                document.body.style.overflow = document.body.classList.contains('menu-aberto') ? 'hidden' : '';
            });
        }

    // 3. HERO SLIDER
        const slides = document.querySelectorAll('.hero-slide');
        const nextBtn = document.getElementById('nextSlide');
        const prevBtn = document.getElementById('prevSlide');
        let currentSlide = 0;
        const slideInterval = 5000; 
        let autoSlide;

        if (slides.length > 0) {
            function showSlide(index) {
                slides.forEach(slide => slide.classList.remove('active'));
                if (index >= slides.length) currentSlide = 0;
                else if (index < 0) currentSlide = slides.length - 1;
                else currentSlide = index;
                slides[currentSlide].classList.add('active');
            }
            function nextSlide() { showSlide(currentSlide + 1); }
            function prevSlide() { showSlide(currentSlide - 1); }

            if(nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); resetTimer(); });
            if(prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); resetTimer(); });

            function startTimer() { autoSlide = setInterval(nextSlide, slideInterval); }
            function resetTimer() { clearInterval(autoSlide); startTimer(); }
            startTimer();
        }

    // 4. LOGOS ASSOCIADAS
        const bancoLogos = document.getElementById('banco-de-logos');
        const track1 = document.getElementById('track-linha-1');
        const track2 = document.getElementById('track-linha-2');

        if(bancoLogos && track1 && track2) {
            let imagens = Array.from(bancoLogos.querySelectorAll('img'));
            for (let i = imagens.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [imagens[i], imagens[j]] = [imagens[j], imagens[i]];
            }
            const meio = Math.ceil(imagens.length / 2);
            const grupo1 = imagens.slice(0, meio);
            const grupo2 = imagens.slice(meio);

            function criarSlide(imgElement) {
                const div = document.createElement('div');
                div.className = 'slide-logo';
                div.appendChild(imgElement.cloneNode(true)); 
                return div;
            }

            grupo1.forEach(img => track1.appendChild(criarSlide(img)));
            grupo1.forEach(img => track1.appendChild(criarSlide(img))); 

            grupo2.forEach(img => track2.appendChild(criarSlide(img)));
            grupo2.forEach(img => track2.appendChild(criarSlide(img))); 
        }
    });

document.addEventListener('DOMContentLoaded', function() {

        // 1. BARRA DE COOKIES
            const privacyBar = document.querySelector('.privacy-bar-clean');
            const acceptBtn = document.getElementById('btn-accept-privacy');
            const jaAceitou = localStorage.getItem('privacyAccepted');

            if (!jaAceitou) {
                setTimeout(() => {
                    if(privacyBar) privacyBar.classList.add('show-bar');
                }, 1000); 
            }

            if(acceptBtn && privacyBar) {
                acceptBtn.addEventListener('click', function() {
                    privacyBar.classList.remove('show-bar');
                    localStorage.setItem('privacyAccepted', 'true');
                });
            }

        // 2. MENU MOBILE
            const menuToggle = document.getElementById('mobile-menu-toggle');
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    document.body.classList.toggle('menu-aberto');
                    document.body.style.overflow = document.body.classList.contains('menu-aberto') ? 'hidden' : '';
                });
            }

        // 3. HERO SLIDER
            const slides = document.querySelectorAll('.hero-slide');
            const nextBtn = document.getElementById('nextSlide');
            const prevBtn = document.getElementById('prevSlide');
            let currentSlide = 0;
            const slideInterval = 5000; 
            let autoSlide;

            if (slides.length > 0) {
                function showSlide(index) {
                    slides.forEach(slide => slide.classList.remove('active'));
                    if (index >= slides.length) currentSlide = 0;
                    else if (index < 0) currentSlide = slides.length - 1;
                    else currentSlide = index;
                    slides[currentSlide].classList.add('active');
                }
                function nextSlide() { showSlide(currentSlide + 1); }
                function prevSlide() { showSlide(currentSlide - 1); }

                if(nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); resetTimer(); });
                if(prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); resetTimer(); });

                function startTimer() { autoSlide = setInterval(nextSlide, slideInterval); }
                function resetTimer() { clearInterval(autoSlide); startTimer(); }
                startTimer();
            }

        // 4. LOGOS ASSOCIADAS
            const bancoLogos = document.getElementById('banco-de-logos');
            const track1 = document.getElementById('track-linha-1');
            const track2 = document.getElementById('track-linha-2');

            if(bancoLogos && track1 && track2) {
                let imagens = Array.from(bancoLogos.querySelectorAll('img'));
                for (let i = imagens.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [imagens[i], imagens[j]] = [imagens[j], imagens[i]];
                }
                const meio = Math.ceil(imagens.length / 2);
                const grupo1 = imagens.slice(0, meio);
                const grupo2 = imagens.slice(meio);

                function criarSlide(imgElement) {
                    const div = document.createElement('div');
                    div.className = 'slide-logo';
                    div.appendChild(imgElement.cloneNode(true)); 
                    return div;
                }

                grupo1.forEach(img => track1.appendChild(criarSlide(img)));
                grupo1.forEach(img => track1.appendChild(criarSlide(img))); 

                grupo2.forEach(img => track2.appendChild(criarSlide(img)));
                grupo2.forEach(img => track2.appendChild(criarSlide(img))); 
            }
        });

document.addEventListener('DOMContentLoaded', function() {

        // 1. BARRA DE COOKIES
        const privacyBar = document.querySelector('.privacy-bar-clean');
        const acceptBtn = document.getElementById('btn-accept-privacy');
        const jaAceitou = localStorage.getItem('privacyAccepted');

        if (!jaAceitou) {
            setTimeout(() => {
                if(privacyBar) privacyBar.classList.add('show-bar');
            }, 1000); 
        }

        if(acceptBtn && privacyBar) {
            acceptBtn.addEventListener('click', function() {
                privacyBar.classList.remove('show-bar');
                localStorage.setItem('privacyAccepted', 'true');
            });
        }

        // 2. MENU MOBILE
        const menuToggle = document.getElementById('mobile-menu-toggle');
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                document.body.classList.toggle('menu-aberto');
                document.body.style.overflow = document.body.classList.contains('menu-aberto') ? 'hidden' : '';
            });
        }

        // 3. HERO SLIDER
        const slides = document.querySelectorAll('.hero-slide');
        const nextBtn = document.getElementById('nextSlide');
        const prevBtn = document.getElementById('prevSlide');
        let currentSlide = 0;
        const slideInterval = 5000; 
        let autoSlide;

        if (slides.length > 0) {
            function showSlide(index) {
                slides.forEach(slide => slide.classList.remove('active'));
                if (index >= slides.length) currentSlide = 0;
                else if (index < 0) currentSlide = slides.length - 1;
                else currentSlide = index;
                slides[currentSlide].classList.add('active');
            }
            function nextSlide() { showSlide(currentSlide + 1); }
            function prevSlide() { showSlide(currentSlide - 1); }

            if(nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); resetTimer(); });
            if(prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); resetTimer(); });

            function startTimer() { autoSlide = setInterval(nextSlide, slideInterval); }
            function resetTimer() { clearInterval(autoSlide); startTimer(); }
            startTimer();
        }

        // 4. LOGOS ASSOCIADAS
        const bancoLogos = document.getElementById('banco-de-logos');
        const track1 = document.getElementById('track-linha-1');
        const track2 = document.getElementById('track-linha-2');

        if(bancoLogos && track1 && track2) {
            let imagens = Array.from(bancoLogos.querySelectorAll('img'));
            for (let i = imagens.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [imagens[i], imagens[j]] = [imagens[j], imagens[i]];
            }
            const meio = Math.ceil(imagens.length / 2);
            const grupo1 = imagens.slice(0, meio);
            const grupo2 = imagens.slice(meio);

            function criarSlide(imgElement) {
                const div = document.createElement('div');
                div.className = 'slide-logo';
                div.appendChild(imgElement.cloneNode(true)); 
                return div;
            }

            grupo1.forEach(img => track1.appendChild(criarSlide(img)));
            grupo1.forEach(img => track1.appendChild(criarSlide(img))); 

            grupo2.forEach(img => track2.appendChild(criarSlide(img)));
            grupo2.forEach(img => track2.appendChild(criarSlide(img))); 
        }
    });

/* SIAMFESP - ajustes responsivos finais do frontend */
document.addEventListener('DOMContentLoaded', function () {
    var menuToggle = document.getElementById('mobile-menu-toggle');
    var menuClose = document.getElementById('mobile-menu-close');
    var menuLinks = document.querySelectorAll('.main-menu a');

    function closeMobileMenu() {
        document.body.classList.remove('menu-aberto');
        document.body.style.overflow = '';
        resetMobileSubmenus();
    }

    if (menuClose) {
        menuClose.addEventListener('click', closeMobileMenu);
    }

    function resetMobileSubmenus() {
        document.querySelectorAll('.menu-item-has-children.is-open').forEach(function (item) {
            item.classList.remove('is-open');
            var toggle = item.querySelector('.mobile-submenu-toggle');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    document.querySelectorAll('.mobile-submenu-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (window.innerWidth > 1024) {
                return;
            }

            var item = toggle.closest('.menu-item-has-children');
            if (!item) {
                return;
            }

            var willOpen = !item.classList.contains('is-open');

            document.querySelectorAll('.main-menu > ul > .menu-item-has-children.is-open').forEach(function (openItem) {
                if (openItem !== item) {
                    openItem.classList.remove('is-open');
                    var openToggle = openItem.querySelector('.mobile-submenu-toggle');
                    if (openToggle) {
                        openToggle.setAttribute('aria-expanded', 'false');
                    }
                }
            });

            item.classList.toggle('is-open', willOpen);
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
    });

    menuLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 1024 && !link.parentElement.querySelector('ul')) {
                closeMobileMenu();
            }
        });
    });

    document.querySelectorAll('.mobile-menu-social a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 1024) {
                closeMobileMenu();
            }
        });
    });

    document.querySelectorAll('.mobile-menu-logo').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 1024) {
                closeMobileMenu();
            }
        });
    });

    document.addEventListener('keyup', function (event) {
        if (event.key === 'Escape') {
            closeMobileMenu();
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 1024) {
            closeMobileMenu();
        }
    });

    /*
     * Garante que iframes/vídeos inseridos pelo CMS fiquem responsivos.
     */
    document.querySelectorAll('.article-content iframe, .article-content video, .content-page-section iframe, .content-page-section video').forEach(function (el) {
        if (!el.parentElement.classList.contains('siamfesp-responsive-media')) {
            var wrap = document.createElement('div');
            wrap.className = 'siamfesp-responsive-media';
            el.parentNode.insertBefore(wrap, el);
            wrap.appendChild(el);
        }
    });
});


/* SIAMFESP - hotfix mobile cookies */
document.addEventListener('DOMContentLoaded', function () {
    function syncCookieState() {
        var visibleCookie = document.querySelector(
            '.lgpd-cookie-banner.is-visible, .privacy-bar-clean.show-bar, .cookie-banner.is-visible, .cookie-consent.is-visible'
        );

        document.body.classList.toggle('lgpd-open', !!visibleCookie);
        document.body.classList.toggle('cookie-open', !!visibleCookie);
    }

    syncCookieState();
    window.setInterval(syncCookieState, 500);

    document.addEventListener('click', function (event) {
        if (event.target.closest('.lgpd-cookie-actions button, .privacy-actions button, .cookie-actions button, .privacy-bar-clean button')) {
            window.setTimeout(syncCookieState, 250);
            window.setTimeout(syncCookieState, 800);
        }
    });
});
