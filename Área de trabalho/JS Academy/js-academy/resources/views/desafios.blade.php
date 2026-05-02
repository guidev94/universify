@extends('layouts.app')

@section('title', 'Desafios de Lógica JavaScript — JS Academy')

@section('head')
<meta name="description" content="12 desafios técnicos de JavaScript: FizzBuzz, Two Sum, Debounce, Deep Clone e mais. Teste sua lógica e ganhe XP.">
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12" x-data="arenaHandler()">
    <header class="mb-16 text-center lg:text-left page-header">
        <h1 class="heading-huge text-white">
            Arena de <span class="bg-gradient-to-r from-accent-cyan to-blue-500 bg-clip-text text-transparent italic">Lógica</span> 🥊
        </h1>
        <p class="mt-4 text-xl text-zinc-500 max-w-2xl mx-auto lg:mx-0">
            Encare desafios de algoritmos e prove sua maestria. Resolva rápido para ganhar bônus de XP! 🚫 Proibido colar.
        </p>
        <p class="text-zinc-500 text-base max-w-lg mx-auto lg:mx-0 mt-4 leading-relaxed">
            Desafios reais usados em entrevistas técnicas. Resolva no editor, ganhe XP e prove que sabe programar de verdade.
        </p>
        <div class="flex items-center justify-center lg:justify-start gap-6 mt-6 text-xs font-mono text-zinc-600">
            <span>{{ \App\Models\LogicChallenge::count() }} desafios disponíveis</span>
            <span>·</span>
            <span>Fácil · Médio · Difícil</span>
            <span>·</span>
            <span>XP + Diamantes ao completar</span>
        </div>
    </header>

    <!-- Grid de Cards de Desafios -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" x-show="!currentChallenge" x-transition>
        @foreach($challenges as $challenge)
        <div class="bento-card bento-card-hover challenge-card p-8 flex flex-col justify-between min-h-[350px]">
            <div>
                <div class="flex justify-between items-center mb-6">
                    <span class="text-[10px] font-mono text-accent-cyan uppercase font-black tracking-[0.2em]">{{ $challenge->category }}</span>
                    <span class="difficulty-badge px-3 py-1 rounded-full bg-zinc-800 text-[9px] font-black text-zinc-400 uppercase tracking-tighter border border-white/5">{{ $challenge->difficulty }}</span>
                </div>
                <h3 class="text-3xl font-black text-white mb-4 group-hover:text-js-yellow transition-colors tracking-tighter">{{ $challenge->title }}</h3>
                <p class="text-zinc-500 text-sm leading-relaxed mb-8 line-clamp-3">{{ $challenge->description }}</p>
            </div>
            
            <div class="flex flex-col gap-6">
                <div class="flex items-center gap-6 text-[11px] font-black uppercase tracking-widest border-t border-white/5 pt-6">
                    <span class="flex items-center gap-2"><span class="text-lg">⚡</span> +{{ $challenge->xp_reward }} XP</span>
                    <span class="flex items-center gap-2"><span class="text-lg">💎</span> {{ $challenge->diamond_reward }}</span>
                </div>
                <button @click="selectChallenge({{ $challenge }}, $event)" class="btn-primary w-full">
                    Iniciar Desafio
                </button>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Arena do Desafio (Aberta após selecionar) -->
    <div id="arena-ui" class="max-w-6xl mx-auto space-y-6" x-show="currentChallenge" x-cloak x-transition>
        <button @click="currentChallenge = null; clearInterval(interval)" class="flex items-center gap-2 text-zinc-500 hover:text-white transition-colors text-xs font-black uppercase tracking-widest mb-6 group">
            <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Voltar para a Lista
        </button>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Instruções -->
            <div class="lg:col-span-5 space-y-6">
                <div class="bento-card p-8 border-accent-cyan/20 shadow-2xl">
                    <h2 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2" x-text="currentChallenge?.title"></h2>
                    <span class="text-[10px] font-mono text-accent-cyan uppercase font-black tracking-widest" x-text="currentChallenge?.category"></span>
                    
                    <div class="mt-8 p-6 bg-zinc-950/50 rounded-bento-sm border border-white/5 border-l-4 border-l-accent-cyan">
                        <h4 class="text-[10px] font-black text-accent-cyan uppercase tracking-[0.2em] mb-3">Sua Missão:</h4>
                        <p class="text-white text-sm font-bold leading-relaxed italic" x-text="currentChallenge?.task"></p>
                    </div>

                    <!-- Cronômetro -->
                    <div class="mt-8 flex items-center justify-between p-5 bg-zinc-950 rounded-bento-sm border border-white/5 shadow-inner">
                        <span class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Tempo de Arena</span>
                        <div class="font-mono text-3xl text-accent-cyan font-black text-glow" x-text="formatTime(timer)">00:00</div>
                    </div>
                </div>

                <div class="bento-card p-6 bg-zinc-950 border-zinc-900 font-mono text-xs shadow-inner min-h-[180px]">
                    <div class="flex items-center gap-2 mb-4 border-b border-white/5 pb-2">
                        <span class="w-2 h-2 rounded-full bg-accent-cyan animate-pulse shadow-[0_0_10px_rgba(34,211,238,0.5)]"></span>
                        <span class="uppercase text-[9px] text-zinc-500 font-black tracking-widest">Saída do Console</span>
                    </div>
                    <div id="arena-console" class="text-zinc-400 space-y-1 custom-scrollbar overflow-y-auto max-h-[100px]">> Aguardando execução...</div>
                </div>
            </div>

            <!-- Editor -->
            <div class="lg:col-span-7 flex flex-col gap-6">
                <div class="bento-card bg-zinc-950 border-zinc-900 h-[500px] overflow-hidden flex flex-col shadow-2xl relative">
                    <div class="flex items-center justify-between p-4 bg-zinc-900/50 border-b border-zinc-800">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-500/20 border border-red-500/50"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500/20 border border-yellow-500/50"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500/20 border border-green-500/50"></div>
                            <span class="ml-4 text-[10px] font-mono text-zinc-500 uppercase tracking-widest font-bold">arena_runtime.js</span>
                        </div>
                    </div>
                    <div id="arena-editor" class="flex-grow w-full"></div>
                </div>

                <button @click="validateChallenge()" class="btn-primary w-full py-6 text-base border-b-4 border-zinc-300">
                    Validar Algoritmo 🚀
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function arenaHandler() {
        return {
            currentChallenge: null,
            timer: 0,
            interval: null,
            editor: null,

            init() {
                // Proteções do Design System
                document.addEventListener('contextmenu', e => e.preventDefault());
                document.addEventListener('keydown', e => {
                    if (e.ctrlKey && (e.key === 'c' || e.key === 'v' || e.key === 'u' || e.key === 's')) {
                        e.preventDefault();
                        this.showSecurityToast();
                    }
                });
            },

            showSecurityToast() {
                // Implementar toast ou alert estilizado
                alert('🛡️ JS ACADEMY: Digite seu próprio código para aprender de verdade!');
            },

            selectChallenge(challenge, event) {
                // Microinteraction: highlight selected card
                document.querySelectorAll('.challenge-card').forEach(c => c.classList.remove('active'));
                if (event && event.currentTarget) {
                    event.currentTarget.closest('.challenge-card')?.classList.add('active');
                }

                this.currentChallenge = challenge;
                this.timer = 0;
                this.startTimer();
                
                setTimeout(() => {
                    if (!this.editor) {
                        const container = document.getElementById('arena-editor');
                        this.editor = monaco.editor.create(container, {
                            language: 'javascript',
                            theme: 'vs-dark',
                            automaticLayout: true,
                            fontSize: 14,
                            minimap: { enabled: false },
                            fontFamily: 'JetBrains Mono, Fira Code, monospace',
                            padding: { top: 20 },
                            bracketPairColorization: { enabled: true },
                            smoothScrolling: true,
                            cursorBlinking: "expand"
                        });
                    }
                    this.editor.setValue(challenge.starter_code.replace(/\\n/g, '\n'));
                }, 100);
            },

            startTimer() {
                if (this.interval) clearInterval(this.interval);
                this.interval = setInterval(() => this.timer++, 1000);
            },

            formatTime(seconds) {
                const mins = Math.floor(seconds / 60).toString().padStart(2, '0');
                const secs = (seconds % 60).toString().padStart(2, '0');
                return `${mins}:${secs}`;
            },

            validateChallenge() {
                const code = this.editor.getValue();
                const consoleUI = document.getElementById('arena-console');
                consoleUI.innerHTML = '';

                try {
                    const logs = [];
                    const originalLog = console.log;
                    console.log = (...args) => {
                        const msg = args.map(a => typeof a === 'object' ? JSON.stringify(a) : a).join(' ');
                        const line = document.createElement('div');
                        line.className = 'py-1 text-accent-cyan border-b border-white/5 font-mono';
                        line.textContent = `> ${msg}`;
                        consoleUI.appendChild(line);
                    };
                    
                    eval(code);
                    console.log = originalLog;

                    clearInterval(this.interval);
                    
                    import('canvas-confetti').then(confetti => {
                        confetti.default({ particleCount: 150, spread: 70, origin: { y: 0.6 } });
                    });

                    alert(`🏁 BRILHANTE! Desafio "${this.currentChallenge.title}" concluído em ${this.formatTime(this.timer)}.\nXP: +${this.currentChallenge.xp_reward}`);
                    
                    window.location.reload();

                } catch (err) {
                    consoleUI.innerHTML = `<div class="text-red-500 font-bold p-2 bg-red-500/10 rounded-lg">✕ ERRO DE LÓGICA: ${err.message}</div>`;
                }
            }
        };
    }
