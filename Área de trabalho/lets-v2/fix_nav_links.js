const fs = require('fs');

const files = fs.readdirSync('.').filter(f => f.endsWith('.html'));

for (const file of files) {
    let content = fs.readFileSync(file, 'utf8');
    let changed = false;

    // Fix <a href="#planos"> to <a href="planos.html">
    if (content.includes('href="#planos"')) {
        content = content.replace(/href="#planos"/g, 'href="planos.html"');
        changed = true;
    }
    
    // Some links might be going directly to the old pricing URL
    const oldPricingLink = 'https://www.letswork.com.br/preco-de-aplicativo-de-ponto-com-reconhecimento-facial/';
    if (content.includes(oldPricingLink)) {
        // We removed them mostly, but let's be sure
        content = content.replace(/https:\/\/www\.letswork\.com\.br\/preco-de-aplicativo-de-ponto-com-reconhecimento-facial\//g, 'planos.html');
        changed = true;
    }

    if (changed) {
        fs.writeFileSync(file, content);
        console.log(`Updated links in ${file}`);
    }
}
