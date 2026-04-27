const fs = require('fs');

const pagesData = {
  'gerenciamento-de-projetos.html': {
    highlightTitle: 'Visão 360º da Produtividade',
    highlightTag: 'Controle Total',
    highlightDesc: 'Tenha controle absoluto sobre o tempo gasto em cada tarefa, identifique gargalos na sua operação e saiba exatamente a rentabilidade de cada projeto e cliente.',
    highlightIcon: 'pie-chart',
    steps: ['Apropriação de horas por tarefa.', 'Relatórios de rentabilidade automática.'],
    faq: [
      { q: 'O sistema possui integração com outras ferramentas?', a: 'Sim, nosso sistema permite exportação de dados e possui APIs para integração com os principais ERPs do mercado.' },
      { q: 'Posso criar múltiplos projetos?', a: 'Não há limite de projetos. Você pode organizar sua agência ou consultoria em quantos projetos e sub-projetos forem necessários.' }
    ]
  },
  'gerenciamento-de-escalas.html': {
    highlightTitle: 'Adeus às Planilhas de Turnos',
    highlightTag: 'Automação',
    highlightDesc: 'Automatize a criação de escalas complexas (12x36, 5x2, 6x1) e evite furos na sua operação. O sistema alerta automaticamente sobre conflitos de horários e sobreposição de turnos.',
    highlightIcon: 'calendar-clock',
    steps: ['Geração automática de turnos.', 'Alertas de conflito trabalhista em tempo real.'],
    faq: [
      { q: 'Os funcionários são notificados de mudanças na escala?', a: 'Sim! Qualquer alteração na escala dispara uma notificação push imediatamente para o celular do colaborador.' },
      { q: 'Funciona para hospitais e varejo?', a: 'Perfeitamente. O sistema foi desenhado para lidar com a alta complexidade de turnos de hospitais, fábricas e grandes redes de varejo.' }
    ]
  },
  'rastreamento.html': {
    highlightTitle: 'Gestão de Equipes em Campo',
    highlightTag: 'Geolocalização',
    highlightDesc: 'Saiba exatamente onde seus colaboradores externos estão. Acompanhe a rota, tempo de permanência em cada cliente e reduza custos operacionais com deslocamentos desnecessários.',
    highlightIcon: 'map',
    steps: ['Cercas virtuais precisas.', 'Relatórios automáticos de visita ao cliente.'],
    faq: [
      { q: 'O rastreamento consome muita bateria do celular?', a: 'Nosso app foi otimizado para coletar pontos de GPS de forma inteligente, minimizando o impacto na bateria do dispositivo do colaborador.' },
      { q: 'Consigo ver o histórico de rotas?', a: 'Sim, o painel do gestor mantém o histórico completo de rotas e paradas realizadas em dias anteriores.' }
    ]
  },
  'gestao-de-documentos.html': {
    highlightTitle: 'Seu RH 100% Paperless',
    highlightTag: 'Segurança Jurídica',
    highlightDesc: 'Elimine o papel. Armazene contratos, atestados médicos e entregue holerites diretamente no aplicativo do colaborador, com assinatura digital criptografada e validade jurídica.',
    highlightIcon: 'file-lock-2',
    steps: ['Envio em lote de holerites.', 'Assinatura eletrônica em conformidade com a LGPD.'],
    faq: [
      { q: 'A assinatura digital tem validade jurídica?', a: 'Sim, nossas assinaturas possuem carimbo de tempo e rastreabilidade que garantem total validade perante a lei.' },
      { q: 'Os colaboradores podem enviar atestados pelo app?', a: 'Sim! O colaborador tira uma foto do atestado e o documento vai direto para a fila de aprovação do RH.' }
    ]
  },
  'comunicacao-de-equipe.html': {
    highlightTitle: 'Comunicação Centralizada e Segura',
    highlightTag: 'Engajamento',
    highlightDesc: 'Separe a vida pessoal da profissional. Troque o WhatsApp por um chat corporativo onde a empresa tem total controle sobre os dados, garantindo privacidade e foco.',
    highlightIcon: 'messages-square',
    steps: ['Mural de comunicados oficiais.', 'Canais privados e grupos por departamento.'],
    faq: [
      { q: 'O histórico de mensagens é salvo?', a: 'Todo o histórico corporativo é salvo e pode ser auditado pela administração, garantindo compliance.' },
      { q: 'Posso enviar arquivos e fotos?', a: 'Sim, o chat suporta compartilhamento seguro de documentos, imagens e arquivos de áudio.' }
    ]
  },
  'gerenciamento-de-ferias.html': {
    highlightTitle: 'Controle Exato do Período Aquisitivo',
    highlightTag: 'Prevenção',
    highlightDesc: 'Nunca mais pague férias em dobro. Nosso painel mostra visualmente o calendário de férias de toda a empresa, calculando automaticamente vencimentos e proporções.',
    highlightIcon: 'palmtree',
    steps: ['Avisos automáticos de vencimento.', 'Aprovação de solicitações em 1 clique.'],
    faq: [
      { q: 'O colaborador pode solicitar férias pelo app?', a: 'Sim, ele pode sugerir o período desejado e o gestor aprova ou reprova direto pelo painel.' },
      { q: 'O sistema calcula o abono pecuniário?', a: 'Sim, o cálculo contempla regras de abono (vender férias) de forma automática.' }
    ]
  },
  'gerenciamento-de-vagas.html': {
    highlightTitle: 'Portal de Carreiras Profissional',
    highlightTag: 'Recrutamento',
    highlightDesc: 'Crie uma página de vagas com a cara da sua empresa. Receba currículos padronizados, faça triagem automática e gerencie candidatos em um pipeline visual (ATS).',
    highlightIcon: 'briefcase',
    steps: ['Página de vagas (Employer Branding).', 'Pipeline Kanban de candidatos.'],
    faq: [
      { q: 'Os candidatos precisam pagar para se candidatar?', a: 'Não, o portal é 100% gratuito para os candidatos.' },
      { q: 'Posso personalizar as etapas do processo seletivo?', a: 'Sim, você pode criar quantas etapas precisar (ex: Entrevista, Teste Técnico, Proposta) no seu funil.' }
    ]
  }
};

