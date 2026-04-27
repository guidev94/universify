const fs = require('fs');

// 1. UPDATE TESTIMONIALS AND LOGOS IN INDEX.HTML
let index = fs.readFileSync('index.html', 'utf8');

const realTestimonials = `
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
                        <p class="text-black dark:text-white text-lg font-medium leading-relaxed mb-8 flex-1 italic">"Estou há 2 anos com a Lets Work e só tenho elogios! É um sistema simples, didático e muito eficiente. Por termos várias lojas, a flexibilidade é essencial, e ele funciona em todos os formatos."</p>
                        <div class="flex items-center gap-4 pt-6 border-t border-borderline-light dark:border-borderline-dark">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center font-bold text-primary text-lg overflow-hidden"><img src="https://www.letswork.com.br/wp-content/uploads/2025/08/fom.png" class="w-full h-full object-contain p-1"></div>
                            <div><h4 class="font-bold text-black dark:text-white">Kaue</h4><p class="text-sm text-subtle-light dark:text-subtle-dark">Gestão (Fom)</p></div>
                        </div>
                    </div>

                    <div class="solid-card p-10 rounded-[2rem] w-[450px] shrink-0 flex flex-col shadow-soft hover:border-primary transition-colors">
                        <div class="flex items-center gap-1 text-amber-500 mb-6"><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i></div>
                        <p class="text-black dark:text-white text-lg font-medium leading-relaxed mb-8 flex-1 italic">"E agradeça ao Jean (implantação), ele tem sido um parceiro incrível para nós! A gente começa a ver diferença e querer um suporte de qualidade igual a vocês!"</p>
                        <div class="flex items-center gap-4 pt-6 border-t border-borderline-light dark:border-borderline-dark">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center font-bold text-primary text-lg overflow-hidden"><img src="https://www.letswork.com.br/wp-content/uploads/2025/09/CEAL.png" class="w-full h-full object-contain p-1"></div>
                            <div><h4 class="font-bold text-black dark:text-white">Dani</h4><p class="text-sm text-subtle-light dark:text-subtle-dark">Gestão (CEAL)</p></div>
                        </div>
                    </div>

                    <div class="solid-card p-10 rounded-[2rem] w-[450px] shrink-0 flex flex-col shadow-soft hover:border-primary transition-colors">
                        <div class="flex items-center gap-1 text-amber-500 mb-6"><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i></div>
                        <p class="text-black dark:text-white text-lg font-medium leading-relaxed mb-8 flex-1 italic">"O suporte é bem atencioso, nunca deixa a empresa sem resposta, existem ótimas funcionalidades."</p>
                        <div class="flex items-center gap-4 pt-6 border-t border-borderline-light dark:border-borderline-dark">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center font-bold text-primary text-lg overflow-hidden"><img src="https://www.letswork.com.br/wp-content/uploads/2022/11/Post-Redes-Sociais-14.png" class="w-full h-full object-cover"></div>
                            <div><h4 class="font-bold text-black dark:text-white">Vanessa</h4><p class="text-sm text-subtle-light dark:text-subtle-dark">Suporte (Stanley)</p></div>
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
                </div>`;

const testTrack = '<div class="testimonial-track">\n' + realTestimonials + realTestimonials.replace('<!-- Group 1 -->', '<!-- Group 2 (Duplicate for Infinite Loop) -->\n                <div aria-hidden="true">').replace('<div class="flex gap-8 px-4">', '<div class="flex gap-8 px-4" aria-hidden="true">') + '\n                </div>';

// Replacing testimonials safely
function replaceBetween(str, start, end, replacement) {
    const startIdx = str.indexOf(start);
    if (startIdx === -1) return str;
    const endIdx = str.indexOf(end, startIdx);
    if (endIdx === -1) return str;
    return str.substring(0, startIdx) + replacement + str.substring(endIdx + end.length);
}

