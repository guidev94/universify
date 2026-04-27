const fs = require('fs');

// WhatsApp URL
const WPP_URL = "https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20um%20especialista%20da%20Let%27s%20Work%21";

// 1. Update index.html
let index = fs.readFileSync('index.html', 'utf8');

// Change first "Falar com Especialista" button
index = index.replace(
    /<button class="([^"]*) open-modal-btn">(\s*)<span class="pointer-events-none">Falar com Especialista<\/span>(\s*)<\/button>/,
    `<a href="${WPP_URL}" target="_blank" class="$1">\n                        <span>Falar com Especialista</span>\n                    </a>`
);

// Add floating WhatsApp button right before </body>
const floatingWpp = `
    <!-- Botão Flutuante WhatsApp -->
    <a href="${WPP_URL}" target="_blank" class="fixed bottom-6 right-6 z-50 bg-[#25D366] text-white p-4 rounded-full shadow-lg hover:scale-110 hover:shadow-xl transition-all duration-300 flex items-center justify-center group">
        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-14.416c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm.029 18.88c-1.161 0-2.305-.292-3.318-.844l-3.677.964.984-3.595c-.607-1.052-.927-2.246-.926-3.468.001-5.824 4.74-10.563 10.564-10.563 5.832 0 10.564 4.742 10.564 10.564 0 5.827-4.732 10.564-10.564 10.564z"/></svg>
        <span class="absolute right-full mr-4 bg-black dark:bg-white text-white dark:text-black text-sm px-3 py-1.5 rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none font-bold">Fale com a Vitória</span>
    </a>
`;

index = index.replace('</body>', floatingWpp + '\n</body>');

// Replace standard CTA section with multiple WhatsApp CTAs
// Let's add them to specific sections
const WPP_BTN_1 = `<div class="mt-8 text-center"><a href="${WPP_URL}" target="_blank" class="inline-flex items-center gap-2 bg-[#25D366] text-white px-8 py-4 rounded-full font-bold text-lg hover:-translate-y-1 active:scale-95 transition-all shadow-lg hover:shadow-xl"><svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-14.416c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm.029 18.88c-1.161 0-2.305-.292-3.318-.844l-3.677.964.984-3.595c-.607-1.052-.927-2.246-.926-3.468.001-5.824 4.74-10.563 10.564-10.563 5.832 0 10.564 4.742 10.564 10.564 0 5.827-4.732 10.564-10.564 10.564z"/></svg> Solicite Uma Demonstração</a></div>`;

const WPP_BTN_2 = `<div class="mt-8 text-center"><a href="${WPP_URL}" target="_blank" class="inline-flex items-center gap-2 bg-[#25D366] text-white px-8 py-4 rounded-full font-bold text-lg hover:-translate-y-1 active:scale-95 transition-all shadow-lg hover:shadow-xl"><svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-14.416c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm.029 18.88c-1.161 0-2.305-.292-3.318-.844l-3.677.964.984-3.595c-.607-1.052-.927-2.246-.926-3.468.001-5.824 4.74-10.563 10.564-10.563 5.832 0 10.564 4.742 10.564 10.564 0 5.827-4.732 10.564-10.564 10.564z"/></svg> Entre em Contato</a></div>`;

// Insert after features section closing
index = index.replace('<!-- End Features Section -->', '<!-- End Features Section -->\n' + WPP_BTN_1);

// Insert after integration section
index = index.replace('<!-- Start Reviews Section -->', WPP_BTN_2 + '\n\n<!-- Start Reviews Section -->');

