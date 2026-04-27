const fs = require('fs');

let index = fs.readFileSync('index.html', 'utf8');

// 1. Re-add the sleek buttons INSIDE the sections so they don't create new lined sections
const sleekBtnDemonstracao = `
            <div class="mt-12 text-center">
                <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20um%20especialista%20da%20Let%27s%20Work%21" target="_blank" class="magnetic bg-black dark:bg-white text-white dark:text-black px-10 py-5 rounded-full font-bold text-lg md:text-xl hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-[0_10px_20px_rgba(0,0,0,0.1)] dark:shadow-[0_10px_20px_rgba(255,255,255,0.1)] inline-flex items-center justify-center gap-2">
                    Solicite Uma Demonstração
                    <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
`;

const sleekBtnContato = `
            <div class="mt-12 text-center pb-8">
                <a href="https://api.whatsapp.com/send?phone=5511934670924&text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20um%20especialista%20da%20Let%27s%20Work%21" target="_blank" class="magnetic bg-black dark:bg-white text-white dark:text-black px-10 py-5 rounded-full font-bold text-lg md:text-xl hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-[0_10px_20px_rgba(0,0,0,0.1)] dark:shadow-[0_10px_20px_rgba(255,255,255,0.1)] inline-flex items-center justify-center gap-2">
                    Entre em Contato
                    <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
`;

// Add Demonstração at the end of Soluções section (before closing div and section)
// The Soluções section ends right before <!-- 3. VANTAGENS (Grid Bento) -->
index = index.replace('            </div>\n        </div>\n    </section>\n\n    <!-- 3. VANTAGENS (Grid Bento) -->', 
                      sleekBtnDemonstracao + '            </div>\n        </div>\n    </section>\n\n    <!-- 3. VANTAGENS (Grid Bento) -->');

// Add Contato at the end of Compliance section (before closing div and section)
// The Compliance section ends right before <!-- 6.5. DEPOIMENTOS (Marquee 3D) -->
index = index.replace('                </div>\n            </div>\n\n        </div>\n    </section>\n\n\n    \n\n<!-- 6.5. DEPOIMENTOS (Marquee 3D) -->',
                      '                </div>\n            </div>\n' + sleekBtnContato + '        </div>\n    </section>\n\n<!-- 6.5. DEPOIMENTOS (Marquee 3D) -->');

// Also remove border-b from compliance to make it perfectly fluid with depoimentos
index = index.replace('<section id="compliance" class="py-24 lg:py-32 border-b border-borderline-light dark:border-borderline-dark relative">', 
                      '<section id="compliance" class="py-24 lg:py-32 relative">');

// Also remove border-t from depoimentos
index = index.replace('<section class="pt-20 lg:pt-24 pb-20 lg:pb-24 border-t border-borderline-light dark:border-borderline-dark overflow-hidden relative">',
                      '<section class="pt-10 lg:pt-12 pb-20 lg:pb-24 overflow-hidden relative">');

// 2. Change the Scan Face Icon to a much more detailed custom SVG
const customFaceID = `<svg xmlns="http://www.0.0.org/2000/svg" class="w-16 h-16 text-primary relative z-10 drop-shadow-md group-hover:scale-110 transition-transform duration-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
  <path d="M3 7V5a2 2 0 0 1 2-2h2"></path>
  <path d="M17 3h2a2 2 0 0 1 2 2v2"></path>
  <path d="M21 17v2a2 2 0 0 1-2 2h-2"></path>
  <path d="M7 21H5a2 2 0 0 1-2-2v-2"></path>
  <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
  <path d="M9 9h.01"></path>
  <path d="M15 9h.01"></path>
</svg>`;

const customFaceIDSmall = `<svg xmlns="http://www.0.0.org/2000/svg" class="w-12 h-12 text-white relative z-10 drop-shadow-md group-hover:scale-110 transition-transform duration-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
  <path d="M3 7V5a2 2 0 0 1 2-2h2"></path>
  <path d="M17 3h2a2 2 0 0 1 2 2v2"></path>
  <path d="M21 17v2a2 2 0 0 1-2 2h-2"></path>
  <path d="M7 21H5a2 2 0 0 1-2-2v-2"></path>
  <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
  <path d="M9 9h.01"></path>
  <path d="M15 9h.01"></path>
</svg>`;

index = index.replace(/<i data-lucide="scan-face" class="w-16 h-16 text-primary relative z-10"><\/i>/g, customFaceID);
index = index.replace(/<i data-lucide="scan-face" class="w-10 h-10 text-white group-hover:scale-110 transition-transform duration-500 relative z-10"><\/i>/g, customFaceIDSmall);

// Also replace in the LP
fs.writeFileSync('index.html', index);

let como = fs.readFileSync('como-funciona.html', 'utf8');
como = como.replace(/<i data-lucide="scan-face"[^>]*><\/i>/g, customFaceIDSmall);
fs.writeFileSync('como-funciona.html', como);

