const fs = require('fs');

let index = fs.readFileSync('index.html', 'utf8');

// Remove the green WPP button block
const greenBtnRegex = /<!-- CTA Button Fluid -->[\s\S]*?<span class="relative z-10">Entre em Contato<\/span>[\s\S]*?<\/div>/;
index = index.replace(greenBtnRegex, '');

fs.writeFileSync('index.html', index);
