const fs = require('fs');

// 1. Update image in ia-no-rh.html
let iaHtml = fs.readFileSync('ia-no-rh.html', 'utf8');

iaHtml = iaHtml.replace(
    /<img src="https:\/\/images\.unsplash\.com\/photo-1677442136019-21780ecad995\?auto=format&fit=crop&q=80&w=800" alt="Dashboard Analítico de Inteligência Artificial" class="w-full h-auto bg-surface-light dark:bg-surface-dark object-cover">/,
    `<div class="flex justify-center py-4 bg-gradient-to-b from-transparent to-primary/5 dark:to-primary/10 rounded-[2rem]">
                        <img src="https://www.letswork.com.br/wp-content/uploads/2022/04/WhatsApp-Image-2022-04-13-at-11.23.21-4.jpeg" alt="Demonstração da Vittoria no WhatsApp" class="w-[240px] rounded-[2rem] shadow-2xl border-4 border-white dark:border-[#222]">
                    </div>`
);
iaHtml = iaHtml.replace(
    /<meta property="og:image" content="https:\/\/images\.unsplash\.com\/photo-1677442136019-21780ecad995\?auto=format&fit=crop&q=80&w=800">/,
    `<meta property="og:image" content="https://www.letswork.com.br/wp-content/uploads/2022/04/WhatsApp-Image-2022-04-13-at-11.23.21-4.jpeg">`
);

fs.writeFileSync('ia-no-rh.html', iaHtml);

// 2. Update WhatsApp Button in all 3 files
const files = ['index.html', 'como-funciona.html', 'ia-no-rh.html'];

const oldBtnRegex = /<!-- Floating WhatsApp Button -->[\s\S]*?<\/a>/;

const newButton = `<!-- Floating WhatsApp Button -->
<a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20um%20especialista%20da%20Let%27s%20Work%21" target="_blank" class="fixed bottom-8 right-8 z-[100] flex items-center justify-center w-16 h-16 bg-[#25D366] text-white rounded-full shadow-[0_4px_15px_rgba(37,211,102,0.4)] hover:-translate-y-1 hover:shadow-[0_8px_25px_rgba(37,211,102,0.5)] transition-all duration-300">
    <span class="absolute inset-0 rounded-full bg-[#25D366] animate-[ping_2s_cubic-bezier(0,0,0.2,1)_infinite] opacity-75"></span>
    <svg class="w-8 h-8 fill-current relative z-10" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
    </svg>
</a>`;

for (const file of files) {
    if (fs.existsSync(file)) {
        let content = fs.readFileSync(file, 'utf8');
        if (content.match(oldBtnRegex)) {
            content = content.replace(oldBtnRegex, newButton);
            fs.writeFileSync(file, content);
            console.log(`Updated WhatsApp button in ${file}`);
        }
    }
}