// Regex for the WhatsApp integration section and FAQ section
const wsSectionRegex = /<!-- Section: Integração WhatsApp -->[\s\S]*?<!-- Section: FAQ -->/;
const faqSectionRegex = /<!-- Section: FAQ -->[\s\S]*?(?=<\/main>)/;

for (const [file, data] of Object.entries(pagesData)) {
  if (fs.existsSync(file)) {
    let content = fs.readFileSync(file, 'utf8');

    // Build new Highlight Section
    const newHighlight = `<!-- Section: Destaque Específico -->
            <section class="solid-card p-8 md:p-12 rounded-[2rem] bg-surface-light dark:bg-surface-dark border-primary/20 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-10">
                    <i data-lucide="${data.highlightIcon}" class="w-48 h-48 text-primary"></i>
                </div>
                <div class="relative z-10 grid md:grid-cols-2 gap-10 items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary font-bold text-xs mb-4 uppercase tracking-wider">
                            ${data.highlightTag}
                        </div>
                        <h2 class="text-2xl md:text-3xl font-display font-bold text-black dark:text-white mb-4">${data.highlightTitle}</h2>
                        <p class="text-subtle-light dark:text-subtle-dark text-sm md:text-base leading-relaxed mb-6">
                            ${data.highlightDesc}
                        </p>
                        <ol class="space-y-4">
                            <li class="flex gap-3">
                                <span class="w-6 h-6 rounded-full bg-primary/20 text-primary flex items-center justify-center text-xs font-bold shrink-0 mt-0.5"><i data-lucide="check" class="w-3 h-3"></i></span>
                                <span class="text-sm text-black dark:text-white font-medium">${data.steps[0]}</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="w-6 h-6 rounded-full bg-primary/20 text-primary flex items-center justify-center text-xs font-bold shrink-0 mt-0.5"><i data-lucide="check" class="w-3 h-3"></i></span>
                                <span class="text-sm text-black dark:text-white font-medium">${data.steps[1]}</span>
                            </li>
                        </ol>
                    </div>
                </div>
            </section>

            <!-- Section: FAQ -->`;

    content = content.replace(wsSectionRegex, newHighlight);

    // Build new FAQ Section
    let newFaq = `<!-- Section: FAQ -->
            <section>
                <h2 class="text-2xl font-display font-bold text-black dark:text-white mb-8 border-b border-borderline-light dark:border-borderline-dark pb-4">Dúvidas Frequentes</h2>
                <div class="space-y-4">
                    <details class="group solid-card p-6 rounded-2xl cursor-pointer">
                        <summary class="flex justify-between items-center font-bold text-black dark:text-white list-none">
                            ${data.faq[0].q}
                            <span class="transition group-open:rotate-180">
                                <i data-lucide="chevron-down" class="w-5 h-5"></i>
                            </span>
                        </summary>
                        <div class="text-sm text-subtle-light dark:text-subtle-dark mt-4 leading-relaxed">
                            ${data.faq[0].a}
                        </div>
                    </details>

                    <details class="group solid-card p-6 rounded-2xl cursor-pointer">
                        <summary class="flex justify-between items-center font-bold text-black dark:text-white list-none">
                            ${data.faq[1].q}
                            <span class="transition group-open:rotate-180">
                                <i data-lucide="chevron-down" class="w-5 h-5"></i>
                            </span>
                        </summary>
                        <div class="text-sm text-subtle-light dark:text-subtle-dark mt-4 leading-relaxed">
                            ${data.faq[1].a}
                        </div>
                    </details>
                </div>
            </section>\n`;

    content = content.replace(faqSectionRegex, newFaq);
    fs.writeFileSync(file, content);
    console.log(`Updated content for ${file}`);
  }
}
