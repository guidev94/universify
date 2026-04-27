const fs = require('fs');

const heroHtml = `
<header class="pt-32 pb-16 md:pt-40 md:pb-24 bg-surface-light dark:bg-[#050505] border-b border-borderline-light dark:border-borderline-dark relative overflow-hidden">
    <!-- Grid/Dot Background -->
    <div class="absolute inset-0 bg-[linear-gradient(rgba(0,0,0,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(0,0,0,0.02)_1px,transparent_1px)] dark:bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)]" style="background-size: 32px 32px;"></div>
    
    <div class="max-w-[1000px] mx-auto px-6 relative z-10 text-center">
        <nav class="flex items-center justify-center gap-2 text-sm text-subtle-light dark:text-subtle-dark mb-8 font-medium">
            <a href="/" class="hover:text-primary transition-colors">Início</a> 
            <i data-lucide="chevron-right" class="w-4 h-4 opacity-50"></i>
            <span class="text-black dark:text-white">Como Funciona</span>
        </nav>
        
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-sm border border-borderline-light dark:border-borderline-dark bg-white dark:bg-[#111] text-xs font-bold mb-6 uppercase tracking-widest text-subtle-light dark:text-subtle-dark shadow-[2px_2px_0px_rgba(0,0,0,0.1)] dark:shadow-[2px_2px_0px_rgba(255,255,255,0.1)]">
            <span class="w-2 h-2 rounded-full bg-primary animate-[pulse_2s_ease-in-out_infinite]"></span> Plataforma All-in-One
        </div>

        <h1 class="text-4xl md:text-6xl lg:text-7xl font-display font-black text-black dark:text-white tracking-tight mb-8 leading-[1.1]">
            A Evolução da Gestão de RH com <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-orange-500">Inteligência Artificial</span>
        </h1>
        
        <p class="text-xl md:text-2xl text-subtle-light dark:text-subtle-dark max-w-3xl mx-auto leading-relaxed mb-12">
            A Let's Work é uma plataforma completa que integra IA, automação e usabilidade para aprimorar a forma como empresas contratam, desenvolvem e gerenciam pessoas. Nossa proposta é simples: <strong>reduzir a complexidade e aumentar a eficiência operacional</strong>.
        </p>

        <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20um%20especialista%20da%20Let%27s%20Work%21" target="_blank" class="magnetic bg-black dark:bg-white text-white dark:text-black px-10 py-5 rounded-full font-bold text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-[0_10px_20px_rgba(0,0,0,0.1)] dark:shadow-[0_10px_20px_rgba(255,255,255,0.1)] inline-flex items-center gap-2 mx-auto">
            Agendar Demonstração
            <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
        </a>
    </div>
</header>
`;

