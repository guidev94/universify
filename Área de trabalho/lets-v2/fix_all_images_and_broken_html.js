const fs = require('fs');

// 1. FIX DOWNLOAD APP HTML
const comoFunciona = fs.readFileSync('como-funciona.html', 'utf8');
const navEndStr = '</nav>';
const navEndIdx = comoFunciona.indexOf(navEndStr) + navEndStr.length;
const correctHeadAndNav = comoFunciona.substring(0, navEndIdx);

let downloadApp = fs.readFileSync('download-app.html', 'utf8');
const mainStart = '<!-- MAIN WRAPPER -->';
if (downloadApp.includes(mainStart)) {
    const mainContent = downloadApp.substring(downloadApp.indexOf(mainStart));
    
    // Correct Meta Tags for Download App
    let newHead = correctHeadAndNav.replace(/<title>.*?<\/title>/, '<title>Download do App | Let\\'s Work</title>');
    newHead = newHead.replace(/<meta name="description" content=".*?">/, '<meta name="description" content="Baixe o aplicativo da Let\\'s Work para iOS, Android ou Desktop e tenha o controle da sua jornada na palma da mão.">');
    
    fs.writeFileSync('download-app.html', newHead + '\\n' + mainContent);
    console.log('Fixed download-app.html structure!');
}

// 2. REPLACE PHOTOS WITH 3D ILLUSTRATIONS
const illustrationMap = {
  'gerenciamento-de-projetos.html': 'https://images.unsplash.com/photo-1611224923853-80b023f02d71?auto=format&fit=crop&q=80&w=800', // 3D blocks/tasks
  'gerenciamento-de-escalas.html': 'https://images.unsplash.com/photo-1633435492471-700c25d88667?auto=format&fit=crop&q=80&w=800', // 3D clock/time
  'rastreamento.html': 'https://images.unsplash.com/photo-1633394867160-c116d4c1b184?auto=format&fit=crop&q=80&w=800', // 3D map/globe
  'gestao-de-documentos.html': 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&q=80&w=800', // 3D abstract clean
  'comunicacao-de-equipe.html': 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?auto=format&fit=crop&q=80&w=800', // 3D chat/social
  'gerenciamento-de-ferias.html': 'https://images.unsplash.com/photo-1618005198919-d3d4b5a92ead?auto=format&fit=crop&q=80&w=800', // 3D abstract light
  'gerenciamento-de-vagas.html': 'https://images.unsplash.com/photo-1616423640778-28d1b53229bd?auto=format&fit=crop&q=80&w=800', // 3D megaphone/hiring
  'ia-no-rh.html': 'https://images.unsplash.com/photo-1677442136019-21780ecad995?auto=format&fit=crop&q=80&w=800' // 3D AI/Bot
};

const oldPhotos = [
    'https://images.unsplash.com/photo-1531403009284-440f080d1e12?auto=format&fit=crop&q=80&w=1000',
    'https://images.unsplash.com/photo-1506784983877-45594efa4cbe?auto=format&fit=crop&q=80&w=1000',
    'https://images.unsplash.com/photo-1524661135-423995f22d0b?auto=format&fit=crop&q=80&w=1000',
    'https://images.unsplash.com/photo-1618044733300-9472054094ee?auto=format&fit=crop&q=80&w=1000',
    'https://images.unsplash.com/photo-1577563908411-5077b6dc7624?auto=format&fit=crop&q=80&w=1000',
    'https://images.unsplash.com/photo-1499696010180-025ef6e1a8f9?auto=format&fit=crop&q=80&w=1000',
    'https://images.unsplash.com/photo-1586281380349-632531db7ed4?auto=format&fit=crop&q=80&w=1000',
    'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&q=80&w=800' // used in IA
];

const files = fs.readdirSync(__dirname).filter(f => f.endsWith('.html'));

files.forEach(file => {
    let content = fs.readFileSync(file, 'utf8');
    let changed = false;

    // Apply the specific 3D illustration for this file
    if (illustrationMap[file]) {
        // Replace any old photo with the specific new 3D illustration
        oldPhotos.forEach(old => {
            if (content.includes(old)) {
                content = content.split(old).join(illustrationMap[file]);
                changed = true;
            }
        });
        
        // Em ia-no-rh.html, tem mais de uma imagem que precisa ser trocada pra 3D
        if (file === 'ia-no-rh.html') {
             // O Dashboard Analitico
             content = content.replace('https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&q=80&w=800', illustrationMap[file]);
             changed = true;
        }
    }

    if (changed) {
        fs.writeFileSync(file, content);
        console.log(`Updated illustrations in ${file}`);
    }
});

// Also fix download-app.html hero image
let dlApp = fs.readFileSync('download-app.html', 'utf8');
if (dlApp.includes('https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c')) {
    dlApp = dlApp.replace(/https:\/\/images\.unsplash\.com\/photo-1512941937669-90a1b58e7e9c[^\s"]*/, 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&q=80&w=600');
    fs.writeFileSync('download-app.html', dlApp);
    console.log('Fixed background mockup in download-app.html');
}

