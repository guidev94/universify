const fs = require('fs');

let htmlComo = fs.readFileSync('como-funciona.html', 'utf8');

const regexImgComo = /<img src="https:\/\/www\.letswork\.com\.br\/wp-content\/themes\/letswork2022\/assets\/img\/banner-circle-desk\.avif" alt="[^"]*" class="w-full h-auto object-contain drop-shadow-2xl hover:scale-\[1\.02\] transition-transform duration-500">/;

htmlComo = htmlComo.replace(
    regexImgComo,
    '<img src="https://images.unsplash.com/photo-1551434678-e076c223a692?auto=format&fit=crop&q=80&w=1200" alt="Equipe de gestores de Recursos Humanos analisando dashboard de dados na plataforma Lets Work" class="w-full h-auto object-cover rounded-2xl drop-shadow-xl hover:scale-[1.02] transition-transform duration-500">'
);

fs.writeFileSync('como-funciona.html', htmlComo);

let htmlIa = fs.readFileSync('ia-no-rh.html', 'utf8');

const regexImgIa = /<img src="https:\/\/www\.letswork\.com\.br\/wp-content\/themes\/letswork2022\/assets\/img\/WhatsApp-Image-2022-04-13-at-11\.23\.21-4\.avif" alt="[^"]*" class="w-\[240px\] rounded-\[2rem\] shadow-2xl border-4 border-white dark:border-\[#222\]">/;

htmlIa = htmlIa.replace(
    regexImgIa,
    '<img src="https://images.unsplash.com/photo-1677442136019-21780ecad995?auto=format&fit=crop&q=80&w=800" alt="Representação 3D de Inteligência Artificial e Redes Neurais para automação de tarefas de RH" class="w-full h-auto rounded-[2rem] shadow-2xl object-cover hover:scale-[1.02] transition-transform duration-500">'
);

htmlIa = htmlIa.replace(
    /<div class="flex justify-center py-4 bg-gradient-to-b from-transparent to-primary\/5 dark:to-primary\/10 rounded-\[2rem\]">/,
    '<div class="flex justify-center rounded-[2rem] w-full h-full">'
);

fs.writeFileSync('ia-no-rh.html', htmlIa);
console.log('Fixed');
