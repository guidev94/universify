const fs = require('fs');

let html = fs.readFileSync('como-funciona.html', 'utf8');

html = html.replace(
    /Evolução da Gestão de RH com <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-orange-500">IA<\/span>/,
    'Evolução da Gestão de RH com <span class="text-primary">IA</span>'
);

fs.writeFileSync('como-funciona.html', html);
console.log('Fixed gradient in como-funciona');
