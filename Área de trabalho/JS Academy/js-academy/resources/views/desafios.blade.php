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
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5" x-show="!currentChallenge" x-transition>
        @foreach($challenges as $challenge)
        @php
            $diffColor = match($challenge->difficulty) {
                'Fácil'  => 'text-green-400 bg-green-500/10 border-green-500/20',
                'Médio'  => 'text-yellow-400 bg-yellow-500/10 border-yellow-500/20',
                'Difícil'=> 'text-red-400 bg-red-500/10 border-red-500/20',
                default  => 'text-zinc-400 bg-zinc-800 border-white/5',
            };
            $diffIcon = match($challenge->difficulty) {
                'Fácil'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'Médio'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
                default  => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/>',
            };
        @endphp
        <div class="challenge-card group rounded-2xl border border-white/6 bg-zinc-900/40 hover:bg-zinc-900/70 hover:border-white/12 transition-all duration-300 flex flex-col p-0 overflow-hidden cursor-pointer"
             @click="selectChallenge({{ $challenge }}, $event)">
            {{-- Header colorido --}}
            <div class="px-5 pt-5 pb-4">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <span class="text-[9px] font-mono text-cyan-500 uppercase tracking-[0.25em] font-bold">{{ $challenge->category }}</span>
                    <span class="difficulty-badge inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider border {{ $diffColor }}">
                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">{!! $diffIcon !!}</svg>
                        {{ $challenge->difficulty }}
                    </span>
                </div>
                <h3 class="text-xl font-black text-white group-hover:text-yellow-400 transition-colors leading-tight tracking-tight mb-2">{{ $challenge->title }}</h3>
                <p class="text-zinc-500 text-xs leading-relaxed line-clamp-2">{{ $challenge->description }}</p>
            </div>

            {{-- Footer --}}
            <div class="mt-auto px-5 py-4 border-t border-white/5 flex items-center justify-between">
                <div class="flex items-center gap-4 text-[10px] font-bold text-zinc-500">
                    <span class="flex items-center gap-1">
                        <svg class="w-3 h-3 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        +{{ $challenge->xp_reward }} XP
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="w-3 h-3 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        {{ $challenge->diamond_reward }} 💎
                    </span>
                </div>
                <span class="text-[9px] font-black text-zinc-600 group-hover:text-yellow-400 transition-colors uppercase tracking-widest flex items-center gap-1">
                    Iniciar
                    <svg class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </span>
            </div>
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
            solved: false,

            init() { /* sem bloqueio de copiar/colar — foco no aprendizado */ },

            selectChallenge(challenge, event) {
                document.querySelectorAll('.challenge-card').forEach(c => c.classList.remove('active'));
                event?.currentTarget?.closest?.('.challenge-card')?.classList.add('active');

                this.currentChallenge = challenge;
                this.timer = 0;
                this.solved = false;
                this.startTimer();

                // Scroll suave para arena
                setTimeout(() => {
                    document.getElementById('arena-ui')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 50);

                setTimeout(() => {
                    const container = document.getElementById('arena-editor');
                    if (!this.editor) {
                        this.editor = monaco.editor.create(container, {
                            language: 'javascript',
                            theme: document.body.classList.contains('light-mode') ? 'vs' : 'vs-dark',
                            automaticLayout: true,
                            fontSize: 14,
                            minimap: { enabled: false },
                            fontFamily: '"JetBrains Mono", "Fira Code", monospace',
                            padding: { top: 20 },
                            bracketPairColorization: { enabled: true },
                            smoothScrolling: true,
                            cursorBlinking: 'expand',
                            scrollbar: { vertical: 'auto' },
                        });
                        // Sync Monaco com tema
                        window.addEventListener('theme-changed', e => {
                            monaco.editor.setTheme(e.detail.theme === 'light' ? 'vs' : 'vs-dark');
                        });
                    }
                    this.editor.setValue((challenge.starter_code || '').replace(/\\n/g, '\n'));
                    this.editor.focus();
                    if (window.SoundEngine) SoundEngine.click();
                }, 150);
            },

            startTimer() {
                if (this.interval) clearInterval(this.interval);
                this.interval = setInterval(() => this.timer++, 1000);
            },

            formatTime(s) {
                return `${String(Math.floor(s/60)).padStart(2,'0')}:${String(s%60).padStart(2,'0')}`;
            },

            validateChallenge() {
                if (!this.currentChallenge) return;
                if (this.solved) return;

                const code = this.editor?.getValue?.() || '';
                const consoleUI = document.getElementById('arena-console');

                // Bloquear envio de código vazio ou idêntico ao starter
                const starterClean = (this.currentChallenge.starter_code || '').replace(/\\n/g, '\n').trim();
                if (!code.trim() || code.trim() === starterClean) {
                    consoleUI.innerHTML = '<div class="text-yellow-500 font-bold p-2 bg-yellow-500/10 rounded-lg flex items-center gap-2"><svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/></svg> Escreva sua solução antes de validar!</div>';
                    if (window.SoundEngine) SoundEngine.error();
                    return;
                }

                consoleUI.innerHTML = '';
                const outputs = [];
                const originalLog = console.log;
                const originalError = console.error;

                console.log = (...args) => {
                    const msg = args.map(a => typeof a === 'object' ? JSON.stringify(a, null, 2) : String(a)).join(' ');
                    outputs.push({ type: 'log', msg });
                    const line = document.createElement('div');
                    line.className = 'py-0.5 text-cyan-300 font-mono text-xs';
                    line.textContent = '› ' + msg;
                    consoleUI.appendChild(line);
                };
                console.error = (...args) => {
                    const msg = args.join(' ');
                    outputs.push({ type: 'error', msg });
                };

                let execError = null;
                try {
                    eval(code);
                } catch (err) {
                    execError = err;
                } finally {
                    console.log = originalLog;
                    console.error = originalError;
                }

                if (execError) {
                    consoleUI.innerHTML = `<div class="text-red-400 font-bold p-2 bg-red-500/10 rounded-lg text-xs flex items-start gap-2"><svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg><span>Erro: ${execError.message}</span></div>`;
                    if (window.SoundEngine) SoundEngine.error();
                    return;
                }

                if (outputs.length === 0) {
                    consoleUI.innerHTML = '<div class="text-zinc-500 font-mono text-xs p-2 flex items-center gap-2"><svg class="w-4 h-4 text-yellow-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/></svg>Nenhuma saída. Use console.log() para exibir o resultado da sua solução.</div>';
                    if (window.SoundEngine) SoundEngine.error();
                    return;
                }

                // Validação por test cases embutidos (por slug do desafio)
                const slug = this.currentChallenge.slug;
                const validationResult = this._runTestCases(slug, code, outputs);

                if (!validationResult.passed) {
                    consoleUI.innerHTML += `<div class="mt-2 p-2 bg-red-500/10 rounded-lg text-xs text-red-400 flex items-start gap-2"><svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span>${validationResult.message}</span></div>`;
                    if (window.SoundEngine) SoundEngine.error();
                    return;
                }

                // Sucesso — passou nos testes
                this.solved = true;
                clearInterval(this.interval);

                if (window.SoundEngine) { SoundEngine.success(); setTimeout(() => SoundEngine.xp(), 500); }

                import('canvas-confetti').then(m => {
                    m.default({ particleCount: 200, spread: 90, origin: { y: 0.5 }, colors: ['#F7DF1E','#ffffff','#22d3ee'] });
                });

                // POST XP
                fetch('/progress/update-xp', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ xp: this.currentChallenge.xp_reward })
                });

                // Mostrar modal de conclusão
                this.showSuccessModal();
            },

            showSuccessModal() {
                const modal = document.getElementById('challenge-success-modal');
                if (!modal) return;

                const c = this.currentChallenge;
                document.getElementById('success-challenge-title').textContent = c.title;
                document.getElementById('success-xp').textContent = '+' + c.xp_reward + ' XP';
                document.getElementById('success-time').textContent = this.formatTime(this.timer);
                document.getElementById('success-diamonds').textContent = '+' + (c.diamond_reward || 0) + ' 💎';

                // Share text
                const shareText = encodeURIComponent(`Acabei de resolver "${c.title}" na JS Academy em ${this.formatTime(this.timer)}! 🏆 +${c.xp_reward} XP #JSAcademy #JavaScript`);
                const shareUrl = encodeURIComponent(window.location.origin + '/desafios');
                document.getElementById('success-share-x').href = `https://twitter.com/intent/tweet?text=${shareText}&url=${shareUrl}`;
                document.getElementById('success-share-linkedin').href = `https://www.linkedin.com/sharing/share-offsite/?url=${shareUrl}`;

                modal.classList.remove('hidden');
                modal.classList.add('flex');

                if (typeof gsap !== 'undefined') {
                    gsap.fromTo('#challenge-success-modal > div', { scale: 0.8, opacity: 0, y: 30 }, { scale: 1, opacity: 1, y: 0, duration: 0.5, ease: 'back.out(1.7)' });
                }
            },

            // Valida por test cases específicos de cada desafio
            _runTestCases(slug, code, outputs) {
                try {
                    // Cria um ambiente de execução isolado
                    const run = (fn, ...args) => {
                        try { return { ok: true, val: fn(...args) }; }
                        catch(e) { return { ok: false, err: e.message }; }
                    };

                    const testCases = {
                        'fizzbuzz-elite': () => {
                            const fn = new Function(code + '; return fizzBuzz;')();
                            if (!fn) return { passed: false, message: 'Função fizzBuzz não encontrada. Defina: function fizzBuzz(n) { ... }' };
                            const t = [run(fn,3), run(fn,5), run(fn,15), run(fn,1)];
                            if (!t.every(r=>r.ok)) return { passed: false, message: 'Erro ao executar fizzBuzz. Verifique se retorna um valor.' };
                            if (t[0].val !== 'Fizz') return { passed: false, message: `fizzBuzz(3) deveria retornar "Fizz", mas retornou "${t[0].val}"` };
                            if (t[1].val !== 'Buzz') return { passed: false, message: `fizzBuzz(5) deveria retornar "Buzz", mas retornou "${t[1].val}"` };
                            if (t[2].val !== 'FizzBuzz') return { passed: false, message: `fizzBuzz(15) deveria retornar "FizzBuzz", mas retornou "${t[2].val}"` };
                            if (t[3].val !== '1' && t[3].val !== 1) return { passed: false, message: `fizzBuzz(1) deveria retornar "1" ou 1, mas retornou "${t[3].val}"` };
                            return { passed: true };
                        },
                        'string-reverse': () => {
                            const logs = outputs.map(o => String(o.msg).trim());
                            const expected = 'tpircSavaJ';
                            if (!logs.some(l => l === expected || l.includes(expected))) {
                                return { passed: false, message: `O resultado deveria ser "${expected}". Você obteve: "${logs[0] || '(vazio)'}"` };
                            }
                            return { passed: true };
                        },
                        'max-number': () => {
                            const logs = outputs.map(o => String(o.msg).trim());
                            if (!logs.some(l => l === '20')) {
                                return { passed: false, message: `O maior número é 20. Você obteve: "${logs[0] || '(vazio)'}"` };
                            }
                            return { passed: true };
                        },
                        'vowel-count': () => {
                            const logs = outputs.map(o => String(o.msg).trim());
                            if (!logs.some(l => l === '10')) {
                                return { passed: false, message: `A frase "Eu amo programar em JavaScript" tem 10 vogais. Você obteve: "${logs[0] || '(vazio)'}"` };
                            }
                            return { passed: true };
                        },
                        'palindrome-check': () => {
                            const logs = outputs.map(o => String(o.msg).toLowerCase());
                            if (!logs.some(l => l === 'true')) {
                                return { passed: false, message: `"arara" é um palíndromo — o resultado deve ser true. Você obteve: "${logs[0] || '(vazio)'}"` };
                            }
                            return { passed: true };
                        },
                        'recursive-factorial': () => {
                            const fn = new Function(code + '; return fatorial;')();
                            if (!fn) return { passed: false, message: 'Função fatorial não encontrada. Defina: function fatorial(n) { ... }' };
                            const r = run(fn, 5);
                            if (!r.ok) return { passed: false, message: `Erro ao executar fatorial(5): ${r.err}` };
                            if (r.val !== 120) return { passed: false, message: `fatorial(5) deveria ser 120, mas retornou ${r.val}` };
                            return { passed: true };
                        },
                        'two-sum': () => {
                            const fn = new Function(code + '; return twoSum;')();
                            if (!fn) return { passed: false, message: 'Função twoSum não encontrada. Defina: function twoSum(nums, target) { ... }' };
                            const r = run(fn, [2,7,11,15], 9);
                            if (!r.ok) return { passed: false, message: `Erro: ${r.err}` };
                            const v = r.val;
                            if (!Array.isArray(v) || !((v[0]===0&&v[1]===1)||(v[0]===1&&v[1]===0))) {
                                return { passed: false, message: `twoSum([2,7,11,15], 9) deveria retornar [0,1], mas retornou ${JSON.stringify(v)}` };
                            }
                            return { passed: true };
                        },
                        'debounce': () => {
                            const fn = new Function(code + '; return debounce;')();
                            if (!fn) return { passed: false, message: 'Função debounce não encontrada.' };
                            if (typeof fn !== 'function') return { passed: false, message: 'debounce deve ser uma função.' };
                            const r = run(fn, () => {}, 300);
                            if (!r.ok || typeof r.val !== 'function') return { passed: false, message: 'debounce deve retornar uma função.' };
                            return { passed: true };
                        },
                        'deep-clone': () => {
                            const fn = new Function(code + '; return deepClone;')();
                            if (!fn) return { passed: false, message: 'Função deepClone não encontrada.' };
                            const obj = { a: 1, b: { c: 2 } };
                            const r = run(fn, obj);
                            if (!r.ok) return { passed: false, message: `Erro: ${r.err}` };
                            if (r.val === obj) return { passed: false, message: 'deepClone deve retornar um novo objeto, não a mesma referência.' };
                            if (r.val.b === obj.b) return { passed: false, message: 'deepClone deve copiar objetos aninhados (cópia profunda).' };
                            if (r.val.a !== 1 || r.val.b.c !== 2) return { passed: false, message: 'Os valores do clone estão incorretos.' };
                            return { passed: true };
                        },
                        'flatten-array': () => {
                            const fn = new Function(code + '; return flatten;')();
                            if (!fn) return { passed: false, message: 'Função flatten não encontrada.' };
                            const r = run(fn, [[1,2],[3,[4,5]],6]);
                            if (!r.ok) return { passed: false, message: `Erro: ${r.err}` };
                            if (JSON.stringify(r.val) !== '[1,2,3,4,5,6]') {
                                return { passed: false, message: `flatten([[1,2],[3,[4,5]],6]) deveria ser [1,2,3,4,5,6], mas retornou ${JSON.stringify(r.val)}` };
                            }
                            return { passed: true };
                        },
                        'memoize': () => {
                            const fn = new Function(code + '; return memoize;')();
                            if (!fn) return { passed: false, message: 'Função memoize não encontrada.' };
                            let calls = 0;
                            const heavy = (n) => { calls++; return n * 2; };
                            const r = run(fn, heavy);
                            if (!r.ok || typeof r.val !== 'function') return { passed: false, message: 'memoize deve retornar uma função.' };
                            r.val(5); r.val(5); r.val(5);
                            if (calls !== 1) return { passed: false, message: `memoize deveria chamar a função original apenas 1x para o mesmo input. Chamou ${calls}x.` };
                            return { passed: true };
                        },
                        'anagramas': () => {
                            const fn = new Function(code + '; return isAnagram;')();
                            if (!fn) return { passed: false, message: 'Função isAnagram não encontrada.' };
                            const r1 = run(fn, 'listen', 'silent');
                            const r2 = run(fn, 'hello', 'world');
                            if (!r1.ok) return { passed: false, message: `Erro: ${r1.err}` };
                            if (r1.val !== true) return { passed: false, message: '"listen" e "silent" são anagramas — deveria retornar true.' };
                            if (r2.val === true) return { passed: false, message: '"hello" e "world" não são anagramas — deveria retornar false.' };
                            return { passed: true };
                        },
                    };

                    const testFn = testCases[slug];
                    if (!testFn) {
                        // Sem teste específico: aceita se houve output
                        return outputs.length > 0 ? { passed: true } : { passed: false, message: 'Use console.log() para mostrar o resultado.' };
                    }

                    return testFn();
                } catch(e) {
                    return { passed: false, message: `Erro ao validar: ${e.message}` };
                }
            },

            closeSuccessModal() {
                const modal = document.getElementById('challenge-success-modal');
                if (typeof gsap !== 'undefined') {
                    gsap.to('#challenge-success-modal > div', { scale: 0.9, opacity: 0, y: 20, duration: 0.3, ease: 'power2.in',
                        onComplete: () => { modal.classList.add('hidden'); modal.classList.remove('flex'); this.currentChallenge = null; }
                    });
                } else {
                    modal.classList.add('hidden'); modal.classList.remove('flex'); this.currentChallenge = null;
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

{{-- Modal de Sucesso do Desafio --}}
<div id="challenge-success-modal" class="hidden fixed inset-0 z-[500] items-center justify-center p-4 bg-zinc-950/95 backdrop-blur-md">
    <div class="w-full max-w-md bg-zinc-900 border border-white/10 rounded-[2.5rem] p-8 shadow-2xl text-center">
        {{-- Ícone de troféu --}}
        <div class="w-20 h-20 bg-gradient-to-br from-yellow-400/20 to-orange-500/10 rounded-3xl flex items-center justify-center mx-auto mb-6 border border-yellow-400/20">
            <svg class="w-10 h-10 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0"/>
            </svg>
        </div>

        {{-- Título --}}
        <div class="mb-1">
            <span class="text-[10px] font-mono text-yellow-500 uppercase tracking-[0.3em]">Desafio Concluído</span>
        </div>
        <h3 class="text-2xl font-black text-white tracking-tight mb-1" id="success-challenge-title">Título</h3>
        <p class="text-zinc-500 text-sm mb-6">Seu código foi executado com sucesso.</p>

        {{-- Rewards --}}
        <div class="grid grid-cols-3 gap-3 mb-7">
            <div class="rounded-2xl bg-yellow-400/8 border border-yellow-400/15 p-3">
                <p class="text-xl font-black text-yellow-400" id="success-xp">+0 XP</p>
                <p class="text-[9px] text-zinc-600 uppercase tracking-widest mt-0.5">XP Ganho</p>
            </div>
            <div class="rounded-2xl bg-cyan-400/8 border border-cyan-400/15 p-3">
                <p class="text-xl font-black text-cyan-400" id="success-time">00:00</p>
                <p class="text-[9px] text-zinc-600 uppercase tracking-widest mt-0.5">Tempo</p>
            </div>
            <div class="rounded-2xl bg-violet-400/8 border border-violet-400/15 p-3">
                <p class="text-xl font-black text-violet-400" id="success-diamonds">+0 💎</p>
                <p class="text-[9px] text-zinc-600 uppercase tracking-widest mt-0.5">Bônus</p>
            </div>
        </div>

        {{-- Share --}}
        <p class="text-[10px] text-zinc-600 uppercase tracking-widest mb-3">Compartilhar conquista</p>
        <div class="flex gap-3 mb-6">
            <a id="success-share-x" href="#" target="_blank"
                class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl bg-white/5 border border-white/8 text-zinc-400 hover:text-white hover:border-white/15 transition-all text-xs font-bold">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L2.09 2.25h6.813l4.261 5.638 5.08-5.638Zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                Postar no X
            </a>
            <a id="success-share-linkedin" href="#" target="_blank"
                class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl bg-blue-600/10 border border-blue-600/20 text-blue-400 hover:bg-blue-600/20 transition-all text-xs font-bold">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                LinkedIn
            </a>
        </div>

        {{-- CTA --}}
        <button onclick="Alpine.$data(document.querySelector('[x-data]')).closeSuccessModal()"
            class="w-full py-3.5 bg-white text-zinc-950 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-yellow-400 transition-all active:scale-95">
            Próximo Desafio
        </button>
    </div>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) return;
    gsap.from('.page-header', { y: 50, opacity: 0, duration: 0.9, ease: 'power3.out' });
    gsap.from('.challenge-card', {
        y: 30, opacity: 0, stagger: 0.06, duration: 0.5, ease: 'power2.out', delay: 0.2
    });
});
</script>
@endsection
@endsection
