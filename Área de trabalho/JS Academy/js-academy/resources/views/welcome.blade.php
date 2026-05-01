@extends('layouts.app')

@section('head')
<script>
// Registrar ScrollTrigger do GSAP — importado no bundle via app.js
if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
    gsap.registerPlugin(ScrollTrigger);
}
</script>
@endsection

@section('content')

{{-- INTRO: Lógica de Programação (aparece apenas na primeira visita) --}}
<div id="intro-overlay" class="hidden fixed inset-0 z-[300] flex items-center justify-center p-4" style="background: rgba(9,9,11,0.97); backdrop-filter: blur(20px);">
    <div class="w-full max-w-2xl">
        {{-- Progress steps --}}
        <div class="flex gap-2 mb-10" id="intro-steps">
            <div class="h-1 flex-1 rounded-full bg-yellow-400 intro-step-bar" style="transition: width 8s linear"></div>
            <div class="h-1 w-8 rounded-full bg-zinc-800"></div>
            <div class="h-1 w-8 rounded-full bg-zinc-800"></div>
        </div>

        {{-- Content --}}
        <div id="intro-content">
            <span class="text-[10px] font-mono text-yellow-500 uppercase tracking-[0.4em] font-bold">Antes de começar</span>
            <h2 class="text-4xl md:text-6xl font-black text-white uppercase italic tracking-tighter mt-4 leading-[0.9]">
                O que é<br><span class="text-yellow-400">Lógica de<br>Programação?</span>
            </h2>
            <p class="text-zinc-400 mt-6 leading-relaxed text-base max-w-lg">
                Antes de qualquer sintaxe, existe o <strong class="text-white">pensamento estruturado</strong>. Lógica de programação é a habilidade de quebrar um problema em passos sequenciais que um computador possa executar.
            </p>
            <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 rounded-2xl bg-white/5 border border-white/10">
                    <div class="text-2xl mb-2">📥</div>
                    <p class="text-[11px] font-black text-white uppercase tracking-wider">Entrada</p>
                    <p class="text-[11px] text-zinc-500 mt-1">Dados que o programa recebe</p>
                </div>
                <div class="p-4 rounded-2xl bg-white/5 border border-white/10">
                    <div class="text-2xl mb-2">⚙️</div>
                    <p class="text-[11px] font-black text-white uppercase tracking-wider">Processamento</p>
                    <p class="text-[11px] text-zinc-500 mt-1">Transformação dos dados</p>
                </div>
                <div class="p-4 rounded-2xl bg-white/5 border border-white/10">
                    <div class="text-2xl mb-2">📤</div>
                    <p class="text-[11px] font-black text-white uppercase tracking-wider">Saída</p>
                    <p class="text-[11px] text-zinc-500 mt-1">Resultado gerado</p>
                </div>
            </div>
            <div class="mt-8 p-5 rounded-2xl bg-yellow-400/5 border border-yellow-400/20">
                <p class="text-[11px] font-mono text-zinc-500 mb-2">// Exemplo: receita de café como algoritmo</p>
                <pre class="text-xs font-mono text-yellow-300 leading-relaxed">1. <span class="text-zinc-400">INÍCIO</span>
