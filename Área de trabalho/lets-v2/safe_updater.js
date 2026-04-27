const fs = require('fs');
const path = require('path');

const WPP_URL = "https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20um%20especialista%20da%20Let%27s%20Work%21";

const floatBtnHTML = `
    <!-- Botão Flutuante WhatsApp Elegante -->
    <a href="${WPP_URL}" target="_blank" class="fixed bottom-6 right-6 z-50 bg-[#25D366] text-white p-3 md:p-3.5 rounded-full shadow-[0_10px_30px_rgba(37,211,102,0.4)] hover:shadow-[0_15px_40px_rgba(37,211,102,0.6)] hover:-translate-y-2 active:scale-95 transition-all duration-300 flex items-center justify-center group">
        <svg class="w-7 h-7 md:w-8 md:h-8 drop-shadow-md" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-14.416c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm.029 18.88c-1.161 0-2.305-.292-3.318-.844l-3.677.964.984-3.595c-.607-1.052-.927-2.246-.926-3.468.001-5.824 4.74-10.563 10.564-10.563 5.832 0 10.564 4.742 10.564 10.564 0 5.827-4.732 10.564-10.564 10.564z"/></svg>
        <span class="absolute right-full mr-4 bg-white dark:bg-[#111] text-black dark:text-white border border-borderline-light dark:border-borderline-dark text-sm px-4 py-2 rounded-xl whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none font-bold shadow-lg flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Falar com Comercial
        </span>
    </a>
`;

// Helper for exact replacement between boundaries
function replaceBetween(str, start, end, replacement) {
    const startIdx = str.indexOf(start);
    if (startIdx === -1) return str;
    const endIdx = str.indexOf(end, startIdx);
    if (endIdx === -1) return str;
    return str.substring(0, startIdx) + replacement + str.substring(endIdx + end.length);
}

// 1. UPDATE INDEX.HTML
let index = fs.readFileSync('index.html', 'utf8');

// A. Testimonials
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
const testTrack = '<div class="testimonial-track">\n' + realTestimonials + realTestimonials.replace('<!-- Group 1 -->', '<!-- Group 2 (Duplicate for Infinite Loop) -->\n                <div aria-hidden="true">').replace('<div class="flex gap-8 px-4">', '<div class="flex gap-8 px-4" aria-hidden="true">') + '\n                </div>';
index = replaceBetween(index, '<div class="testimonial-track">', '</div>\n        </div>\n\n        <div class="testimonial-container" id="testimonial-container">', testTrack + '\n            </div>\n        </div>\n    </section>\n\n    <!-- Start Stats/Banner Section -->\n    <section class="pt-20 pb-0 lg:pt-24 border-t border-borderline-light dark:border-borderline-dark">');
// Note: If exact bounds aren't found, we'll try a fallback
if(index.indexOf(realTestimonials) === -1) {
    const sIdx = index.indexOf('<div class="testimonial-track">');
    const eIdx = index.indexOf('</div>\n        </div>\n    </section>\n\n    <!-- Start Stats/Banner Section -->');
    if (sIdx !== -1 && eIdx !== -1) {
        index = index.substring(0, sIdx) + testTrack + '\n            ' + index.substring(eIdx);
    }
}

