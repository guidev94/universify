import './bootstrap';
import Alpine from 'alpinejs';
import Collapse from '@alpinejs/collapse';
Alpine.plugin(Collapse);
import * as monaco from 'monaco-editor';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
gsap.registerPlugin(ScrollTrigger);

// ===== SOUND ENGINE (Web Audio API — zero dependências) =====
const SoundEngine = {
    ctx: null,
    enabled: localStorage.getItem('jsacademy_sounds') !== 'off',

    _init() {
        if (!this.ctx) {
            this.ctx = new (window.AudioContext || window.webkitAudioContext)();
        }
    },

    _beep(freq, type = 'sine', vol = 0.1, dur = 0.1) {
        if (!this.enabled) return;
        try {
            this._init();
            const osc = this.ctx.createOscillator();
            const gain = this.ctx.createGain();
            osc.connect(gain);
            gain.connect(this.ctx.destination);
            osc.frequency.value = freq;
            osc.type = type;
            gain.gain.setValueAtTime(vol, this.ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, this.ctx.currentTime + dur);
            osc.start(this.ctx.currentTime);
            osc.stop(this.ctx.currentTime + dur);
        } catch (e) { /* silencia erros de audio */ }
    },

    // Clique em lição — tick suave
    click() { this._beep(600, 'sine', 0.06, 0.07); },

    // Código validado com sucesso — acorde ascendente
    success() {
        this._beep(523.25, 'sine', 0.12, 0.15);
        setTimeout(() => this._beep(659.25, 'sine', 0.12, 0.15), 120);
        setTimeout(() => this._beep(783.99, 'sine', 0.15, 0.25), 240);
    },

    // Erro no código — tom descendente
    error() {
        this._beep(300, 'sawtooth', 0.08, 0.08);
        setTimeout(() => this._beep(200, 'sawtooth', 0.08, 0.12), 100);
    },

    // XP ganho — coin sound
    xp() {
        this._beep(880, 'sine', 0.1, 0.08);
        setTimeout(() => this._beep(1108, 'sine', 0.08, 0.1), 80);
    },

    // Troca de aba — clique neutro
    tab() { this._beep(440, 'sine', 0.04, 0.06); },

    // Badge desbloqueada — fanfarra
    badge() {
        this._beep(523, 'sine', 0.12, 0.1);
        setTimeout(() => this._beep(659, 'sine', 0.12, 0.1), 100);
        setTimeout(() => this._beep(784, 'sine', 0.12, 0.1), 200);
        setTimeout(() => this._beep(1047, 'sine', 0.15, 0.3), 300);
    },

    toggle() {
        this.enabled = !this.enabled;
        localStorage.setItem('jsacademy_sounds', this.enabled ? 'on' : 'off');
        return this.enabled;
    }
};

window.SoundEngine = SoundEngine;

// Aplica o tema salvo ANTES de Alpine inicializar para evitar flash
const savedTheme = localStorage.getItem('jsacademy_theme');
if (savedTheme === 'light') {
    document.documentElement.classList.add('light-mode');
    document.body.classList.add('light-mode');
}

window.Alpine = Alpine;

// Store global para Auth
Alpine.store('auth', {
    guest: document.body.hasAttribute('data-guest')
});

// Store global para Tema (inicializado antes do Alpine.start())
Alpine.store('theme', { light: savedTheme === 'light' });

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
                SoundEngine.click();

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

                // Atualizar copy do botão de validação
                const validateBtnText = document.getElementById('validate-btn-text');
                if (validateBtnText) {
                    const xp = btn.getAttribute('data-lesson-xp') || '100';
                    validateBtnText.textContent = `Validar e ganhar +${xp} XP`;
                }
            });
        });

        // Tab switches — som de troca
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => SoundEngine.tab());
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

                    SoundEngine.success();

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
                                SoundEngine.xp();

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
                    SoundEngine.error();

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

    // === THEME TOGGLE ===
    const themeToggle = document.getElementById('theme-toggle');
    const iconDark = document.querySelector('.theme-icon-dark');
    const iconLight = document.querySelector('.theme-icon-light');

    const applyTheme = (isLight) => {
        document.body.classList.toggle('light-mode', isLight);
        document.documentElement.classList.toggle('light-mode', isLight); // garante seletores html.light-mode

        // Update Alpine store
        if (window.Alpine && Alpine.store('theme')) {
            Alpine.store('theme').light = isLight;
        }

        // Sync icons
        if (iconDark) iconDark.classList.toggle('hidden', isLight);
        if (iconLight) iconLight.classList.toggle('hidden', !isLight);

        // Sync Monaco theme
        const monacoTheme = isLight ? 'light' : 'dark';
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: monacoTheme } }));
    };

    const initTheme = () => {
        const saved = localStorage.getItem('jsacademy_theme') || 'dark';
        applyTheme(saved === 'light');
    };

    // Call after Alpine initializes
    document.addEventListener('alpine:initialized', initTheme);
    // Fallback: also call directly in case Alpine already initialized
    setTimeout(initTheme, 50);

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const isCurrentlyLight = document.body.classList.contains('light-mode');
            const newTheme = isCurrentlyLight ? 'dark' : 'light';

            // Save preference
            localStorage.setItem('jsacademy_theme', newTheme);

            applyTheme(!isCurrentlyLight);

            // Button press animation via GSAP
            gsap.fromTo(themeToggle,
                { scale: 0.85, rotate: -15 },
                { scale: 1, rotate: 0, duration: 0.4, ease: 'back.out(2)' }
            );
        });
    }

    // === SOUND TOGGLE ===
    const soundToggle = document.getElementById('sound-toggle');
    const soundOnIcon = document.getElementById('sound-on-icon');
    const soundOffIcon = document.getElementById('sound-off-icon');

    const updateSoundIcons = (enabled) => {
        if (soundOnIcon) soundOnIcon.classList.toggle('hidden', !enabled);
        if (soundOffIcon) soundOffIcon.classList.toggle('hidden', enabled);
    };

    updateSoundIcons(SoundEngine.enabled);

    if (soundToggle) {
        soundToggle.addEventListener('click', () => {
            const isNowEnabled = SoundEngine.toggle();
            updateSoundIcons(isNowEnabled);
            if (isNowEnabled) SoundEngine.click(); // feedback sonoro ao ligar
            if (typeof gsap !== 'undefined') {
                gsap.fromTo(soundToggle, { scale: 0.85 }, { scale: 1, duration: 0.3, ease: 'back.out(2)' });
            }
        });
    }
});