2. Aquecer água a 90°C   <span class="text-zinc-600">// processamento</span>
3. Adicionar 2 colheres de café  <span class="text-zinc-600">// processamento</span>
4. Servir na xícara      <span class="text-zinc-600">// saída</span>
5. <span class="text-zinc-400">FIM</span></pre>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row gap-4 mt-10">
            <button id="intro-start-btn" class="flex-1 py-4 bg-yellow-400 text-zinc-950 rounded-2xl font-black text-sm uppercase tracking-[0.2em] hover:bg-yellow-300 transition-all active:scale-95 shadow-xl shadow-yellow-400/20">
                Entendido — Começar Agora 🚀
            </button>
            <button id="intro-skip-btn" class="px-8 py-4 text-zinc-500 rounded-2xl font-bold text-xs uppercase tracking-widest hover:text-white transition-colors">
                Pular introdução
            </button>
        </div>
        <p class="text-center text-[10px] text-zinc-700 mt-4 font-mono">Esta introdução aparece apenas uma vez</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Cabeçalho Principal -->
    <header class="mb-20 flex flex-col md:flex-row justify-between items-start md:items-end gap-10">
        <div class="max-w-3xl">
            <h1 class="hero-headline text-6xl md:text-8xl font-black text-white leading-[0.9] tracking-tighter light:text-zinc-950">
                Evolua seu <span class="bg-gradient-to-r from-yellow-400 to-orange-500 bg-clip-text text-transparent italic">Javascript</span> <br>ao próximo nível.
            </h1>
            <p class="hero-subtitle mt-6 text-xl text-zinc-500 max-w-xl leading-relaxed light:text-zinc-600">
                Plataforma profissional de aprendizado gamificado. Lógica de programação, padrões de projeto e maestria técnica.
            </p>
        </div>
        
        <!-- Estatísticas Rápidas -->
        <div class="grid grid-cols-2 sm:flex gap-4 w-full md:w-auto">
             <div class="stat-card bento-card p-4 min-w-[140px] border-emerald-500/20 shadow-lg shadow-emerald-500/5 group hover:border-emerald-500/40 transition-all opacity-100">
                <div class="flex justify-between items-start">
                    <span class="text-2xl">🎯</span>
                    <span class="text-[8px] font-black text-emerald-500 bg-emerald-500/10 px-1.5 py-0.5 rounded uppercase tracking-widest">+10% XP</span>
                </div>
                <div class="mt-4">
                    <span class="text-[9px] font-mono text-zinc-500 uppercase tracking-widest">Meta Diária</span>
                    <p class="text-2xl font-black text-white light:text-zinc-900">0/2</p>
                </div>
            </div>

             <div class="stat-card bento-card p-4 min-w-[140px] border-orange-500/20 shadow-lg shadow-orange-500/5 group hover:border-orange-500/40 transition-all opacity-100">
                <div class="flex justify-between items-start">
                    <span class="streak-fire text-2xl">🔥</span>
                </div>
                <div class="mt-4">
                    <span class="text-[9px] font-mono text-zinc-500 uppercase tracking-widest">Ofensiva</span>
                    <p class="text-2xl font-black text-white light:text-zinc-900"><span class="streak-count">{{ $user->streak ?? 0 }}</span> Dias</p>
                </div>
            </div>
        </div>
    </header>

    <!-- Dashboard do Aluno -->
    <section class="mb-16 grid grid-cols-1 md:grid-cols-12 gap-6">
        <!-- Progresso Geral -->
        <div class="md:col-span-4 bento-card p-8 bg-gradient-to-br from-yellow-400/5 to-transparent border-yellow-400/10 hover:border-yellow-400/30 transition-all group opacity-100">
            <span class="text-[10px] font-mono text-yellow-500 uppercase font-bold tracking-[0.3em]">Seu Progresso Total</span>
            <div class="mt-6 flex items-end justify-between">
                @php
                    $totalLessons = \App\Models\Lesson::count();
                    $completedCount = $user ? $user->progress()->where('status', 'completed')->count() : 0;
                    $percent = $totalLessons > 0 ? round(($completedCount / $totalLessons) * 100) : 0;
                @endphp
                <div>
                    <p class="text-6xl font-black text-white light:text-zinc-900 group-hover:scale-110 transition-transform origin-left">{{ $percent }}%</p>
                    <p class="text-[10px] text-zinc-500 mt-2 uppercase tracking-widest">{{ $completedCount }} de {{ $totalLessons }} aulas concluídas</p>
                </div>
                <div class="w-20 h-20 rounded-full border-[6px] border-zinc-900 light:border-zinc-100 border-t-yellow-400 flex items-center justify-center shadow-xl shadow-yellow-400/10">
                    <span class="text-xs font-bold text-zinc-400">XP</span>
                </div>
            </div>
        </div>

        <!-- Próxima Aula -->
        <div class="md:col-span-5 bento-card p-8 bg-gradient-to-br from-cyan-400/5 to-transparent border-cyan-400/10 hover:border-cyan-400/30 transition-all group opacity-100">
            <span class="text-[10px] font-mono text-cyan-400 uppercase font-bold tracking-[0.3em]">Retomar de onde parou</span>
            <div class="mt-6">
                @if($nextLesson)
                <h3 class="text-3xl font-black text-white light:text-zinc-900 group-hover:text-cyan-400 transition-colors">{{ $nextLesson->title }}</h3>
                <p class="text-xs text-zinc-500 mt-2">Módulo: {{ $nextLesson->track->name }}</p>
                <div class="mt-8">
                    <button 
                        data-lesson-id="{{ $nextLesson->id }}"
                        data-lesson-title="{{ $nextLesson->title }}"
                        data-lesson-content="{{ $nextLesson->content }}"
                        data-lesson-task="{{ $nextLesson->task }}"
                        data-lesson-starter="{{ $nextLesson->starter_code }}"
                        data-lesson-tips="{{ $nextLesson->tips }}"
                        data-lesson-examples="{{ $nextLesson->examples }}"
                        data-lesson-case="{{ $nextLesson->real_world_case }}"
                        data-lesson-docs="{{ $nextLesson->docs_link }}"
                        class="lesson-btn inline-flex px-8 py-3 bg-cyan-400 text-zinc-950 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:bg-cyan-300 transition-all active:scale-95 shadow-xl shadow-cyan-400/20">
                        Continuar Agora
                    </button>
                </div>
                @else
                <h3 class="text-3xl font-black text-white light:text-zinc-900">Todas as trilhas concluídas! 🎉</h3>
                <p class="text-xs text-zinc-500 mt-2 italic">Você atingiu a maestria total no momento.</p>
                @endif
            </div>
        </div>

        <!-- Ranking -->
        <div class="md:col-span-3 bento-card p-8 bg-gradient-to-br from-orange-400/5 to-transparent border-orange-400/10 opacity-100">
            <span class="text-[10px] font-mono text-orange-400 uppercase font-bold tracking-[0.3em]">Ranking Global</span>
            <div class="mt-6 text-center">
                <p class="text-7xl font-black text-white light:text-zinc-900 italic">#{{ $rankingPosition }}</p>
                <div class="mt-4 inline-block px-4 py-1.5 bg-orange-400/10 rounded-full border border-orange-400/20">
                    <span class="text-[10px] font-black text-orange-400 uppercase tracking-widest">{{ $user->league ?? 'Bronze' }} League</span>
                </div>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- O Caminho (Trilhas) -->
        <div id="o-caminho" class="lg:col-span-6 order-2 lg:order-1">
            <h2 class="section-title text-3xl font-black text-white mb-10 flex items-center gap-4">
                <div class="w-10 h-10 bg-yellow-400/10 border border-yellow-400/20 rounded-xl flex items-center justify-center text-yellow-400 shadow-lg shadow-yellow-400/5">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                </div>
                Trilhas de Aprendizado
            </h2>
            
            <div class="space-y-12 pl-10 border-l-2 border-zinc-900 light:border-zinc-200 ml-4 relative">
                @foreach($tracks as $index => $track)
                @php
                    $isCompleted = $user ? $user->badges->contains('track_id', $track->id) : false;
                    $isLocked = ($index > 0 && ($user ? !$user->badges->contains('track_id', $tracks[$index-1]->id) : true));
                @endphp
                <div class="track-card relative group">
                    <div class="absolute -left-[51px] top-0 w-6 h-6 rounded-full {{ $isCompleted ? 'bg-green-500' : ($isLocked ? 'bg-zinc-800' : 'bg-yellow-400') }} border-[6px] border-zinc-950 light:border-white z-10 transition-transform group-hover:scale-125 shadow-xl"></div>
                    
                    <div class="bento-card p-6 bg-zinc-900/40 light:bg-white hover:bg-zinc-900/60 transition-all relative overflow-hidden group opacity-100">
                        @if($isLocked)
                        <div class="absolute inset-0 bg-zinc-950/40 backdrop-blur-[2px] flex items-center justify-center z-20">
                            <span class="bg-zinc-800 border border-white/10 px-4 py-2 rounded-2xl text-xs font-bold text-zinc-500 uppercase tracking-widest shadow-2xl flex items-center gap-2 light:bg-white light:text-zinc-400">
                                🔒 Bloqueado
                            </span>
                        </div>
                        @endif

                        <div class="flex flex-col md:flex-row justify-between items-start gap-4">
                            <div class="flex-grow">
                                <span class="text-[9px] font-mono text-zinc-600 uppercase tracking-[0.3em]">Módulo {{ $index + 1 }}</span>
                                <h3 class="text-2xl font-black text-white mt-1 group-hover:text-yellow-400 transition-colors light:text-zinc-900">{{ $track->name }}</h3>
                            </div>
                            <div class="flex items-center gap-3">
                                @if(!$isLocked && !$isCompleted)
                                <button onclick="skipTrack({{ $track->id }})" class="px-3 py-1.5 rounded-lg bg-zinc-800/50 border border-white/5 text-[9px] font-bold text-zinc-500 hover:text-white hover:bg-zinc-700 transition-all uppercase tracking-widest">Pular Módulo</button>
                                @endif
                                @if($track->badge)
                                <div class="w-12 h-12 rounded-xl bg-zinc-800/50 border border-white/5 flex items-center justify-center text-2xl shadow-inner" title="{{ $track->badge->name }}">
                                    {{ $track->badge->icon }}
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Lista de Lições Slim -->
                        <div class="mt-6 space-y-2">
                            @foreach($track->lessons as $lesson)
                            @php
                                $lessonCompleted = $user ? $user->progress()->where('lesson_id', $lesson->id)->where('status', 'completed')->exists() : false;
                            @endphp
                            <button 
                                data-lesson-id="{{ $lesson->id }}"
                                data-lesson-title="{{ $lesson->title }}"
                                data-lesson-content="{{ $lesson->content }}"
                                data-lesson-task="{{ $lesson->task }}"
                                data-lesson-starter="{{ $lesson->starter_code }}"
                                data-lesson-tips="{{ $lesson->tips }}"
                                data-lesson-examples="{{ $lesson->examples }}"
                                data-lesson-case="{{ $lesson->real_world_case }}"
                                data-lesson-docs="{{ $lesson->docs_link }}"
                                class="lesson-btn w-full flex items-center justify-between p-3 rounded-xl bg-zinc-950/40 light:bg-zinc-50 border border-white/5 hover:border-yellow-400/40 transition-all text-left {{ $isLocked ? 'cursor-not-allowed' : '' }} {{ $lessonCompleted ? 'border-green-500/50 bg-green-500/5' : '' }}"
                                {{ $isLocked ? 'disabled' : '' }}>
                                <div class="flex items-center gap-3">
                                    <div class="w-7 h-7 rounded-lg border flex-shrink-0 flex items-center justify-center {{ $lessonCompleted ? 'bg-green-500 border-green-400 shadow-lg shadow-green-500/20' : 'bg-zinc-800 border-white/5' }}">
                                        <span class="text-[10px] font-black {{ $lessonCompleted ? 'text-white' : 'text-zinc-500' }}">{{ $lesson->order }}</span>
                                    </div>
                                    <span class="text-xs font-bold text-white light:text-zinc-900">{{ $lesson->title }}</span>
                                </div>
                                @if($lessonCompleted)
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[8px] font-black text-green-500 uppercase tracking-widest">Concluído</span>
                                    <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                </div>
                                @endif
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Lateral: Arena de Desafio -->
        <div class="lg:col-span-6 order-1 lg:order-2">
            <div id="editor-section" class="lg:sticky lg:top-24 flex flex-col gap-6">
                <!-- Conteúdo Educacional (Tabs) -->
                <div class="bento-card bg-zinc-900/80 border-zinc-800 overflow-hidden flex flex-col min-h-[300px]">
                    <div class="flex border-b border-white/5 bg-zinc-950/50 overflow-x-auto custom-scrollbar">
                        <button onclick="switchTab('tab-desc')" class="tab-btn active px-6 py-4 text-[10px] font-black uppercase tracking-widest text-zinc-500 border-b-2 border-transparent transition-all flex-shrink-0">Explicação</button>
                        <button onclick="switchTab('tab-tips')" class="tab-btn px-6 py-4 text-[10px] font-black uppercase tracking-widest text-zinc-500 border-b-2 border-transparent transition-all flex-shrink-0">Dicas</button>
                        <button onclick="switchTab('tab-examples')" class="tab-btn px-6 py-4 text-[10px] font-black uppercase tracking-widest text-zinc-500 border-b-2 border-transparent transition-all flex-shrink-0">Exemplos</button>
                        <button onclick="switchTab('tab-case')" class="tab-btn px-6 py-4 text-[10px] font-black uppercase tracking-widest text-zinc-500 border-b-2 border-transparent transition-all flex-shrink-0">Caso Real</button>
                        <button onclick="switchTab('tab-docs')" class="tab-btn px-6 py-4 text-[10px] font-black uppercase tracking-widest text-zinc-500 border-b-2 border-transparent transition-all flex-shrink-0">Docs Oficiais 📚</button>
                    </div>
                    
                    <div class="p-8 flex-grow">
                        <div id="tab-desc" class="tab-content">
                            <h3 id="lesson-title-display" class="text-2xl font-black text-white light:text-zinc-900 mb-4 uppercase italic tracking-tighter">Bem-vindo à Arena</h3>
                            <div id="lesson-desc-display" class="text-zinc-400 light:text-zinc-600 leading-relaxed text-sm space-y-4">
                                <p>Selecione uma lição na trilha ao lado para carregar o conteúdo técnico e começar seu aprendizado.</p>
                            </div>
                        </div>
                        <div id="tab-tips" class="tab-content hidden">
                            <h4 class="text-sm font-bold text-yellow-500 uppercase mb-4 tracking-widest">Dicas do Mentor 👨‍🏫</h4>
                            <p id="lesson-tips-display" class="text-zinc-400 italic text-sm">Nada aqui ainda...</p>
                        </div>
                        <div id="tab-examples" class="tab-content hidden">
                            <h4 class="text-sm font-bold text-cyan-500 uppercase mb-4 tracking-widest">Código de Exemplo 💻</h4>
                            <div class="bg-zinc-950 rounded-xl overflow-hidden border border-white/5">
                                <pre class="p-4 text-xs font-mono text-cyan-300 overflow-x-auto custom-scrollbar"><code id="lesson-examples-display">// Selecione uma lição</code></pre>
                            </div>
                        </div>
                        <div id="tab-case" class="tab-content hidden">
                            <h4 class="text-sm font-bold text-pink-500 uppercase mb-4 tracking-widest">Uso no Mercado 💼</h4>
                            <p id="lesson-case-display" class="text-zinc-400 text-sm">Como isso é aplicado em projetos reais?</p>
                        </div>
                        <div id="tab-docs" class="tab-content hidden text-center py-10">
                            <div class="mb-6 text-4xl">📚</div>
                            <h4 class="text-xl font-black text-white mb-2 uppercase">Documentação MDN</h4>
                            <p class="text-zinc-500 text-sm mb-6">Aprofunde-se na fonte oficial da Mozilla.</p>
                            <a id="lesson-docs-display" href="#" target="_blank" class="inline-flex px-6 py-3 bg-zinc-800 text-white rounded-xl font-bold text-xs uppercase tracking-widest border border-white/10 hover:bg-zinc-700 transition-all">Abrir MDN Docs</a>
                        </div>
                    </div>
                </div>

                <!-- MISSÃO (O QUE FAZER) -->
                <div id="task-container" class="bento-card p-8 bg-gradient-to-br from-yellow-400/20 via-orange-500/10 to-transparent border-yellow-500/30 shadow-[0_0_30px_rgba(234,179,8,0.1)] relative overflow-hidden hidden group/task">
                    <!-- Decorativo -->
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-yellow-500/10 rounded-full blur-3xl group-hover/task:bg-yellow-500/20 transition-all"></div>
                    
                    <div class="relative">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 rounded-2xl bg-yellow-400 flex items-center justify-center text-zinc-950 shadow-lg shadow-yellow-400/20 animate-pulse">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-[11px] font-black text-yellow-500 uppercase tracking-[0.3em]">Missão de Treinamento</h4>
                                <p class="text-xs text-zinc-500 uppercase font-mono">Objetivo Prioritário</p>
                            </div>
                        </div>
                        <p id="lesson-task-display" class="text-white font-black text-lg leading-tight italic tracking-tight"></p>
                    </div>
                </div>

                <!-- Editor IDE -->
                <div class="bento-card bg-zinc-950 border-zinc-900 shadow-2xl overflow-hidden flex flex-col h-[350px]">
                    <div class="flex items-center justify-between p-4 bg-zinc-900/50 border-b border-zinc-800">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-500/20 border border-red-500/50"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500/20 border border-yellow-500/50"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500/20 border border-green-500/50"></div>
                            <span class="ml-4 text-[10px] font-mono text-zinc-500 uppercase tracking-widest">sandbox.js</span>
                        </div>
                    </div>

                    <div id="monaco-editor" class="flex-grow w-full"></div>

                    <!-- Console Slim -->
                    <div class="h-20 bg-zinc-950 border-t border-zinc-900 p-3 font-mono">
                        <div id="console-output" class="text-[10px] text-zinc-600 overflow-y-auto max-h-full custom-scrollbar">> Pronto para executar...</div>
                    </div>
                </div>

                <!-- Botão de Validação -->
                <button id="validate-btn" class="w-full py-5 bg-white text-zinc-950 rounded-2xl font-black text-base uppercase tracking-widest hover:bg-yellow-400 transition-all active:scale-95 shadow-2xl shadow-white/5 border-b-4 border-zinc-300 hover:border-yellow-600">
                    Validar Código 🚀
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .bento-card {
        border-radius: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.05);
        background: rgba(24, 24, 27, 0.4);
        backdrop-filter: blur(20px);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    .tab-btn.active {
        color: white;
        border-bottom-color: #F7DF1E;
        background: rgba(247, 223, 30, 0.05);
    }

    .light-mode .tab-btn.active {
        color: #0f172a;
        background: rgba(247, 223, 30, 0.1);
    }

    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #27272a; border-radius: 10px; }
    
    /* Markdown Styles for Content */
    #lesson-desc-display p { margin-bottom: 1rem; }
    #lesson-desc-display ul { list-style: disc; margin-left: 1.5rem; margin-bottom: 1rem; }
    #lesson-desc-display strong { color: white; font-weight: 800; }
