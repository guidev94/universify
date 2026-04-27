const fs = require('fs');

const htmlFiles = fs.readdirSync('.').filter(f => f.endsWith('.html'));

for (const file of htmlFiles) {
    let content = fs.readFileSync(file, 'utf8');
    
    // Add arrow icon back to inline buttons
    content = content.replace(/>\s*<span>Entre Em Contato<\/span>/g, '>\n                        <span>Entre Em Contato</span>\n                        <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>');
    
    content = content.replace(/>\s*Solicite Uma Demonstração<\/a>/g, '>\n                        Solicite Uma Demonstração\n                        <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i></a>');
    
    fs.writeFileSync(file, content);
}
