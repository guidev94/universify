const fs = require('fs');

function replaceBetween(str, start, end, replacement) {
    const startIdx = str.indexOf(start);
    if (startIdx === -1) return str;
    const endIdx = str.indexOf(end, startIdx);
    if (endIdx === -1) return str;
    return str.substring(0, startIdx) + replacement + str.substring(endIdx + end.length);
}

// 1. UPDATE TESTIMONIAL HEIGHT
let index = fs.readFileSync('index.html', 'utf8');

// The class "solid-card p-10 rounded-[2rem] w-[450px] shrink-0 flex flex-col shadow-soft" needs to have h-[380px] and justify-between
index = index.replace(/class="solid-card p-10 rounded-\[2rem\] w-\[450px\] shrink-0 flex flex-col shadow-soft hover:border-primary transition-colors"/g, 
'class="solid-card p-10 rounded-[2rem] w-[450px] shrink-0 flex flex-col justify-between h-[420px] shadow-soft hover:border-primary transition-colors"');

// 2. BIGGER FACE SCAN ICON IN ECOSSISTEMA INTELIGENTE
const oldFaceIcon = `<div class="mt-20 w-28 h-28 rounded-full border-[3px] border-dashed border-primary flex items-center justify-center relative bg-primary/5">
                                <div class="absolute inset-0 bg-primary/10 rounded-full animate-pulse"></div>
                                <i data-lucide="scan-face" class="w-12 h-12 text-primary"></i>
                            </div>`;

const newFaceIcon = `<div class="mt-16 w-36 h-36 rounded-full border-[3px] border-dashed border-primary flex items-center justify-center relative bg-primary/5 overflow-hidden shadow-[0_0_20px_rgba(255,45,45,0.2)]">
                                <div class="absolute inset-0 bg-primary/10 rounded-full animate-pulse"></div>
                                <div class="absolute top-0 left-0 w-full h-[4px] bg-primary shadow-[0_0_15px_rgba(255,45,45,0.9)] animate-[scanline_2s_linear_infinite]"></div>
                                <i data-lucide="scan-face" class="w-16 h-16 text-primary relative z-10"></i>
                            </div>`;

index = index.replace(oldFaceIcon, newFaceIcon);

// 3. REVERT INLINE WHATSAPP BUTTONS TO PREVIOUS SLEEK DESIGN
const sleekBtnDesign = 'class="magnetic bg-black dark:bg-white text-white dark:text-black px-8 py-4 rounded-full font-bold text-base md:text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-[0_10px_20px_rgba(0,0,0,0.1)] dark:shadow-[0_10px_20px_rgba(255,255,255,0.1)] inline-flex items-center gap-2"';
const sleekBtnDesignCenter = 'class="magnetic bg-black dark:bg-white text-white dark:text-black px-8 py-4 rounded-full font-bold text-base md:text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-[0_10px_20px_rgba(0,0,0,0.1)] dark:shadow-[0_10px_20px_rgba(255,255,255,0.1)] inline-flex items-center justify-center gap-2"';

// Find the green buttons and replace
const greenBtnRegex = /class="[^"]*bg-\[#25D366\][^"]*"/g;
index = index.replace(greenBtnRegex, sleekBtnDesignCenter);

// Also remove the whatsapp SVG icon from the inline buttons to "not change design from before" 
// Or we can keep a lucide icon
index = index.replace(/<svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path[^>]*><\/path><\/svg>/g, '');

fs.writeFileSync('index.html', index);

// LPs
const htmlFiles = fs.readdirSync('.').filter(f => f.endsWith('.html') && f !== 'index.html');
for (const file of htmlFiles) {
    let content = fs.readFileSync(file, 'utf8');
    
    // Replace green CTA buttons with sleek ones
    content = content.replace(greenBtnRegex, sleekBtnDesignCenter);
    content = content.replace(/<svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path[^>]*><\/path><\/svg>/g, '');
    
    fs.writeFileSync(file, content);
}
