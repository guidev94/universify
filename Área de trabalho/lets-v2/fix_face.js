const fs = require('fs');

let index = fs.readFileSync('index.html', 'utf8');

// Ensure Face Icon is updated
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
}

fs.writeFileSync('index.html', index);
