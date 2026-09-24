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