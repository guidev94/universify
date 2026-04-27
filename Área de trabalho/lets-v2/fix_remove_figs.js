const fs = require('fs');

const files = ['como-funciona.html', 'ia-no-rh.html'];

for (const file of files) {
    if (fs.existsSync(file)) {
        let html = fs.readFileSync(file, 'utf8');

        // Remove the main <figure> block with the unsplash images
        const figureRegex = /<figure[^>]*>[\s\S]*?<\/figure>/;
        
        while (html.match(figureRegex)) {
            html = html.replace(figureRegex, '');
        }

        fs.writeFileSync(file, html);
        console.log(`Removed figures from ${file}`);
    }
}
