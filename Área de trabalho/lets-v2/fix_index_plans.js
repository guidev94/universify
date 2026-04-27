const fs = require('fs');

let html = fs.readFileSync('index.html', 'utf8');

const regexPlanos = /<!-- 7\. PLANOS \(Clean Pricing\) -->[\s\S]*?<\/section>/;

const newPlanos = `<!-- 7. PLANOS (Clean Pricing) -->
    <section id="planos" class="py-24 lg:py-32 border-b border-borderline-light dark:border-borderline-dark relative bg-surface-light dark:bg-[#050505] overflow-hidden">
        <!-- Grid/Dot Background -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMCwgMCwgMCwgMC4wNSkiLz48L3N2Zz4=')] dark:bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMjU1LCAyNTUsIDI1NSwgMC4wNSkiLz48L3N2Zz4=')] opacity-50 pointer-events-none mask-fade-out"></div>

        <div class="max-w-[1400px] mx-auto px-6 relative z-10">
            <div class="text-center mb-16 section-reveal max-w-3xl mx-auto">
                <div class="inline-flex px-3 py-1.5 rounded-sm border border-borderline-light dark:border-borderline-dark bg-white dark:bg-[#111] text-xs font-bold mb-6 uppercase tracking-widest text-subtle-light dark:text-subtle-dark shadow-[2px_2px_0px_rgba(0,0,0,0.1)] dark:shadow-[2px_2px_0px_rgba(255,255,255,0.1)] items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-primary animate-[pulse_2s_ease-in-out_infinite]"></span> Flexibilidade e Escalabilidade
                </div>
                <h2 class="text-4xl md:text-5xl lg:text-[3.5rem] font-display font-black tracking-tighter mb-4 leading-[1.05] text-black dark:text-white">
                    Planos flexíveis para o tamanho e necessidades da sua empresa.
                </h2>
                <p class="text-lg text-subtle-light dark:text-subtle-dark font-medium mb-8">Soluções modulares para controle de ponto, timesheet, comunicação e muito mais. Cresça com previsibilidade e sem taxas ocultas.</p>
                <a href="planos.html" class="inline-flex items-center gap-2 text-primary font-bold hover:underline">Ver todas as soluções e módulos na página de Planos <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
            </div>

            <div class="grid md:grid-cols-3 gap-6 md:gap-4 lg:gap-8 items-center max-w-sm md:max-w-none mx-auto group/pricing">
                <!-- Plano 1 -->
                <div class="solid-card p-6 md:p-8 rounded-2xl flex flex-col items-center text-center section-reveal transition-all duration-500 hover:shadow-xl hover:-translate-y-2 bg-white dark:bg-[#111]">
                    <h3 class="text-xl font-bold mb-1 text-black dark:text-white">Time Ágil 10</h3>
                    <p class="text-xs text-subtle-light dark:text-subtle-dark uppercase tracking-widest mb-6">Até 10 Funcionários</p>
                    <div class="mb-8 flex flex-col items-center justify-center gap-1">
                        <span class="text-3xl font-display font-black text-black dark:text-white">Sob Consulta</span>
                        <span class="text-sm text-subtle-light dark:text-subtle-dark font-medium">Personalizado para o seu negócio</span>
                    </div>
                    <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20um%20especialista%20da%20Let%27s%20Work%21" target="_blank" class="w-full py-3 rounded-full text-center text-sm font-bold border border-borderline-light dark:border-borderline-dark hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black transition-all mb-8 block text-black dark:text-white">Falar com Consultor</a>
                    <ul class="space-y-4 text-sm font-medium flex-1 text-left w-full pl-2 lg:pl-4 text-subtle-light dark:text-subtle-dark">
                        <li class="flex items-start gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 shrink-0 mt-0.5"></i> Reconhecimento Facial</li>
                        <li class="flex items-start gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 shrink-0 mt-0.5"></i> Registro Offline e Localização</li>
                        <li class="flex items-start gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 shrink-0 mt-0.5"></i> Gestão de Documentos e Escalas</li>
                        <li class="flex items-start gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 shrink-0 mt-0.5"></i> Mais de 25 Relatórios</li>
                    </ul>
                </div>

                <!-- Plano 2 (Destaque) -->
                <div class="p-8 md:p-10 rounded-3xl border-2 border-primary relative flex flex-col items-center text-center section-reveal shadow-2xl bg-black dark:bg-white text-white dark:text-black md:scale-105 z-10 transition-all duration-500 hover:scale-110">
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 bg-primary text-white text-[10px] font-bold px-4 py-1 rounded-b-lg uppercase tracking-widest">Mais Adotado</div>
                    <h3 class="text-2xl font-bold mb-1 mt-2 text-white dark:text-black">Equipe Dinâmica 50</h3>
                    <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-6">Até 50 Funcionários</p>
                    <div class="mb-8 flex flex-col items-center justify-center gap-1">
                        <span class="text-4xl font-display font-black text-white dark:text-black">Orçamento Personalizado</span>
                        <span class="text-sm text-gray-400 dark:text-gray-500 font-medium">Custo-benefício ideal para crescer</span>
                    </div>
                    <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20um%20especialista%20da%20Let%27s%20Work%21" target="_blank" class="btn-primary magnetic block w-full py-4 rounded-full text-center text-sm font-bold mb-8 shadow-glow hover:scale-105 transition-transform">Solicitar Orçamento</a>
                    <ul class="space-y-4 text-sm font-bold flex-1 text-left w-full pl-2 lg:pl-4 text-gray-300 dark:text-gray-700">
                        <li class="flex items-start gap-3"><i data-lucide="check-circle-2" class="w-5 h-5 text-primary shrink-0 mt-0.5"></i> Tudo do plano Ágil</li>
                        <li class="flex items-start gap-3"><i data-lucide="check-circle-2" class="w-5 h-5 text-primary shrink-0 mt-0.5"></i> Ponto via WhatsApp (IA Vittoria)</li>
                        <li class="flex items-start gap-3"><i data-lucide="check-circle-2" class="w-5 h-5 text-primary shrink-0 mt-0.5"></i> Gestão de Escalas Avançada</li>
                        <li class="flex items-start gap-3"><i data-lucide="check-circle-2" class="w-5 h-5 text-primary shrink-0 mt-0.5"></i> Suporte Humanizado Dedicado</li>
                    </ul>
                </div>

                <!-- Plano 3 -->
                <div class="solid-card p-6 md:p-8 rounded-2xl flex flex-col items-center text-center section-reveal transition-all duration-500 hover:shadow-xl hover:-translate-y-2 bg-white dark:bg-[#111]">
                    <h3 class="text-xl font-bold mb-1 text-black dark:text-white">Time de Elite 50+</h3>
                    <p class="text-xs text-subtle-light dark:text-subtle-dark uppercase tracking-widest mb-6">Acima de 50 Funcionários</p>
                    <div class="mb-8 flex flex-col items-center justify-center gap-1">
                        <span class="text-3xl font-display font-black text-black dark:text-white">Solução Corporate</span>
                        <span class="text-sm text-subtle-light dark:text-subtle-dark font-medium">Sob medida para grandes volumes</span>
                    </div>
                    <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20um%20especialista%20da%20Let%27s%20Work%21" target="_blank" class="w-full py-3 rounded-full text-center text-sm font-bold border border-borderline-light dark:border-borderline-dark hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black transition-all mb-8 block text-black dark:text-white">Falar com Vendas</a>
                    <ul class="space-y-4 text-sm font-medium flex-1 text-left w-full pl-2 lg:pl-4 text-subtle-light dark:text-subtle-dark">
                        <li class="flex items-start gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 shrink-0 mt-0.5"></i> Módulos Ilimitados Customizados</li>
                        <li class="flex items-start gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 shrink-0 mt-0.5"></i> Rastreamento de Rotas Premium</li>
                        <li class="flex items-start gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 shrink-0 mt-0.5"></i> Gestão de Vagas com Inteligência</li>
                        <li class="flex items-start gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 shrink-0 mt-0.5"></i> Implantação Totalmente Assistida</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>`;

if (html.match(regexPlanos)) {
    html = html.replace(regexPlanos, newPlanos);
    fs.writeFileSync('index.html', html);
    console.log('index.html updated successfully without explicit prices.');
} else {
    console.log('Could not find the plans section.');
}

