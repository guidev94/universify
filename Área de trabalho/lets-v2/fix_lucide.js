const fs = require('fs');
let index = fs.readFileSync('index.html', 'utf8');

// The lucide script initialization is completely missing from the very bottom of the document
// I need to add `lucide.createIcons();` right before the closing tag, inside a script block.

if (!index.includes('lucide.createIcons()')) {
    index = index.replace('</body>', `
    <script>
        // Inicializar os icones do Lucide globalmente
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>`);
} else {
    // If it exists but is broken, let's put it firmly at the bottom
    index = index.replace(/lucide\.createIcons\(\);/g, '');
    index = index.replace('</body>', `
    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>`);
}

fs.writeFileSync('index.html', index);

// Do the same for LPs
const files = fs.readdirSync('.').filter(f => f.endsWith('.html') && f !== 'index.html');
for (const file of files) {
    let content = fs.readFileSync(file, 'utf8');
    content = content.replace(/lucide\.createIcons\(\);/g, '');
    content = content.replace('</body>', `
    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>`);
    fs.writeFileSync(file, content);
}
