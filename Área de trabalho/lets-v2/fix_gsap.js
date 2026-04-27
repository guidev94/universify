const fs = require('fs');
let index = fs.readFileSync('index.html', 'utf8');

// The duplicate sectionsToReveal logic is breaking everything because of syntax errors inserted previously.
// Let's strip out the injected "const sectionsToReveal" block which was malformed, and just use the bottom one

const badBlockRegex = /const sectionsToReveal = gsap\.utils\.toArray\('\.section-reveal'\);[\s\S]*?if\(sectionsToReveal\) \{[\s\S]*?\}\s*\}/;

index = index.replace(badBlockRegex, '');
index = index.replace(/const sectionsToReveal = gsap\.utils\.toArray\('\.section-reveal'\);\n        if\(sectionsToReveal\.length > 0\) \{\n            sectionsToReveal\.forEach\(section => \{[\s\S]*?\}\);\n        \}\n/g, '');

// Also let's style the WhatsApp floating button to be smaller and more elegant.
const oldWpp = `<!-- Botão Flutuante WhatsApp -->
    <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20um%20especialista%20da%20Let%27s%20Work%21" target="_blank" class="fixed bottom-6 right-6 z-50 bg-[#25D366] text-white p-4 rounded-full shadow-lg hover:scale-110 hover:shadow-xl transition-all duration-300 flex items-center justify-center group">
        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-14.416c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm.029 18.88c-1.161 0-2.305-.292-3.318-.844l-3.677.964.984-3.595c-.607-1.052-.927-2.246-.926-3.468.001-5.824 4.74-10.563 10.564-10.563 5.832 0 10.564 4.742 10.564 10.564 0 5.827-4.732 10.564-10.564 10.564z"/></svg>
        <span class="absolute right-full mr-4 bg-black dark:bg-white text-white dark:text-black text-sm px-3 py-1.5 rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none font-bold">Fale com a Vitória</span>
    </a>`;

const newWpp = `<!-- Botão Flutuante WhatsApp Elegante -->
    <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20um%20especialista%20da%20Let%27s%20Work%21" target="_blank" class="fixed bottom-6 right-6 z-50 bg-[#25D366] text-white p-3 md:p-3.5 rounded-full shadow-[0_10px_30px_rgba(37,211,102,0.4)] hover:shadow-[0_15px_40px_rgba(37,211,102,0.6)] hover:-translate-y-2 active:scale-95 transition-all duration-300 flex items-center justify-center group">
        <svg class="w-7 h-7 md:w-8 md:h-8 drop-shadow-md" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-14.416c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm.029 18.88c-1.161 0-2.305-.292-3.318-.844l-3.677.964.984-3.595c-.607-1.052-.927-2.246-.926-3.468.001-5.824 4.74-10.563 10.564-10.563 5.832 0 10.564 4.742 10.564 10.564 0 5.827-4.732 10.564-10.564 10.564z"/></svg>
        <span class="absolute right-full mr-4 bg-white dark:bg-[#111] text-black dark:text-white border border-borderline-light dark:border-borderline-dark text-sm px-4 py-2 rounded-xl whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none font-bold shadow-lg flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Falar com Comercial
        </span>
    </a>`;

index = index.replace(oldWpp, newWpp);
// Also apply to inline CTA buttons if they are ugly
index = index.replace(/bg-\[#25D366\] text-white px-8 py-4 rounded-full font-bold text-lg hover:-translate-y-1 active:scale-95 transition-all shadow-lg hover:shadow-xl/g, 
"bg-[#25D366] text-white px-8 py-4 rounded-full font-bold text-base md:text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-[0_10px_20px_rgba(37,211,102,0.3)] hover:shadow-[0_15px_30px_rgba(37,211,102,0.5)] border border-[#20b858]");

fs.writeFileSync('index.html', index);

// Let's do the same for all LPs
const files = fs.readdirSync('.').filter(f => f.endsWith('.html') && f !== 'index.html');
for (const file of files) {
    let content = fs.readFileSync(file, 'utf8');
    content = content.replace(oldWpp, newWpp);
    content = content.replace(/bg-\[#25D366\] text-white px-8 py-4/g, "bg-[#25D366] text-white px-8 py-4");
    content = content.replace(/shadow-lg hover:shadow-xl/g, "shadow-[0_10px_20px_rgba(37,211,102,0.3)] hover:shadow-[0_15px_30px_rgba(37,211,102,0.5)] border border-[#20b858]");
    fs.writeFileSync(file, content);
}
