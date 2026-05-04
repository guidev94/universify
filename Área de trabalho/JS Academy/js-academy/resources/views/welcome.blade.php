@extends('layouts.app')

@section('title', 'JS Academy — Aprenda JavaScript como um Dev de Elite')

@section('head')
<meta name="description" content="8 trilhas estruturadas, 38 lições com exemplos reais do mercado. Do zero ao TypeScript. Aprenda JavaScript como os devs do Stripe, Netflix e Vercel. Grátis.">
<meta property="og:title" content="JS Academy — Aprenda JavaScript como um Dev de Elite">
<meta property="og:description" content="8 trilhas, 38 lições, 12 desafios técnicos. Do zero ao TypeScript. Aprenda com exemplos reais do mercado.">
<meta property="og:url" content="{{ url('/') }}">
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
    {{-- ===== HERO ===== --}}
    <header class="hero-headline mb-10 relative">
        {{-- Glow decorativo --}}
        <div class="absolute -top-40 left-0 w-[500px] h-[300px] bg-yellow-400/5 rounded-full blur-3xl pointer-events-none -z-10"></div>

        <div class="flex flex-col lg:flex-row gap-10 lg:items-center justify-between">
            <div class="max-w-2xl">
                {{-- Badge de plataforma --}}
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-yellow-400/10 border border-yellow-400/20 mb-5">
                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 animate-pulse"></span>
                    <span class="text-[10px] font-black text-yellow-500 uppercase tracking-[0.25em]">100% Gratuito</span>
                </div>

                <h1 class="text-5xl md:text-[4.5rem] font-black text-white leading-[0.88] tracking-tighter">
                    Domine<br>
                    <span class="bg-gradient-to-r from-yellow-400 via-orange-400 to-yellow-500 bg-clip-text text-transparent italic">JavaScript</span><br>
                    <span class="text-zinc-500 text-4xl md:text-5xl">de verdade.</span>
                </h1>

                <p class="hero-subtitle mt-5 text-base text-zinc-400 max-w-lg leading-relaxed">
                    38 lições com exemplos reais do <span class="text-white font-semibold">Stripe</span>, <span class="text-white font-semibold">Netflix</span> e <span class="text-white font-semibold">Vercel</span>. Do zero ao TypeScript.
                </p>

                {{-- Stats --}}
                <div class="flex items-center gap-5 mt-7 pt-6 border-t border-white/5 flex-wrap">
                    @php
                        $totalLessons = \App\Models\Lesson::count();
                        $completedCount = $user ? $user->progress()->where('status', 'completed')->count() : 0;
                        $percent = $totalLessons > 0 ? round(($completedCount / $totalLessons) * 100) : 0;
                    @endphp
                    <div class="text-center">
                        <p class="text-xl font-black text-white">{{ \App\Models\User::count() }}+</p>
                        <p class="text-[9px] text-zinc-600 uppercase tracking-wider mt-0.5">Devs</p>
                    </div>
                    <div class="w-px h-6 bg-white/8"></div>
                    <div class="text-center">
                        <p class="text-xl font-black text-white">{{ $totalLessons }}</p>
                        <p class="text-[9px] text-zinc-600 uppercase tracking-wider mt-0.5">Lições</p>
                    </div>
                    <div class="w-px h-6 bg-white/8"></div>
                    <div class="text-center">
                        <p class="text-xl font-black text-white">8</p>
                        <p class="text-[9px] text-zinc-600 uppercase tracking-wider mt-0.5">Módulos</p>
                    </div>
                    <div class="w-px h-6 bg-white/8"></div>
                    <div class="text-center">
                        <p class="text-xl font-black text-white">12</p>
                        <p class="text-[9px] text-zinc-600 uppercase tracking-wider mt-0.5">Desafios</p>
                    </div>
                </div>
            </div>

            {{-- Cards compactos de gamificação --}}
            <div class="flex flex-row lg:flex-col gap-3 flex-wrap lg:min-w-[210px]">
                {{-- Progresso --}}
                <div class="stat-card flex-1 lg:flex-none rounded-2xl border border-white/8 bg-zinc-900/50 p-4 flex items-center gap-3 min-w-[150px]">
                    <div class="w-9 h-9 rounded-xl bg-yellow-400/10 border border-yellow-400/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                    <div class="flex-grow min-w-0">
                        <p class="text-[9px] text-zinc-600 uppercase tracking-wider">Progresso</p>
                        <div class="flex items-center gap-2 mt-1">
                            <div class="flex-grow h-1 bg-zinc-800 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full" style="width: {{ $percent }}%"></div>
                            </div>
                            <span class="text-xs font-black text-white flex-shrink-0">{{ $percent }}%</span>
                        </div>
                    </div>
                </div>

                {{-- Streak --}}
                <div class="stat-card flex-1 lg:flex-none rounded-2xl border border-white/8 bg-zinc-900/50 p-4 flex items-center gap-3 min-w-[130px]">
                    <div class="w-9 h-9 rounded-xl bg-orange-500/10 border border-orange-500/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-orange-400 streak-fire" fill="currentColor" viewBox="0 0 24 24"><path d="M13.5 2c0 0 1.5 3 1.5 5.5 0 1.5-.5 2.5-1.5 3 .5-2-1-3-1-3-1 2-2 4.5-2 6.5 0 2 1 3.5 2.5 4.5-.5-1-.5-2 0-3 1 1.5 2 2.5 2 4 0 2-1.5 3.5-3.5 3.5S8 21.5 8 19.5c0-3 1.5-5.5 4-7 0 1.5.5 2.5 1.5 3C12 14 12 12.5 12 10.5c0-3 1.5-8.5 1.5-8.5z"/></svg>
                    </div>
                    <div>
                        <p class="text-[9px] text-zinc-600 uppercase tracking-wider">Streak</p>
                        <p class="text-lg font-black text-white leading-tight"><span class="streak-count">{{ $user->streak ?? 0 }}</span> <span class="text-zinc-500 text-xs font-normal">dias</span></p>
                    </div>
                </div>

                {{-- XP --}}
                <div class="stat-card flex-1 lg:flex-none rounded-2xl border border-white/8 bg-zinc-900/50 p-4 flex items-center gap-3 min-w-[130px]">
                    <div class="w-9 h-9 rounded-xl bg-violet-500/10 border border-violet-500/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-violet-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </div>
                    <div>
                        <p class="text-[9px] text-zinc-600 uppercase tracking-wider">XP Total</p>
                        <p class="text-lg font-black text-white leading-tight">{{ number_format($user->xp ?? 0) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- ===== BARRA DE AÇÃO RÁPIDA (Próxima Aula + Ranking) ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-8">
        {{-- Continuar --}}
        <div class="rounded-2xl border border-cyan-500/15 bg-cyan-500/5 hover:border-cyan-500/25 transition-all p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-cyan-500/15 border border-cyan-500/20 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex-grow min-w-0">
                <p class="text-[9px] text-cyan-400 uppercase tracking-widest font-bold">Continue de onde parou</p>
                @if($nextLesson)
                <p class="text-sm font-bold text-white truncate mt-0.5">{{ $nextLesson->title }}</p>
                <p class="text-[10px] text-zinc-600">{{ $nextLesson->track->name }}</p>
                @else
                <p class="text-sm font-bold text-white mt-0.5">Todas concluídas!</p>
                @endif
            </div>
            @if($nextLesson)
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
                data-lesson-xp="{{ $nextLesson->xp_reward }}"
                class="lesson-btn flex-shrink-0 px-4 py-2 bg-cyan-400 text-zinc-950 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-cyan-300 transition-all active:scale-95">
                Continuar
            </button>
            @endif
        </div>

        {{-- Ranking --}}
        <a href="{{ route('ranking') }}" class="rounded-2xl border border-orange-500/15 bg-orange-500/5 hover:border-orange-500/25 transition-all p-4 flex items-center gap-4 group">
            <div class="w-10 h-10 rounded-xl bg-orange-500/15 border border-orange-500/20 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div class="flex-grow">
                <p class="text-[9px] text-orange-400 uppercase tracking-widest font-bold">Ranking Global</p>
                <p class="text-sm font-black text-white mt-0.5">#{{ $rankingPosition }} <span class="text-zinc-500 text-xs font-normal">entre {{ \App\Models\User::count() }} devs</span></p>
            </div>
            <svg class="w-4 h-4 text-zinc-600 group-hover:text-orange-400 group-hover:translate-x-0.5 transition-all flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- O Caminho — Trilhas estilo Duolingo -->
        <div id="o-caminho" class="lg:col-span-5 order-2 lg:order-1">
            @php
                $totalLicoes = \App\Models\Lesson::count();
                $totalTrilhas = $tracks->count();
                // Ícones únicos por módulo (Heroicons SVG paths)
                $trackIcons = [
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>',
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>',
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>',
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M5 3l14 9-14 9V3z"/>',
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>',
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>',
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
                ];
                $trackColors = [
                    ['bg' => 'bg-violet-500/15', 'border' => 'border-violet-500/30', 'text' => 'text-violet-400', 'progress' => 'bg-violet-500'],
                    ['bg' => 'bg-blue-500/15', 'border' => 'border-blue-500/30', 'text' => 'text-blue-400', 'progress' => 'bg-blue-500'],
                    ['bg' => 'bg-cyan-500/15', 'border' => 'border-cyan-500/30', 'text' => 'text-cyan-400', 'progress' => 'bg-cyan-500'],
                    ['bg' => 'bg-yellow-500/15', 'border' => 'border-yellow-500/30', 'text' => 'text-yellow-400', 'progress' => 'bg-yellow-500'],
                    ['bg' => 'bg-orange-500/15', 'border' => 'border-orange-500/30', 'text' => 'text-orange-400', 'progress' => 'bg-orange-500'],
                    ['bg' => 'bg-green-500/15', 'border' => 'border-green-500/30', 'text' => 'text-green-400', 'progress' => 'bg-green-500'],
                    ['bg' => 'bg-pink-500/15', 'border' => 'border-pink-500/30', 'text' => 'text-pink-400', 'progress' => 'bg-pink-500'],
                    ['bg' => 'bg-indigo-500/15', 'border' => 'border-indigo-500/30', 'text' => 'text-indigo-400', 'progress' => 'bg-indigo-500'],
                ];
            @endphp

            {{-- Cabeçalho da seção --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="section-title text-2xl font-black text-white tracking-tight">Sua Jornada</h2>
                    <p class="text-[11px] text-zinc-600 mt-0.5 font-mono">{{ $totalTrilhas }} módulos · {{ $totalLicoes }} aulas</p>
                </div>
                {{-- Barra de XP global --}}
                @php
                    $globalPercent = $totalLicoes > 0 ? round((count($completedLessonIds ?? []) / $totalLicoes) * 100) : 0;
                @endphp
                <div class="text-right">
                    <span class="text-[10px] font-mono text-zinc-500">{{ $globalPercent }}% completo</span>
                    <div class="w-24 h-1.5 bg-zinc-800 rounded-full mt-1 overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full transition-all duration-700" style="width: {{ $globalPercent }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Lista de módulos estilo Duolingo --}}
            <div class="space-y-3">
                @foreach($tracks as $index => $track)
                @php
                    $isCompleted = $user ? $user->badges->contains('track_id', $track->id) : false;
                    $isLocked = ($index > 0 && ($user ? !$user->badges->contains('track_id', $tracks[$index-1]->id) : true));
                    $trackLessonCount = $track->lessons->count();
                    $trackXP = $track->lessons->sum('xp_reward');
                    $trackCompletedCount = collect($track->lessons)->filter(fn($l) => in_array($l->id, $completedLessonIds ?? []))->count();
                    $trackPercent = $trackLessonCount > 0 ? round(($trackCompletedCount / $trackLessonCount) * 100) : 0;
                    $isOpen = !$isLocked && !$isCompleted;
                    $color = $trackColors[$index % count($trackColors)];
                    $iconPath = $trackIcons[$index % count($trackIcons)];
                @endphp

                @if($isLocked)
                {{-- ═══ MÓDULO BLOQUEADO ═══ --}}
                <div class="track-card">
                    <div class="rounded-2xl border border-white/5 bg-zinc-950/50 overflow-hidden">
                        <div class="px-5 py-4 flex items-center gap-4">
                            {{-- Ícone cadeado --}}
                            <div class="flex-shrink-0 w-11 h-11 rounded-2xl bg-zinc-900 border border-white/5 flex items-center justify-center">
                                <svg class="w-5 h-5 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>

                            {{-- Info --}}
                            <div class="flex-grow min-w-0">
                                <span class="text-[9px] font-mono text-zinc-700 uppercase tracking-wider">Módulo {{ $index + 1 }}</span>
                                <p class="text-sm font-semibold text-zinc-600 leading-tight truncate pr-2">{{ $track->name }}</p>
                                <p class="text-[10px] text-zinc-700 mt-0.5">
                                    {{-- Indica qual módulo precisa ser concluído --}}
                                    @if(auth()->check())
                                    Conclua o Módulo {{ $index }} para desbloquear
                                    @else
                                    Complete o Módulo 1 e crie sua conta
                                    @endif
                                </p>
                            </div>

                            {{-- Ação --}}
                            <div class="flex-shrink-0">
                                @if(!auth()->check())
                                <button @click="$dispatch('open-register')"
                                    class="px-3 py-1.5 rounded-xl bg-yellow-400/10 border border-yellow-400/20 text-[9px] font-black text-yellow-500 uppercase tracking-widest hover:bg-yellow-400/15 transition-all whitespace-nowrap">
                                    Criar conta
                                </button>
                                @else
                                <div class="flex items-center gap-1.5 text-[9px] font-mono text-zinc-700">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    {{ $trackLessonCount }} aulas · +{{ number_format($trackXP) }} XP
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @else
                {{-- ═══ MÓDULO ATIVO (desbloqueado ou concluído) ═══ --}}
                <div class="track-card" x-data="{ open: {{ $isOpen ? 'true' : 'false' }} }">
                    <div class="rounded-2xl border transition-all duration-300 overflow-hidden
                        {{ $isCompleted ? 'border-green-500/20 bg-green-500/[0.03]' : 'border-white/8 bg-zinc-900/50 hover:border-white/12' }}">

                        {{-- Header clicável --}}
                        <button
                            @click="open = !open"
                            class="w-full px-5 py-4 flex items-center gap-4 text-left cursor-pointer"
                        >
                            {{-- Ícone do módulo --}}
                            <div class="flex-shrink-0 w-11 h-11 rounded-2xl flex items-center justify-center border transition-all
                                {{ $isCompleted ? 'bg-green-500/15 border-green-500/30' : $color['bg'] . ' ' . $color['border'] }}">
                                @if($isCompleted)
                                <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                @else
                                <svg class="w-5 h-5 {{ $color['text'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">{!! $iconPath !!}</svg>
                                @endif
                            </div>

                            {{-- Info --}}
                            <div class="flex-grow min-w-0">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <span class="text-[9px] font-mono text-zinc-500 uppercase tracking-wider">Módulo {{ $index + 1 }}</span>
                                    @if($isCompleted)
                                    <span class="text-[8px] font-black text-green-500 bg-green-500/10 px-1.5 py-0.5 rounded-full">Concluído</span>
                                    @elseif($trackCompletedCount > 0)
                                    <span class="text-[8px] font-black text-yellow-500 bg-yellow-500/10 px-1.5 py-0.5 rounded-full">Em progresso</span>
                                    @endif
                                </div>
                                <p class="text-sm font-bold text-white leading-tight truncate pr-2">{{ $track->name }}</p>
                                {{-- Barra de progresso --}}
                                <div class="flex items-center gap-2 mt-1.5">
                                    <div class="flex-grow h-1 bg-zinc-800 rounded-full overflow-hidden">
                                        <div class="h-full {{ $isCompleted ? 'bg-green-500' : $color['progress'] }} rounded-full transition-all duration-700"
                                            style="width: {{ $trackPercent }}%"></div>
                                    </div>
                                    <span class="text-[9px] font-mono {{ $isCompleted ? 'text-green-500' : 'text-zinc-600' }} flex-shrink-0">{{ $trackCompletedCount }}/{{ $trackLessonCount }}</span>
                                </div>
                            </div>

                            {{-- Chevron + XP total --}}
                            <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                <div :class="open ? 'rotate-180' : ''" class="transition-transform duration-250 text-zinc-500">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                                <span class="text-[9px] font-mono text-zinc-700">+{{ number_format($trackXP) }} XP</span>
                            </div>
                        </button>

                        {{-- Lições colapsáveis --}}
                        <div x-show="open" x-collapse>
                            <div class="px-4 pb-4 pt-1 space-y-1 border-t border-white/5">
                                @foreach($track->lessons->sortBy('order') as $lesson)
                                @php
                                    $lessonCompleted = in_array($lesson->id, $completedLessonIds ?? []);
                                    $completedInTrack = collect($completedLessonIds ?? [])->intersect($track->lessons->pluck('id'))->count();
                                    $isNextLesson = !$lessonCompleted && $completedInTrack === $lesson->order - 1;
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
                                    data-lesson-xp="{{ $lesson->xp_reward }}"
                                    class="lesson-btn w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all text-left group/lesson
                                        {{ $lessonCompleted ? 'bg-green-500/5 border border-green-500/15' : 'bg-zinc-950/40 border border-white/5 hover:border-yellow-400/25 hover:bg-yellow-400/3' }}
                                        {{ $isNextLesson ? 'border-yellow-400/25 ring-1 ring-yellow-400/15' : '' }}">
                                    {{-- Indicador de estado --}}
                                    <div class="flex-shrink-0 w-6 h-6 rounded-lg flex items-center justify-center
                                        {{ $lessonCompleted ? 'bg-green-500' : ($isNextLesson ? 'bg-yellow-400/20 border border-yellow-400/30' : 'bg-zinc-800 border border-white/5') }}">
                                        @if($lessonCompleted)
                                        <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        @elseif($isNextLesson)
                                        <svg class="w-3 h-3 text-yellow-400" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        @else
                                        <span class="text-[8px] font-black text-zinc-600">{{ $lesson->order }}</span>
                                        @endif
                                    </div>
                                    {{-- Título --}}
                                    <span class="text-xs font-medium flex-grow text-left transition-colors
                                        {{ $lessonCompleted ? 'text-zinc-500 line-through decoration-zinc-700' : ($isNextLesson ? 'text-white font-semibold' : 'text-zinc-400 group-hover/lesson:text-white') }}">{{ $lesson->title }}</span>
                                    {{-- XP --}}
                                    <span class="flex-shrink-0 text-[9px] font-mono {{ $lessonCompleted ? 'text-green-600' : 'text-zinc-700' }}">+{{ $lesson->xp_reward }}</span>
                                </button>
                                @endforeach

                                {{-- Pular módulo --}}
                                @if(!$isCompleted)
                                <button onclick="skipTrack({{ $track->id }})"
                                    class="w-full mt-2 py-2 text-[9px] font-medium text-zinc-700 hover:text-zinc-500 transition-colors uppercase tracking-widest border border-dashed border-zinc-800 hover:border-zinc-700 rounded-xl">
                                    Pular módulo · 200 diamantes
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                @endforeach
            </div>
        </div>

        <!-- Lateral: Arena de Desafio -->
        <div class="lg:col-span-7 order-1 lg:order-2">
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
                <button id="validate-btn" class="w-full py-5 bg-white text-zinc-950 rounded-2xl font-black text-base uppercase tracking-widest hover:bg-yellow-400 transition-all active:scale-95 shadow-2xl shadow-white/5 border-b-4 border-zinc-300 hover:border-yellow-600 flex items-center justify-center gap-3">
                    <span id="validate-btn-text">Selecione uma lição</span>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
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

    let _skipTrackId = null;

    function skipTrack(trackId) {
        _skipTrackId = trackId;
        const modal = document.getElementById('skip-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Mostrar saldo de diamantes
        const balanceEl = document.getElementById('skip-diamond-balance');
        const diamondDisplay = document.querySelector('[data-diamonds]');
        if (diamondDisplay) {
            balanceEl.textContent = `Seu saldo atual: ${diamondDisplay.dataset.diamonds} diamantes`;
        } else {
            balanceEl.textContent = 'Custo: 200 diamantes';
        }

        // Animação de entrada
        if (typeof gsap !== 'undefined') {
            gsap.fromTo('#skip-modal-inner', { scale: 0.85, opacity: 0, y: 20 }, { scale: 1, opacity: 1, y: 0, duration: 0.35, ease: 'back.out(1.5)' });
        }

        document.getElementById('skip-confirm-btn').onclick = confirmarSkip;
    }

    function fecharSkipModal() {
        const modal = document.getElementById('skip-modal');
        if (typeof gsap !== 'undefined') {
            gsap.to('#skip-modal-inner', { scale: 0.9, opacity: 0, y: 10, duration: 0.2, ease: 'power2.in',
                onComplete: () => { modal.classList.add('hidden'); modal.classList.remove('flex'); }
            });
        } else {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    async function confirmarSkip() {
        const btn = document.getElementById('skip-confirm-btn');
        btn.textContent = 'Pulando...';
        btn.disabled = true;

        try {
            const res = await fetch(`/progress/skip-track/${_skipTrackId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            const data = await res.json();

            if (data.success) {
                fecharSkipModal();
                mostrarToast('Módulo pulado! Badge desbloqueada.', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                mostrarToast(data.message || 'Diamantes insuficientes.', 'error');
                fecharSkipModal();
            }
        } catch (e) {
            mostrarToast('Erro ao processar. Tente novamente.', 'error');
            fecharSkipModal();
        }
    }

    function mostrarToast(mensagem, tipo = 'success') {
        const toast = document.createElement('div');
        const cores = {
            success: 'bg-green-500/10 border-green-500/30 text-green-400',
            error: 'bg-red-500/10 border-red-500/30 text-red-400',
            info: 'bg-blue-500/10 border-blue-500/30 text-blue-400',
        };
        toast.className = `fixed bottom-6 left-1/2 -translate-x-1/2 z-[300] px-6 py-3 rounded-2xl border font-bold text-sm ${cores[tipo]} backdrop-blur-xl shadow-2xl`;
        toast.textContent = mensagem;
        document.body.appendChild(toast);

        if (typeof gsap !== 'undefined') {
            gsap.fromTo(toast, { y: 20, opacity: 0 }, { y: 0, opacity: 1, duration: 0.3, ease: 'power2.out' });
            gsap.to(toast, { y: 20, opacity: 0, duration: 0.3, delay: 2.5, ease: 'power2.in', onComplete: () => toast.remove() });
        } else {
            setTimeout(() => toast.remove(), 3000);
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

{{-- Modal: Pular Módulo --}}
<div id="skip-modal" class="fixed inset-0 z-[250] hidden items-center justify-center p-4 bg-zinc-950/90 backdrop-blur-md">
    <div class="w-full max-w-sm bg-zinc-900 border border-white/10 rounded-[2rem] p-8 text-center shadow-2xl" id="skip-modal-inner">
        <div class="w-16 h-16 bg-orange-400/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-orange-400/20">
            <svg class="w-8 h-8 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
        </div>
        <h3 class="text-xl font-black text-white uppercase tracking-tight mb-2">Pular este módulo?</h3>
        <p class="text-zinc-500 text-sm leading-relaxed mb-2">Você usará <span class="text-orange-400 font-bold">200 diamantes</span> para pular e receber o badge sem completar as aulas.</p>
        <p id="skip-diamond-balance" class="text-xs text-zinc-600 mb-6">Seu saldo: carregando...</p>
        <div class="flex gap-3">
            <button onclick="fecharSkipModal()" class="flex-1 py-3 bg-zinc-800 text-zinc-400 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-zinc-700 transition-all border border-white/5">
                Cancelar
            </button>
            <button id="skip-confirm-btn" class="flex-1 py-3 bg-orange-400 text-zinc-950 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-orange-300 transition-all">
                Pular Módulo
            </button>
        </div>
    </div>
</div>

<!-- Modal de Compartilhamento -->
<div id="share-modal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4 bg-zinc-950/90 backdrop-blur-md">
    <div class="w-full max-w-sm bg-zinc-900 border border-white/10 rounded-[3rem] p-10 text-center shadow-2xl">
        <div class="w-20 h-20 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
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
