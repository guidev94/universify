const fs = require('fs');
let html = fs.readFileSync('como-funciona.html', 'utf8');

// 1. Update Hero Copy
html = html.replace(
    /Sistema de Controle de <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-orange-500">Ponto Digital<\/span>/,
    'Evolução da Gestão de RH com <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-orange-500">IA</span>'
);
html = html.replace(
    /Controle de ponto via WhatsApp com reconhecimento facial e geolocalização\. Gestão eficiente da jornada de trabalho dos colaboradores internos e externos\./,
    "A Let's Work é uma plataforma completa de gestão de Recursos Humanos que integra inteligência artificial, automação e usabilidade para aprimorar a forma como empresas contratam, desenvolvem e gerenciam pessoas."
);

// 2. Update "Como Funciona o Let's Work" -> "O Problema & A Solução"
html = html.replace(
    /<h2 class="text-3xl font-display font-bold text-black dark:text-white tracking-tight">Como Funciona o Let's Work<\/h2>[\s\S]*?<figure/,
    `<h2 class="text-3xl font-display font-bold text-black dark:text-white tracking-tight mb-6">O Problema & A Solução</h2>
                <div class="prose prose-lg dark:prose-invert max-w-none text-subtle-light dark:text-subtle-dark leading-relaxed">
                    <p>
                        Empresas ainda enfrentam desafios críticos na gestão de RH: processos de recrutamento lentos, falta de integração entre ferramentas e dificuldade em acompanhar performance, gerando alto custo operacional e retrabalho.
                    </p>
                    <p>
                        A <strong>Let's Work</strong> representa a evolução do RH, centralizando toda a jornada do colaborador em uma única plataforma, com apoio da <strong>Vittoria</strong>, nossa assistente de Inteligência Artificial. Nossa proposta é simples: reduzir a complexidade e aumentar a eficiência.
                    </p>
                </div>
                
                <figure`
);

// 3. Update "Recursos do Aplicativo" to "Os 9 Pilares da Plataforma"
const novePilaresHtml = `<h2 class="text-3xl font-display font-bold text-black dark:text-white mb-8 border-b border-borderline-light dark:border-borderline-dark pb-4">A Jornada Completa</h2>
                <div class="grid sm:grid-cols-2 gap-6">
                    <div class="solid-card p-6 rounded-2xl group flex gap-4">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <i data-lucide="mic" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">1. Criação de Vagas</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Fale: "Vittoria, crie uma vaga" e a IA gera a descrição, requisitos e publica automaticamente.</p>
                        </div>
                    </div>
                    
                    <div class="solid-card p-6 rounded-2xl group flex gap-4">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <i data-lucide="users" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">2. Recrutamento</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Triagem automatizada de currículos, análise de compatibilidade e classificação de candidatos.</p>
                        </div>
                    </div>

                    <div class="solid-card p-6 rounded-2xl group flex gap-4">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <i data-lucide="file-check-2" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">3. Admissão Digital</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Contratação na plataforma com coleta, assinatura de documentos e organização do dossiê.</p>
                        </div>
                    </div>

                    <div class="solid-card p-6 rounded-2xl group flex gap-4">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <i data-lucide="folder-open" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">4. Documentos</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Armazenamento seguro, assinaturas digitais com validade jurídica e acesso centralizado.</p>
                        </div>
                    </div>

                    <div class="solid-card p-6 rounded-2xl group flex gap-4">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <i data-lucide="trending-up" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">5. PDI e Performance</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Criação de Planos de Desenvolvimento, definição de metas e insights de desempenho.</p>
                        </div>
                    </div>

                    <div class="solid-card p-6 rounded-2xl group flex gap-4">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <i data-lucide="graduation-cap" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">6. Treinamento</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Desenvolvimento orientado por dados para fortalecer as competências estratégicas do time.</p>
                        </div>
                    </div>

                    <div class="solid-card p-6 rounded-2xl group flex gap-4">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <i data-lucide="clock" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">7. Controle de Ponto</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Registro com Liveness e GPS, controle de horas extras, banco de horas e atestados.</p>
                        </div>
                    </div>

                    <div class="solid-card p-6 rounded-2xl group flex gap-4">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <i data-lucide="calendar" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">8. Gestão de Escalas</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Ajustes visuais, melhor distribuição da jornada e redução drástica de conflitos.</p>
                        </div>
                    </div>

                    <div class="solid-card p-6 rounded-2xl group flex gap-4 sm:col-span-2 md:col-span-1 lg:col-span-2 xl:col-span-1 xl:col-start-1 xl:col-end-3 xl:mx-auto xl:max-w-md">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <i data-lucide="kanban" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">9. Processos e Projetos</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Gestão de tarefas por colaborador, timesheet integrado e acompanhamento completo.</p>
                        </div>
                    </div>
                </div>`;