index = replaceBetween(index, '<div class="testimonial-track">', '</div>\n        </div>\n\n        <div class="testimonial-container" id="testimonial-container">', testTrack + '\n            </div>\n        </div>\n    </section>\n\n    <!-- Start Stats/Banner Section -->\n    <section class="pt-20 pb-0 lg:pt-24 border-t border-borderline-light dark:border-borderline-dark">');
// Fallback if not found
if(index.indexOf(realTestimonials) === -1) {
    const sIdx = index.indexOf('<div class="testimonial-track">');
    const eIdx = index.indexOf('</div>\n        </div>\n    </section>\n\n    <!-- Start Stats/Banner Section -->');
    if (sIdx !== -1 && eIdx !== -1) {
        index = index.substring(0, sIdx) + testTrack + '\n            ' + index.substring(eIdx);
    }
}

// 2. INCREASE LOGO SIZE FOR CLIENTS ONLY
const fakeLogosRegex = /<!-- Fake Logos -->[\s\S]*?<\/div>/g;
index = index.replace(fakeLogosRegex, '');
const fakeLogos2Regex = /<!-- Fake Logos \(Duplicado\) -->[\s\S]*?<\/div>/g;
index = index.replace(fakeLogos2Regex, '');

const marqueeLogos = `                <!-- Logos Reais Let's Work -->
                <img src="https://www.letswork.com.br/wp-content/uploads/2022/07/1.png" alt="O Boticario" class="h-12 md:h-16 object-contain grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2022/07/zarpng.png" alt="Zarpo" class="h-12 md:h-16 object-contain grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2022/07/5.png" alt="Los Los" class="h-12 md:h-16 object-contain grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2022/07/giupng.png" alt="Giuliana Flores" class="h-12 md:h-16 object-contain grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/08/sebrae.png" alt="Sebrae" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/08/confirp.png" alt="Confirp" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/08/zap-contabil.png" alt="Zap Contabil" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/10/organica.avif" alt="Organica" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/10/inova.avif" alt="Inova" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/10/Unifique.avif" alt="Unifique" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/08/rajaVentures.png" alt="Raja Ventures" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/08/bossa.png" alt="Bossa" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/10/BRDE.avif" alt="BRDE" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">`;

const currentLogos = /<!-- Logos Reais Let's Work -->[\s\S]*?<\/div>/;
if (index.match(currentLogos)) {
    index = index.replace(currentLogos, marqueeLogos + '\n            </div>');
} else {
    // try to replace the whole div content if not matched
    const startM = index.indexOf('<div class="flex gap-12 lg:gap-20 items-center px-6 whitespace-nowrap animate-marquee">');
    if (startM !== -1) {
        const endM = index.indexOf('</div>', startM);
        index = index.substring(0, startM + 89) + '\n' + marqueeLogos + '\n            ' + index.substring(endM);
    }
}

const currentLogos2 = /<!-- Logos Reais Let's Work \(Duplicado\) -->[\s\S]*?<\/div>/;
if (index.match(currentLogos2)) {
    index = index.replace(currentLogos2, marqueeLogos.replace("<!-- Logos Reais Let's Work -->", "<!-- Logos Reais Let's Work (Duplicado) -->") + '\n            </div>');
} else {
    const startM2 = index.indexOf('<div class="flex gap-12 lg:gap-20 items-center px-6 whitespace-nowrap animate-marquee" aria-hidden="true">');
    if (startM2 !== -1) {
        const endM2 = index.indexOf('</div>', startM2);
        index = index.substring(0, startM2 + 108) + '\n' + marqueeLogos.replace("<!-- Logos Reais Let's Work -->", "<!-- Logos Reais Let's Work (Duplicado) -->") + '\n            ' + index.substring(endM2);
    }
}

fs.writeFileSync('index.html', index);

// 3. ADD "INÍCIO" BUTTON TO NAV HEADER FOR ALL PAGES
const files = fs.readdirSync('.').filter(f => f.endsWith('.html'));
for (const file of files) {
    let content = fs.readFileSync(file, 'utf8');
    
    // Check if Início is already there
    if (!content.includes('>Início</a>')) {
        content = content.replace(
            /<div class="hidden lg:flex gap-8 text-sm font-semibold text-subtle-light dark:text-subtle-dark">/,
            `<div class="hidden lg:flex gap-8 text-sm font-semibold text-subtle-light dark:text-subtle-dark">\n                <a href="index.html" class="hover:text-black dark:hover:text-white transition-colors">Início</a>`
        );
        fs.writeFileSync(file, content);
    }
}
