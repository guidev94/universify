const fs = require('fs');

const template = fs.readFileSync('como-funciona.html', 'utf8');

const navEndIdx = template.indexOf('</nav>') + 6;
const headerContent = template.substring(0, navEndIdx);

const footerStartIdx = template.indexOf('<footer');
const footerContent = template.substring(footerStartIdx);


// ===== CREATE CONTATO.HTML =====
let contatoHead = headerContent.replace(/<title>.*?<\/title>/, `<title>Contato e Suporte | Let's Work</title>`);
contatoHead = contatoHead.replace(/<meta name="description" content=".*?">/, `<meta name="description" content="Fale com a Let's Work. Suporte, financeiro, implantação ou comercial. Estamos prontos para ajudar.">`);

const contatoMain = `
<!-- TITLE BAR -->
<header class="pt-32 md:pt-40 pb-16 bg-surface-light dark:bg-surface-dark border-b border-borderline-light dark:border-borderline-dark">
    <div class="max-w-[1400px] mx-auto px-6 text-center">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-display font-bold text-black dark:text-white tracking-tight mb-6">
            Fale com a <span class="text-primary">Let's Work</span>
        </h1>
        <p class="text-lg text-subtle-light dark:text-subtle-dark max-w-2xl mx-auto">
            Estamos prontos para ajudar a sua empresa. Escolha o departamento desejado abaixo ou preencha o formulário.
        </p>
    </div>
</header>

<main class="py-16 md:py-24 relative">
    <div class="max-w-[1400px] mx-auto px-6 grid grid-cols-1 lg:grid-cols-12 gap-12">
        
        <!-- Contatos Diretos -->
        <div class="lg:col-span-5 space-y-6">
            <h2 class="text-2xl font-display font-bold text-black dark:text-white mb-8">Nossos Canais</h2>
            
            <a href="https://api.whatsapp.com/send?phone=5511934670924" class="solid-card p-6 rounded-2xl flex items-center gap-5 group hover:border-[#25D366]">
                <div class="w-12 h-12 rounded-xl bg-[#25D366]/10 text-[#25D366] flex items-center justify-center shrink-0">
                    <i data-lucide="shopping-cart" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="font-bold text-black dark:text-white">Comercial</h3>
                    <p class="text-sm text-subtle-light dark:text-subtle-dark">11 93467-0924 (09h às 18h)</p>
                </div>
            </a>

            <a href="https://web.whatsapp.com/send?phone=5511971163576" class="solid-card p-6 rounded-2xl flex items-center gap-5 group hover:border-[#25D366]">
                <div class="w-12 h-12 rounded-xl bg-[#25D366]/10 text-[#25D366] flex items-center justify-center shrink-0">
                    <i data-lucide="life-buoy" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="font-bold text-black dark:text-white">Suporte Técnico</h3>
                    <p class="text-sm text-subtle-light dark:text-subtle-dark">11 97116-3576 (09h às 18h)</p>
                </div>
            </a>

            <a href="https://api.whatsapp.com/send?phone=5511998514728" class="solid-card p-6 rounded-2xl flex items-center gap-5 group hover:border-[#25D366]">
                <div class="w-12 h-12 rounded-xl bg-[#25D366]/10 text-[#25D366] flex items-center justify-center shrink-0">
                    <i data-lucide="settings" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="font-bold text-black dark:text-white">Implantação</h3>
                    <p class="text-sm text-subtle-light dark:text-subtle-dark">11 99851-4728 (09h às 18h)</p>
                </div>
            </a>

            <a href="https://api.whatsapp.com/send?phone=5511997558180" class="solid-card p-6 rounded-2xl flex items-center gap-5 group hover:border-[#25D366]">
                <div class="w-12 h-12 rounded-xl bg-[#25D366]/10 text-[#25D366] flex items-center justify-center shrink-0">
                    <i data-lucide="wallet" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="font-bold text-black dark:text-white">Financeiro</h3>
                    <p class="text-sm text-subtle-light dark:text-subtle-dark">11 99755-8180 (09h às 18h)</p>
                </div>
            </a>
        </div>

        <!-- Formulário -->
        <div class="lg:col-span-7">
            <div class="solid-card p-8 md:p-12 rounded-[2rem] shadow-soft">
                <h2 class="text-2xl font-display font-bold text-black dark:text-white mb-6">Envie uma mensagem</h2>
                <form class="space-y-5" onsubmit="event.preventDefault(); alert('Mensagem enviada com sucesso!');">
                    <div class="space-y-1.5">
                        <label class="text-sm font-semibold text-black dark:text-white">Nome Completo</label>
                        <input type="text" required class="w-full px-4 py-3 rounded-xl bg-surface-light dark:bg-[#111] border border-borderline-light dark:border-borderline-dark focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-black dark:text-white">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-sm font-semibold text-black dark:text-white">E-mail</label>
                            <input type="email" required class="w-full px-4 py-3 rounded-xl bg-surface-light dark:bg-[#111] border border-borderline-light dark:border-borderline-dark focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-black dark:text-white">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-semibold text-black dark:text-white">Telefone</label>
                            <input type="tel" required class="w-full px-4 py-3 rounded-xl bg-surface-light dark:bg-[#111] border border-borderline-light dark:border-borderline-dark focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-black dark:text-white">
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-semibold text-black dark:text-white">Mensagem</label>
                        <textarea required rows="4" class="w-full px-4 py-3 rounded-xl bg-surface-light dark:bg-[#111] border border-borderline-light dark:border-borderline-dark focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-black dark:text-white resize-none"></textarea>
                    </div>
                    <button type="submit" class="btn-primary w-full py-4 rounded-xl font-bold text-lg mt-2 magnetic">
                        Enviar Mensagem
                    </button>
                </form>
            </div>
        </div>

    </div>
</main>
`;

