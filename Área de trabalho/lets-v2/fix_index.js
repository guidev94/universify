const fs = require('fs');

let html = fs.readFileSync('index.html', 'utf8');

// 1. Replace Hero Social Proof
const oldSocialProof = `<div class="flex gap-4">
                    <div class="w-14 h-14 rounded-full shadow-sm flex items-center justify-center overflow-hidden bg-white dark:bg-white/10 border border-borderline-light dark:border-borderline-dark p-2"><img src="https://www.letswork.com.br/wp-content/uploads/2022/07/1.png" alt="O Boticario" class="w-full h-full object-contain"></div>
                    <div class="w-14 h-14 rounded-full shadow-sm flex items-center justify-center overflow-hidden bg-white dark:bg-white/10 border border-borderline-light dark:border-borderline-dark p-2"><img src="https://www.letswork.com.br/wp-content/uploads/2022/07/zarpng.png" alt="Zarpo" class="w-full h-full object-contain"></div>
                    <div class="w-14 h-14 rounded-full shadow-sm flex items-center justify-center overflow-hidden bg-white dark:bg-white/10 border border-borderline-light dark:border-borderline-dark p-2"><img src="https://www.letswork.com.br/wp-content/uploads/2022/07/giupng.png" alt="Giuliana Flores" class="w-full h-full object-contain"></div>
                    <div class="w-14 h-14 rounded-full shadow-sm flex items-center justify-center overflow-hidden bg-white dark:bg-white/10 border border-borderline-light dark:border-borderline-dark p-2"><img src="https://www.letswork.com.br/wp-content/uploads/2025/08/sebrae.png" alt="Sebrae" class="w-full h-full object-contain dark:brightness-0 dark:invert"></div>
                </div>`;

const newSocialProof = `<div class="flex gap-4">
                    <div class="w-14 h-14 rounded-full shadow-sm flex items-center justify-center overflow-hidden bg-white dark:bg-white/10 border border-borderline-light dark:border-borderline-dark p-2"><img src="https://www.letswork.com.br/wp-content/uploads/2025/08/sebrae.png" alt="Sebrae" class="w-full h-full object-contain dark:brightness-0 dark:invert"></div>
                    <div class="w-14 h-14 rounded-full shadow-sm flex items-center justify-center overflow-hidden bg-white dark:bg-white/10 border border-borderline-light dark:border-borderline-dark p-2"><img src="https://www.letswork.com.br/wp-content/uploads/2025/08/bossa.png" alt="Bossa" class="w-full h-full object-contain dark:brightness-0 dark:invert"></div>
                    <div class="w-14 h-14 rounded-full shadow-sm flex items-center justify-center overflow-hidden bg-white dark:bg-white/10 border border-borderline-light dark:border-borderline-dark p-2"><img src="https://www.letswork.com.br/wp-content/uploads/2025/10/BRDE.avif" alt="BRDE" class="w-full h-full object-contain dark:brightness-0 dark:invert"></div>
                    <div class="w-14 h-14 rounded-full shadow-sm flex items-center justify-center overflow-hidden bg-white dark:bg-white/10 border border-borderline-light dark:border-borderline-dark p-2"><img src="https://www.letswork.com.br/wp-content/uploads/2025/10/organica.avif" alt="Organica" class="w-full h-full object-contain dark:brightness-0 dark:invert"></div>
                </div>`;

if(html.includes(oldSocialProof)) {
    html = html.replace(oldSocialProof, newSocialProof);
}

// 2. Inject Floating WhatsApp Button before </body>
const floatingButton = `

<!-- Floating WhatsApp Button -->
<a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20a%20Vittoria%21" target="_blank" class="group fixed bottom-8 right-8 z-[100] flex items-center bg-[#25D366] text-white rounded-full p-4 shadow-[0_10px_20px_rgba(37,211,102,0.3)] hover:pr-6 hover:shadow-[0_10px_25px_rgba(37,211,102,0.5)] hover:-translate-y-1 transition-all duration-300 border-2 border-transparent hover:border-white/20">
    <svg class="w-8 h-8 fill-current shrink-0 drop-shadow-sm" viewBox="0 0 24 24">
        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-14.416c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm.029 18.88c-1.161 0-2.305-.292-3.318-.844l-3.677.964.984-3.595c-.607-1.052-.927-2.246-.926-3.468.001-5.824 4.74-10.563 10.564-10.563 5.832 0 10.564 4.742 10.564 10.564 0 5.827-4.732 10.564-10.564 10.564z"/>
    </svg>
    <span class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 group-hover:max-w-xs group-hover:opacity-100 group-hover:ml-3 font-bold tracking-wide transition-all duration-500 ease-out">
        Falar com atendimento
    </span>
</a>

</body>`;

html = html.replace('</body>', floatingButton);

fs.writeFileSync('index.html', html);
console.log('index.html updated');
