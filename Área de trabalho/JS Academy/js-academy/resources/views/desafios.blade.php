@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12" x-data="arenaHandler()">
    <header class="mb-16">
        <h1 class="text-6xl md:text-8xl font-black text-white italic leading-tight">
            Arena de <span class="bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent italic">Lógica</span> 🥊
        </h1>
        <p class="mt-4 text-xl text-zinc-400 max-w-2xl">
            Desafios cronometrados para testar sua velocidade e precisão. Proibido colar! 🚫
        </p>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Lista de Desafios -->
        <div class="lg:col-span-5 space-y-4">
            @foreach($challenges as $challenge)
            <div class="bento-card p-6 bg-zinc-900/40 border-white/5 hover:border-cyan-500/30 transition-all group cursor-pointer" @click="selectChallenge({{ $challenge }})">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <span class="text-[9px] font-mono text-cyan-500 uppercase font-bold tracking-widest">{{ $challenge->category }}</span>
                        <h3 class="text-xl font-black text-white group-hover:text-cyan-400 transition-colors">{{ $challenge->title }}</h3>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-zinc-800 text-[8px] font-bold text-zinc-500 uppercase">{{ $challenge->difficulty }}</span>
                </div>
                <div class="flex items-center gap-4 text-[10px] font-bold text-zinc-500">
                    <span class="flex items-center gap-1"><span class="text-yellow-500">⚡</span> +{{ $challenge->xp_reward }} XP</span>
                    <span class="flex items-center gap-1"><span class="text-cyan-500">💎</span> {{ $challenge->diamond_reward }}</span>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Arena do Desafio -->
        <div class="lg:col-span-7">
            <div id="arena-ui" class="lg:sticky lg:top-24 space-y-6" x-show="currentChallenge">
                <!-- Header Arena -->
                <div class="bento-card p-6 bg-zinc-900/80 border-cyan-500/20">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-black text-white italic uppercase tracking-tighter" x-text="currentChallenge.title"></h2>
                        <div class="flex items-center gap-4">
                            <!-- Cronômetro -->
                            <div class="px-4 py-2 bg-zinc-950 rounded-xl border border-white/5 font-mono text-xl text-cyan-500 shadow-inner">
                                <span x-text="formatTime(timer)">00:00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-zinc-950/50 rounded-2xl border border-white/5 mb-6">
                        <h4 class="text-[10px] font-black text-cyan-500 uppercase tracking-widest mb-2">Objetivo:</h4>
                        <p class="text-white text-sm leading-relaxed" x-text="currentChallenge.task"></p>
                    </div>

                    <!-- Editor -->
                    <div class="bento-card bg-zinc-950 border-zinc-900 h-[300px] overflow-hidden flex flex-col mb-4">
                        <div id="arena-editor" class="flex-grow w-full"></div>
                    </div>

                    <button @click="validateChallenge()" class="w-full py-5 bg-cyan-500 text-zinc-950 rounded-2xl font-black text-base uppercase tracking-widest hover:bg-cyan-400 transition-all active:scale-95 shadow-xl shadow-cyan-500/20">
                        Finalizar Desafio 🏁
                    </button>
                </div>

                <!-- Console -->
                <div class="bento-card p-4 bg-zinc-950 font-mono text-xs">
                    <div id="arena-console" class="text-zinc-500">> Aguardando execução...</div>
                </div>
            </div>

            <!-- Empty State -->
            <div x-show="!currentChallenge" class="bento-card p-20 text-center border-dashed border-white/10">
                <div class="text-6xl mb-6">🤺</div>
                <h3 class="text-xl font-bold text-white">Selecione um desafio</h3>
                <p class="text-zinc-500 text-sm mt-2">Sua arena de treinamento está pronta.</p>
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
                // Bloqueio de Copiar/Colar e Seleção
                document.addEventListener('contextmenu', e => e.preventDefault());
                document.addEventListener('keydown', e => {
                    if (e.ctrlKey && (e.key === 'c' || e.key === 'v' || e.key === 'u' || e.key === 's')) {
                        e.preventDefault();
                        alert('🛡️ Proteção Ativa: A cópia de código é proibida na Arena!');
                    }
                });
            },

            selectChallenge(challenge) {
                this.currentChallenge = challenge;
                this.timer = 0;
                this.startTimer();
                
                // Inicializar Editor se não existir
                setTimeout(() => {
                    if (!this.editor) {
                        const container = document.getElementById('arena-editor');
                        this.editor = monaco.editor.create(container, {
                            language: 'javascript',
                            theme: 'vs-dark',
                            automaticLayout: true,
                            fontSize: 14,
                            minimap: { enabled: false }
                        });
                    }
                    this.editor.setValue(challenge.starter_code);
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
                    // Redirecionar log
                    const logs = [];
                    const originalLog = console.log;
                    console.log = (...args) => logs.push(args.join(' '));
                    
                    eval(code);
                    console.log = originalLog;

                    clearInterval(this.interval);
                    
                    // Sucesso visual
                    alert(`🔥 Desafio Concluído em ${this.formatTime(this.timer)}!\nVocê ganhou ${this.currentChallenge.xp_reward} XP!`);
                    
                    // Enviar progresso (opcional, implementar rota se necessário)
                    window.location.reload();

                } catch (err) {
                    consoleUI.innerHTML = `<span class="text-red-500">✕ Erro: ${err.message}</span>`;
                }
            }
        };
    }
</script>

<style>
    .bento-card {
        border-radius: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.05);
        background: rgba(24, 24, 27, 0.4);
        backdrop-filter: blur(20px);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        user-select: none !important; /* Bloqueia seleção de texto */
    }
</style>
@endsection
