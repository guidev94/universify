const fs = require('fs');
let index = fs.readFileSync('index.html', 'utf8');

// The issue might be that I added `if(sectionsToReveal.length > 0) { sectionsToReveal.forEach(section => {`
// But didn't close it correctly or messed up the block structure. Let's look for that block.
const sectionToRevealRegex = /const sectionsToReveal = gsap\.utils\.toArray\('\.section-reveal'\);\s*if\(sectionsToReveal\.length > 0\) \{\s*sectionsToReveal\.forEach\(section => \{[\s\S]*?\}\);\s*\}\s*\}/;

index = index.replace(/const sectionsToReveal = gsap\.utils\.toArray\('\.section-reveal'\);\n        if\(sectionsToReveal\.length > 0\) \{\n            sectionsToReveal\.forEach\(section => \{\n    scrollTrigger: \{[\s\S]*?            \}\);\n        \}\n\}\);\n/g, `
        const sectionsToReveal = gsap.utils.toArray('.section-reveal');
        if(sectionsToReveal) {
            sectionsToReveal.forEach(section => {
                gsap.fromTo(section, 
                    { y: 50, opacity: 0 },
                    {
                        y: 0, 
                        opacity: 1, 
                        duration: 0.8, 
                        ease: "power3.out",
                        scrollTrigger: {
                            trigger: section,
                            start: "top 85%",
                            toggleActions: "play none none reverse"
                        }
                    }
                );
            });
        }
`);

fs.writeFileSync('index.html', index);

