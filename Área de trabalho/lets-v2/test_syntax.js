const fs = require('fs');
let index = fs.readFileSync('index.html', 'utf8');

// There might be syntax errors from my previous GSAP logic replacements.
// Let's re-inject the standard GSAP initialization to make sure it's clean.

// Remove any broken GSAP initialize loops
index = index.replace(/gsap\.utils\.toArray\('\.section-reveal'\)\.forEach\(elem => \{[\s\S]*?\}\);/g, '');

const safeGSAP = `
        gsap.utils.toArray('.section-reveal').forEach(elem => {
            gsap.from(elem, {
                scrollTrigger: { trigger: elem, start: "top 85%" },
                y: 30, opacity: 0, duration: 0.8, ease: "power3.out"
            });
        });
`;

// Insert it right after mm.add
index = index.replace(/let mm = gsap\.matchMedia\(\);/, 'let mm = gsap.matchMedia();\n' + safeGSAP);

fs.writeFileSync('index.html', index);