fs.writeFileSync('contato.html', contatoHead + contatoMain + footerContent);
console.log('contato.html created!');


// ===== CREATE BLOG.HTML (News) =====
let blogHead = headerContent.replace(/<title>.*?<\/title>/, `<title>Blog & Let's News | Let's Work</title>`);
blogHead = blogHead.replace(/<meta name="description" content=".*?">/, `<meta name="description" content="Fique por dentro das novidades sobre gestão de RH, controle de ponto e inovação em tecnologia corporativa.">`);

const blogMain = `
<!-- TITLE BAR -->
<header class="pt-32 md:pt-40 pb-16 bg-surface-light dark:bg-surface-dark border-b border-borderline-light dark:border-borderline-dark">
    <div class="max-w-[1400px] mx-auto px-6">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-display font-bold text-black dark:text-white tracking-tight mb-6">
            Let's <span class="text-primary">News</span>
        </h1>
        <p class="text-lg text-subtle-light dark:text-subtle-dark max-w-2xl">
            As principais novidades sobre tecnologia, gestão de pessoas e inovações do mercado de RH corporativo.
        </p>
    </div>
</header>

<main class="py-16 md:py-24 relative">
    <div class="max-w-[1400px] mx-auto px-6 grid grid-cols-1 lg:grid-cols-12 gap-12">
        
        <!-- Grid de Posts -->
        <div class="lg:col-span-8 grid sm:grid-cols-2 gap-8">
            
            <!-- Post Card 1 -->
            <a href="#" class="solid-card rounded-2xl overflow-hidden group flex flex-col h-full hover:border-primary transition-colors">
                <div class="h-48 bg-gray-200 dark:bg-[#111] overflow-hidden relative">
                    <img src="https://www.letswork.com.br/wp-content/uploads/2023/05/Relatorio.png" alt="IA no RH" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute top-4 left-4 bg-primary text-white text-xs font-bold px-3 py-1 rounded-full">Tecnologia</div>
                </div>
                <div class="p-6 flex flex-col flex-grow">
                    <span class="text-xs text-subtle-light dark:text-subtle-dark mb-2">24 de Abril, 2026</span>
                    <h3 class="text-xl font-bold text-black dark:text-white mb-3">Transformando o Controle de Ponto com IA</h3>
                    <p class="text-sm text-subtle-light dark:text-subtle-dark mb-6 flex-grow">Descubra como algoritmos estão sendo usados para prever burnout, analisar produtividade e revolucionar o setor de DP.</p>
                    <span class="text-primary font-bold text-sm inline-flex items-center gap-1 group-hover:underline mt-auto">Ler artigo completo <i data-lucide="arrow-right" class="w-4 h-4"></i></span>
                </div>
            </a>

            <!-- Post Card 2 -->
            <a href="#" class="solid-card rounded-2xl overflow-hidden group flex flex-col h-full hover:border-primary transition-colors">
                <div class="h-48 bg-gray-200 dark:bg-[#111] overflow-hidden relative">
                    <img src="https://www.letswork.com.br/wp-content/uploads/2022/11/gestao-documento.png" alt="Paperless" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute top-4 left-4 bg-primary text-white text-xs font-bold px-3 py-1 rounded-full">Legislação</div>
                </div>
                <div class="p-6 flex flex-col flex-grow">
                    <span class="text-xs text-subtle-light dark:text-subtle-dark mb-2">12 de Abril, 2026</span>
                    <h3 class="text-xl font-bold text-black dark:text-white mb-3">Validade Jurídica do Holerite Digital</h3>
                    <p class="text-sm text-subtle-light dark:text-subtle-dark mb-6 flex-grow">Entenda as portarias do Ministério do Trabalho sobre assinatura eletrônica e como tornar seu RH 100% livre de papel.</p>
                    <span class="text-primary font-bold text-sm inline-flex items-center gap-1 group-hover:underline mt-auto">Ler artigo completo <i data-lucide="arrow-right" class="w-4 h-4"></i></span>
                </div>
            </a>

            <!-- Post Card 3 -->
            <a href="#" class="solid-card rounded-2xl overflow-hidden group flex flex-col h-full hover:border-primary transition-colors">
                <div class="h-48 bg-gray-200 dark:bg-[#111] overflow-hidden relative">
                    <img src="https://www.letswork.com.br/wp-content/uploads/2023/10/pessoas-negocio-em-um-reuniao_53876-15137-1024x682.jpg" alt="Equipe" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute top-4 left-4 bg-primary text-white text-xs font-bold px-3 py-1 rounded-full">Liderança</div>
                </div>
                <div class="p-6 flex flex-col flex-grow">
                    <span class="text-xs text-subtle-light dark:text-subtle-dark mb-2">05 de Março, 2026</span>
                    <h3 class="text-xl font-bold text-black dark:text-white mb-3">O Futuro das Escalas de Trabalho (4 Dias?)</h3>
                    <p class="text-sm text-subtle-light dark:text-subtle-dark mb-6 flex-grow">O movimento pela semana de 4 dias chegou ao Brasil. Como as empresas estão adaptando a gestão de turnos sem perder receita.</p>
                    <span class="text-primary font-bold text-sm inline-flex items-center gap-1 group-hover:underline mt-auto">Ler artigo completo <i data-lucide="arrow-right" class="w-4 h-4"></i></span>
                </div>
            </a>

        </div>

        <!-- Sidebar do Blog -->
        <aside class="lg:col-span-4 space-y-8">
            <!-- Newsletter -->
            <div class="solid-card p-6 rounded-2xl bg-gradient-to-b from-surface-light to-white dark:from-surface-dark dark:to-black">
                <h3 class="font-display font-bold text-xl mb-2 text-black dark:text-white">Newsletter</h3>
                <p class="text-sm text-subtle-light dark:text-subtle-dark mb-6">Receba as melhores dicas de RH e Inovação na sua caixa de entrada.</p>
                <form class="space-y-3" onsubmit="event.preventDefault(); alert('Inscrito com sucesso!');">
                    <input type="email" required placeholder="Seu melhor e-mail" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-[#111] border border-borderline-light dark:border-borderline-dark focus:border-primary focus:ring-1 outline-none text-sm">
                    <button type="submit" class="btn-primary w-full py-3 rounded-xl font-bold text-sm magnetic">Inscrever-se</button>
                </form>
            </div>

            <!-- Categorias -->
            <div class="solid-card p-6 rounded-2xl">
                <h3 class="font-display font-bold text-lg mb-4 text-black dark:text-white border-b border-borderline-light dark:border-borderline-dark pb-2">Categorias</h3>
                <ul class="space-y-3 text-sm font-medium">
                    <li><a href="#" class="text-subtle-light dark:text-subtle-dark hover:text-primary dark:hover:text-primary transition-colors flex justify-between">Tecnologia <span>(12)</span></a></li>
                    <li><a href="#" class="text-subtle-light dark:text-subtle-dark hover:text-primary dark:hover:text-primary transition-colors flex justify-between">Legislação Trabalhista <span>(8)</span></a></li>
                    <li><a href="#" class="text-subtle-light dark:text-subtle-dark hover:text-primary dark:hover:text-primary transition-colors flex justify-between">Liderança <span>(15)</span></a></li>
                    <li><a href="#" class="text-subtle-light dark:text-subtle-dark hover:text-primary dark:hover:text-primary transition-colors flex justify-between">Atualizações Let's Work <span>(4)</span></a></li>
                </ul>
            </div>
        </aside>

    </div>
</main>
`;

fs.writeFileSync('blog.html', blogHead + blogMain + footerContent);
console.log('blog.html created!');
