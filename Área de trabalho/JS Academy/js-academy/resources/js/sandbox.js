import * as monaco from 'monaco-editor';
import axios from 'axios';
import confetti from 'canvas-confetti';

document.addEventListener('DOMContentLoaded', () => {
    const editorContainer = document.getElementById('monaco-editor');
    
    if (editorContainer) {
        // Initialize Monaco Editor with JavaScript Autocomplete
        const editor = monaco.editor.create(editorContainer, {
            value: [
                '// UNIDADE 6: Node & Testing',
                '// PROJETO REAL: Validador de Dados',
                '// Regra: Deve retornar true se o email for válido.',
                '',
                'function isEmailValid(email) {',
                '    const regex = /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/;',
                '    return regex.test(email);',
                '}',
                '',
                'console.log(isEmailValid("test@test.com"));'
            ].join('\n'),
            language: 'javascript',
            theme: 'vs-dark',
            automaticLayout: true,
            minimap: { enabled: false },
            fontSize: 14,
            fontFamily: '"JetBrains Mono", monospace',
            roundedSelection: true,
            scrollBeyondLastLine: false,
            padding: { top: 20 },
            scrollbar: {
                vertical: 'hidden'
            },
            // Desabilitar Menu de Contexto e Comandos de Colar
            contextmenu: false,
        });

        window.editor = editor;

        // Impedir Copy-Paste no Editor
        editorContainer.addEventListener('paste', (e) => {
            e.preventDefault();
            showNotification('Ei! No JS Academy o aprendizado vem da prática. Digite seu código para fixar o conhecimento! 🧠');
        }, true);

        // Bloquear atalhos de teclado de colar
        editor.onKeyDown((e) => {
            if (e.keyCode === monaco.KeyCode.KeyV && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                showNotification('Ei! No JS Academy o aprendizado vem da prática. Digite seu código para fixar o conhecimento! 🧠');
            }
        });

        // Connect Validate Button
        const validateBtn = document.getElementById('validate-btn');
        if (validateBtn) {
            validateBtn.addEventListener('click', () => {
                const code = editor.getValue();
                validateCode(code);
            });
        }
    }

    let consecutiveCorrect = 0;

    async function validateCode(code) {
        const consoleOutput = document.getElementById('console-output');
        consoleOutput.innerHTML = '<div class="text-zinc-500 italic animate-pulse">Running Test Cases...</div>';

        // Secure evaluation mock
        const tests = [
            { name: 'isEmailValid("test@test.com") === true', check: (code) => {
                const isEmailValid = new Function(code + '; return isEmailValid;')();
                return isEmailValid("test@test.com") === true;
            }},
            { name: 'isEmailValid("invalid") === false', check: (code) => {
                const isEmailValid = new Function(code + '; return isEmailValid;')();
                return isEmailValid("invalid") === false;
            }}
        ];

        setTimeout(async () => {
            consoleOutput.innerHTML = '';
            let allPassed = true;

            tests.forEach(test => {
                let passed = false;
                try {
                    passed = test.check(code);
                } catch (e) {
                    passed = false;
                }

                if (!passed) allPassed = false;

                const testEl = document.createElement('div');
                testEl.className = 'flex justify-between items-center py-1 border-b border-zinc-900/50';
                testEl.innerHTML = `
                    <span class="text-zinc-400 font-mono text-[10px]">${test.name}</span>
                    <span class="${passed ? 'text-green-500' : 'text-red-500'} font-bold text-[10px]">
                        ${passed ? '✓ PASSED' : '✗ FAILED'}
                    </span>
                `;
                consoleOutput.appendChild(testEl);
            });

            if (allPassed) {
                consecutiveCorrect++;
                checkGenerativeUI();
                
                // Confetti & Motivational Message (Intense 2026 Effect)
                const end = Date.now() + (2 * 1000);
                const colors = ['#ec4899', '#06b6d4', '#eab308'];

                (function frame() {
                    confetti({
                        particleCount: 3,
                        angle: 60,
                        spread: 55,
                        origin: { x: 0 },
                        colors: colors
                    });
                    confetti({
                        particleCount: 3,
                        angle: 120,
                        spread: 55,
                        origin: { x: 1 },
                        colors: colors
                    });

                    if (Date.now() < end) {
                        requestAnimationFrame(frame);
                    }
                }());

                const motivationalMessages = [
                    "Incrível! Você acaba de ficar 1% melhor que ontem. Continue assim! 🔥",
                    "Uau! Sua lógica está evoluindo a passos largos! 🚀",
                    "Genial! Próximo nível desbloqueado com sucesso. 💎",
                    "Excelente! Cada linha de código te aproxima da maestria. 👑"
                ];
                const randomMsg = motivationalMessages[Math.floor(Math.random() * motivationalMessages.length)];
                
                const msgEl = document.createElement('div');
                msgEl.className = 'mt-3 text-center text-green-400 font-bold text-xs animate-bounce';
                msgEl.textContent = randomMsg;
                consoleOutput.appendChild(msgEl);

                await completeLesson();
            } else {
                consecutiveCorrect = 0; // Reset counter
                showNotification('Quase lá! Errar faz parte do processo de todo grande desenvolvedor. Vamos tentar de novo? 🛠️');
            }
        }, 1200);
    }

    function checkGenerativeUI() {
        if (consecutiveCorrect >= 10) {
            document.documentElement.classList.add('cyber-gold-theme');
            showNotification('👑 MESTRE DO JS: Tema Cyber-Gold Desbloqueado!');
        }
    }

    async function completeLesson() {
        try {
            // Send request to complete lesson (Mocking lesson ID 12 which corresponds to Unit 6 last lesson)
            const response = await axios.post('/progress/complete-lesson/12');
            if (response.data.success) {
                showNotification('🎉 Unidade Completada! XP +150');
                if (response.data.badges && response.data.badges.length > 0) {
                    showNotification('🏆 Nova Badge Conquistada!');
                }
            }
        } catch (err) {
            console.error('Error updating progress:', err);
            // Fallback for demo
            showNotification('🎉 Desafio concluído! (Modo Preview)');
        }
    }

    function showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'fixed bottom-8 left-1/2 -translate-x-1/2 bg-white text-black px-8 py-4 rounded-3xl font-black shadow-[0_20px_50px_rgba(0,0,0,0.5)] z-[100] border-4 border-zinc-200';
        notification.innerHTML = message;
        document.body.appendChild(notification);
        
        if (window.gsap) {
            window.gsap.from(notification, { y: 100, opacity: 0, duration: 0.5, ease: 'back.out' });
            setTimeout(() => {
                window.gsap.to(notification, { y: 100, opacity: 0, duration: 0.5, onComplete: () => notification.remove() });
            }, 4000);
        } else {
            setTimeout(() => notification.remove(), 4000);
        }
    }
    
    // Streak Freeze Handler
    const freezeBtn = document.getElementById('streak-freeze-btn');
    if (freezeBtn) {
        freezeBtn.addEventListener('click', async () => {
            if (freezeBtn.getAttribute('disabled')) return;
            
            try {
                const resp = await axios.post('/progress/freeze-streak');
                if (resp.data.success) {
                    freezeBtn.innerHTML = '🧊 Frozen';
                    freezeBtn.classList.replace('bg-zinc-800', 'bg-cyan-500');
                    freezeBtn.classList.replace('text-zinc-300', 'text-white');
                    freezeBtn.setAttribute('disabled', 'true');
                    showNotification('Streak Protegida! 🧊');
                }
            } catch (err) {
                showNotification('Diamantes insuficientes! 💎');
            }
        });
    }
    
    // Skip Button Handler
    document.querySelectorAll('button').forEach(btn => {
        if (btn.textContent === 'Skip') {
            btn.addEventListener('click', () => {
                showNotification('Unidade pulada. (Lógica e Introdução)');
                btn.closest('.relative.group').classList.add('opacity-40');
                btn.remove();
            });
        }
    });
});
