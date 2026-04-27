const fs = require('fs');
let index = fs.readFileSync('index.html', 'utf8');

const newSlider = `<!-- Group 1 -->
                <div class="flex gap-8 px-4">
                    <div class="solid-card p-10 rounded-[2rem] w-[450px] shrink-0 flex flex-col shadow-soft hover:border-primary transition-colors">
                        <div class="flex items-center gap-1 text-amber-500 mb-6"><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i></div>
                        <p class="text-black dark:text-white text-lg font-medium leading-relaxed mb-8 flex-1 italic">"Recomendo a todos A Lets Work, um sistema claro e objetivo. Impactou positivamente na nossa empresa, facilitando as marcações do ponto, que nos proporcionou um controle mais eficaz e efetivo."</p>
                        <div class="flex items-center gap-4 pt-6 border-t border-borderline-light dark:border-borderline-dark">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center font-bold text-primary text-lg overflow-hidden"><img src="https://www.letswork.com.br/wp-content/uploads/2025/08/cardal-1.png" class="w-full h-full object-contain p-1"></div>
                            <div><h4 class="font-bold text-black dark:text-white">Luciene</h4><p class="text-sm text-subtle-light dark:text-subtle-dark">Gestão (Cardal)</p></div>
                        </div>
                    </div>

                    <div class="solid-card p-10 rounded-[2rem] w-[450px] shrink-0 flex flex-col shadow-soft hover:border-primary transition-colors">
                        <div class="flex items-center gap-1 text-amber-500 mb-6"><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i></div>
                        <p class="text-black dark:text-white text-lg font-medium leading-relaxed mb-8 flex-1 italic">"Estou há 2 anos com a Lets Work e só tenho elogios! É um sistema simples, didático e muito eficiente. Por termos várias lojas, a flexibilidade é essencial, e ele funciona em todos os formatos, o que facilita bastante o dia a dia."</p>
                        <div class="flex items-center gap-4 pt-6 border-t border-borderline-light dark:border-borderline-dark">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center font-bold text-primary text-lg overflow-hidden"><img src="https://www.letswork.com.br/wp-content/uploads/2025/08/fom.png" class="w-full h-full object-contain p-1"></div>
                            <div><h4 class="font-bold text-black dark:text-white">Kaue</h4><p class="text-sm text-subtle-light dark:text-subtle-dark">Gestão</p></div>
                        </div>
                    </div>

                    <div class="solid-card p-10 rounded-[2rem] w-[450px] shrink-0 flex flex-col shadow-soft hover:border-primary transition-colors">
                        <div class="flex items-center gap-1 text-amber-500 mb-6"><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i></div>
                        <p class="text-black dark:text-white text-lg font-medium leading-relaxed mb-8 flex-1 italic">"E agradeça ao Jean (implantação), ele tem sido um parceiro incrível para nós! Está tendo uma implantação de um sistema na parte do faturamento aqui e a gente começa a ver diferença e querer um suporte de qualidade igual a vocês!"</p>
                        <div class="flex items-center gap-4 pt-6 border-t border-borderline-light dark:border-borderline-dark">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center font-bold text-primary text-lg overflow-hidden"><img src="https://www.letswork.com.br/wp-content/uploads/2025/09/CEAL.png" class="w-full h-full object-contain p-1"></div>
                            <div><h4 class="font-bold text-black dark:text-white">Dani da CEAL</h4><p class="text-sm text-subtle-light dark:text-subtle-dark">Gestão</p></div>
                        </div>
                    </div>

                    <div class="solid-card p-10 rounded-[2rem] w-[450px] shrink-0 flex flex-col shadow-soft hover:border-primary transition-colors">
                        <div class="flex items-center gap-1 text-amber-500 mb-6"><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i></div>
                        <p class="text-black dark:text-white text-lg font-medium leading-relaxed mb-8 flex-1 italic">"O suporte é bem atencioso, nunca deixa a empresa sem resposta, existem ótimas funcionalidades."</p>
                        <div class="flex items-center gap-4 pt-6 border-t border-borderline-light dark:border-borderline-dark">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center font-bold text-primary text-lg overflow-hidden"><img src="https://www.letswork.com.br/wp-content/uploads/2022/11/Post-Redes-Sociais-14.png" class="w-full h-full object-cover"></div>
                            <div><h4 class="font-bold text-black dark:text-white">Vanessa</h4><p class="text-sm text-subtle-light dark:text-subtle-dark">Suporte</p></div>
                        </div>
                    </div>

                    <div class="solid-card p-10 rounded-[2rem] w-[450px] shrink-0 flex flex-col shadow-soft hover:border-primary transition-colors">
                        <div class="flex items-center gap-1 text-amber-500 mb-6"><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i></div>
                        <p class="text-black dark:text-white text-lg font-medium leading-relaxed mb-8 flex-1 italic">"O que melhorou foram o relatórios que solicitamos e foram criados e isso tem facilitado nossas consultas"</p>
                        <div class="flex items-center gap-4 pt-6 border-t border-borderline-light dark:border-borderline-dark">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center font-bold text-primary text-lg overflow-hidden"><img src="https://www.letswork.com.br/wp-content/uploads/2022/11/Post-Redes-Sociais-13-1.png" class="w-full h-full object-cover"></div>
                            <div><h4 class="font-bold text-black dark:text-white">Ana</h4><p class="text-sm text-subtle-light dark:text-subtle-dark">Gestão</p></div>
                        </div>
                    </div>
                </div>

                <!-- Group 2 (Duplicate for Infinite Loop) -->
                <div class="flex gap-8 px-4" aria-hidden="true">
                    <div class="solid-card p-10 rounded-[2rem] w-[450px] shrink-0 flex flex-col shadow-soft hover:border-primary transition-colors">
                        <div class="flex items-center gap-1 text-amber-500 mb-6"><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i></div>
                        <p class="text-black dark:text-white text-lg font-medium leading-relaxed mb-8 flex-1 italic">"Recomendo a todos A Lets Work, um sistema claro e objetivo. Impactou positivamente na nossa empresa, facilitando as marcações do ponto, que nos proporcionou um controle mais eficaz e efetivo."</p>
                        <div class="flex items-center gap-4 pt-6 border-t border-borderline-light dark:border-borderline-dark">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center font-bold text-primary text-lg overflow-hidden"><img src="https://www.letswork.com.br/wp-content/uploads/2025/08/cardal-1.png" class="w-full h-full object-contain p-1"></div>
                            <div><h4 class="font-bold text-black dark:text-white">Luciene</h4><p class="text-sm text-subtle-light dark:text-subtle-dark">Gestão (Cardal)</p></div>
                        </div>
                    </div>

                    <div class="solid-card p-10 rounded-[2rem] w-[450px] shrink-0 flex flex-col shadow-soft hover:border-primary transition-colors">
                        <div class="flex items-center gap-1 text-amber-500 mb-6"><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i></div>
                        <p class="text-black dark:text-white text-lg font-medium leading-relaxed mb-8 flex-1 italic">"Estou há 2 anos com a Lets Work e só tenho elogios! É um sistema simples, didático e muito eficiente. Por termos várias lojas, a flexibilidade é essencial, e ele funciona em todos os formatos, o que facilita bastante o dia a dia."</p>
                        <div class="flex items-center gap-4 pt-6 border-t border-borderline-light dark:border-borderline-dark">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center font-bold text-primary text-lg overflow-hidden"><img src="https://www.letswork.com.br/wp-content/uploads/2025/08/fom.png" class="w-full h-full object-contain p-1"></div>
                            <div><h4 class="font-bold text-black dark:text-white">Kaue</h4><p class="text-sm text-subtle-light dark:text-subtle-dark">Gestão</p></div>
                        </div>
                    </div>

                    <div class="solid-card p-10 rounded-[2rem] w-[450px] shrink-0 flex flex-col shadow-soft hover:border-primary transition-colors">
                        <div class="flex items-center gap-1 text-amber-500 mb-6"><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i></div>
                        <p class="text-black dark:text-white text-lg font-medium leading-relaxed mb-8 flex-1 italic">"E agradeça ao Jean (implantação), ele tem sido um parceiro incrível para nós! Está tendo uma implantação de um sistema na parte do faturamento aqui e a gente começa a ver diferença e querer um suporte de qualidade igual a vocês!"</p>
                        <div class="flex items-center gap-4 pt-6 border-t border-borderline-light dark:border-borderline-dark">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center font-bold text-primary text-lg overflow-hidden"><img src="https://www.letswork.com.br/wp-content/uploads/2025/09/CEAL.png" class="w-full h-full object-contain p-1"></div>
                            <div><h4 class="font-bold text-black dark:text-white">Dani da CEAL</h4><p class="text-sm text-subtle-light dark:text-subtle-dark">Gestão</p></div>
                        </div>
                    </div>

                    <div class="solid-card p-10 rounded-[2rem] w-[450px] shrink-0 flex flex-col shadow-soft hover:border-primary transition-colors">
                        <div class="flex items-center gap-1 text-amber-500 mb-6"><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i></div>
                        <p class="text-black dark:text-white text-lg font-medium leading-relaxed mb-8 flex-1 italic">"O suporte é bem atencioso, nunca deixa a empresa sem resposta, existem ótimas funcionalidades."</p>
                        <div class="flex items-center gap-4 pt-6 border-t border-borderline-light dark:border-borderline-dark">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center font-bold text-primary text-lg overflow-hidden"><img src="https://www.letswork.com.br/wp-content/uploads/2022/11/Post-Redes-Sociais-14.png" class="w-full h-full object-cover"></div>
                            <div><h4 class="font-bold text-black dark:text-white">Vanessa</h4><p class="text-sm text-subtle-light dark:text-subtle-dark">Suporte</p></div>
                        </div>
                    </div>

                    <div class="solid-card p-10 rounded-[2rem] w-[450px] shrink-0 flex flex-col shadow-soft hover:border-primary transition-colors">
                        <div class="flex items-center gap-1 text-amber-500 mb-6"><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i><i data-lucide="star" class="w-4 h-4 fill-current"></i></div>
                        <p class="text-black dark:text-white text-lg font-medium leading-relaxed mb-8 flex-1 italic">"O que melhorou foram o relatórios que solicitamos e foram criados e isso tem facilitado nossas consultas"</p>
                        <div class="flex items-center gap-4 pt-6 border-t border-borderline-light dark:border-borderline-dark">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center font-bold text-primary text-lg overflow-hidden"><img src="https://www.letswork.com.br/wp-content/uploads/2022/11/Post-Redes-Sociais-13-1.png" class="w-full h-full object-cover"></div>
                            <div><h4 class="font-bold text-black dark:text-white">Ana</h4><p class="text-sm text-subtle-light dark:text-subtle-dark">Gestão</p></div>
                        </div>
                    </div>
                </div>`;

const startIdx = index.indexOf('<!-- Group 1 -->');
const endIdx = index.indexOf('<!-- Start Stats/Banner Section -->');
const subEnd = index.substring(0, endIdx).lastIndexOf('</div>\n        </div>\n    </section>');

if (startIdx !== -1 && subEnd !== -1) {
    index = index.substring(0, startIdx) + newSlider + '\n            ' + index.substring(subEnd);
    fs.writeFileSync('index.html', index);
}
