const fs = require('fs');
let index = fs.readFileSync('index.html', 'utf8');

// Some JS elements were removed, which breaks GSAP animations because the DOM elements are missing.
// We must protect GSAP interactions with null checks.

// Replace document.querySelector('.testimonial-container') logic with safe checks
index = index.replace(/const testimonialContainer = document\.getElementById\('testimonial-container'\);[\s\S]*?testimonialContainer\.addEventListener\('mouseleave', \(\) => \{[\s\S]*?\}\);/g, `
        const testimonialContainer = document.getElementById('testimonial-container');
        if(testimonialContainer) {
            testimonialContainer.addEventListener('mousemove', (e) => {
                const rect = testimonialContainer.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = ((y - centerY) / centerY) * -5;
                const rotateY = ((x - centerX) / centerX) * 5;
                
                gsap.to('#testimonial-wrapper', {
                    rotateX: rotateX,
                    rotateY: rotateY,
                    rotateZ: 0,
                    duration: 0.5,
                    ease: "power2.out"
                });
            });

            testimonialContainer.addEventListener('mouseleave', () => {
                gsap.to('#testimonial-wrapper', {
                    rotateX: 10,
                    rotateY: -5,
                    rotateZ: 2,
                    duration: 1,
                    ease: "power3.out"
                });
            });
        }
`);

// The "Planos" toggle script was breaking JS because "btn-mensal" and "btn-anual" no longer exist.
index = index.replace(/const btnMensal = document\.getElementById\('btn-mensal'\);[\s\S]*?updatePricingUI\(\);/g, `
        // Pricing JS removed safely
`);

fs.writeFileSync('index.html', index);

