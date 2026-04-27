const fs = require('fs');

const WPP_URL = "https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20um%20especialista%20da%20Let%27s%20Work%21";

let pricingHTML = `
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
                        <span class="text-4xl font-display font-black">R$89</span>
                        <span class="text-subtle-light dark:text-subtle-dark font-medium">,90/mês</span>
                    </div>
                    <a href="${WPP_URL}" target="_blank" class="w-full py-3 rounded-full text-sm font-bold border border-borderline-light dark:border-borderline-dark hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black active:scale-95 transition-all mb-8 block">Contrate Agora</a>
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
                    <a href="${WPP_URL}" target="_blank" class="btn-primary magnetic w-full py-4 rounded-full text-sm font-bold mb-8 shadow-glow hover:scale-105 transition-transform block">Assinar Agora</a>
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
                        <span class="text-4xl font-display font-black">Sob Consulta</span>
                    </div>
                    <a href="${WPP_URL}" target="_blank" class="w-full py-3 rounded-full text-sm font-bold border border-borderline-light dark:border-borderline-dark hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black active:scale-95 transition-all mb-8 block">Falar com Vendas</a>
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

let index = fs.readFileSync('index.html', 'utf8');

// Replace everything from <!-- 7. PLANOS (Clean Pricing) --> until <!-- 7.5 FAQ SECTION
index = index.replace(/<!-- 7\. PLANOS \(Clean Pricing\) -->[\s\S]*?<!-- 7\.5 FAQ SECTION/, pricingHTML + '\n\n    <!-- 7.5 FAQ SECTION');

fs.writeFileSync('index.html', index);