const recursosRegex = /<h2 class="text-2xl font-display font-bold text-black dark:text-white mb-8 border-b border-borderline-light dark:border-borderline-dark pb-4">Recursos do Aplicativo<\/h2>[\s\S]*?<\/section>/;

if (recursosRegex.test(html)) {
    html = html.replace(recursosRegex, novePilaresHtml + '\n            </section>');
}

// 4. Update "Integração WhatsApp" to "A Inteligência por trás: Vittoria" & Fix "Exclusividade" button issue
const integraRegex = /<div class="magnetic bg-black dark:bg-white text-white dark:text-black px-8 py-4 rounded-full font-bold text-base md:text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-\[0_10px_20px_rgba\(0,0,0,0\.1\)\] dark:shadow-\[0_10px_20px_rgba\(255,255,255,0\.1\)\] inline-flex items-center justify-center gap-2">\s*Exclusividade\s*<\/div>/;

html = html.replace(integraRegex, `<div class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary/10 text-primary font-bold text-xs mb-4 uppercase tracking-widest border border-primary/20"><i data-lucide="sparkles" class="w-4 h-4"></i> Diferencial de IA</div>`);

html = html.replace(/<h2 class="text-2xl md:text-3xl font-display font-bold text-black dark:text-white mb-4">Integrado com WhatsApp<\/h2>/, `<h2 class="text-2xl md:text-3xl font-display font-bold text-black dark:text-white mb-4">A Inteligência por trás: Vittoria</h2>`);

html = html.replace(
    /Com o Let's Work, os colaboradores podem marcar seu ponto através do próprio Whatsapp, de forma fácil e rápida\. Trazemos uma solução inovadora que utiliza a popularidade do WhatsApp para proporcionar maior eficiência\./,
    `A Vittoria torna a operação de RH incrivelmente mais simples e eficiente. O resultado é muito mais produtividade com menos esforço operacional e tarefas manuais.`
);

html = html.replace(
    /<ol class="space-y-4">[\s\S]*?<\/ol>/,
    `<ul class="space-y-4">
                            <li class="flex gap-3 items-center">
                                <i data-lucide="message-square" class="w-5 h-5 text-primary shrink-0"></i>
                                <span class="text-sm text-black dark:text-white font-medium">Interface baseada em linguagem natural e conversa.</span>
                            </li>
                            <li class="flex gap-3 items-center">
                                <i data-lucide="mouse-pointer-click" class="w-5 h-5 text-primary shrink-0"></i>
                                <span class="text-sm text-black dark:text-white font-medium">Redução da necessidade de navegação manual.</span>
                            </li>
                            <li class="flex gap-3 items-center">
                                <i data-lucide="zap" class="w-5 h-5 text-primary shrink-0"></i>
                                <span class="text-sm text-black dark:text-white font-medium">Respostas rápidas e apoio contínuo ao gestor.</span>
                            </li>
                        </ul>`
);

// 5. Update FAQ to "Benefícios"
const faqHtml = `<h2 class="text-3xl font-display font-bold text-black dark:text-white mb-8 border-b border-borderline-light dark:border-borderline-dark pb-4">Benefícios e Resultados</h2>
                <div class="space-y-4">
                    <div class="solid-card p-6 rounded-2xl flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center text-green-500 shrink-0">
                            <i data-lucide="trending-down" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">Redução de Custos Operacionais</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Diminuição de horas extras indevidas, passivos trabalhistas e retrabalho.</p>
                        </div>
                    </div>
                    <div class="solid-card p-6 rounded-2xl flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-500 shrink-0">
                            <i data-lucide="bar-chart" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">Decisões Baseadas em Dados</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Segurança na tomada de decisão com dashboards integrados e análises da IA.</p>
                        </div>
                    </div>
                    <div class="solid-card p-6 rounded-2xl flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-500 shrink-0">
                            <i data-lucide="layers" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">Centralização da Gestão</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Toda a jornada do colaborador conectada em um só lugar com eficiência.</p>
                        </div>
                    </div>
                </div>`;

const faqRegex = /<h2 class="text-2xl font-display font-bold text-black dark:text-white mb-8 border-b border-borderline-light dark:border-borderline-dark pb-4">Dúvidas Frequentes<\/h2>[\s\S]*?<\/section>/;
html = html.replace(faqRegex, faqHtml + '\n            </section>');

fs.writeFileSync('como-funciona.html', html);
console.log('Done');
