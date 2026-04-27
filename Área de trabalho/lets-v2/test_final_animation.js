const fs = require('fs');
let index = fs.readFileSync('index.html', 'utf8');

// Also make sure .testimonial-wrapper-3d is safe if it was missing the class somehow
index = index.replace(/const testimonialContainer = document\.getElementById\('testimonial-container'\);[\s\S]*?\}\);/g, `
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

fs.writeFileSync('index.html', index);
