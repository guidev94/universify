@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12" x-data="arenaHandler()">
    <header class="mb-16 text-center lg:text-left">
        <h1 class="text-6xl md:text-8xl font-black text-white italic leading-tight">
            Arena de <span class="bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent italic">Lógica</span> 🥊
        </h1>
        <p class="mt-4 text-xl text-zinc-400 max-w-2xl mx-auto lg:mx-0">
            Encare desafios de algoritmos e prove sua maestria. Resolva rápido para ganhar bônus de XP! 🚫 Proibido colar.
        </p>
    </header>

    <!-- Grid de Cards de Desafios -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" x-show="!currentChallenge">
        @foreach($challenges as $challenge)
        <div class="bento-card p-8 bg-zinc-900/40 border-white/5 hover:border-cyan-500/30 hover:scale-[1.02] transition-all group flex flex-col justify-between min-h-[320px]">
            <div>
                <div class="flex justify-between items-center mb-6">
                    <span class="text-[10px] font-mono text-cyan-500 uppercase font-black tracking-[0.2em]">{{ $challenge->category }}</span>
                    <span class="px-3 py-1 rounded-full bg-zinc-800 text-[9px] font-black text-zinc-400 uppercase tracking-tighter border border-white/5">{{ $challenge->difficulty }}</span>
                </div>
                <h3 class="text-3xl font-black text-white mb-4 group-hover:text-cyan-400 transition-colors tracking-tighter">{{ $challenge->title }}</h3>
                <p class="text-zinc-500 text-sm leading-relaxed mb-8 line-clamp-3">{{ $challenge->description }}</p>
            </div>
            
            <div class="flex flex-col gap-6">
                <div class="flex items-center gap-6 text-[11px] font-black uppercase tracking-widest border-t border-white/5 pt-6">
                    <span class="flex items-center gap-2"><span class="text-lg">⚡</span> +{{ $challenge->xp_reward }} XP</span>
                    <span class="flex items-center gap-2"><span class="text-lg">💎</span> {{ $challenge->diamond_reward }}</span>
                </div>
                <button @click="selectChallenge({{ $challenge }})" class="w-full py-4 bg-white text-zinc-950 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:bg-cyan-400 hover:text-zinc-950 transition-all active:scale-95 shadow-xl">
                    Iniciar Desafio
                </button>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Arena do Desafio (Aberta após selecionar) -->
    <div id="arena-ui" class="max-w-5xl mx-auto space-y-6" x-show="currentChallenge" x-cloak>
        <button @click="currentChallenge = null; clearInterval(interval)" class="flex items-center gap-2 text-zinc-500 hover:text-white transition-colors text-xs font-black uppercase tracking-widest mb-4">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Voltar para a Lista
        </button>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Instruções -->
            <div class="lg:col-span-5 space-y-6">
                <div class="bento-card p-8 bg-zinc-900/80 border-cyan-500/20 shadow-2xl">
                    <h2 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2" x-text="currentChallenge?.title"></h2>
                    <span class="text-[10px] font-mono text-cyan-500 uppercase font-black" x-text="currentChallenge?.category"></span>
                    
                    <div class="mt-8 p-6 bg-zinc-950/50 rounded-[2rem] border border-white/5 border-l-4 border-l-cyan-500">
                        <h4 class="text-[10px] font-black text-cyan-500 uppercase tracking-[0.2em] mb-3">Sua Missão:</h4>
                        <p class="text-white text-sm font-bold leading-relaxed italic" x-text="currentChallenge?.task"></p>
                    </div>

                    <!-- Cronômetro -->
                    <div class="mt-8 flex items-center justify-between p-4 bg-zinc-950 rounded-2xl border border-white/5">
                        <span class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Tempo de Execução</span>
                        <div class="font-mono text-2xl text-cyan-500 font-bold" x-text="formatTime(timer)">00:00</div>
                    </div>
                </div>

                <div class="bento-card p-6 bg-zinc-950 border-zinc-900 font-mono text-xs shadow-inner min-h-[150px]">
                    <div class="flex items-center gap-2 mb-4 border-b border-white/5 pb-2">
                        <span class="w-2 h-2 rounded-full bg-cyan-500 animate-pulse"></span>
                        <span class="uppercase text-[9px] text-zinc-500 font-black tracking-widest">Console Output</span>
                    </div>
                    <div id="arena-console" class="text-zinc-400">> Aguardando execução de código...</div>
                </div>
            </div>

            <!-- Editor -->
            <div class="lg:col-span-7 flex flex-col gap-6">
                <div class="bento-card bg-zinc-950 border-zinc-900 h-[500px] overflow-hidden flex flex-col shadow-2xl">
                    <div class="flex items-center justify-between p-4 bg-zinc-900/50 border-b border-zinc-800">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-500/20 border border-red-500/50"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500/20 border border-yellow-500/50"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500/20 border border-green-500/50"></div>
                            <span class="ml-4 text-[10px] font-mono text-zinc-500 uppercase tracking-widest">arena.js</span>
                        </div>
                    </div>
                    <div id="arena-editor" class="flex-grow w-full"></div>
                </div>

                <button @click="validateChallenge()" class="w-full py-5 bg-cyan-500 text-zinc-950 rounded-[2rem] font-black text-base uppercase tracking-widest hover:bg-cyan-400 transition-all active:scale-95 shadow-2xl shadow-cyan-500/20 border-b-4 border-cyan-700">
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
                // Proteções Contra Cópia
                document.addEventListener('contextmenu', e => e.preventDefault());
                document.addEventListener('keydown', e => {
                    if (e.ctrlKey && (e.key === 'c' || e.key === 'v' || e.key === 'u' || e.key === 's')) {
                        e.preventDefault();
                        alert('🛡️ SEGURANÇA: Copiar ou colar código na Arena é proibido!');
                    }
                });
            },

            selectChallenge(challenge) {
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
                            padding: { top: 20 }
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
                        line.className = 'py-1 text-cyan-400 border-b border-white/5';
                        line.textContent = `> ${msg}`;
                        consoleUI.appendChild(line);
                    };
                    
                    eval(code);
                    console.log = originalLog;

                    clearInterval(this.interval);
                    
                    alert(`🏁 EXCELENTE! Desafio concluído em ${this.formatTime(this.timer)}.\nXP Ganhado: +${this.currentChallenge.xp_reward}`);
                    
                    // Recarregar para atualizar estado (Num sistema real, aqui chamaria a API de progresso)
                    window.location.reload();

                } catch (err) {
                    consoleUI.innerHTML = `<span class="text-red-500 font-bold tracking-tight">✕ ERRO DE LÓGICA: ${err.message}</span>`;
                }
            }
        };
    }
</script>

<style>
    [x-cloak] { display: none !important; }
    .bento-card {
        border-radius: 2.5rem;
        border: 1px solid rgba(255, 255, 255, 0.05);
        background: rgba(24, 24, 27, 0.4);
        backdrop-filter: blur(20px);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        user-select: none !important;
    }
</style>
@endsection