</style>

<script>
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        
        document.getElementById(tabId).classList.remove('hidden');
        event.currentTarget.classList.add('active');
    }

    function skipTrack(trackId) {
        if(confirm('Deseja pular este módulo? Você não ganhará os pontos das lições não concluídas.')) {
            fetch(`/progress/skip-track/${trackId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            }).then(res => res.json()).then(data => {
                if(data.success) window.location.reload();
            });
        }
    }

    function showShareModal(xp, lessonTitle) {
        const modal = document.getElementById('share-modal');
        document.getElementById('share-xp-text').textContent = xp;
        document.getElementById('share-lesson-text').textContent = lessonTitle;
        
        // Configurar links de compartilhamento
        const text = encodeURIComponent(`Acabei de completar a lição "${lessonTitle}" na JS Academy e ganhei ${xp} XP! 🚀 #JSAcademy #JavaScript #Coding`);
        const url = encodeURIComponent(window.location.origin);
        
        document.getElementById('share-x').href = `https://twitter.com/intent/tweet?text=${text}&url=${url}`;
        document.getElementById('share-linkedin').href = `https://www.linkedin.com/sharing/share-offsite/?url=${url}`;
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
</script>

<!-- Modal de Compartilhamento -->
<div id="share-modal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4 bg-zinc-950/90 backdrop-blur-md">
    <div class="w-full max-w-sm bg-zinc-900 border border-white/10 rounded-[3rem] p-10 text-center shadow-2xl">
        <div class="w-20 h-20 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
            <span class="text-4xl text-green-500">🎉</span>
        </div>
        <h3 class="text-3xl font-black text-white uppercase italic tracking-tighter">Parabéns!</h3>
        <p class="text-zinc-400 mt-4 leading-relaxed text-sm">Você concluiu <br><span id="share-lesson-text" class="text-white font-bold text-lg"></span> <br>e faturou <span id="share-xp-text" class="text-yellow-500 font-bold"></span> XP!</p>
        
        <div class="grid grid-cols-2 gap-4 mt-10">
            <a id="share-x" target="_blank" class="flex flex-col items-center gap-3 p-4 bg-white/5 rounded-2xl hover:bg-white/10 transition-all border border-white/5 group">
                <span class="text-2xl group-hover:scale-110 transition-transform">𝕏</span>
                <span class="text-[9px] font-bold uppercase text-zinc-500">Postar no X</span>
            </a>
            <a id="share-linkedin" target="_blank" class="flex flex-col items-center gap-3 p-4 bg-white/5 rounded-2xl hover:bg-white/10 transition-all border border-white/5 group">
                <span class="text-2xl text-blue-500 group-hover:scale-110 transition-transform">in</span>
                <span class="text-[9px] font-bold uppercase text-zinc-500">LinkedIn</span>
            </a>
        </div>

        <button onclick="window.location.reload()" class="w-full py-4 mt-8 bg-white text-zinc-950 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-zinc-200 transition-all">
            Continuar Aprendendo
        </button>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // === INTRO ANIMATION SEQUENCE ===
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!prefersReduced) {
        // Headline stagger
        gsap.from('.hero-headline', {
            y: 80, opacity: 0, duration: 1.1, ease: 'power3.out', delay: 0.1
        });
        gsap.from('.hero-subtitle', {
            y: 40, opacity: 0, duration: 0.9, ease: 'power3.out', delay: 0.35
        });
        // Stats cards stagger
        gsap.from('.stat-card', {
            y: 50, opacity: 0, stagger: 0.1, duration: 0.7, ease: 'power2.out', delay: 0.5
        });
        // Section title
        gsap.from('.section-title', {
            x: -30, opacity: 0, duration: 0.8, ease: 'power2.out', delay: 0.8
        });
        // Track cards scroll reveal
        gsap.from('.track-card', {
            scrollTrigger: { trigger: '.track-card', start: 'top 85%', toggleActions: 'play none none none' },
            y: 60, opacity: 0, stagger: 0.15, duration: 0.8, ease: 'power3.out'
        });
        // Editor panel slide in
        gsap.from('#editor-section', {
            scrollTrigger: { trigger: '#editor-section', start: 'top 90%' },
            x: 40, opacity: 0, duration: 0.9, ease: 'power3.out'
        });
        // Progress bar fill animation
        gsap.to('.progress-bar-fill', {
            scrollTrigger: { trigger: '.progress-bar-fill', start: 'top 85%' },
            scaleX: 1, duration: 1.2, ease: 'power2.out', transformOrigin: 'left'
        });
        // Lesson buttons hover pulse
        document.querySelectorAll('.lesson-btn').forEach(btn => {
            btn.addEventListener('mouseenter', () => {
                if (!btn.disabled) gsap.to(btn, { scale: 1.02, duration: 0.2, ease: 'power1.out' });
            });
            btn.addEventListener('mouseleave', () => {
                gsap.to(btn, { scale: 1, duration: 0.2, ease: 'power1.out' });
            });
        });
    }

    // === INTRO SKIP LOGIC ===
    const introOverlay = document.getElementById('intro-overlay');
    if (introOverlay) {
        const hasSeenIntro = localStorage.getItem('jsacademy_intro_seen');
        if (hasSeenIntro) {
            introOverlay.remove();
        } else {
            introOverlay.classList.remove('hidden');
            // Auto-dismiss after 8s
            const autoDismiss = setTimeout(() => dismissIntro(), 8000);
            document.getElementById('intro-skip-btn')?.addEventListener('click', () => {
                clearTimeout(autoDismiss);
                dismissIntro();
            });
            document.getElementById('intro-start-btn')?.addEventListener('click', () => {
                clearTimeout(autoDismiss);
                dismissIntro();
                document.getElementById('o-caminho')?.scrollIntoView({ behavior: 'smooth' });
            });
        }
    }

    function dismissIntro() {
        const overlay = document.getElementById('intro-overlay');
        if (!overlay) return;
        localStorage.setItem('jsacademy_intro_seen', '1');
        gsap.to(overlay, {
            opacity: 0, y: -30, duration: 0.6, ease: 'power2.in',
            onComplete: () => overlay.remove()
        });
    }

    // === VALIDATE BTN microinteraction ===
    const validateBtn = document.getElementById('validate-btn');
    if (validateBtn) {
        validateBtn.addEventListener('mouseenter', () => gsap.to(validateBtn, { scale: 1.02, duration: 0.15 }));
        validateBtn.addEventListener('mouseleave', () => gsap.to(validateBtn, { scale: 1, duration: 0.15 }));
        validateBtn.addEventListener('mousedown', () => gsap.to(validateBtn, { scale: 0.97, duration: 0.1 }));
        validateBtn.addEventListener('mouseup', () => gsap.to(validateBtn, { scale: 1, duration: 0.1 }));
    }

    // === XP COUNTER animation ===
    window.animateXP = function(targetXp) {
        const xpEl = document.getElementById('xp-display');
        if (!xpEl) return;
        const start = parseInt(xpEl.textContent) || 0;
        gsap.to({ val: start }, {
            val: targetXp, duration: 1.2, ease: 'power2.out',
            onUpdate: function() { xpEl.textContent = Math.round(this.targets()[0].val); }
        });
    };

    // === STREAK NUMBER animation ===
    const streakEl = document.querySelector('.streak-count');
    if (streakEl && !prefersReduced) {
        gsap.from(streakEl, { textContent: 0, duration: 1.5, ease: 'power2.out', snap: { textContent: 1 }, scrollTrigger: { trigger: streakEl, start: 'top 90%' }});
    }
});
</script>
@endsection
