const fs = require('fs');

let html = fs.readFileSync('planos.html', 'utf8');

// Header replacement
const headerRegex = /<header[\s\S]*?<\/header>/;
const newHeader = `<header class="pt-32 pb-16 md:pt-40 md:pb-24 bg-surface-light dark:bg-[#050505] border-b border-borderline-light dark:border-borderline-dark relative overflow-hidden">
    <div class="absolute inset-0 bg-[linear-gradient(rgba(0,0,0,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(0,0,0,0.02)_1px,transparent_1px)] dark:bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)]" style="background-size: 32px 32px;"></div>
    
    <div class="max-w-[1000px] mx-auto px-6 relative z-10 text-center">
        <nav class="flex items-center justify-center gap-2 text-sm text-subtle-light dark:text-subtle-dark mb-8 font-medium">
            <a href="index.html" class="hover:text-primary transition-colors">Início</a> 
            <i data-lucide="chevron-right" class="w-4 h-4 opacity-50"></i>
            <span class="text-black dark:text-white">Planos e Preços</span>
        </nav>
        
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-sm border border-borderline-light dark:border-borderline-dark bg-white dark:bg-[#111] text-xs font-bold mb-6 uppercase tracking-widest text-subtle-light dark:text-subtle-dark shadow-[2px_2px_0px_rgba(0,0,0,0.1)] dark:shadow-[2px_2px_0px_rgba(255,255,255,0.1)]">
            <span class="w-2 h-2 rounded-full bg-primary animate-[pulse_2s_ease-in-out_infinite]"></span> Sem Taxas Ocultas
        </div>

        <h1 class="text-4xl md:text-6xl lg:text-7xl font-display font-black text-black dark:text-white tracking-tight mb-8 leading-[1.1]">
            Planos Flexíveis para a sua <span class="text-primary">Empresa</span>
        </h1>
        
        <p class="text-xl md:text-2xl text-subtle-light dark:text-subtle-dark max-w-3xl mx-auto leading-relaxed mb-12">
            Nós não vendemos apenas um software, mas entregamos tranquilidade operacional e atendimento incomparável com soluções desenhadas para cada estágio do seu RH.
        </p>

        <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20um%20or%C3%A7amento%21" target="_blank" class="magnetic bg-black dark:bg-white text-white dark:text-black px-10 py-5 rounded-full font-bold text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-[0_10px_20px_rgba(0,0,0,0.1)] dark:shadow-[0_10px_20px_rgba(255,255,255,0.1)] inline-flex items-center gap-2 mx-auto">
            Fale com um Consultor
            <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
        </a>
    </div>
</header>`;

if(html.match(headerRegex)){
    html = html.replace(headerRegex, newHeader);
}

