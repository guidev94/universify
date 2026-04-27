const fs = require('fs');

const sleekHeroBtn = `
                <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20um%20especialista%20da%20Let%27s%20Work%21" target="_blank" class="magnetic bg-black dark:bg-white text-white dark:text-black px-8 py-4 rounded-full font-bold text-base md:text-lg hover:-translate-y-1 active:scale-95 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:ring-offset-2 transition-all duration-300 flex items-center justify-center gap-2 shadow-[0_10px_20px_rgba(0,0,0,0.1)] dark:shadow-[0_10px_20px_rgba(255,255,255,0.1)] group w-full sm:w-auto">
                    <span>Entre Em Contato</span>
                    <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                </a>
`;

const files = fs.readdirSync('.').filter(f => f.endsWith('.html') && f !== 'index.html');
for (const file of files) {
    let content = fs.readFileSync(file, 'utf8');
    
    // Replace green CTA buttons with sleek ones
    content = content.replace(/<a href="[^"]*" target="_blank" class="magnetic inline-flex items-center justify-center gap-2 bg-\[#25D366\] text-white px-8 py-4 rounded-full font-bold text-base md:text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-lg hover:shadow-xl w-full sm:w-auto">\s*<span>Entre Em Contato<\/span>\s*<i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"><\/i><\/a>/g, 
                              sleekHeroBtn);

    fs.writeFileSync(file, content);
}