const mainContent = `
<!-- MAIN CONTENT -->
<div class="py-20 md:py-32 relative bg-white dark:bg-[#0A0A0A]">
    <div class="max-w-[1000px] mx-auto px-6 relative z-10 space-y-24 md:space-y-32">
        
        <!-- O Problema -->
        <section class="border-l-4 border-primary pl-6 md:pl-10">
            <h2 class="text-sm font-bold tracking-widest uppercase text-primary mb-4">O Cenário Atual</h2>
            <h3 class="text-3xl md:text-4xl font-display font-bold text-black dark:text-white mb-6">O Problema</h3>
            <p class="text-lg md:text-xl text-subtle-light dark:text-subtle-dark mb-8 leading-relaxed">
                Empresas ainda enfrentam desafios críticos na gestão de RH que atrasam o crescimento e aumentam os custos operacionais:
            </p>
            <ul class="space-y-4">
                <li class="flex items-start gap-4">
                    <i data-lucide="x-circle" class="w-6 h-6 text-red-500 shrink-0 mt-0.5"></i>
                    <span class="text-lg text-black dark:text-white font-medium">Processos de recrutamento lentos e manuais</span>
                </li>
                <li class="flex items-start gap-4">
                    <i data-lucide="x-circle" class="w-6 h-6 text-red-500 shrink-0 mt-0.5"></i>
                    <span class="text-lg text-black dark:text-white font-medium">Falta de integração entre ferramentas</span>
                </li>
                <li class="flex items-start gap-4">
                    <i data-lucide="x-circle" class="w-6 h-6 text-red-500 shrink-0 mt-0.5"></i>
                    <span class="text-lg text-black dark:text-white font-medium">Dificuldade em acompanhar performance e desenvolvimento</span>
                </li>
                <li class="flex items-start gap-4">
                    <i data-lucide="x-circle" class="w-6 h-6 text-red-500 shrink-0 mt-0.5"></i>
                    <span class="text-lg text-black dark:text-white font-medium">Gestão ineficiente de ponto, escalas e produtividade</span>
                </li>
                <li class="flex items-start gap-4">
                    <i data-lucide="x-circle" class="w-6 h-6 text-red-500 shrink-0 mt-0.5"></i>
                    <span class="text-lg text-black dark:text-white font-medium">Alto custo operacional e retrabalho</span>
                </li>
            </ul>
        </section>

        <!-- A Solução -->
        <section class="solid-card p-8 md:p-16 rounded-[2rem] bg-black dark:bg-white text-white dark:text-black border-transparent relative overflow-hidden group shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-br from-primary/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>
            <div class="relative z-10">
                <h2 class="text-sm font-bold tracking-widest uppercase text-white/50 dark:text-black/50 mb-4">A Revolução</h2>
                <h3 class="text-3xl md:text-5xl font-display font-bold mb-6">A Solução Let's Work</h3>
                <p class="text-xl md:text-2xl font-light mb-10 leading-relaxed text-white/80 dark:text-black/80">
                    A Let's Work representa a evolução do RH, centralizando toda a jornada do colaborador em uma única plataforma, com apoio da <strong>Vittoria</strong>, nossa assistente de IA.
                </p>
                <div class="bg-white/10 dark:bg-black/5 rounded-2xl p-8 backdrop-blur-sm border border-white/10 dark:border-black/10">
                    <h4 class="text-xl font-bold mb-4 flex items-center gap-3">
                        <i data-lucide="bot" class="w-6 h-6 text-primary"></i> A Vittoria como facilitadora
                    </h4>
                    <p class="mb-6 text-white/70 dark:text-black/70">A Vittoria não é apenas um recurso — ela atua como uma facilitadora da operação:</p>
                    <ul class="grid md:grid-cols-2 gap-4">
                        <li class="flex items-center gap-3"><i data-lucide="check-circle-2" class="w-5 h-5 text-green-400 dark:text-green-600"></i> Entende comandos em linguagem natural</li>
                        <li class="flex items-center gap-3"><i data-lucide="check-circle-2" class="w-5 h-5 text-green-400 dark:text-green-600"></i> Navega automaticamente pela plataforma</li>
                        <li class="flex items-center gap-3"><i data-lucide="check-circle-2" class="w-5 h-5 text-green-400 dark:text-green-600"></i> Executa tarefas com rapidez e precisão</li>
                        <li class="flex items-center gap-3"><i data-lucide="check-circle-2" class="w-5 h-5 text-green-400 dark:text-green-600"></i> Apoia gestores na tomada de decisão</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Como Funciona Steps -->
        <section>
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-display font-black text-black dark:text-white mb-6">Como Funciona</h2>
                <p class="text-xl text-subtle-light dark:text-subtle-dark max-w-2xl mx-auto">Uma suíte completa dividida em 9 pilares fundamentais, todos impulsionados por Inteligência Artificial.</p>
            </div>

            <div class="space-y-6">
                <!-- Step 1 -->
                <div class="solid-card p-6 md:p-8 rounded-2xl bg-surface-light dark:bg-[#111] flex flex-col md:flex-row gap-6 items-start group hover:border-primary/50 transition-colors">
                    <div class="w-16 h-16 rounded-2xl bg-primary/10 flex items-center justify-center shrink-0 text-primary">
                        <span class="text-2xl font-black">1</span>
                    </div>
                    <div class="w-full">
                        <h3 class="text-2xl font-bold text-black dark:text-white mb-4">Criação de Vagas com IA</h3>
                        <div class="bg-white dark:bg-black/50 p-4 rounded-xl border border-borderline-light dark:border-borderline-dark mb-4 italic text-subtle-light dark:text-subtle-dark text-sm md:text-base flex items-center gap-3">
                            <i data-lucide="mic" class="w-5 h-5 text-primary shrink-0"></i>
                            "Vittoria, crie uma vaga para desenvolvedor backend"
                        </div>
                        <ul class="grid sm:grid-cols-3 gap-3 text-sm md:text-base text-subtle-light dark:text-subtle-dark">
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Gera descrição da vaga</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Sugere requisitos</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Publica automaticamente</li>
                        </ul>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="solid-card p-6 md:p-8 rounded-2xl bg-surface-light dark:bg-[#111] flex flex-col md:flex-row gap-6 items-start group hover:border-primary/50 transition-colors">
                    <div class="w-16 h-16 rounded-2xl bg-primary/10 flex items-center justify-center shrink-0 text-primary">
                        <span class="text-2xl font-black">2</span>
                    </div>
                    <div class="w-full">
                        <h3 class="text-2xl font-bold text-black dark:text-white mb-4">Recrutamento Inteligente</h3>
                        <ul class="grid sm:grid-cols-2 gap-4 text-sm md:text-base text-subtle-light dark:text-subtle-dark">
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Triagem automatizada de currículos</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Análise de perfil e compatibilidade</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Apoio na avaliação de entrevistas</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Classificação dos candidatos mais aderentes</li>
                        </ul>
                    </div>
                </div>

                <!-- Step 3 & 4 (Grid) -->
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="solid-card p-6 md:p-8 rounded-2xl bg-surface-light dark:bg-[#111] group hover:border-primary/50 transition-colors">
                        <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary mb-6">
                            <span class="text-xl font-black">3</span>
                        </div>
                        <h3 class="text-xl font-bold text-black dark:text-white mb-4">Admissão Digital</h3>
                        <ul class="space-y-3 text-sm text-subtle-light dark:text-subtle-dark">
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Contratação realizada dentro da plataforma</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Coleta e assinatura de documentos</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Organização completa do dossiê</li>
                        </ul>
                    </div>
                    <div class="solid-card p-6 md:p-8 rounded-2xl bg-surface-light dark:bg-[#111] group hover:border-primary/50 transition-colors">
                        <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary mb-6">
                            <span class="text-xl font-black">4</span>
                        </div>
                        <h3 class="text-xl font-bold text-black dark:text-white mb-4">Gestão de Documentos</h3>
                        <ul class="space-y-3 text-sm text-subtle-light dark:text-subtle-dark">
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Armazenamento seguro e estruturado</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Assinaturas digitais</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Acesso rápido e centralizado</li>
                        </ul>
                    </div>
                </div>

                <!-- Step 5 & 6 (Grid) -->
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="solid-card p-6 md:p-8 rounded-2xl bg-surface-light dark:bg-[#111] group hover:border-primary/50 transition-colors">
                        <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary mb-6">
                            <span class="text-xl font-black">5</span>
                        </div>
                        <h3 class="text-xl font-bold text-black dark:text-white mb-4">PDI e Performance</h3>
                        <ul class="space-y-3 text-sm text-subtle-light dark:text-subtle-dark">
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Criação de PDI</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Definição de metas e entregáveis</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Acompanhamento contínuo de desempenho</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Insights gerados por IA</li>
                        </ul>
                    </div>
                    <div class="solid-card p-6 md:p-8 rounded-2xl bg-surface-light dark:bg-[#111] group hover:border-primary/50 transition-colors">
                        <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary mb-6">
                            <span class="text-xl font-black">6</span>
                        </div>
                        <h3 class="text-xl font-bold text-black dark:text-white mb-4">Treinamento e Desenvolvimento</h3>
                        <ul class="space-y-3 text-sm text-subtle-light dark:text-subtle-dark">
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Gestão da evolução dos colaboradores</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Desenvolvimento orientado por dados</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-primary shrink-0"></i> Fortalecimento de competências estratégicas</li>
                        </ul>
                    </div>
                </div>

                <!-- Step 7, 8 & 9 (Grid) -->
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="solid-card p-6 rounded-2xl bg-surface-light dark:bg-[#111] group hover:border-primary/50 transition-colors">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-4">
                            <span class="font-black">7</span>
                        </div>
                        <h3 class="text-lg font-bold text-black dark:text-white mb-3">Controle de Ponto</h3>
                        <ul class="space-y-2 text-sm text-subtle-light dark:text-subtle-dark">
                            <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-primary mt-0.5 shrink-0"></i> Registro de ponto simplificado</li>
                            <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-primary mt-0.5 shrink-0"></i> Controle de horas extras</li>
                            <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-primary mt-0.5 shrink-0"></i> Banco de horas automatizado</li>
                            <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-primary mt-0.5 shrink-0"></i> Gestão de atrasos e atestados</li>
                        </ul>
                    </div>
                    <div class="solid-card p-6 rounded-2xl bg-surface-light dark:bg-[#111] group hover:border-primary/50 transition-colors">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-4">
                            <span class="font-black">8</span>
                        </div>
                        <h3 class="text-lg font-bold text-black dark:text-white mb-3">Gestão de Escalas</h3>
                        <ul class="space-y-2 text-sm text-subtle-light dark:text-subtle-dark">
                            <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-primary mt-0.5 shrink-0"></i> Ajustes visuais e rápidos</li>
                            <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-primary mt-0.5 shrink-0"></i> Melhor distribuição da jornada</li>
                            <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-primary mt-0.5 shrink-0"></i> Redução de conflitos e horas</li>
                        </ul>
                    </div>
                    <div class="solid-card p-6 rounded-2xl bg-surface-light dark:bg-[#111] group hover:border-primary/50 transition-colors">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-4">
                            <span class="font-black">9</span>
                        </div>
                        <h3 class="text-lg font-bold text-black dark:text-white mb-3">Processos e Projetos</h3>
                        <ul class="space-y-2 text-sm text-subtle-light dark:text-subtle-dark">
                            <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-primary mt-0.5 shrink-0"></i> Gestão de tarefas por colaborador</li>
                            <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-primary mt-0.5 shrink-0"></i> Timesheet integrado</li>
                            <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-primary mt-0.5 shrink-0"></i> Acompanhamento de produtividade</li>
                            <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-primary mt-0.5 shrink-0"></i> Relatórios gerenciais completos</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Diferencial & Benefícios -->
        <section class="grid lg:grid-cols-2 gap-12 items-center">
            <div class="space-y-8">
                <div>
                    <h2 class="text-sm font-bold tracking-widest uppercase text-primary mb-2">O Diferencial</h2>
                    <h3 class="text-3xl md:text-4xl font-display font-bold text-black dark:text-white mb-6">A Inteligência por trás: Vittoria</h3>
                    <p class="text-lg text-subtle-light dark:text-subtle-dark mb-6 leading-relaxed">
                        A Vittoria torna a operação mais simples e eficiente. O <strong>resultado</strong> é mais produtividade com menos esforço operacional.
                    </p>
                    <ul class="space-y-4">
                        <li class="flex items-center gap-4 text-black dark:text-white font-medium">
                            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0"><i data-lucide="message-square" class="w-5 h-5"></i></div>
                            Interface baseada em conversa
                        </li>
                        <li class="flex items-center gap-4 text-black dark:text-white font-medium">
                            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0"><i data-lucide="mouse-pointer-click" class="w-5 h-5"></i></div>
                            Redução da necessidade de navegação manual
                        </li>
                        <li class="flex items-center gap-4 text-black dark:text-white font-medium">
                            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0"><i data-lucide="zap" class="w-5 h-5"></i></div>
                            Respostas rápidas e contextualizadas
                        </li>
                        <li class="flex items-center gap-4 text-black dark:text-white font-medium">
                            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0"><i data-lucide="users" class="w-5 h-5"></i></div>
                            Apoio contínuo ao gestor
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="bg-surface-light dark:bg-[#111] border border-borderline-light dark:border-borderline-dark p-8 md:p-12 rounded-[2rem] shadow-lg">
                <h3 class="text-2xl font-bold text-black dark:text-white mb-6 border-b border-borderline-light dark:border-borderline-dark pb-4">Benefícios da Plataforma</h3>
                <ul class="space-y-6">
                    <li class="flex items-start gap-4">
                        <i data-lucide="trending-down" class="w-6 h-6 text-green-500 shrink-0"></i>
                        <div>
                            <span class="block font-bold text-black dark:text-white text-lg">Redução de custos operacionais</span>
                        </div>
                    </li>
                    <li class="flex items-start gap-4">
                        <i data-lucide="zap" class="w-6 h-6 text-primary shrink-0"></i>
                        <div>
                            <span class="block font-bold text-black dark:text-white text-lg">Maior eficiência do RH</span>
                        </div>
                    </li>
                    <li class="flex items-start gap-4">
                        <i data-lucide="bar-chart" class="w-6 h-6 text-blue-500 shrink-0"></i>
                        <div>
                            <span class="block font-bold text-black dark:text-white text-lg">Decisões seguras com base em dados</span>
                        </div>
                    </li>
                    <li class="flex items-start gap-4">
                        <i data-lucide="heart" class="w-6 h-6 text-red-500 shrink-0"></i>
                        <div>
                            <span class="block font-bold text-black dark:text-white text-lg">Melhor experiência para gestores e colaboradores</span>
                        </div>
                    </li>
                    <li class="flex items-start gap-4">
                        <i data-lucide="layers" class="w-6 h-6 text-purple-500 shrink-0"></i>
                        <div>
                            <span class="block font-bold text-black dark:text-white text-lg">Centralização da gestão de pessoas</span>
                        </div>
                    </li>
                </ul>
            </div>
        </section>

        <!-- Conclusão e CTA -->
        <section class="text-center bg-black dark:bg-white text-white dark:text-black p-10 md:p-16 rounded-[2rem] relative overflow-hidden shadow-2xl">
            <div class="absolute -top-24 -right-24 w-64 h-64 bg-primary/30 blur-3xl rounded-full"></div>
            <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-orange-500/30 blur-3xl rounded-full"></div>
            
            <div class="relative z-10 max-w-3xl mx-auto">
                <h2 class="text-sm font-bold tracking-widest uppercase text-white/60 dark:text-black/60 mb-4">Posicionamento</h2>
                <p class="text-2xl md:text-4xl font-display font-black mb-6 leading-tight">A Let's Work representa a evolução natural do RH: <br><span class="text-primary">Mais integrado, mais eficiente e mais inteligente.</span></p>
                <p class="text-lg md:text-xl font-light text-white/80 dark:text-black/80 mb-10">Aprimoramos a gestão de RH com tecnologia, mantendo a segurança e a consistência que sua empresa precisa.</p>
                
                <div class="pt-8 border-t border-white/20 dark:border-black/20">
                    <p class="font-bold text-xl mb-6">Dê o próximo passo na gestão de pessoas.</p>
                    <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20um%20especialista%20da%20Let%27s%20Work%21" target="_blank" class="magnetic bg-white dark:bg-black text-black dark:text-white px-10 py-5 rounded-full font-bold text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-xl inline-flex items-center gap-2">
                        A evolução do RH começa agora
                        <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>
        </section>

    </div>
</div>
`;

let content = fs.readFileSync('como-funciona.html', 'utf8');

// The original content has <header class="pt-32... up to </header>
// And then <!-- MAIN CONTENT + SIDEBAR LAYOUT --> <div class="py-16... up to </div></div> 
// We will use substring replacement because regex matching across hundreds of lines is fragile.

const headerStart = content.indexOf('<header class="pt-32');
const footerStart = content.indexOf('<footer class="pt-20');

if (headerStart !== -1 && footerStart !== -1) {
    const newContent = content.substring(0, headerStart) + heroHtml + mainContent + content.substring(footerStart);
    fs.writeFileSync('como-funciona.html', newContent);
    console.log("Replacement successful!");
} else {
    console.log("Could not find delimiters.");
}

