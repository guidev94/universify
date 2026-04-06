import './bootstrap';
import Alpine from 'alpinejs';
import * as monaco from 'monaco-editor';
import { gsap } from 'gsap';

window.Alpine = Alpine;
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

        // Lógica do Runner (Execução de código no navegador)
        const validateBtn = document.getElementById('validate-btn');
        const consoleOutput = document.getElementById('console-output');

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
                    
                    // Efeito de Confete em caso de sucesso 🎉
                    import('canvas-confetti').then(confetti => {
                        confetti.default({
                            particleCount: 100,
                            spread: 70,
                            origin: { y: 0.6 },
                            colors: ['#F7DF1E', '#FFFFFF', '#000000']
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
                clearProps: "all" // Remove estilos inline após a animação
            }
        );
    }
});
