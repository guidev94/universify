const fs = require('fs');

const wppSvg = `<svg class="w-6 h-6 md:w-7 md:h-7 drop-shadow-sm relative z-10" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-14.416c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm.029 18.88c-1.161 0-2.305-.292-3.318-.844l-3.677.964.984-3.595c-.607-1.052-.927-2.246-.926-3.468.001-5.824 4.74-10.563 10.564-10.563 5.832 0 10.564 4.742 10.564 10.564 0 5.827-4.732 10.564-10.564 10.564z"/></svg>`;

const floatBtnHTML = `
    <!-- Styles de Animação Extras -->
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

    <!-- Botão Flutuante WhatsApp Intuitivo -->
    <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20um%20especialista%20da%20Let%27s%20Work%21" target="_blank" class="fixed bottom-6 right-6 lg:bottom-8 lg:right-8 z-50 bg-[#25D366] hover:bg-[#20b858] text-white px-5 py-3 md:px-6 md:py-3.5 rounded-full shadow-[0_8px_25px_rgba(37,211,102,0.4)] hover:shadow-[0_15px_35px_rgba(37,211,102,0.5)] hover:-translate-y-1 active:scale-95 transition-all duration-300 flex items-center gap-3 border border-[#25D366]/50 group overflow-hidden">
        <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/30 to-transparent group-hover:animate-[shimmer_1.5s_infinite]"></div>
        ${wppSvg}
        <div class="flex flex-col items-start hidden sm:flex relative z-10">
            <span class="text-[9px] md:text-[10px] uppercase tracking-widest font-black text-green-100 leading-none mb-1 flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> Online agora</span>
            <span class="text-sm md:text-base font-bold leading-none">Falar com a Vitória</span>
        </div>
        <span class="flex sm:hidden font-bold text-sm relative z-10">Contato</span>
    </a>
`;

let index = fs.readFileSync('index.html', 'utf8');

// Replace floating button
index = index.replace(/<!-- Botão Flutuante WhatsApp Elegante -->[\s\S]*?<\/a>/, floatBtnHTML);

// Enhance Face Scan Icon in Home
const oldFaceIcon = `<div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center mb-6 backdrop-blur-md border border-white/10">
                            <i data-lucide="scan-face" class="w-6 h-6 text-white"></i>
                        </div>`;
const newFaceIcon = `
                        <!-- Ícone Refatorado e Maior (Reconhecimento Facial) -->
                        <div class="w-20 h-20 rounded-2xl bg-gradient-to-b from-white/10 to-white/5 flex items-center justify-center mb-6 backdrop-blur-md border border-white/20 relative overflow-hidden group-hover:border-primary/50 transition-colors duration-500 shadow-[0_0_30px_rgba(255,255,255,0.05)]">
                            <div class="absolute top-0 left-0 w-full h-[3px] bg-primary shadow-[0_0_15px_rgba(255,45,45,0.9)] animate-[scanline_2s_linear_infinite]"></div>
                            <i data-lucide="scan-face" class="w-10 h-10 text-white group-hover:scale-110 transition-transform duration-500 relative z-10"></i>
                        </div>`;

if(index.includes(oldFaceIcon)) {
    index = index.replace(oldFaceIcon, newFaceIcon);
} else {
    // alternative match
    index = index.replace(/<div class="w-12 h-12 rounded-xl bg-white\/10 flex items-center justify-center mb-6 backdrop-blur-md border border-white\/10">[\s\S]*?<i data-lucide="scan-face" class="w-6 h-6 text-white"><\/i>[\s\S]*?<\/div>/, newFaceIcon);
}

// Enhance Inline WhatsApp Buttons
index = index.replace(/class="inline-flex items-center justify-center gap-2 bg-\[#25D366\] text-white px-8 py-4 rounded-full font-bold text-base md:text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-\[0_10px_20px_rgba\(37,211,102,0\.3\)\] hover:shadow-\[0_15px_30px_rgba\(37,211,102,0\.5\)\] border border-\[#20b858\]"/g, 
    'class="inline-flex items-center justify-center gap-3 bg-[#25D366] text-white px-8 py-4 rounded-full font-bold text-base md:text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-[0_10px_25px_rgba(37,211,102,0.4)] hover:shadow-[0_15px_35px_rgba(37,211,102,0.5)] border border-[#20b858] relative overflow-hidden group"');

// Inject shimmer div into existing inline WPP links
index = index.replace(/<svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path/g, 
    '<div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/30 to-transparent group-hover:animate-[shimmer_1.5s_infinite]"></div><svg class="w-6 h-6 fill-current relative z-10" viewBox="0 0 24 24"><path');

index = index.replace(/<span>Entre Em Contato<\/span>/g, '<span class="relative z-10">Entre Em Contato</span>');
index = index.replace(/Solicite Uma Demonstração<\/a>/g, '<span class="relative z-10">Solicite Uma Demonstração</span></a>');

fs.writeFileSync('index.html', index);

// 2. Process LPs
const htmlFiles = fs.readdirSync('.').filter(f => f.endsWith('.html') && f !== 'index.html');
for (const file of htmlFiles) {
    let content = fs.readFileSync(file, 'utf8');

    // Float Btn
    content = content.replace(/<!-- Botão Flutuante WhatsApp Elegante -->[\s\S]*?<\/a>/, floatBtnHTML);
    content = content.replace(/<!-- Botão Flutuante WhatsApp -->[\s\S]*?<\/a>/, floatBtnHTML);

    // Inline Wpp buttons
    content = content.replace(/class="magnetic inline-flex items-center justify-center gap-2 bg-\[#25D366\] text-white px-8 py-4 rounded-full font-bold text-base md:text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-\[0_10px_20px_rgba\(37,211,102,0\.3\)\] hover:shadow-\[0_15px_30px_rgba\(37,211,102,0\.5\)\] border border-\[#20b858\] w-full sm:w-auto"/g, 
    'class="magnetic inline-flex items-center justify-center gap-3 bg-[#25D366] text-white px-8 py-4 rounded-full font-bold text-base md:text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-[0_10px_25px_rgba(37,211,102,0.4)] hover:shadow-[0_15px_35px_rgba(37,211,102,0.5)] border border-[#20b858] w-full sm:w-auto relative overflow-hidden group"');

    content = content.replace(/<svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path/g, 
    '<div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/30 to-transparent group-hover:animate-[shimmer_1.5s_infinite]"></div><svg class="w-6 h-6 fill-current relative z-10" viewBox="0 0 24 24"><path');

    content = content.replace(/<span>Entre Em Contato<\/span>/g, '<span class="relative z-10">Entre Em Contato</span>');

    fs.writeFileSync(file, content);
}
