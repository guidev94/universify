const fs = require('fs');

let index = fs.readFileSync('index.html', 'utf8');

// The 5 unique reviews from the site
const reviewsHTML = `
                <!-- Group 1 -->
                <div class="flex gap-8 px-4">
                    <div class="solid-card p-10 rounded-[2rem] w-[450px] shrink-0 flex flex-col shadow-soft hover:border-primary transition-colors">
                        <div class="flex items-center gap-1 text-amber-500 mb-6"><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i></div>
                        <p class="text-black dark:text-white text-lg font-medium leading-relaxed mb-8 flex-1 italic">"Recomendo a todos A Lets Work, um sistema claro e objetivo. Impactou positivamente na nossa empresa, facilitando as marcações do ponto, que nos proporcionou um controle mais eficaz e efetivo."</p>
                        <div class="flex items-center gap-4 pt-6 border-t border-borderline-light dark:border-borderline-dark">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center font-bold text-primary text-lg overflow-hidden"><img src="https://www.letswork.com.br/wp-content/uploads/2025/08/cardal-1.png" class="w-full h-full object-contain p-1"></div>
                            <div><h4 class="font-bold text-black dark:text-white">Luciene</h4><p class="text-sm text-subtle-light dark:text-subtle-dark">Gestão (Cardal)</p></div>
                        </div>
                    </div>

                    <div class="solid-card p-10 rounded-[2rem] w-[450px] shrink-0 flex flex-col shadow-soft hover:border-primary transition-colors">
                        <div class="flex items-center gap-1 text-amber-500 mb-6"><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i></div>
                        <p class="text-black dark:text-white text-lg font-medium leading-relaxed mb-8 flex-1 italic">"Estou há 2 anos com a Lets Work e só tenho elogios! É um sistema simples, didático e muito eficiente. Por termos várias lojas, a flexibilidade é essencial, e ele funciona em todos os formatos, o que facilita bastante o dia a dia."</p>
                        <div class="flex items-center gap-4 pt-6 border-t border-borderline-light dark:border-borderline-dark">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center font-bold text-primary text-lg overflow-hidden"><img src="https://www.letswork.com.br/wp-content/uploads/2025/08/fom.png" class="w-full h-full object-contain p-1"></div>
                            <div><h4 class="font-bold text-black dark:text-white">Kaue</h4><p class="text-sm text-subtle-light dark:text-subtle-dark">Gestão</p></div>
                        </div>
                    </div>

                    <div class="solid-card p-10 rounded-[2rem] w-[450px] shrink-0 flex flex-col shadow-soft hover:border-primary transition-colors">
                        <div class="flex items-center gap-1 text-amber-500 mb-6"><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i></div>
                        <p class="text-black dark:text-white text-lg font-medium leading-relaxed mb-8 flex-1 italic">"E agradeça ao Jean (implantação), ele tem sido um parceiro incrível para nós! Está tendo uma implantação de um sistema na parte do faturamento aqui e a gente começa a ver diferença e querer um suporte de qualidade igual a vocês!"</p>
                        <div class="flex items-center gap-4 pt-6 border-t border-borderline-light dark:border-borderline-dark">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center font-bold text-primary text-lg overflow-hidden"><img src="https://www.letswork.com.br/wp-content/uploads/2025/09/CEAL.png" class="w-full h-full object-contain p-1"></div>
                            <div><h4 class="font-bold text-black dark:text-white">Dani da CEAL</h4><p class="text-sm text-subtle-light dark:text-subtle-dark">Gestão</p></div>
                        </div>
                    </div>

                    <div class="solid-card p-10 rounded-[2rem] w-[450px] shrink-0 flex flex-col shadow-soft hover:border-primary transition-colors">
                        <div class="flex items-center gap-1 text-amber-500 mb-6"><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i></div>
                        <p class="text-black dark:text-white text-lg font-medium leading-relaxed mb-8 flex-1 italic">"O suporte é bem atencioso, nunca deixa a empresa sem resposta, existem ótimas funcionalidades."</p>
                        <div class="flex items-center gap-4 pt-6 border-t border-borderline-light dark:border-borderline-dark">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center font-bold text-primary text-lg overflow-hidden"><img src="https://www.letswork.com.br/wp-content/uploads/2022/11/Post-Redes-Sociais-14.png" class="w-full h-full object-cover"></div>
                            <div><h4 class="font-bold text-black dark:text-white">Vanessa</h4><p class="text-sm text-subtle-light dark:text-subtle-dark">Suporte</p></div>
                        </div>
                    </div>

                    <div class="solid-card p-10 rounded-[2rem] w-[450px] shrink-0 flex flex-col shadow-soft hover:border-primary transition-colors">
                        <div class="flex items-center gap-1 text-amber-500 mb-6"><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i></div>
                        <p class="text-black dark:text-white text-lg font-medium leading-relaxed mb-8 flex-1 italic">"O que melhorou foram o relatórios que solicitamos e foram criados e isso tem facilitado nossas consultas"</p>
                        <div class="flex items-center gap-4 pt-6 border-t border-borderline-light dark:border-borderline-dark">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center font-bold text-primary text-lg overflow-hidden"><img src="https://www.letswork.com.br/wp-content/uploads/2022/11/Post-Redes-Sociais-13-1.png" class="w-full h-full object-cover"></div>
                            <div><h4 class="font-bold text-black dark:text-white">Ana</h4><p class="text-sm text-subtle-light dark:text-subtle-dark">Gestão</p></div>
                        </div>
                    </div>
                </div>
`;

