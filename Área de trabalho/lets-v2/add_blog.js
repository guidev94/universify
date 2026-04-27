const fs = require('fs');

let html = fs.readFileSync('index.html', 'utf8');

const faqRegex = /<section class="py-24 lg:py-32 border-b border-borderline-light dark:border-borderline-dark">\s*<div class="max-w-\[1400px\] mx-auto px-6">\s*<div class="max-w-\[800px\] mx-auto text-center mb-10 section-reveal">/;

const blogSection = `
    <!-- BLOG SECTION -->
    <section class="py-24 lg:py-32 border-b border-borderline-light dark:border-borderline-dark bg-surface-light dark:bg-[#050505]">
        <div class="max-w-[1400px] mx-auto px-6">
            
            <div class="flex flex-col md:flex-row justify-between items-end gap-6 mb-16 section-reveal">
                <div class="max-w-2xl">
                    <div class="inline-flex px-3 py-1.5 rounded-sm border border-borderline-light dark:border-borderline-dark bg-white dark:bg-[#111] text-xs font-bold mb-6 uppercase tracking-widest text-subtle-light dark:text-subtle-dark shadow-[2px_2px_0px_rgba(0,0,0,0.1)] dark:shadow-[2px_2px_0px_rgba(255,255,255,0.1)] items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary animate-[pulse_2s_ease-in-out_infinite]"></span> Let's News
                    </div>
                    <h2 class="text-4xl md:text-5xl font-display font-black tracking-tighter mb-4 text-black dark:text-white">
                        Conteúdo exclusivo para <span class="text-primary">lideranças de RH</span>.
                    </h2>
                </div>
                <a href="blog.html" class="inline-flex items-center gap-2 font-bold hover:text-primary transition-colors text-black dark:text-white group">
                    Ver todos os artigos <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>

            <!-- CSS Grid for Blog Posts: 1 featured on left (spans 2 rows), 4 normal on right -->
            <div class="grid lg:grid-cols-12 gap-8">
                
                <!-- Destaque Principal -->
                <a href="https://www.letswork.com.br/noticias/integracao-entre-sistema-de-ponto-e-esocial-o-que-e-e-o-que-nao-e-obrigatorio-hoje/" target="_blank" class="lg:col-span-7 group block relative rounded-3xl overflow-hidden bg-black dark:bg-[#111] aspect-square lg:aspect-auto h-full min-h-[400px]">
                    <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&q=80&w=1200" alt="Dashboard Analítico eSocial" class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:opacity-40 group-hover:scale-105 transition-all duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent"></div>
                    
                    <div class="absolute inset-0 p-8 md:p-12 flex flex-col justify-end z-10">
                        <div class="flex items-center gap-3 text-xs font-bold text-white mb-4">
                            <span class="bg-primary px-3 py-1 rounded-full uppercase tracking-wider">Destaque</span>
                            <span>12 de dezembro de 2025</span>
                        </div>
                        <h3 class="text-2xl md:text-4xl font-display font-bold text-white mb-4 leading-tight group-hover:text-primary transition-colors duration-300">
                            Integração entre sistema de ponto e eSocial: o que é (e o que não é) obrigatório hoje
                        </h3>
                        <p class="text-white/80 text-sm md:text-base line-clamp-2 md:line-clamp-3">
                            Não existe, hoje, obrigação legal para integração automática do sistema de ponto ao eSocial. A lei exige que empresas com mais de 20...
                        </p>
                    </div>
                </a>

                <!-- Grid com 4 Posts Menores -->
                <div class="lg:col-span-5 grid sm:grid-cols-2 lg:grid-cols-1 gap-6">
                    
                    <!-- Artigo 1 -->
                    <a href="https://www.letswork.com.br/noticias/rh-inteligente-como-decisoes-baseadas-em-dados-vao-definir-quem-lidera-e-quem-vai-ficar-para-tras-em-2025/" target="_blank" class="group flex flex-col lg:flex-row gap-4 items-start">
                        <div class="w-full lg:w-32 aspect-video shrink-0 rounded-2xl overflow-hidden relative">
                            <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&q=80&w=400" alt="Decisões Baseadas em Dados" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-colors"></div>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase tracking-widest font-bold text-primary mb-2 block">8 de julho de 2025</span>
                            <h4 class="font-bold text-black dark:text-white leading-snug line-clamp-2 group-hover:text-primary transition-colors">RH inteligente: Como Decisões Baseadas em Dados Vão Definir quem Lidera...</h4>
                        </div>
                    </a>

                    <!-- Artigo 2 -->
                    <a href="https://www.letswork.com.br/noticias/%f0%9f%9a%a8-rh-2025-como-blindar-sua-equipe-contra-a-sobrecarga-emocional-e-a-automacao-mal-planejada/" target="_blank" class="group flex flex-col lg:flex-row gap-4 items-start">
                        <div class="w-full lg:w-32 aspect-video shrink-0 rounded-2xl overflow-hidden relative">
                            <img src="https://images.unsplash.com/photo-1573164713988-8665fc963095?auto=format&fit=crop&q=80&w=400" alt="Saúde Mental no Trabalho" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-colors"></div>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase tracking-widest font-bold text-primary mb-2 block">1 de julho de 2025</span>
                            <h4 class="font-bold text-black dark:text-white leading-snug line-clamp-2 group-hover:text-primary transition-colors">RH 2025: Como blindar sua equipe contra a sobrecarga emocional e automação...</h4>
                        </div>
                    </a>

                    <!-- Artigo 3 -->
                    <a href="https://www.letswork.com.br/noticias/rh-2025-como-usar-automacao-people-analytics-e-humanizacao-pra-atrair-e-reter-talentos-sem-gastar-rios-de-dinheiro/" target="_blank" class="group flex flex-col lg:flex-row gap-4 items-start">
                        <div class="w-full lg:w-32 aspect-video shrink-0 rounded-2xl overflow-hidden relative">
                            <img src="https://images.unsplash.com/photo-1552581234-26160f608093?auto=format&fit=crop&q=80&w=400" alt="People Analytics" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-colors"></div>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase tracking-widest font-bold text-primary mb-2 block">24 de junho de 2025</span>
                            <h4 class="font-bold text-black dark:text-white leading-snug line-clamp-2 group-hover:text-primary transition-colors">Como usar automação, People Analytics e humanização para atrair e reter talentos...</h4>
                        </div>
                    </a>

                    <!-- Artigo 4 -->
                    <a href="https://www.letswork.com.br/noticias/rh-5-0-na-pratica-como-ia-e-empatia-estao-transformando-o-recrutamento-nas-pequenas-e-medias-empresas/" target="_blank" class="group flex flex-col lg:flex-row gap-4 items-start">
                        <div class="w-full lg:w-32 aspect-video shrink-0 rounded-2xl overflow-hidden relative">
                            <img src="https://images.unsplash.com/photo-1531482615713-2afd69097998?auto=format&fit=crop&q=80&w=400" alt="Recrutamento e IA" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-colors"></div>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase tracking-widest font-bold text-primary mb-2 block">11 de junho de 2025</span>
                            <h4 class="font-bold text-black dark:text-white leading-snug line-clamp-2 group-hover:text-primary transition-colors">RH 5.0 na Prática: Como IA e Empatia Estão Transformando o Recrutamento...</h4>
                        </div>
                    </a>

                </div>
            </div>

        </div>
    </section>

    `;

if (html.match(faqRegex)) {
    html = html.replace(faqRegex, blogSection + '<section class="py-24 lg:py-32 border-b border-borderline-light dark:border-borderline-dark">\n        <div class="max-w-[1400px] mx-auto px-6">\n            <div class="max-w-[800px] mx-auto text-center mb-10 section-reveal">');
    fs.writeFileSync('index.html', html);
    console.log('Blog section added successfully!');
} else {
    console.log('Could not find FAQ regex match');
}