</script>

<style>
    [x-cloak] { display: none !important; }

    /* Remove user-select: none do bento-card (conflita com light mode) */
    .bento-card { user-select: auto !important; }

    /* Challenge card hover */
    .challenge-card {
        transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1), border-color 0.2s ease;
    }
    .challenge-card:hover {
        transform: translateY(-3px);
        border-color: rgba(247,223,30,0.3) !important;
    }
    .challenge-card.active {
        border-color: rgba(247,223,30,0.5) !important;
        background: rgba(247,223,30,0.03) !important;
    }

    /* Difficulty badge microinteraction */
    .difficulty-badge {
        transition: transform 0.2s ease;
        display: inline-block;
    }
    .challenge-card:hover .difficulty-badge { transform: scale(1.05); }

    /* Arena editor fade-in */
    #arena-panel { transition: opacity 0.3s ease; }
    #arena-panel.loading { opacity: 0.6; }
</style>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) return;

    gsap.from('.page-header', { y: 60, opacity: 0, duration: 1, ease: 'power3.out' });
    gsap.from('.challenge-card', {
        scrollTrigger: { trigger: '.challenge-card', start: 'top 85%' },
        y: 40, opacity: 0, stagger: 0.08, duration: 0.6, ease: 'power2.out', delay: 0.3
    });
});
</script>
@endsection
@endsection
