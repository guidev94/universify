const fs = require('fs');

let html = fs.readFileSync('index.html', 'utf8');

// Remove old button block
const oldBtnRegex = /<!-- Botão Flutuante WhatsApp Intuitivo -->[\s\S]*?<\/a>\s*(?=<!-- Floating WhatsApp Button -->)/;

if(html.match(oldBtnRegex)) {
    html = html.replace(oldBtnRegex, '');
    fs.writeFileSync('index.html', html);
    console.log('Removed old floating button');
} else {
    console.log('Old button not found');
}
