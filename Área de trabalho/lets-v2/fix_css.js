const fs = require('fs');

let index = fs.readFileSync('index.html', 'utf8');

// Ensure CSS keyframes are present
const styleInject = `
    <!-- Extra CSS Keyframes -->
    <style>
        @keyframes shimmer {
            100% { transform: translateX(100%); }
        }
        @keyframes scanline {
            0% { transform: translateY(-10px); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(60px); opacity: 0; }
        }
    </style>
</head>`;

if (!index.includes('@keyframes scanline')) {
    index = index.replace('</head>', styleInject);
    fs.writeFileSync('index.html', index);
}

// Do the same for LPs
const files = fs.readdirSync('.').filter(f => f.endsWith('.html') && f !== 'index.html');
for (const file of files) {
    let content = fs.readFileSync(file, 'utf8');
    if (!content.includes('@keyframes scanline')) {
        content = content.replace('</head>', styleInject);
        fs.writeFileSync(file, content);
    }
}