// B. Home Avatars Proof
const socialProofAvatars = `
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
index = replaceBetween(index, '<!-- Human Social Proof / Avatares -->', '</div>\n            </div>', socialProofAvatars);

// C. Real Logos in Marquee
const marqueeLogos = `                <!-- Logos Reais Let's Work -->
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
                <img src="https://www.letswork.com.br/wp-content/uploads/2025/10/BRDE.avif" alt="BRDE" class="h-8 md:h-10 object-contain dark:brightness-0 dark:invert grayscale hover:grayscale-0 transition-all">`;

index = index.replace(/<!-- Fake Logos -->[\s\S]*?<\/div>/, marqueeLogos + '\n            </div>');
index = index.replace(/<!-- Fake Logos \(Duplicado\) -->[\s\S]*?<\/div>/, marqueeLogos.replace("<!-- Logos Reais Let's Work -->", "<!-- Logos Reais Let's Work (Duplicado) -->") + '\n            </div>');

// D. Home Pricing Update
const pricingHome = `
    <!-- 7. PLANOS (Clean Pricing) -->
    <section id="planos" class="pt-0 pb-20 lg:pb-24 border-b border-borderline-light dark:border-borderline-dark">
        <div class="max-w-[1400px] mx-auto px-6">
            <div class="text-center mb-16 section-reveal max-w-3xl mx-auto">
                <div class="inline-flex px-3 py-1.5 rounded-sm border border-borderline-light dark:border-borderline-dark bg-surface-light dark:bg-[#111] text-xs font-bold mb-6 uppercase tracking-widest text-subtle-light dark:text-subtle-dark shadow-[2px_2px_0px_rgba(0,0,0,0.1)] dark:shadow-[2px_2px_0px_rgba(255,255,255,0.1)] items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-primary animate-[pulse_2s_ease-in-out_infinite]"></span> Preços Transparentes
                </div>
                <h2 class="text-4xl md:text-5xl lg:text-[3.5rem] font-display font-black tracking-tighter mb-4 leading-[1.05] text-black dark:text-white">
                    Investimento escalável.
                </h2>
                <p class="text-lg text-subtle-light dark:text-subtle-dark font-medium mb-8">Planos desenhados para o momento da sua empresa. Sem taxas ocultas.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6 md:gap-4 lg:gap-8 items-center max-w-sm md:max-w-none mx-auto group/pricing">
                <!-- Plano 1 -->
                <div class="solid-card p-6 md:p-8 rounded-2xl flex flex-col items-center text-center section-reveal transition-all duration-500 hover:shadow-xl hover:-translate-y-2 bg-white dark:bg-[#111]">
                    <h3 class="text-xl font-bold mb-1">Time Ágil 10</h3>
                    <p class="text-xs text-subtle-light dark:text-subtle-dark uppercase tracking-widest mb-6">Até 10 Funcionários</p>
                    <div class="mb-8 flex items-baseline justify-center gap-1">
                        <span class="text-4xl font-display font-black text-black dark:text-white">R$89</span>
                        <span class="text-subtle-light dark:text-subtle-dark font-medium">,90/mês</span>
                    </div>
                    <a href="${WPP_URL}" target="_blank" class="w-full py-3 rounded-full text-center text-sm font-bold border border-borderline-light dark:border-borderline-dark hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black transition-all mb-8 block">Contrate Agora</a>
                    <ul class="space-y-4 text-sm font-medium flex-1 text-left w-full pl-2 lg:pl-4">
                        <li class="flex items-center gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 shrink-0"></i> Reconhecimento Facial</li>
                        <li class="flex items-center gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 shrink-0"></i> Ponto Offline</li>
                        <li class="flex items-center gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 shrink-0"></i> Geolocalização GPS</li>
                    </ul>
                </div>

                <!-- Plano 2 (Destaque) -->
                <div class="p-8 md:p-10 rounded-3xl border-2 border-primary relative flex flex-col items-center text-center section-reveal shadow-2xl bg-black dark:bg-white text-white dark:text-black md:scale-105 z-10 transition-all duration-500 hover:scale-110">
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 bg-primary text-white text-[10px] font-bold px-4 py-1 rounded-b-lg uppercase tracking-widest">Mais Adotado</div>
                    <h3 class="text-2xl font-bold mb-1 mt-2 text-white dark:text-black">Equipe Dinâmica 50</h3>
                    <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-6">Até 50 Funcionários</p>
                    <div class="mb-8 flex items-baseline justify-center gap-1">
                        <span class="text-5xl font-display font-black text-white dark:text-black">R$299</span>
                        <span class="text-gray-400 dark:text-gray-500 font-medium">,90/mês</span>
                    </div>
                    <a href="${WPP_URL}" target="_blank" class="btn-primary magnetic block w-full py-4 rounded-full text-center text-sm font-bold mb-8 shadow-glow hover:scale-105 transition-transform">Assinar Agora</a>
                    <ul class="space-y-4 text-sm font-bold flex-1 text-left w-full pl-2 lg:pl-4 text-gray-300 dark:text-gray-700">
                        <li class="flex items-center gap-3"><i data-lucide="check-circle-2" class="w-5 h-5 text-primary shrink-0"></i> Ponto via WhatsApp</li>
                        <li class="flex items-center gap-3"><i data-lucide="check-circle-2" class="w-5 h-5 text-primary shrink-0"></i> Gestão de Escalas Completa</li>
                        <li class="flex items-center gap-3"><i data-lucide="check-circle-2" class="w-5 h-5 text-primary shrink-0"></i> Relatórios para Contabilidade</li>
                        <li class="flex items-center gap-3"><i data-lucide="check-circle-2" class="w-5 h-5 text-primary shrink-0"></i> Suporte Humanizado e Dedicado</li>
                    </ul>
                </div>

                <!-- Plano 3 -->
                <div class="solid-card p-6 md:p-8 rounded-2xl flex flex-col items-center text-center section-reveal transition-all duration-500 hover:shadow-xl hover:-translate-y-2 bg-white dark:bg-[#111]">
                    <h3 class="text-xl font-bold mb-1">Enterprise Scale</h3>
                    <p class="text-xs text-subtle-light dark:text-subtle-dark uppercase tracking-widest mb-6">Acima de 50 Funcionários</p>
                    <div class="mb-8 flex items-baseline justify-center gap-1">
                        <span class="text-4xl font-display font-black text-black dark:text-white">Sob Consulta</span>
                    </div>
                    <a href="${WPP_URL}" target="_blank" class="w-full py-3 rounded-full text-center text-sm font-bold border border-borderline-light dark:border-borderline-dark hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black transition-all mb-8 block">Falar com Vendas</a>
                    <ul class="space-y-4 text-sm font-medium flex-1 text-left w-full pl-2 lg:pl-4">
                        <li class="flex items-center gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 shrink-0"></i> Módulos Ilimitados</li>
                        <li class="flex items-center gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 shrink-0"></i> Gestão de Vagas com IA</li>
                        <li class="flex items-center gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 shrink-0"></i> Automação VittorIA (Chat)</li>
                        <li class="flex items-center gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 shrink-0"></i> Implantação Assistida</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
