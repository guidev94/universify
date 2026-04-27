const fs = require('fs');

let index = fs.readFileSync('index.html', 'utf8');

const sleekBtnDemonstracao = `
            <div class="mt-12 text-center relative z-20">
                <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20um%20especialista%20da%20Let%27s%20Work%21" target="_blank" class="magnetic bg-black dark:bg-white text-white dark:text-black px-10 py-5 rounded-full font-bold text-lg md:text-xl hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-[0_10px_20px_rgba(0,0,0,0.1)] dark:shadow-[0_10px_20px_rgba(255,255,255,0.1)] inline-flex items-center justify-center gap-2">
                    Solicite Uma Demonstração
                    <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
`;

const sleekBtnContato = `
            <div class="mt-16 text-center pb-8 relative z-20">
                <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20um%20especialista%20da%20Let%27s%20Work%21" target="_blank" class="magnetic bg-black dark:bg-white text-white dark:text-black px-10 py-5 rounded-full font-bold text-lg md:text-xl hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-[0_10px_20px_rgba(0,0,0,0.1)] dark:shadow-[0_10px_20px_rgba(255,255,255,0.1)] inline-flex items-center justify-center gap-2">
                    Entre em Contato
                    <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
`;

// Insert after Bento Grid: id="ecossistema"
const bentoEnd = '                </a>\n\n            </div>\n        </div>\n    </section>';
if (index.includes(bentoEnd)) {
    index = index.replace(bentoEnd, '                </a>\n\n            </div>\n' + sleekBtnDemonstracao + '\n        </div>\n    </section>');
}

// Insert before Depoimentos section
const depSection = '<!-- 6.5. DEPOIMENTOS (Marquee 3D) -->';
if (index.includes(depSection)) {
    index = index.replace(depSection, sleekBtnContato + '\n' + depSection);
}

fs.writeFileSync('index.html', index);

