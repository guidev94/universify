const fs = require('fs');
let html = fs.readFileSync('ia-no-rh.html', 'utf8');

// 1. Hero text
html = html.replace(
    /O Futuro do RH com <span class="text-primary">IA<\/span>/,
    'A Evolução do RH com <span class="text-primary">IA</span>'
);
html = html.replace(
    /Libere o potencial da sua equipe delegando tarefas repetitivas para a nossa Assistente Virtual e gere relatórios de produtividade analíticos\./,
    'Imagine um RH que conversa com você. Na Let’s Work, a IA cuida de toda a operação de forma simples e natural, centralizando processos sem complicações.'
);

// 2. Main Content
const mainContentOldRegex = /<main class="lg:col-span-8 space-y-20">[\s\S]*?<\/main>/;
const newMainContent = `<main class="lg:col-span-8 space-y-20">
            <!-- Section: O Novo Modelo -->
            <section class="space-y-8">
                <h2 class="text-3xl font-display font-bold text-black dark:text-white tracking-tight">O Novo Modelo de RH</h2>
                <div class="prose prose-lg dark:prose-invert max-w-none text-subtle-light dark:text-subtle-dark leading-relaxed">
                    <p>
                        Imagine um RH que conversa com você. Na Let’s Work, tudo começa com a Vittoria, a assistente de IA que entende suas necessidades.
                    </p>
                    <p>
                        Com um comando simples, você abre uma vaga, ajusta uma escala ou define um PDI. A IA cuida da triagem, analisa currículos, acompanha metas e gera relatórios. O registro de ponto e o timesheet são integrados, e o gestor navega por todo o sistema com uma visão completa e natural.
                    </p>
                    <p>
                        Com a Let’s Work e a Vittoria, o RH evolui para uma experiência fluida, inteligente e sem complicações.
                    </p>
                </div>
                
                <figure class="mt-10 max-w-xl mx-auto rounded-[2rem] overflow-hidden border border-borderline-light dark:border-borderline-dark shadow-soft">
                    <img src="https://images.unsplash.com/photo-1677442136019-21780ecad995?auto=format&fit=crop&q=80&w=800" alt="Dashboard Analítico de Inteligência Artificial" class="w-full h-auto bg-surface-light dark:bg-surface-dark object-cover">
                </figure>
            </section>

            <!-- Section: Funcionalidades -->
            <section>
                <h2 class="text-2xl font-display font-bold text-black dark:text-white mb-8 border-b border-borderline-light dark:border-borderline-dark pb-4">Como a Let's Work Funciona</h2>
                <div class="grid sm:grid-cols-2 gap-6">
                    
                    <div class="solid-card p-6 rounded-2xl group flex gap-4">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="font-bold">1</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">Criação de Vagas</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Geração automática de descrições e perfis ideais utilizando Inteligência Artificial.</p>
                        </div>
                    </div>
                    
                    <div class="solid-card p-6 rounded-2xl group flex gap-4">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="font-bold">2</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">Recrutamento e Triagem</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Análise automatizada de currículos, perfil e compatibilidade com os candidatos.</p>
                        </div>
                    </div>

                    <div class="solid-card p-6 rounded-2xl group flex gap-4">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="font-bold">3</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">Admissão Digital</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Contratação simplificada dentro da plataforma com coleta guiada de documentos.</p>
                        </div>
                    </div>
                    
                    <div class="solid-card p-6 rounded-2xl group flex gap-4">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="font-bold">4</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">Gestão de Documentos</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Armazenamento seguro e estruturado com suporte a assinaturas digitais.</p>
                        </div>
                    </div>
                    
                    <div class="solid-card p-6 rounded-2xl group flex gap-4">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="font-bold">5</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">PDI e Performance</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Acompanhamento contínuo de metas, desenvolvimento e entregáveis.</p>
                        </div>
                    </div>

                    <div class="solid-card p-6 rounded-2xl group flex gap-4">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="font-bold">6</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">Ponto e Escalas</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Registro inteligente e antifraude com ajustes visuais rápidos de jornada.</p>
                        </div>
                    </div>

                    <div class="solid-card p-6 rounded-2xl group flex gap-4 sm:col-span-2 md:col-span-1 lg:col-span-2 xl:col-span-1 xl:col-start-1 xl:col-end-3 xl:mx-auto xl:max-w-md">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="font-bold">7</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">Gestão de Tarefas</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Timesheet integrado e acompanhamento de produtividade centralizado.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section: Destaque Específico -->
            <section class="solid-card p-8 md:p-12 rounded-[2rem] bg-surface-light dark:bg-surface-dark border-primary/20 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-10">
                    <i data-lucide="bot" class="w-48 h-48 text-primary"></i>
                </div>
                <div class="relative z-10 grid md:grid-cols-2 gap-10 items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary font-bold text-xs mb-4 uppercase tracking-wider border border-primary/20">
                            <i data-lucide="sparkles" class="w-4 h-4"></i> Vittoria
                        </div>
                        <h2 class="text-2xl md:text-3xl font-display font-bold text-black dark:text-white mb-4">O Diferencial de IA</h2>
                        <p class="text-subtle-light dark:text-subtle-dark text-sm md:text-base leading-relaxed mb-6 font-semibold">
                            "Você não precisa aprender um sistema. O sistema entende você."
                        </p>
                        <ol class="space-y-4">
                            <li class="flex gap-3">
                                <span class="w-6 h-6 rounded-full bg-primary/20 text-primary flex items-center justify-center text-xs font-bold shrink-0 mt-0.5"><i data-lucide="check" class="w-3 h-3"></i></span>
                                <span class="text-sm text-black dark:text-white font-medium">Interface por conversa e comandos simples.</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="w-6 h-6 rounded-full bg-primary/20 text-primary flex items-center justify-center text-xs font-bold shrink-0 mt-0.5"><i data-lucide="check" class="w-3 h-3"></i></span>
                                <span class="text-sm text-black dark:text-white font-medium">Navegação inteligente e execução rápida de tarefas.</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="w-6 h-6 rounded-full bg-primary/20 text-primary flex items-center justify-center text-xs font-bold shrink-0 mt-0.5"><i data-lucide="check" class="w-3 h-3"></i></span>
                                <span class="text-sm text-black dark:text-white font-medium">Apoio contínuo na tomada de decisão estratégica do gestor.</span>
                            </li>
                        </ol>
                    </div>
                </div>
            </section>

            <!-- Section: Beneficios -->
            <section>
                <h2 class="text-2xl font-display font-bold text-black dark:text-white mb-8 border-b border-borderline-light dark:border-borderline-dark pb-4">Benefícios e Para Quem É</h2>
                
                <div class="grid sm:grid-cols-2 gap-6 mb-10">
                    <div class="solid-card p-6 rounded-2xl flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-500 shrink-0">
                            <i data-lucide="users" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">Para Empresas em Crescimento</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Ideal para RHs que buscam eficiência, gestores que precisam de controle e times que querem reduzir a burocracia.</p>
                        </div>
                    </div>
                    <div class="solid-card p-6 rounded-2xl flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center text-green-500 shrink-0">
                            <i data-lucide="shield-check" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white mb-2">Prova de Valor Constante</h3>
                            <p class="text-sm text-subtle-light dark:text-subtle-dark leading-relaxed">Redução drástica de erros manuais, eliminação de retrabalho e decisões seguras e guiadas por dados estruturados.</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>`;