// Re-write Pricing Section in index.html to only have monthly, and make it beautiful for both dark/light mode
// Also we need to do the same for planos.html if it exists.
let pricingHTML = `
<!-- Start Pricing Section -->
<section id="planos" class="py-24 relative overflow-hidden">
    <!-- Decorative -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[1000px] h-[500px] bg-primary/10 rounded-full blur-[100px] -z-10 pointer-events-none"></div>

    <div class="container mx-auto px-6 max-w-6xl relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="inline-block py-1 px-3 rounded-full bg-primary/10 text-primary font-bold text-sm tracking-wide uppercase mb-4">Planos Simples</span>
            <h2 class="text-4xl md:text-5xl font-display font-bold mb-6 text-black dark:text-white">Transparência total,<br>sem letras miúdas.</h2>
            <p class="text-lg text-subtle-light dark:text-subtle-dark font-medium">
                O melhor custo-benefício do mercado para a gestão de ponto eletrônico.
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 items-center max-w-5xl mx-auto">
            <!-- Plano 1 -->
            <div class="bg-surface-light dark:bg-[#111] rounded-3xl p-8 border border-borderline-light dark:border-borderline-dark hover:border-primary/50 transition-colors shadow-soft">
                <h3 class="text-xl font-bold text-black dark:text-white mb-2">Time Ágil 10</h3>
                <p class="text-sm text-subtle-light dark:text-subtle-dark mb-6">Até 10 funcionários</p>
                <div class="mb-6">
                    <span class="text-4xl font-display font-black text-black dark:text-white">R$89,90</span>
                    <span class="text-subtle-light dark:text-subtle-dark">/mês</span>
                </div>
                <a href="${WPP_URL}" target="_blank" class="block w-full py-3 rounded-full text-center text-sm font-bold border border-borderline-light dark:border-borderline-dark hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black transition-all mb-8">Contrate Agora</a>
                <ul class="space-y-4 text-sm font-medium text-subtle-light dark:text-subtle-dark">
                    <li class="flex items-center gap-3"><i data-lucide="check" class="w-5 h-5 text-primary"></i> Reconhecimento Facial</li>
                    <li class="flex items-center gap-3"><i data-lucide="check" class="w-5 h-5 text-primary"></i> Registro Offline</li>
                    <li class="flex items-center gap-3"><i data-lucide="check" class="w-5 h-5 text-primary"></i> Geolocalização</li>
                    <li class="flex items-center gap-3"><i data-lucide="check" class="w-5 h-5 text-primary"></i> Gestão de Documentos</li>
                </ul>
            </div>

            <!-- Plano 2 (Destaque) -->
            <div class="bg-black dark:bg-white rounded-3xl p-8 border border-primary relative transform md:-translate-y-4 shadow-2xl">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-primary text-white text-xs font-bold px-4 py-1 rounded-full uppercase tracking-wide">
                    Mais Vendido
                </div>
                <h3 class="text-xl font-bold text-white dark:text-black mb-2">Equipe Dinâmica 50</h3>
                <p class="text-sm text-gray-400 dark:text-gray-600 mb-6">Até 50 Funcionários</p>
                <div class="mb-6">
                    <span class="text-4xl font-display font-black text-white dark:text-black">R$299,90</span>
                    <span class="text-gray-400 dark:text-gray-600">/mês</span>
                </div>
                <a href="${WPP_URL}" target="_blank" class="block w-full py-3 rounded-full text-center text-sm font-bold bg-primary text-white hover:scale-105 transition-transform mb-8">Assinar Agora</a>
                <ul class="space-y-4 text-sm font-medium text-gray-300 dark:text-gray-700">
                    <li class="flex items-center gap-3"><i data-lucide="check" class="w-5 h-5 text-primary"></i> Reconhecimento Facial</li>
                    <li class="flex items-center gap-3"><i data-lucide="check" class="w-5 h-5 text-primary"></i> Registro Offline</li>
                    <li class="flex items-center gap-3"><i data-lucide="check" class="w-5 h-5 text-primary"></i> Geolocalização</li>
                    <li class="flex items-center gap-3"><i data-lucide="check" class="w-5 h-5 text-primary"></i> Tempo Real</li>
                    <li class="flex items-center gap-3"><i data-lucide="check" class="w-5 h-5 text-primary"></i> Gestão de Escalas</li>
                </ul>
            </div>

            <!-- Plano 3 -->
            <div class="bg-surface-light dark:bg-[#111] rounded-3xl p-8 border border-borderline-light dark:border-borderline-dark hover:border-primary/50 transition-colors shadow-soft">
                <h3 class="text-xl font-bold text-black dark:text-white mb-2">Equipe Dinâmica +50</h3>
                <p class="text-sm text-subtle-light dark:text-subtle-dark mb-6">Mais de 50 Funcionários</p>
                <div class="mb-6">
                    <span class="text-4xl font-display font-black text-black dark:text-white">Sob Consulta</span>
                </div>
                <a href="${WPP_URL}" target="_blank" class="block w-full py-3 rounded-full text-center text-sm font-bold border border-borderline-light dark:border-borderline-dark hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black transition-all mb-8">Falar com Vendas</a>
                <ul class="space-y-4 text-sm font-medium text-subtle-light dark:text-subtle-dark">
                    <li class="flex items-center gap-3"><i data-lucide="check" class="w-5 h-5 text-primary"></i> Tudo do plano 50</li>
                    <li class="flex items-center gap-3"><i data-lucide="check" class="w-5 h-5 text-primary"></i> Suporte Prioritário</li>
                    <li class="flex items-center gap-3"><i data-lucide="check" class="w-5 h-5 text-primary"></i> Implantação Dedicada</li>
                    <li class="flex items-center gap-3"><i data-lucide="check" class="w-5 h-5 text-primary"></i> Treinamento</li>
                </ul>
            </div>
        </div>
    </div>
</section>
<!-- End Pricing Section -->
`;

