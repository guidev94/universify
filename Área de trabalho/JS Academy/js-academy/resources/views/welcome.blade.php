@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Cabeçalho Principal -->
    <header class="mb-20 flex flex-col md:flex-row justify-between items-start md:items-end gap-10">
        <div class="max-w-3xl">
            <h1 class="text-6xl md:text-8xl font-black text-white leading-[0.9] tracking-tighter light:text-zinc-950">
                Evolua seu <span class="bg-gradient-to-r from-yellow-400 to-orange-500 bg-clip-text text-transparent italic">Javascript</span> <br>ao próximo nível.
            </h1>
            <p class="mt-6 text-xl text-zinc-500 max-w-xl leading-relaxed light:text-zinc-600">
                Plataforma profissional de aprendizado gamificado. Lógica de programação, padrões de projeto e maestria técnica.
            </p>
        </div>
        
        <!-- Estatísticas Rápidas -->
        <div class="grid grid-cols-2 sm:flex gap-4 w-full md:w-auto">
             <div class="bento-card p-4 min-w-[140px] border-emerald-500/20 shadow-lg shadow-emerald-500/5 group hover:border-emerald-500/40 transition-all opacity-100">
                <div class="flex justify-between items-start">
                    <span class="text-2xl">🎯</span>
                    <span class="text-[8px] font-black text-emerald-500 bg-emerald-500/10 px-1.5 py-0.5 rounded uppercase tracking-widest">+10% XP</span>
                </div>
                <div class="mt-4">
                    <span class="text-[9px] font-mono text-zinc-500 uppercase tracking-widest">Meta Diária</span>
                    <p class="text-2xl font-black text-white light:text-zinc-900">0/2</p>
                </div>
            </div>

             <div class="bento-card p-4 min-w-[140px] border-orange-500/20 shadow-lg shadow-orange-500/5 group hover:border-orange-500/40 transition-all opacity-100">
                <div class="flex justify-between items-start">
                    <span class="text-2xl animate-bounce">🔥</span>
                </div>
                <div class="mt-4">
                    <span class="text-[9px] font-mono text-zinc-500 uppercase tracking-widest">Ofensiva</span>
                    <p class="text-2xl font-black text-white light:text-zinc-900">{{ $user->streak ?? 0 }} Dias</p>
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
                    <a href="#editor-section" class="inline-flex px-8 py-3 bg-cyan-400 text-zinc-950 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:bg-cyan-300 transition-all active:scale-95 shadow-xl shadow-cyan-400/20">Continuar Agora</a>
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
        <div id="o-caminho" class="lg:col-span-8 order-2 lg:order-1">
            <h2 class="text-3xl font-black text-white mb-10 flex items-center gap-4">
                <div class="w-10 h-10 bg-yellow-400 rounded-xl flex items-center justify-center text-zinc-950 text-xl shadow-lg shadow-yellow-400/20">📍</div>
                O Caminho (Trilhas)
            </h2>
            
            <div class="space-y-12 pl-10 border-l-2 border-zinc-900 light:border-zinc-200 ml-4 relative">
                @foreach($tracks as $index => $track)
                @php
                    $isCompleted = $user ? $user->badges->contains('track_id', $track->id) : false;
                    $isLocked = ($index > 0 && ($user ? !$user->badges->contains('track_id', $tracks[$index-1]->id) : true));
                @endphp
                <div class="relative group">
                    <div class="absolute -left-[51px] top-0 w-6 h-6 rounded-full {{ $isCompleted ? 'bg-green-500' : ($isLocked ? 'bg-zinc-800' : 'bg-yellow-400') }} border-[6px] border-zinc-950 light:border-white z-10 transition-transform group-hover:scale-125 shadow-xl"></div>
                    
                    <div class="bento-card p-8 bg-zinc-900/40 light:bg-white hover:bg-zinc-900/60 transition-all relative overflow-hidden group opacity-100">
                        @if($isLocked)
                        <div class="absolute inset-0 bg-zinc-950/40 backdrop-blur-[2px] flex items-center justify-center z-20">
                            <span class="bg-zinc-800 border border-white/10 px-4 py-2 rounded-2xl text-xs font-bold text-zinc-500 uppercase tracking-widest shadow-2xl flex items-center gap-2 light:bg-white light:text-zinc-400">
                                🔒 Módulo Bloqueado
                            </span>
                        </div>
                        @endif

                        <div class="flex flex-col md:flex-row justify-between items-start gap-6">
                            <div class="max-w-md">
                                <span class="text-[10px] font-mono text-zinc-600 uppercase tracking-[0.3em]">Módulo {{ $index + 1 }}</span>
                                <h3 class="text-3xl font-black text-white light:text-zinc-900 mt-1 group-hover:text-yellow-400 transition-colors">{{ $track->name }}</h3>
                                <p class="text-zinc-500 text-sm mt-4 leading-relaxed light:text-zinc-600">{{ $track->description }}</p>
                            </div>
                            
                            <div class="flex items-center gap-4">
                                @if($track->badge)
                                <div class="w-16 h-16 rounded-2xl bg-zinc-800/50 light:bg-zinc-100 border border-white/5 flex items-center justify-center text-3xl shadow-inner group-hover:border-yellow-500/20 transition-all" title="{{ $track->badge->name }}">
                                    {{ $track->badge->icon }}
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Lista de Lições -->
                        <div class="mt-8 pt-8 border-t border-white/5 light:border-zinc-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4">
                            @foreach($track->lessons as $lesson)
                            @php
                                $lessonCompleted = $user ? $user->progress()->where('lesson_id', $lesson->id)->where('status', 'completed')->exists() : false;
                            @endphp
                            <button class="flex items-center gap-4 p-4 rounded-2xl bg-zinc-950/40 light:bg-zinc-50 border border-white/5 hover:border-yellow-400/40 transition-all text-left">
                                <div class="w-10 h-10 rounded-xl border flex-shrink-0 flex items-center justify-center {{ $lessonCompleted ? 'bg-green-500 border-green-400 shadow-lg shadow-green-500/20' : 'bg-zinc-800 border-white/5 group-hover:border-yellow-400/50' }}">
                                    <span class="text-sm font-black {{ $lessonCompleted ? 'text-white' : 'text-zinc-500' }}">{{ $lesson->order }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-white light:text-zinc-900">{{ $lesson->title }}</span>
                                    <span class="text-[9px] font-mono text-zinc-500 uppercase tracking-widest">{{ $lessonCompleted ? 'Concluído' : 'Disponível' }}</span>
                                </div>
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Lateral: IDE Sandbox -->
        <div class="lg:col-span-4 order-1 lg:order-2">
            <div id="editor-section" class="lg:sticky lg:top-24 bento-card bg-zinc-950 border-zinc-900 light:bg-white light:border-zinc-200 shadow-2xl overflow-hidden flex flex-col h-[650px] opacity-100">
                <div class="flex items-center justify-between p-4 bg-zinc-900/50 light:bg-zinc-50 border-b border-zinc-800 light:border-zinc-200">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-red-500/20 border border-red-500/50"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-500/20 border border-yellow-500/50"></div>
                        <div class="w-3 h-3 rounded-full bg-green-500/20 border border-green-500/50"></div>
                        <span class="ml-4 text-[10px] font-mono text-zinc-500 uppercase tracking-widest">sandbox.js</span>
                    </div>
                </div>

                <!-- Monaco Editor Container -->
                <div id="monaco-editor" class="flex-grow w-full"></div>

                <!-- Saída do Console -->
                <div class="h-40 bg-zinc-950 light:bg-zinc-900 border-t border-zinc-900 p-4 font-mono">
                    <h4 class="text-[10px] font-bold text-zinc-600 uppercase mb-3 tracking-widest flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-cyan-500 animate-pulse"></span>
                        Saída do Console
                    </h4>
                    <div id="console-output" class="text-[11px] text-zinc-500 overflow-y-auto max-h-24 custom-scrollbar">
                        > Aguardando execução de código...
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="p-4 grid grid-cols-1 gap-2">
                    <button id="validate-btn" class="w-full py-4 bg-white text-zinc-950 light:bg-zinc-950 light:text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-zinc-200 transition-all active:scale-95 shadow-xl shadow-white/5">Validar Código 🚀</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Forçar visibilidade caso GSAP falhe */
    .bento-card {
        border-radius: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.05);
        background: rgba(24, 24, 27, 0.4);
        backdrop-filter: blur(20px);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        opacity: 1 !important; /* Garantir que seja visível */
        visibility: visible !important;
    }
    
    .bento-card:hover { transform: translateY(-4px); }

    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #27272a;
        border-radius: 10px;
    }
</style>
@endsection
