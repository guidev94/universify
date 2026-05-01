@extends('layouts.app')

@section('title', $profileUser->name . ' — JS Academy')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    {{-- Cabeçalho do Perfil --}}
    <div class="profile-header bento-card p-8 mb-8 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-yellow-400/5 to-transparent pointer-events-none"></div>
        <div class="relative flex flex-col sm:flex-row items-start sm:items-center gap-6">
            {{-- Avatar --}}
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center text-4xl font-black text-zinc-950 shadow-xl shadow-yellow-400/20 flex-shrink-0">
                {{ mb_substr($profileUser->name, 0, 1) }}
            </div>
            {{-- Info --}}
            <div class="flex-grow">
                <div class="flex items-start justify-between flex-wrap gap-3">
                    <div>
                        <h1 class="text-2xl font-black text-white tracking-tight">{{ $profileUser->name }}</h1>
                        <div class="flex items-center gap-3 mt-2 flex-wrap">
                            <span class="text-[9px] font-black uppercase tracking-[0.2em] px-2.5 py-1 rounded-full
                                @if(str_contains($profileUser->league ?? '', 'Gold')) bg-yellow-400/10 text-yellow-500 border border-yellow-400/20
                                @elseif(str_contains($profileUser->league ?? '', 'Silver')) bg-zinc-400/10 text-zinc-400 border border-zinc-400/20
                                @else bg-amber-700/10 text-amber-600 border border-amber-700/20 @endif">
                                {{ $profileUser->league ?? 'Bronze' }}
                            </span>
                            <span class="text-[10px] font-mono text-zinc-500">Nível {{ $profileUser->level ?? 1 }}</span>
                            <span class="text-[10px] font-mono text-zinc-600">Rank #{{ $rankingPosition }}</span>
                        </div>
                    </div>
                    @if(auth()->check() && auth()->id() === $profileUser->id)
                    <a href="/profile" class="btn-outline text-[9px] py-2 px-4 flex items-center gap-1.5">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Editar Perfil
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        <div class="profile-stat bento-card p-5 text-center">
            <p class="text-3xl font-black text-white">{{ number_format($profileUser->xp) }}</p>
            <p class="text-[9px] font-mono text-zinc-500 uppercase tracking-wider mt-1">XP Total</p>
        </div>
        <div class="profile-stat bento-card p-5 text-center">
            <p class="text-3xl font-black text-white">{{ $profileUser->streak ?? 0 }}</p>
            <p class="text-[9px] font-mono text-zinc-500 uppercase tracking-wider mt-1">Dias Streak</p>
        </div>
        <div class="profile-stat bento-card p-5 text-center">
            <p class="text-3xl font-black text-white">{{ $completedCount }}</p>
            <p class="text-[9px] font-mono text-zinc-500 uppercase tracking-wider mt-1">Aulas Feitas</p>
        </div>
        <div class="profile-stat bento-card p-5 text-center">
            <p class="text-3xl font-black text-white">{{ $badges->count() }}</p>
            <p class="text-[9px] font-mono text-zinc-500 uppercase tracking-wider mt-1">Conquistas</p>
        </div>
    </div>

    {{-- Progresso --}}
    <div class="profile-progress bento-card p-6 mb-8">
        <div class="flex justify-between items-center mb-3">
            <span class="text-[10px] font-mono text-zinc-500 uppercase tracking-[0.3em]">Progresso na Trilha</span>
            <span class="text-sm font-black text-white">{{ $percent }}%</span>
        </div>
        <div class="h-2 bg-zinc-800 rounded-full overflow-hidden">
            <div class="h-full bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full progress-bar-fill transition-all duration-1000" style="width: {{ $percent }}%"></div>
        </div>
        <p class="text-[10px] text-zinc-600 mt-2">{{ $completedCount }} de {{ $totalLessons }} aulas concluídas</p>
    </div>

    {{-- Conquistas --}}
    @if($badges->count() > 0)
    <div class="profile-badges bento-card p-6">
        <h2 class="text-[10px] font-mono text-zinc-500 uppercase tracking-[0.3em] mb-5">Conquistas Desbloqueadas</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            @foreach($badges as $badge)
            <div class="flex items-center gap-3 p-3 rounded-xl bg-zinc-950/40 border border-white/5 hover:border-yellow-400/20 transition-all">
                <div class="w-10 h-10 rounded-xl bg-yellow-400/10 border border-yellow-400/20 flex items-center justify-center text-xl flex-shrink-0">
                    {{ $badge->icon }}
                </div>
                <div>
                    <p class="text-xs font-black text-white leading-tight">{{ $badge->name }}</p>
                    <p class="text-[9px] text-zinc-600 mt-0.5">{{ $badge->track->name ?? '' }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="mt-8 text-center">
        <a href="{{ route('ranking') }}" class="btn-outline inline-flex items-center gap-2 text-xs">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Ver Ranking Completo
        </a>
    </div>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) return;
    gsap.from('.profile-header', { y: 50, opacity: 0, duration: 0.9, ease: 'power3.out' });
    gsap.from('.profile-stat', { y: 30, opacity: 0, stagger: 0.08, duration: 0.6, ease: 'power2.out', delay: 0.3 });
    gsap.from('.profile-progress', { y: 20, opacity: 0, duration: 0.6, ease: 'power2.out', delay: 0.6 });
    gsap.from('.profile-badges', { y: 20, opacity: 0, duration: 0.6, ease: 'power2.out', delay: 0.8 });
});
</script>
@endsection
@endsection
