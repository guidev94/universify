import './bootstrap';
import Alpine from 'alpinejs';
import * as monaco from 'monaco-editor';
import { gsap } from 'gsap';

window.Alpine = Alpine;

// Store global para Auth
Alpine.store('auth', {
    guest: document.body.hasAttribute('data-guest')
});

Alpine.start();

/**
 * Inicialização dos componentes da plataforma JS Academy.
 */
document.addEventListener('DOMContentLoaded', () => {
    
    // Configuração do Editor Monaco (Sandbox de Código)
    const editorContainer = document.getElementById('monaco-editor');
    if (editorContainer) {
        const editor = monaco.editor.create(editorContainer, {
            value: '// Digite seu código JavaScript aqui...\n\nfunction saudacao() {\n    console.log("Bem-vindo à JS Academy!");\n}\n\nsaudacao();',
            language: 'javascript',
            theme: document.body.classList.contains('light-mode') ? 'vs' : 'vs-dark',
            automaticLayout: true,
            fontSize: 14,
            fontFamily: 'JetBrains Mono, Fira Code, monospace',
            minimap: { enabled: false },
            lineNumbers: 'on',
            scrollBeyondLastLine: false,
            roundedSelection: true,
            padding: { top: 20 },
            bracketPairColorization: { enabled: true }
        });

        // Sincronização do tema do editor com o site
        window.addEventListener('theme-changed', (e) => {
            monaco.editor.setTheme(e.detail.theme === 'light' ? 'vs' : 'vs-dark');
        });

        // Lógica de seleção de lições
        const lessonButtons = document.querySelectorAll('.lesson-btn');
        const lessonTitleDisplay = document.getElementById('lesson-title-display');
        const lessonDescDisplay = document.getElementById('lesson-desc-display');
        const lessonTipsDisplay = document.getElementById('lesson-tips-display');
        const lessonExamplesDisplay = document.getElementById('lesson-examples-display');
        const lessonCaseDisplay = document.getElementById('lesson-case-display');
        const lessonDocsDisplay = document.getElementById('lesson-docs-display');
        const lessonTaskDisplay = document.getElementById('lesson-task-display');
        const taskContainer = document.getElementById('task-container');
        const consoleOutput = document.getElementById('console-output');
        
        let currentLessonId = null;

        lessonButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-lesson-id');
                const title = btn.getAttribute('data-lesson-title');
                const content = btn.getAttribute('data-lesson-content');
                const starter = btn.getAttribute('data-lesson-starter');
                const tips = btn.getAttribute('data-lesson-tips');
                const examples = btn.getAttribute('data-lesson-examples');
                const realCase = btn.getAttribute('data-lesson-case');
                const docs = btn.getAttribute('data-lesson-docs');
                const task = btn.getAttribute('data-lesson-task');

                currentLessonId = id;
                
                // Função auxiliar para tratar quebras de linha vindas do data-attribute
                const formatContent = (str) => {
                    if (!str) return '';
                    return str.replace(/\\n/g, '\n');
                };

                // Atualiza UI Educacional
                if (lessonTitleDisplay) lessonTitleDisplay.textContent = title;
                
                // O conteúdo (descrição) pode ter markdown básico (pelo seeder), 
                // para simplicidade vamos apenas converter \n em <br> se não estiver usando um parser
                if (lessonDescDisplay) {
                    lessonDescDisplay.innerHTML = formatContent(content).replace(/\n/g, '<br>');
                }

                if (lessonTipsDisplay) lessonTipsDisplay.textContent = formatContent(tips) || 'Sem dicas para esta lição.';
                if (lessonExamplesDisplay) lessonExamplesDisplay.textContent = formatContent(examples) || '// Sem exemplos.';
                if (lessonCaseDisplay) lessonCaseDisplay.textContent = formatContent(realCase) || 'Uso geral em desenvolvimento.';
                
                if (lessonTaskDisplay) {
                    lessonTaskDisplay.textContent = formatContent(task);
                    taskContainer.classList.remove('hidden');
                }

                if (lessonDocsDisplay) {
                    if (docs) {
                        lessonDocsDisplay.href = docs;
                        lessonDocsDisplay.parentElement.classList.remove('hidden');
                    } else {
                        lessonDocsDisplay.parentElement.classList.add('hidden');
                    }
                }
                
                // Reset tab to Explanation
                document.querySelector('[onclick="switchTab(\'tab-desc\')"]').click();

                // Carrega no editor
                editor.setValue(formatContent(starter) || '// Comece a codar aqui...');

                // Configurar compartilhamento
                window.currentLessonData = { title, id };
                
                // Scroll suave para o editor
                const editorSection = document.getElementById('editor-section');
                if (editorSection) {
                    editorSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                // Limpa console
                if (consoleOutput) consoleOutput.innerHTML = '> Lendo enunciados...';
            });
        });

        // Lógica do Runner (Execução de código no navegador)
        const validateBtn = document.getElementById('validate-btn');

        if (validateBtn && consoleOutput) {
            validateBtn.addEventListener('click', () => {
                const code = editor.getValue();
                consoleOutput.innerHTML = '';
                
                // Redireciona o console.log para a nossa div de saída
                const originalLog = console.log;
                console.log = (...args) => {
                    const msg = args.map(arg => 
                        typeof arg === 'object' ? JSON.stringify(arg) : arg
                    ).join(' ');
                    const line = document.createElement('div');
                    line.className = 'py-1 border-b border-white/5 text-cyan-400 font-mono';
                    line.textContent = `> ${msg}`;
                    consoleOutput.appendChild(line);
                };

                try {
                    // Executa o código usando eval (seguro em contexto de sandbox local)
                    eval(code);
                    
                    const successMsg = document.createElement('div');
                    successMsg.className = 'py-2 text-green-400 font-bold font-mono';
                    successMsg.textContent = '✓ Código executado com sucesso!';
                    consoleOutput.appendChild(successMsg);

                    // Se houver uma lição selecionada, marcar como concluída no servidor
                    if (currentLessonId) {
                        fetch(`/progress/complete-lesson/${currentLessonId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                const xpMsg = document.createElement('div');
                                xpMsg.className = 'text-[10px] text-yellow-500 font-bold uppercase mt-1';
                                xpMsg.textContent = '+100 XP GANHADOS!';
                                consoleOutput.appendChild(xpMsg);
                                
                                // Abre o modal de compartilhamento após sucesso
                                setTimeout(() => {
                                    if (window.showShareModal) {
                                        window.showShareModal(100, window.currentLessonData.title);
                                    }
                                }, 1000);
                            }
                        });
                    }
                    
                    // Efeito de Confete em caso de sucesso 🎉
                    import('canvas-confetti').then(confetti => {
                        confetti.default({
                            particleCount: 150,
                            spread: 80,
                            origin: { y: 0.7 },
                            colors: ['#F7DF1E', '#FFFFFF', '#000000', '#EC4899']
                        });
                    });
                } catch (err) {
                    // Trata erros de sintaxe ou execução
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'py-2 text-red-400 font-mono';
                    errorMsg.textContent = `✕ Erro: ${err.message}`;
                    consoleOutput.appendChild(errorMsg);
                }

                // Restaura o comportamento original do console.log
                console.log = originalLog;
            });
        }
    }

    // Animações GSAP (Entrada suave dos cards)
    const cards = document.querySelectorAll('.bento-card');
    if (cards.length > 0) {
        gsap.fromTo(cards, 
            { opacity: 0, y: 30 }, 
            { 
                opacity: 1, 
                y: 0, 
                stagger: 0.1, 
                duration: 0.8, 
                ease: "power3.out",
                clearProps: "all"
            }
        );
    }
});
