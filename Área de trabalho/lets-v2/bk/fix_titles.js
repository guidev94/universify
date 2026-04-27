const fs = require('fs');

let html = fs.readFileSync('index.html', 'utf8');

// 1. Vittoria Chatbot
html = html.replace(
    /<div class="section-reveal order-2 lg:order-1">\s*<h2 class="text-4xl md:text-5xl lg:text-\[3\.5rem\] font-display font-black tracking-tighter mb-6 leading-\[1\.05\]">/g,
    `<div class="section-reveal order-2 lg:order-1">
                    <div class="inline-flex px-3 py-1.5 rounded-sm border border-borderline-light dark:border-borderline-dark bg-surface-light dark:bg-[#111] text-xs font-bold mb-6 uppercase tracking-widest text-subtle-light dark:text-subtle-dark shadow-[2px_2px_0px_rgba(0,0,0,0.1)] dark:shadow-[2px_2px_0px_rgba(255,255,255,0.1)] items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary animate-[pulse_2s_ease-in-out_infinite]"></span> Inteligência Artificial
                    </div>
                    <h2 class="text-4xl md:text-5xl lg:text-[3.5rem] font-display font-black tracking-tighter mb-6 leading-[1.05] text-black dark:text-white">`
);

// 2. Ecossistema Completo
html = html.replace(
    /<div class="inline-flex px-3 py-1 bg-primary\/10 text-primary rounded-full text-xs font-bold mb-6 tracking-widest uppercase items-center gap-2">\s*<i data-lucide="layers" class="w-4 h-4"><\/i> Ecossistema Completo\s*<\/div>\s*<h2 class="text-4xl md:text-5xl font-display font-black tracking-tight mb-6">\s*O fim das planilhas e da desorganização\.\s*<\/h2>/g,
    `<div class="inline-flex px-3 py-1.5 rounded-sm border border-borderline-light dark:border-borderline-dark bg-surface-light dark:bg-[#111] text-xs font-bold mb-6 uppercase tracking-widest text-subtle-light dark:text-subtle-dark shadow-[2px_2px_0px_rgba(0,0,0,0.1)] dark:shadow-[2px_2px_0px_rgba(255,255,255,0.1)] items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-primary animate-[pulse_2s_ease-in-out_infinite]"></span> Ecossistema Completo
                </div>
                <h2 class="text-4xl md:text-5xl lg:text-[3.5rem] font-display font-black tracking-tighter mb-6 leading-[1.05] text-black dark:text-white">
                    O fim das planilhas e da desorganização.
                </h2>`
);

// 3. Módulo Exclusivo (Vagas)
html = html.replace(
    /<div class="inline-flex px-3 py-1 bg-primary text-white rounded text-xs font-bold mb-6 tracking-widest uppercase">Módulo Exclusivo<\/div>\s*<h2 class="text-4xl md:text-5xl font-display font-bold tracking-tight mb-6">Recrutamento guiado por IA\.<\/h2>/g,
    `<div class="inline-flex px-3 py-1.5 rounded-sm border border-borderline-light dark:border-borderline-dark bg-surface-light dark:bg-[#111] text-xs font-bold mb-6 uppercase tracking-widest text-subtle-light dark:text-subtle-dark shadow-[2px_2px_0px_rgba(0,0,0,0.1)] dark:shadow-[2px_2px_0px_rgba(255,255,255,0.1)] items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary animate-[pulse_2s_ease-in-out_infinite]"></span> Módulo Exclusivo
                    </div>
                    <h2 class="text-4xl md:text-5xl lg:text-[3.5rem] font-display font-black tracking-tighter mb-6 leading-[1.05] text-black dark:text-white">
                        Recrutamento guiado por IA.
                    </h2>`
);

// 4. Férias
html = html.replace(
    /<div class="inline-flex px-3 py-1 bg-primary\/10 text-primary rounded-full text-xs font-bold mb-8 tracking-widest uppercase items-center gap-2">\s*<span class="w-2 h-2 rounded-full bg-primary animate-pulse"><\/span> Gestão de Férias e Folgas\s*<\/div>\s*<h2 class="text-4xl md:text-5xl lg:text-6xl font-display font-black tracking-tighter mb-8 leading-\[1\.05\] text-black dark:text-white">\s*Controle total, sem <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-orange-500">choques na operação\.<\/span>\s*<\/h2>/g,
    `<div class="inline-flex px-3 py-1.5 rounded-sm border border-borderline-light dark:border-borderline-dark bg-surface-light dark:bg-[#111] text-xs font-bold mb-6 uppercase tracking-widest text-subtle-light dark:text-subtle-dark shadow-[2px_2px_0px_rgba(0,0,0,0.1)] dark:shadow-[2px_2px_0px_rgba(255,255,255,0.1)] items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary animate-[pulse_2s_ease-in-out_infinite]"></span> Gestão de Férias e Folgas
                    </div>
                    <h2 class="text-4xl md:text-5xl lg:text-[3.5rem] font-display font-black tracking-tighter mb-8 leading-[1.05] text-black dark:text-white">
                        Controle total, sem <span class="text-primary relative inline-block">choques<div class="absolute bottom-1 md:bottom-2 left-0 w-full h-[8px] md:h-[12px] bg-primary/20 -z-10"></div></span> na operação.
                    </h2>`
);