// Build the full track content: Group 1 + Duplicate of Group 1
const fullTrackHTML = `<div class="testimonial-track">\n` + reviewsHTML + reviewsHTML.replace('<!-- Group 1 -->', '<!-- Group 2 (Duplicate for Infinite Loop) -->\n                <div aria-hidden="true">').replace('<div class="flex gap-8 px-4">', '<div class="flex gap-8 px-4" aria-hidden="true">') + `\n                </div>`;

// Replace the existing track in index.html
index = index.replace(/<div class="testimonial-track">[\s\S]*?<!-- Start Stats/Banner Section -->/, fullTrackHTML + '\n            </div>\n        </div>\n    </section>\n\n    <!-- Start Stats/Banner Section -->');

// Also update Social proof section in the hero!
const socialProofHTML = `
            <!-- Human Social Proof / Avatares -->
            <div class="hero-reveal mt-8 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 text-sm font-medium">
                <div class="flex gap-4">
                    <div class="w-14 h-14 rounded-full shadow-sm flex items-center justify-center overflow-hidden bg-white dark:bg-white/10 border border-borderline-light dark:border-borderline-dark p-2"><img src="https://www.letswork.com.br/wp-content/uploads/2022/07/1.png" alt="O Boticario" class="w-full h-full object-contain"></div>
                    <div class="w-14 h-14 rounded-full shadow-sm flex items-center justify-center overflow-hidden bg-white dark:bg-white/10 border border-borderline-light dark:border-borderline-dark p-2"><img src="https://www.letswork.com.br/wp-content/uploads/2022/07/zarpng.png" alt="Zarpo" class="w-full h-full object-contain"></div>
                    <div class="w-14 h-14 rounded-full shadow-sm flex items-center justify-center overflow-hidden bg-white dark:bg-white/10 border border-borderline-light dark:border-borderline-dark p-2"><img src="https://www.letswork.com.br/wp-content/uploads/2022/07/giupng.png" alt="Giuliana Flores" class="w-full h-full object-contain"></div>
                    <div class="w-14 h-14 rounded-full shadow-sm flex items-center justify-center overflow-hidden bg-white dark:bg-white/10 border border-borderline-light dark:border-borderline-dark p-2"><img src="https://www.letswork.com.br/wp-content/uploads/2025/08/sebrae.png" alt="Sebrae" class="w-full h-full object-contain dark:brightness-0 dark:invert"></div>
                </div>
                <div class="flex flex-col items-center sm:items-start">
                    <div class="flex items-center gap-1 text-amber-500 mb-1">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    </div>
                    <span class="text-xs text-subtle-light dark:text-subtle-dark font-bold tracking-wide uppercase">Aprovado por investidores e parceiros</span>
                </div>
            </div>`;