// Regex to replace the pricing section
index = index.replace(/<!-- Start Pricing Section -->[\s\S]*?<!-- End Pricing Section -->/, pricingHTML);

fs.writeFileSync('index.html', index);

// 2. Update como-funciona.html
if (fs.existsSync('como-funciona.html')) {
    let como = fs.readFileSync('como-funciona.html', 'utf8');
    
    // Change hero copy
    como = como.replace(/<h1 class="text-5xl md:text-7xl font-display font-black tracking-tight text-black dark:text-white mb-6 leading-\[1\.1\]">[\s\S]*?<\/h1>/, `<h1 class="text-5xl md:text-7xl font-display font-black tracking-tight text-black dark:text-white mb-6 leading-[1.1]">\n                        Sistema de Controle de <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-orange-500">Ponto Digital</span>\n                    </h1>`);
    
    como = como.replace(/<p class="text-lg md:text-xl text-subtle-light dark:text-subtle-dark font-medium mb-10 max-w-2xl">[\s\S]*?<\/p>/, `<p class="text-lg md:text-xl text-subtle-light dark:text-subtle-dark font-medium mb-10 max-w-2xl">\n                        Controle de ponto via WhatsApp com reconhecimento facial e geolocalização. Gestão eficiente da jornada de trabalho dos colaboradores internos e externos.\n                    </p>`);
    
    // Add WhatsApp button below text in hero
    const WPP_HERO_BTN = `<a href="${WPP_URL}" target="_blank" class="magnetic bg-[#25D366] text-white px-8 py-4 rounded-full font-bold text-base md:text-lg hover:-translate-y-1 active:scale-95 transition-all shadow-lg hover:shadow-xl inline-flex items-center gap-2">\n                        <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-14.416c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm.029 18.88c-1.161 0-2.305-.292-3.318-.844l-3.677.964.984-3.595c-.607-1.052-.927-2.246-.926-3.468.001-5.824 4.74-10.563 10.564-10.563 5.832 0 10.564 4.742 10.564 10.564 0 5.827-4.732 10.564-10.564 10.564z"/></svg>\n                        <span>Entre Em Contato</span>\n                    </a>`;
    
    // Replace the existing hero buttons
    como = como.replace(/<div class="hero-reveal flex flex-col sm:flex-row gap-4 w-full sm:w-auto">[\s\S]*?<\/div>/, `<div class="hero-reveal flex flex-col sm:flex-row gap-4 w-full sm:w-auto">\n${WPP_HERO_BTN}\n</div>`);

    // Remove any pricing section from LPs
    como = como.replace(/<!-- Start Pricing Section -->[\s\S]*?<!-- End Pricing Section -->/, '');

    // Add floating WPP
    como = como.replace('</body>', floatingWpp + '\n</body>');

    fs.writeFileSync('como-funciona.html', como);
}

// 3. Update planos.html if exists
if (fs.existsSync('planos.html')) {
    let planos = fs.readFileSync('planos.html', 'utf8');
    planos = planos.replace(/<!-- Start Pricing Section -->[\s\S]*?<!-- End Pricing Section -->/, pricingHTML);
    planos = planos.replace('</body>', floatingWpp + '\n</body>');
    fs.writeFileSync('planos.html', planos);
}
