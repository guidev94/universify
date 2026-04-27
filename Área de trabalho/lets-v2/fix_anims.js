const fs = require('fs');
let index = fs.readFileSync('index.html', 'utf8');

// I also need to make sure the scroll trigger logic has no syntax errors
// One of the common issues is multiple event listeners throwing errors if elements are missing
// I'll wrap the whole GSAP in a try-catch to ensure one failure doesn't break everything else

index = index.replace(/let mm = gsap\.matchMedia\(\);[\s\S]*?\/\/ --- PARALLAX ---/g, `let mm = gsap.matchMedia();

        try {
            gsap.utils.toArray('.section-reveal').forEach(elem => {
                gsap.from(elem, {
                    scrollTrigger: { trigger: elem, start: "top 85%" },
                    y: 30, opacity: 0, duration: 0.8, ease: "power3.out"
                });
            });
        } catch(e) {}

        try {
            const marqueeContent = document.querySelector('.marquee-content');
            if(marqueeContent) {
                gsap.fromTo(marqueeContent, 
                    { xPercent: 0 },
                    {
                        xPercent: -50,
                        ease: "none",
                        duration: 30,
                        repeat: -1
                    }
                );
            }
        } catch(e) {}

        try {
            const counters = document.querySelectorAll('.counter');
            counters.forEach(counter => {
                gsap.to(counter, {
                    scrollTrigger: { trigger: counter, start: "top 85%", once: true },
                    innerHTML: counter.getAttribute('data-val'),
                    duration: 2, snap: { innerHTML: 1 }, ease: "power2.out"
                });
            });
        } catch(e) {}

        mm.add("(min-width: 1024px)", () => {
            try {
                const heroTl = gsap.timeline();
                heroTl.from(".hero-reveal", { y: 20, opacity: 0, duration: 1, stagger: 0.1, ease: "power3.out", delay: 0.1 })
                      .from(".hero-dash", { x: 50, opacity: 0, duration: 1.5, ease: "power3.out" }, "-=0.8")
                      .from(".hero-mobile", { y: 50, x: -30, opacity: 0, duration: 1.5, ease: "back.out(1.2)" }, "-=1.2");
            } catch(e) {}

            try {
                const stepTexts = gsap.utils.toArray('.step-text');
                const visuals = gsap.utils.toArray('.feature-visual');

                if(document.querySelector("#solucoes")) {
                    ScrollTrigger.create({
                        trigger: "#solucoes",
                        start: "top top",
                        end: "bottom bottom",
                        pin: ".lg\\\\:flex.w-1\\\\/2.h-screen.sticky",
                        pinSpacing: false
                    });
                }

                function activateStep(index) {
                    stepTexts.forEach((t, i) => {
                        const card = t.querySelector('div');
                        if (i === index) {
                            card.classList.add('bg-white', 'dark:bg-[#0A0A0A]', 'shadow-soft', 'active');
                            card.classList.remove('bg-transparent', 'border-transparent', 'lg:opacity-40');
                        } else {
                            card.classList.remove('bg-white', 'dark:bg-[#0A0A0A]', 'shadow-soft', 'active');
                            card.classList.add('bg-transparent', 'border-transparent', 'lg:opacity-40');
                        }
                    });

                    visuals.forEach((vis, i) => {
                        if (i === index) {
                            vis.style.opacity = "1";
                            vis.style.transform = "translateY(0) scale(1)";
                            vis.style.zIndex = "10";
                        } else if (i < index) {
                            vis.style.opacity = "0";
                            vis.style.transform = "translateY(-40px) scale(0.95)";
                            vis.style.zIndex = "1";
                        } else {
                            vis.style.opacity = "0";
                            vis.style.transform = "translateY(40px) scale(0.95)";
                            vis.style.zIndex = "1";
                        }
                    });
                }

                stepTexts.forEach((text, i) => {
                    ScrollTrigger.create({
                        trigger: text,
                        start: "top center",
                        end: "bottom center",
                        onEnter: () => activateStep(i),
                        onEnterBack: () => activateStep(i),
                    });
                });
            } catch(e) {}
        });

        // --- PARALLAX ---`);

fs.writeFileSync('index.html', index);