html = html.replace(
    /<div class="inline-flex px-4 py-2 bg-primary\/10 border border-primary\/20 text-primary rounded-full text-xs font-bold mb-8 tracking-widest uppercase items-center gap-2">\s*<span class="w-2 h-2 rounded-full bg-primary animate-pulse"><\/span> Gestão de Férias e Folgas\s*<\/div>\s*<h2 class="text-4xl md:text-5xl lg:text-6xl font-display font-black tracking-tighter mb-8 leading-\[1\.05\] text-black dark:text-white">\s*Controle total, sem <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-orange-500">choques na operação\.<\/span>\s*<\/h2>/g,
    `<div class="inline-flex px-3 py-1.5 rounded-sm border border-borderline-light dark:border-borderline-dark bg-surface-light dark:bg-[#111] text-xs font-bold mb-6 uppercase tracking-widest text-subtle-light dark:text-subtle-dark shadow-[2px_2px_0px_rgba(0,0,0,0.1)] dark:shadow-[2px_2px_0px_rgba(255,255,255,0.1)] items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary animate-[pulse_2s_ease-in-out_infinite]"></span> Gestão de Férias e Folgas
                    </div>
                    <h2 class="text-4xl md:text-5xl lg:text-[3.5rem] font-display font-black tracking-tighter mb-8 leading-[1.05] text-black dark:text-white">
                        Controle total, sem <span class="text-primary relative inline-block">choques<div class="absolute bottom-1 md:bottom-2 left-0 w-full h-[8px] md:h-[12px] bg-primary/20 -z-10"></div></span> na operação.
                    </h2>`
);


// 5. Compliance
html = html.replace(
    /<h2 class="text-5xl md:text-6xl font-display font-black tracking-tighter mb-6 leading-\[1\.05\]">\s*O RH <span class="text-primary underline decoration-primary\/30 underline-offset-8">100% dentro da Lei\.<\/span>\s*<\/h2>/g,
    `<div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-sm border border-borderline-light dark:border-borderline-dark bg-surface-light dark:bg-[#111] text-xs font-bold mb-6 uppercase tracking-widest text-subtle-light dark:text-subtle-dark shadow-[2px_2px_0px_rgba(0,0,0,0.1)] dark:shadow-[2px_2px_0px_rgba(255,255,255,0.1)]">
                        <span class="w-2 h-2 rounded-full bg-primary animate-[pulse_2s_ease-in-out_infinite]"></span> Segurança Jurídica
                    </div>
                    <h2 class="text-4xl md:text-5xl lg:text-[3.5rem] font-display font-black tracking-tighter mb-6 leading-[1.05] text-black dark:text-white">
                        O seu RH <br \/>
                        <span class="text-primary relative inline-block">100% na Lei.<div class="absolute bottom-1 md:bottom-2 left-0 w-full h-[8px] md:h-[12px] bg-primary/20 -z-10"></div></span>
                    </h2>`
);

// 6. Depoimentos
html = html.replace(
    /<div class="text-center mb-16 section-reveal">\s*<div class="inline-flex px-3 py-1 bg-amber-500\/10 text-amber-600 dark:text-amber-400 rounded-full text-xs font-bold mb-6 tracking-widest uppercase items-center gap-2">\s*<i data-lucide="star" class="w-3 h-3 fill-current"><\/i> O que dizem sobre nós\s*<\/div>\s*<h2 class="text-4xl md:text-5xl font-display font-bold tracking-tight mb-4">Parcerias de longo prazo\.<\/h2>/g,
    `<div class="text-center mb-16 section-reveal max-w-3xl mx-auto">
                <div class="inline-flex px-3 py-1.5 rounded-sm border border-borderline-light dark:border-borderline-dark bg-surface-light dark:bg-[#111] text-xs font-bold mb-6 uppercase tracking-widest text-subtle-light dark:text-subtle-dark shadow-[2px_2px_0px_rgba(0,0,0,0.1)] dark:shadow-[2px_2px_0px_rgba(255,255,255,0.1)] items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-primary animate-[pulse_2s_ease-in-out_infinite]"></span> Depoimentos Reais
                </div>
                <h2 class="text-4xl md:text-5xl lg:text-[3.5rem] font-display font-black tracking-tighter mb-4 leading-[1.05] text-black dark:text-white">
                    Parcerias de longo prazo.
                </h2>`
);

