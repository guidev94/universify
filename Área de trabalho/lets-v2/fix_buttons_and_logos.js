const fs = require('fs');

function replaceBetween(str, start, end, replacement) {
    const startIdx = str.indexOf(start);
    if (startIdx === -1) return str;
    const endIdx = str.indexOf(end, startIdx);
    if (endIdx === -1) return str;
    return str.substring(0, startIdx) + replacement + str.substring(endIdx + end.length);
}

// 1. REVERT INLINE WPP BUTTONS
const files = fs.readdirSync('.').filter(f => f.endsWith('.html'));
for (const file of files) {
    let content = fs.readFileSync(file, 'utf8');
    
    // Revert the buttons that were changed in the last pass
    content = content.replace(/class="inline-flex items-center justify-center gap-3 bg-\[#25D366\] text-white px-8 py-4 rounded-full font-bold text-base md:text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-\[0_10px_25px_rgba\(37,211,102,0\.4\)\] hover:shadow-\[0_15px_35px_rgba\(37,211,102,0\.5\)\] border border-\[#20b858\] relative overflow-hidden group"/g, 
    'class="inline-flex items-center justify-center gap-2 bg-[#25D366] text-white px-8 py-4 rounded-full font-bold text-base md:text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-lg hover:shadow-xl w-full sm:w-auto"');
    
    content = content.replace(/class="magnetic inline-flex items-center justify-center gap-3 bg-\[#25D366\] text-white px-8 py-4 rounded-full font-bold text-base md:text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-\[0_10px_25px_rgba\(37,211,102,0\.4\)\] hover:shadow-\[0_15px_35px_rgba\(37,211,102,0\.5\)\] border border-\[#20b858\] w-full sm:w-auto relative overflow-hidden group"/g, 
    'class="magnetic inline-flex items-center justify-center gap-2 bg-[#25D366] text-white px-8 py-4 rounded-full font-bold text-base md:text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-lg hover:shadow-xl w-full sm:w-auto"');

    // Remove shimmer div
    content = content.replace(/<div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white\/30 to-transparent group-hover:animate-\[shimmer_1\.5s_infinite\]"><\/div><svg class="w-6 h-6 fill-current relative z-10"/g, 
    '<svg class="w-6 h-6 fill-current"');

    content = content.replace(/<span class="relative z-10">Entre Em Contato<\/span>/g, '<span>Entre Em Contato</span>');
    content = content.replace(/<span class="relative z-10">Solicite Uma Demonstração<\/span>/g, 'Solicite Uma Demonstração');

    // Remove extra w-full sm:w-auto if added redundantly
    content = content.replace(/w-full sm:w-auto w-full sm:w-auto/g, 'w-full sm:w-auto');

    fs.writeFileSync(file, content);
}

// 2. FIX TESTIMONIALS AND LOGOS IN INDEX.HTML
let index = fs.readFileSync('index.html', 'utf8');

const realTestimonials = `                <!-- Group 1 -->
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

const testTrack = '<div class="testimonial-track">\n' + realTestimonials + '\n' + realTestimonials.replace('<!-- Group 1 -->', '<!-- Group 2 (Duplicate for Infinite Loop) -->\n                <div aria-hidden="true">').replace('<div class="flex gap-8 px-4">', '<div class="flex gap-8 px-4" aria-hidden="true">') + '\n                </div>';

const sIdx = index.indexOf('<div class="testimonial-track">');
const eIdx = index.indexOf('</div>\n        </div>\n\n        <div class="testimonial-container" id="testimonial-container">');
const altEIdx = index.indexOf('</div>\n        </div>\n    </section>\n\n    <!-- Start Stats/Banner Section -->');

let endPoint = eIdx;
if (endPoint === -1) endPoint = altEIdx;

if (sIdx !== -1 && endPoint !== -1) {
    index = index.substring(0, sIdx) + testTrack + '\n            ' + index.substring(endPoint);
}

// Ensure the clients logos are larger
const marqueeLogos = `<!-- Fake Logos -->
            <div class="flex gap-12 lg:gap-20 items-center px-6 whitespace-nowrap animate-marquee">
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
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/10/BRDE.avif" alt="BRDE" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">
            </div>`;

index = replaceBetween(index, '<!-- Fake Logos -->', '<!-- Fake Logos (Duplicado) -->', marqueeLogos + '\n            ');
index = replaceBetween(index, '<!-- Fake Logos (Duplicado) -->', '</div>\n        </div>\n    </section>\n\n    <!-- Start Soluções -->', marqueeLogos.replace('<!-- Fake Logos -->', '<!-- Fake Logos (Duplicado) -->\n            <div class="flex gap-12 lg:gap-20 items-center px-6 whitespace-nowrap animate-marquee" aria-hidden="true">').replace('<div class="flex gap-12 lg:gap-20 items-center px-6 whitespace-nowrap animate-marquee">', '') + '\n        </div>');

fs.writeFileSync('index.html', index);