// Replace main content
const mainRegex = /<!-- MAIN CONTENT -->[\s\S]*?<\/div>\n<\/div>/;
const newMainContent = `<!-- MAIN CONTENT -->
<div class="py-20 md:py-32 relative bg-white dark:bg-[#0A0A0A]">
    <div class="max-w-[1200px] mx-auto px-6 relative z-10 space-y-24 md:space-y-32">
        
        <!-- Ponto e Escalas -->
        <section class="space-y-12">
            <div class="text-center max-w-3xl mx-auto">
                <div class="w-16 h-16 rounded-2xl bg-primary/10 flex items-center justify-center text-primary mx-auto mb-6">
                    <i data-lucide="clock" class="w-8 h-8"></i>
                </div>
                <h2 class="text-3xl md:text-4xl font-display font-bold text-black dark:text-white mb-4">Módulo: Controle de Ponto e Escalas</h2>
                <p class="text-lg text-subtle-light dark:text-subtle-dark">Gestão da jornada com máxima segurança. Escolha o tamanho ideal para a sua operação atual e evolua sob demanda.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                <!-- Plano 1 -->
                <div class="solid-card p-8 rounded-3xl bg-surface-light dark:bg-[#111] flex flex-col transition-all hover:border-primary/50">
                    <h3 class="text-2xl font-bold text-black dark:text-white mb-1">Time Ágil 10</h3>
                    <p class="text-sm font-bold text-primary uppercase tracking-widest mb-6">Até 10 Funcionários</p>
                    <p class="text-subtle-light dark:text-subtle-dark text-sm mb-8 leading-relaxed">Ideal para pequenas empresas estruturando o controle de ponto moderno e digital.</p>
                    
                    <ul class="space-y-3 text-sm text-subtle-light dark:text-subtle-dark font-medium flex-1 mb-8">
                        <li class="flex items-start gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 shrink-0"></i> Reconhecimento Facial</li>
                        <li class="flex items-start gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 shrink-0"></i> Registro Offline e Localização</li>
                        <li class="flex items-start gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 shrink-0"></i> Cadastro de Turnos e Gestores</li>
                        <li class="flex items-start gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 shrink-0"></i> Mais de 25 Relatórios Operacionais</li>
                        <li class="flex items-start gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 shrink-0"></i> Gestão de Escalas e Documentos</li>
                    </ul>
                    
                    <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20tenho%20interesse%20no%20plano%20Time%20%C3%81gil%2010" target="_blank" class="w-full py-4 text-center rounded-xl font-bold bg-white dark:bg-black border border-borderline-light dark:border-borderline-dark hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black transition-colors text-black dark:text-white">Solicitar Orçamento</a>
                </div>

                <!-- Plano 2 -->
                <div class="solid-card p-8 rounded-3xl bg-black dark:bg-white text-white dark:text-black flex flex-col transition-all border-primary shadow-2xl relative md:-translate-y-4">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-primary text-white text-[10px] font-bold px-4 py-1 rounded-full uppercase tracking-widest">Mais Vendido</div>
                    <h3 class="text-2xl font-bold mb-1">Equipe Dinâmica</h3>
                    <p class="text-sm font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-6">Até 50 Funcionários</p>
                    <p class="text-white/70 dark:text-black/70 text-sm mb-8 leading-relaxed">Crescimento acelerado com processos robustos de gestão e conformidade legal.</p>
                    
                    <ul class="space-y-3 text-sm text-gray-300 dark:text-gray-700 font-medium flex-1 mb-8">
                        <li class="flex items-start gap-3"><i data-lucide="check-circle-2" class="w-5 h-5 text-primary mt-0.5 shrink-0"></i> Tudo do plano Time Ágil</li>
                        <li class="flex items-start gap-3"><i data-lucide="check-circle-2" class="w-5 h-5 text-primary mt-0.5 shrink-0"></i> Ponto direto via WhatsApp</li>
                        <li class="flex items-start gap-3"><i data-lucide="check-circle-2" class="w-5 h-5 text-primary mt-0.5 shrink-0"></i> Gerenciamento massivo de alertas</li>
                        <li class="flex items-start gap-3"><i data-lucide="check-circle-2" class="w-5 h-5 text-primary mt-0.5 shrink-0"></i> Integração facilitada</li>
                    </ul>
                    
                    <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20tenho%20interesse%20no%20plano%20Equipe%20Din%C3%A2mica" target="_blank" class="w-full py-4 text-center rounded-xl font-bold btn-primary hover:scale-[1.02] transition-transform">Consultar Valores</a>
                </div>

                <!-- Plano 3 -->
                <div class="solid-card p-8 rounded-3xl bg-surface-light dark:bg-[#111] flex flex-col transition-all hover:border-primary/50">
                    <h3 class="text-2xl font-bold text-black dark:text-white mb-1">Time de Elite 50+</h3>
                    <p class="text-sm font-bold text-primary uppercase tracking-widest mb-6">Acima de 50 Funcionários</p>
                    <p class="text-subtle-light dark:text-subtle-dark text-sm mb-8 leading-relaxed">Planos personalizados para uso em larga escala, multi-filiais ou empresas estruturadas.</p>
                    
                    <ul class="space-y-3 text-sm text-subtle-light dark:text-subtle-dark font-medium flex-1 mb-8">
                        <li class="flex items-start gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 shrink-0"></i> Volume massivo de usuários</li>
                        <li class="flex items-start gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 shrink-0"></i> Implantação e treinamentos customizados</li>
                        <li class="flex items-start gap-3"><i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 shrink-0"></i> Suporte com SLAs dedicados</li>
                    </ul>
                    
                    <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20tenho%20interesse%20no%20plano%20Corporate%20para%20mais%20de%2050%20funcion%C3%A1rios" target="_blank" class="w-full py-4 text-center rounded-xl font-bold bg-white dark:bg-black border border-borderline-light dark:border-borderline-dark hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black transition-colors text-black dark:text-white">Falar com Departamento de Vendas</a>
                </div>
            </div>
        </section>

        <!-- Outros Módulos -->
        <section class="space-y-12">
            <div class="text-center max-w-3xl mx-auto">
                <div class="w-16 h-16 rounded-2xl bg-primary/10 flex items-center justify-center text-primary mx-auto mb-6">
                    <i data-lucide="layers" class="w-8 h-8"></i>
                </div>
                <h2 class="text-3xl md:text-4xl font-display font-bold text-black dark:text-white mb-4">Adicione Novos Módulos</h2>
                <p class="text-lg text-subtle-light dark:text-subtle-dark">Transforme o Let's Work na sua central única de gestão incorporando novas ferramentas conforme sua necessidade de crescimento.</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                
                <!-- Rastreamento -->
                <div class="solid-card p-8 md:p-10 rounded-3xl bg-surface-light dark:bg-[#111] flex flex-col group border-transparent hover:border-primary/20">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-white dark:bg-black rounded-xl border border-borderline-light dark:border-borderline-dark flex items-center justify-center text-black dark:text-white"><i data-lucide="map" class="w-6 h-6"></i></div>
                        <div>
                            <h3 class="text-2xl font-bold text-black dark:text-white">Rastreamento de Rotas</h3>
                            <p class="text-xs font-bold text-subtle-light dark:text-subtle-dark uppercase tracking-widest mt-1">Equipes Externas</p>
                        </div>
                    </div>
                    <ul class="space-y-3 text-sm text-subtle-light dark:text-subtle-dark font-medium mb-8">
                        <li class="flex items-center gap-3"><i data-lucide="check" class="w-4 h-4 text-primary"></i> Rastreamento de rotas e equipes logísticas</li>
                        <li class="flex items-center gap-3"><i data-lucide="check" class="w-4 h-4 text-primary"></i> Relatórios de permanência em locais</li>
                        <li class="flex items-center gap-3"><i data-lucide="check" class="w-4 h-4 text-primary"></i> Integração com o Ponto Eletrônico</li>
                    </ul>
                    <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20quero%20saber%20mais%20sobre%20Rastreamento%20de%20Rotas" target="_blank" class="mt-auto font-bold text-primary flex items-center gap-2 hover:gap-4 transition-all">Consulte este módulo <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
                </div>

                <!-- Projetos -->
                <div class="solid-card p-8 md:p-10 rounded-3xl bg-surface-light dark:bg-[#111] flex flex-col group border-transparent hover:border-primary/20">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-white dark:bg-black rounded-xl border border-borderline-light dark:border-borderline-dark flex items-center justify-center text-black dark:text-white"><i data-lucide="folder-kanban" class="w-6 h-6"></i></div>
                        <div>
                            <h3 class="text-2xl font-bold text-black dark:text-white">Timesheet & Projetos</h3>
                            <p class="text-xs font-bold text-subtle-light dark:text-subtle-dark uppercase tracking-widest mt-1">Produtividade</p>
                        </div>
                    </div>
                    <ul class="space-y-3 text-sm text-subtle-light dark:text-subtle-dark font-medium mb-8">
                        <li class="flex items-center gap-3"><i data-lucide="check" class="w-4 h-4 text-primary"></i> Cadastro de clientes e faturamento</li>
                        <li class="flex items-center gap-3"><i data-lucide="check" class="w-4 h-4 text-primary"></i> Cronômetro de horas dedicado</li>
                        <li class="flex items-center gap-3"><i data-lucide="check" class="w-4 h-4 text-primary"></i> Relatório de rentabilidade cruzado</li>
                    </ul>
                    <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20quero%20saber%20mais%20sobre%20Timesheet%20e%20Projetos" target="_blank" class="mt-auto font-bold text-primary flex items-center gap-2 hover:gap-4 transition-all">Consulte este módulo <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
                </div>

                <!-- Comunicação -->
                <div class="solid-card p-8 md:p-10 rounded-3xl bg-surface-light dark:bg-[#111] flex flex-col group border-transparent hover:border-primary/20">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-white dark:bg-black rounded-xl border border-borderline-light dark:border-borderline-dark flex items-center justify-center text-black dark:text-white"><i data-lucide="message-square" class="w-6 h-6"></i></div>
                        <div>
                            <h3 class="text-2xl font-bold text-black dark:text-white">Comunicação de Equipe</h3>
                            <p class="text-xs font-bold text-subtle-light dark:text-subtle-dark uppercase tracking-widest mt-1">Chat Interno</p>
                        </div>
                    </div>
                    <ul class="space-y-3 text-sm text-subtle-light dark:text-subtle-dark font-medium mb-8">
                        <li class="flex items-center gap-3"><i data-lucide="check" class="w-4 h-4 text-primary"></i> Chat corporativo individual e grupos</li>
                        <li class="flex items-center gap-3"><i data-lucide="check" class="w-4 h-4 text-primary"></i> Envio seguro de arquivos e áudios</li>
                        <li class="flex items-center gap-3"><i data-lucide="check" class="w-4 h-4 text-primary"></i> Sem depender de redes pessoais</li>
                    </ul>
                    <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20quero%20saber%20mais%20sobre%20Comunica%C3%A7%C3%A3o%20de%20Equipe" target="_blank" class="mt-auto font-bold text-primary flex items-center gap-2 hover:gap-4 transition-all">Consulte este módulo <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
                </div>

                <!-- Vagas e Ferias -->
                <div class="solid-card p-8 md:p-10 rounded-3xl bg-surface-light dark:bg-[#111] flex flex-col group border-transparent hover:border-primary/20">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-white dark:bg-black rounded-xl border border-borderline-light dark:border-borderline-dark flex items-center justify-center text-black dark:text-white"><i data-lucide="briefcase" class="w-6 h-6"></i></div>
                        <div>
                            <h3 class="text-2xl font-bold text-black dark:text-white">Gestão de Vagas & Férias</h3>
                            <p class="text-xs font-bold text-subtle-light dark:text-subtle-dark uppercase tracking-widest mt-1">DP Automático</p>
                        </div>
                    </div>
                    <ul class="space-y-3 text-sm text-subtle-light dark:text-subtle-dark font-medium mb-8">
                        <li class="flex items-center gap-3"><i data-lucide="check" class="w-4 h-4 text-primary"></i> Organização centralizada de férias</li>
                        <li class="flex items-center gap-3"><i data-lucide="check" class="w-4 h-4 text-primary"></i> ATS completo (Recrutamento e seleção com IA)</li>
                        <li class="flex items-center gap-3"><i data-lucide="check" class="w-4 h-4 text-primary"></i> Análise de entrevistas gerada por IA</li>
                    </ul>
                    <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20quero%20saber%20mais%20sobre%20Vagas%20e%20F%C3%A9rias" target="_blank" class="mt-auto font-bold text-primary flex items-center gap-2 hover:gap-4 transition-all">Consulte este módulo <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
                </div>

            </div>
        </section>

        <!-- CTA Final -->
        <section class="text-center bg-black dark:bg-white text-white dark:text-black p-10 md:p-16 rounded-[2rem] relative overflow-hidden shadow-2xl">
            <div class="absolute -top-24 -right-24 w-64 h-64 bg-primary/30 blur-3xl rounded-full"></div>
            <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-orange-500/30 blur-3xl rounded-full"></div>
            
            <div class="relative z-10 max-w-3xl mx-auto">
                <h2 class="text-sm font-bold tracking-widest uppercase text-white/60 dark:text-black/60 mb-4">Montagem de Planos</h2>
                <p class="text-2xl md:text-4xl font-display font-black mb-6 leading-tight">Monte a solução perfeita <br><span class="text-primary">para o tamanho do seu negócio.</span></p>
                <p class="text-lg md:text-xl font-light text-white/80 dark:text-black/80 mb-10">Converse com nossos especialistas para formatarmos um orçamento sem compromisso baseado unicamente nas ferramentas que vão trazer retorno financeiro e operacional para você.</p>
                
                <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20montar%20meu%20plano%21" target="_blank" class="magnetic bg-white dark:bg-black text-black dark:text-white px-10 py-5 rounded-full font-bold text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-xl inline-flex items-center gap-2">
                    Falar com um Especialista
                    <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        </section>

    </div>
</div>
`;

if(html.match(mainRegex)) {
    html = html.replace(mainRegex, newMainContent);
}

fs.writeFileSync('planos.html', html);
console.log('planos.html created/updated');