if (mainContentOldRegex.test(html)) {
    html = html.replace(mainContentOldRegex, newMainContent);
}

// 3. Floating Whatsapp button fix (if exists) -> removing buggy white background inside SVG or CSS classes.
// Usually the bug is <svg class="..." fill="currentColor" drop-shadow-sm...
// Let's replace the whatsapp button with the same we used in index.html (the green one).
const whatsappButton = `<!-- Floating WhatsApp Button -->
<a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20a%20Vittoria%21" target="_blank" class="group fixed bottom-8 right-8 z-[100] flex items-center bg-[#25D366] text-white rounded-full p-4 shadow-[0_10px_20px_rgba(37,211,102,0.3)] hover:pr-6 hover:shadow-[0_10px_25px_rgba(37,211,102,0.5)] hover:-translate-y-1 transition-all duration-300 border-2 border-transparent hover:border-white/20">
    <svg class="w-8 h-8 fill-current shrink-0 drop-shadow-sm" viewBox="0 0 24 24">
        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-14.416c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm.029 18.88c-1.161 0-2.305-.292-3.318-.844l-3.677.964.984-3.595c-.607-1.052-.927-2.246-.926-3.468.001-5.824 4.74-10.563 10.564-10.563 5.832 0 10.564 4.742 10.564 10.564 0 5.827-4.732 10.564-10.564 10.564z"/>
    </svg>
    <span class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 group-hover:max-w-xs group-hover:opacity-100 group-hover:ml-3 font-bold tracking-wide transition-all duration-500 ease-out">
        Falar com atendimento
    </span>
</a>
</body>`;

// Remove any existing buttons (the magnetic ones from old theme)
html = html.replace(/<!-- Botão Flutuante WhatsApp Intuitivo -->[\s\S]*?<\/a>/g, '');
html = html.replace(/<!-- Floating WhatsApp Button -->[\s\S]*?<\/a>/g, '');
html = html.replace(/<\/body>/, whatsappButton);

fs.writeFileSync('ia-no-rh.html', html);
console.log('Fixed ia-no-rh.html');
