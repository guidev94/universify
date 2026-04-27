const fs = require('fs');
let html = fs.readFileSync('como-funciona.html', 'utf8');

// There's a WhatsApp mockup image remaining in como-funciona.html inside a <div class="flex justify-center">
const waRegex = /<div class="flex justify-center">\s*<img src="https:\/\/www\.letswork\.com\.br\/wp-content\/uploads\/2022\/04\/WhatsApp-Image-2022-04-13-at-11\.23\.21-4\.jpeg" alt="Demonstração do Registro de Ponto Digital via Integração com WhatsApp" class="w-\[200px\] rounded-3xl shadow-soft border-4 border-white dark:border-\[#222\]">\s*<\/div>/;

if (html.match(waRegex)) {
    html = html.replace(waRegex, '');
    fs.writeFileSync('como-funciona.html', html);
    console.log('Removed WA image from como-funciona.html');
}

// Oh actually, since it was side-by-side with text, the grid might be weird if I just remove it. Let me check the layout.
