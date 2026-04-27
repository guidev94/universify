const fs = require('fs');

let index = fs.readFileSync('index.html', 'utf8');

// Remove the injected mid-section buttons entirely to fix the flow
const regexEntreContato = /<div class="mt-12 text-center section-reveal">[\s\S]*?Entre em Contato[\s\S]*?<\/div>/g;
index = index.replace(regexEntreContato, '');

const regexSolicite = /<div class="mt-8 text-center section-reveal">[\s\S]*?Solicite Uma Demonstração[\s\S]*?<\/div>/g;
index = index.replace(regexSolicite, '');

fs.writeFileSync('index.html', index);

// Let's do the same for LP files just in case they were added there (they weren't, but safely)
const files = fs.readdirSync('.').filter(f => f.endsWith('.html') && f !== 'index.html');
for (const file of files) {
    let content = fs.readFileSync(file, 'utf8');
    content = content.replace(regexEntreContato, '');
    content = content.replace(regexSolicite, '');
    fs.writeFileSync(file, content);
}