index = index.replace(/<!-- Human Social Proof \/ Avatares -->[\s\S]*?<\/div>\n            <\/div>/, socialProofHTML);

// Fix JS animation errors
// The errors happen if we removed elements but gsap tries to animate them
// Let's wrap gsap initialization in try-catch or checks
index = index.replace(/const openModalBtns = document.querySelectorAll\('\.open-modal-btn'\);/g, `
        const openModalBtns = document.querySelectorAll('.open-modal-btn');
        if(openModalBtns) {
`);

index = index.replace(/const closeModalBtn = document\.getElementById\('close-modal'\);/, `
        const closeModalBtn = document.getElementById('close-modal');
`);

index = index.replace(/document\.getElementById\('modal-overlay'\)\.addEventListener\('click', closeModal\);/g, `
        if(document.getElementById('modal-overlay')) document.getElementById('modal-overlay').addEventListener('click', closeModal);
`);

index = index.replace(/gsap\.utils\.toArray\('\.section-reveal'\)\.forEach\(section => \{/g, `
        const sectionsToReveal = gsap.utils.toArray('.section-reveal');
        if(sectionsToReveal.length > 0) {
            sectionsToReveal.forEach(section => {
`);
index = index.replace(/    scrollTrigger: \{[\s\S]*?    \}\n\}\);/g, `    scrollTrigger: {
                    trigger: section,
                    start: "top 85%",
                    toggleActions: "play none none reverse"
                }
            });
        }
`);

// Look for scrolling brands
index = index.replace(/<div class="flex gap-12 lg:gap-20 items-center px-6 whitespace-nowrap animate-marquee">[\s\S]*?<\/div>/, `<div class="flex gap-12 lg:gap-20 items-center px-6 whitespace-nowrap animate-marquee">
                <!-- Logos Reais Let's Work -->
                <img src="https://www.letswork.com.br/wp-content/uploads/2022/07/1.png" alt="O Boticario" class="h-8 md:h-10 object-contain grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2022/07/zarpng.png" alt="Zarpo" class="h-8 md:h-10 object-contain grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2022/07/5.png" alt="Los Los" class="h-8 md:h-10 object-contain grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2022/07/giupng.png" alt="Giuliana Flores" class="h-8 md:h-10 object-contain grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/08/sebrae.png" alt="Sebrae" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/08/confirp.png" alt="Confirp" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/08/zap-contabil.png" alt="Zap Contabil" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/10/organica.avif" alt="Organica" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/10/inova.avif" alt="Inova" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/10/Unifique.avif" alt="Unifique" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/08/rajaVentures.png" alt="Raja Ventures" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/08/bossa.png" alt="Bossa" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/10/BRDE.avif" alt="BRDE" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
            </div>`);

// Make sure the second marquee matches exactly
index = index.replace(/<div class="flex gap-12 lg:gap-20 items-center px-6 whitespace-nowrap animate-marquee" aria-hidden="true">[\s\S]*?<\/div>/, `<div class="flex gap-12 lg:gap-20 items-center px-6 whitespace-nowrap animate-marquee" aria-hidden="true">
                <!-- Logos Reais Let's Work (Duplicado para Scroll Infinito) -->
                <img src="https://www.letswork.com.br/wp-content/uploads/2022/07/1.png" alt="O Boticario" class="h-8 md:h-10 object-contain grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2022/07/zarpng.png" alt="Zarpo" class="h-8 md:h-10 object-contain grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2022/07/5.png" alt="Los Los" class="h-8 md:h-10 object-contain grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2022/07/giupng.png" alt="Giuliana Flores" class="h-8 md:h-10 object-contain grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/08/sebrae.png" alt="Sebrae" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/08/confirp.png" alt="Confirp" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/08/zap-contabil.png" alt="Zap Contabil" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/10/organica.avif" alt="Organica" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/10/inova.avif" alt="Inova" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/10/Unifique.avif" alt="Unifique" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/08/rajaVentures.png" alt="Raja Ventures" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/08/bossa.png" alt="Bossa" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/10/BRDE.avif" alt="BRDE" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
            </div>`);

fs.writeFileSync('index.html', index);