`;
index = replaceBetween(index, '<!-- 7. PLANOS (Clean Pricing) -->', '<!-- 7.5 FAQ SECTION (Quebra de Objeções) -->', pricingHome + '\n\n    ');

// 2. Falar Com Especialista redirect
index = index.replace(/<button class="([^"]*) open-modal-btn">(\s*)<span class="pointer-events-none">Falar com Especialista<\/span>(\s*)<\/button>/g, `<a href="${WPP_URL}" target="_blank" class="$1">\n                        <span>Falar com Especialista</span>\n                    </a>`);

// 3. Add Wpp Floating Btn
index = index.replace('</body>', floatBtnHTML + '\n</body>');

fs.writeFileSync('index.html', index);

// 4. Update Other LP files
const htmlFiles = fs.readdirSync('.').filter(f => f.endsWith('.html') && f !== 'index.html');
for (const file of htmlFiles) {
    let content = fs.readFileSync(file, 'utf8');

    // Float Btn
    content = content.replace('</body>', floatBtnHTML + '\n</body>');
    
    // Falar Com Especialista header replace
    content = content.replace(/<button class="([^"]*) open-modal-btn">(\s*)<span class="pointer-events-none">Falar com Especialista<\/span>(\s*)<\/button>/g, `<a href="${WPP_URL}" target="_blank" class="$1">\n                        <span>Falar com Especialista</span>\n                    </a>`);

    // Remove any embedded pricing section in sub-LPs
    content = content.replace(/<!-- 7\. PLANOS \(Clean Pricing\) -->[\s\S]*?<!-- 7\.5 FAQ SECTION \(Quebra de Objeções\) -->/g, '<!-- 7.5 FAQ SECTION (Quebra de Objeções) -->');

    // Specific fix for como-funciona.html
    if (file === 'como-funciona.html') {
        // Change text
        content = content.replace(/<h1 class="[^"]*font-display[^"]*">[\s\S]*?<\/h1>/, `<h1 class="text-4xl md:text-5xl lg:text-6xl font-display font-bold text-black dark:text-white tracking-tight mb-6">\n                        Sistema de Controle de <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-orange-500">Ponto Digital</span>\n                    </h1>`);
        
        content = content.replace(/<p class="text-lg md:text-xl text-subtle-light dark:text-subtle-dark max-w-3xl leading-relaxed">[\s\S]*?<\/p>/, `<p class="text-lg md:text-xl text-subtle-light dark:text-subtle-dark max-w-3xl leading-relaxed">\n                Controle de ponto via WhatsApp com reconhecimento facial e geolocalização. Gestão eficiente da jornada de trabalho dos colaboradores internos e externos.\n            </p>`);

        // Change Hero Button
        const WPP_HERO_BTN = `<a href="${WPP_URL}" target="_blank" class="magnetic inline-flex items-center justify-center gap-2 bg-[#25D366] text-white px-8 py-4 rounded-full font-bold text-base md:text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-[0_10px_20px_rgba(37,211,102,0.3)] hover:shadow-[0_15px_30px_rgba(37,211,102,0.5)] border border-[#20b858] w-full sm:w-auto">\n                        <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-14.416c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm.029 18.88c-1.161 0-2.305-.292-3.318-.844l-3.677.964.984-3.595c-.607-1.052-.927-2.246-.926-3.468.001-5.824 4.74-10.563 10.564-10.563 5.832 0 10.564 4.742 10.564 10.564 0 5.827-4.732 10.564-10.564 10.564z"/></svg>\n                        <span>Entre Em Contato</span>\n                    </a>`;
        
        content = content.replace(/<div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start w-full">[\s\S]*?<\/div>/, `<div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start w-full">\n${WPP_HERO_BTN}\n                </div>`);
    }

    fs.writeFileSync(file, content);
}