// 7. Pricing
html = html.replace(
    /<div class="text-center mb-16 section-reveal">\s*<h2 class="text-4xl md:text-5xl font-display font-bold tracking-tight mb-4">Investimento escalável\.<\/h2>\s*<p class="text-lg text-subtle-light dark:text-subtle-dark mb-8">Planos claros\. Sem taxas de setup ocultas\.<\/p>/g,
    `<div class="text-center mb-16 section-reveal max-w-3xl mx-auto">
                <div class="inline-flex px-3 py-1.5 rounded-sm border border-borderline-light dark:border-borderline-dark bg-surface-light dark:bg-[#111] text-xs font-bold mb-6 uppercase tracking-widest text-subtle-light dark:text-subtle-dark shadow-[2px_2px_0px_rgba(0,0,0,0.1)] dark:shadow-[2px_2px_0px_rgba(255,255,255,0.1)] items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-primary animate-[pulse_2s_ease-in-out_infinite]"></span> Preços Transparentes
                </div>
                <h2 class="text-4xl md:text-5xl lg:text-[3.5rem] font-display font-black tracking-tighter mb-4 leading-[1.05] text-black dark:text-white">
                    Investimento escalável.
                </h2>
                <p class="text-lg text-subtle-light dark:text-subtle-dark font-medium mb-8">Planos desenhados para o momento da sua empresa. Sem taxas ocultas.</p>`
);

// 8. FAQ
html = html.replace(
    /<div class="max-w-\[800px\] mx-auto">\s*<div class="text-center mb-16 section-reveal">\s*<h2 class="text-3xl md:text-5xl font-display font-bold tracking-tight mb-4">Perguntas Frequentes\.<\/h2>/g,
    `<div class="max-w-[800px] mx-auto text-center mb-16 section-reveal">
                <div class="inline-flex px-3 py-1.5 rounded-sm border border-borderline-light dark:border-borderline-dark bg-surface-light dark:bg-[#111] text-xs font-bold mb-6 uppercase tracking-widest text-subtle-light dark:text-subtle-dark shadow-[2px_2px_0px_rgba(0,0,0,0.1)] dark:shadow-[2px_2px_0px_rgba(255,255,255,0.1)] items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-primary animate-[pulse_2s_ease-in-out_infinite]"></span> Dúvidas Comuns
                </div>
                <h2 class="text-4xl md:text-5xl lg:text-[3.5rem] font-display font-black tracking-tighter mb-4 leading-[1.05] text-black dark:text-white">
                    Perguntas Frequentes.
                </h2>`
);

// 9. CTA
html = html.replace(
    /<div class="max-w-\[800px\] mx-auto section-reveal">\s*<h2 class="text-5xl md:text-7xl font-display font-black tracking-tighter mb-8">\s*Sua empresa merece <br\/> um RH moderno\.\s*<\/h2>/g,
    `<div class="max-w-[800px] mx-auto section-reveal">
                <div class="inline-flex px-3 py-1.5 rounded-sm border border-borderline-light dark:border-borderline-dark bg-surface-light dark:bg-[#111] text-xs font-bold mb-6 uppercase tracking-widest text-subtle-light dark:text-subtle-dark shadow-[2px_2px_0px_rgba(0,0,0,0.1)] dark:shadow-[2px_2px_0px_rgba(255,255,255,0.1)] items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-primary animate-[pulse_2s_ease-in-out_infinite]"></span> Teste Gratuito
                </div>
                <h2 class="text-5xl md:text-6xl lg:text-[4.5rem] font-display font-black tracking-tighter mb-8 leading-[1.05] text-black dark:text-white">
                    Sua empresa merece <br/> um <span class="text-primary relative inline-block">RH moderno.<div class="absolute bottom-1 md:bottom-2 left-0 w-full h-[8px] md:h-[12px] bg-primary/20 -z-10"></div></span>
                </h2>`
);

// Zero atrito (Step Section)
html = html.replace(
    /<div class="pt-20 lg:pt-24 pb-12 lg:pb-16">\s*<div class="inline-flex px-3 py-1 bg-gray-100 dark:bg-white\/5 border border-borderline-light dark:border-borderline-dark rounded-full text-xs font-bold mb-6 tracking-widest uppercase items-center gap-2">\s*<span class="w-2 h-2 rounded-full bg-primary animate-pulse"><\/span> Ecossistema Inteligente\s*<\/div>\s*<h2 class="text-5xl md:text-6xl font-display font-black tracking-tighter mb-6 leading-\[1\.05\] text-black dark:text-white">\s*Uma plataforma\.<br\/>\s*<span class="text-primary">Zero atrito\.<\/span>\s*<\/h2>/g,
    `<div class="pt-24 lg:pt-32 pb-12 lg:pb-16">
                        <div class="inline-flex px-3 py-1.5 rounded-sm border border-borderline-light dark:border-borderline-dark bg-surface-light dark:bg-[#111] text-xs font-bold mb-6 uppercase tracking-widest text-subtle-light dark:text-subtle-dark shadow-[2px_2px_0px_rgba(0,0,0,0.1)] dark:shadow-[2px_2px_0px_rgba(255,255,255,0.1)] items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-primary animate-[pulse_2s_ease-in-out_infinite]"></span> Ecossistema Inteligente
                        </div>
                        <h2 class="text-4xl md:text-5xl lg:text-[3.5rem] font-display font-black tracking-tighter mb-6 leading-[1.05] text-black dark:text-white">
                            Uma plataforma.<br/>
                            <span class="text-primary">Zero atrito.</span>
                        </h2>`
);

fs.writeFileSync('index.html', html);
