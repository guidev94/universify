@extends('layouts.app')

@section('title', 'Ranking Global — JS Academy')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    {{-- Header --}}
    <header class="mb-16 text-center ranking-header">
        <span class="text-[10px] font-mono text-yellow-500 uppercase tracking-[0.4em] font-bold">Temporada Atual</span>
        <h1 class="text-5xl md:text-7xl font-black text-white uppercase italic tracking-tighter mt-4 leading-[0.9]">
            Ranking<br><span class="bg-gradient-to-r from-yellow-400 to-orange-500 bg-clip-text text-transparent">Global</span>
        </h1>
        <p class="text-zinc-500 mt-4 text-sm max-w-sm mx-auto">Os devs mais dedicados da plataforma. Estude todos os dias para subir posições.</p>
    </header>

    {{-- Podium Top 3 --}}
    @if($users->count() >= 3)
    <div class="flex items-end justify-center gap-4 mb-16 podium-section">
        {{-- 2nd place --}}
        <div class="flex flex-col items-center gap-3 podium-2">
            <div class="w-16 h-16 rounded-full bg-zinc-800 border-4 border-zinc-600 flex items-center justify-center text-2xl font-black text-zinc-400">
                {{ mb_substr($users[1]->name, 0, 1) }}
            </div>
            <div class="text-center">
                <p class="text-xs font-black text-white">{{ explode(' ', $users[1]->name)[0] }}</p>
                <p class="text-[10px] font-mono text-zinc-500">{{ number_format($users[1]->xp) }} XP</p>
            </div>
            <div class="w-20 h-24 bg-zinc-800/60 border border-zinc-700 rounded-t-2xl flex items-center justify-center">
                <span class="text-3xl font-black text-zinc-500">2</span>
            </div>
        </div>
        {{-- 1st place --}}
        <div class="flex flex-col items-center gap-3 podium-1">
            <div class="text-3xl animate-bounce">👑</div>
            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 border-4 border-yellow-400 flex items-center justify-center text-3xl font-black text-zinc-950 shadow-2xl shadow-yellow-400/40">
                {{ mb_substr($users[0]->name, 0, 1) }}
            </div>
            <div class="text-center">
                <p class="text-sm font-black text-white">{{ explode(' ', $users[0]->name)[0] }}</p>
                <p class="text-[10px] font-mono text-yellow-500 font-bold">{{ number_format($users[0]->xp) }} XP</p>
            </div>
            <div class="w-24 h-36 bg-gradient-to-b from-yellow-400/20 to-yellow-400/5 border border-yellow-400/30 rounded-t-2xl flex items-center justify-center shadow-xl shadow-yellow-400/10">
                <span class="text-4xl font-black text-yellow-400">1</span>
            </div>
        </div>
        {{-- 3rd place --}}
        <div class="flex flex-col items-center gap-3 podium-3">
            <div class="w-14 h-14 rounded-full bg-zinc-800 border-4 border-amber-700/50 flex items-center justify-center text-xl font-black text-amber-700">
                {{ mb_substr($users[2]->name, 0, 1) }}
            </div>
            <div class="text-center">
                <p class="text-xs font-black text-white">{{ explode(' ', $users[2]->name)[0] }}</p>
                <p class="text-[10px] font-mono text-zinc-500">{{ number_format($users[2]->xp) }} XP</p>
            </div>
            <div class="w-20 h-20 bg-zinc-800/60 border border-amber-700/30 rounded-t-2xl flex items-center justify-center">
                <span class="text-3xl font-black text-amber-700">3</span>
            </div>
        </div>
    </div>
    @endif

    {{-- Full Ranking Table --}}
    <div class="bento-card overflow-hidden ranking-table">
        <div class="p-6 border-b border-white/5 flex items-center justify-between">
            <span class="text-[10px] font-mono text-zinc-500 uppercase tracking-[0.3em]">Top {{ $users->count() }} Desenvolvedores</span>
            <span class="text-[9px] font-black text-zinc-600 uppercase tracking-widest bg-zinc-800/50 px-3 py-1 rounded-full">Atualizado agora</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/5">
                        <th class="text-left text-[9px] font-mono text-zinc-600 uppercase tracking-[0.2em] p-4">#</th>
                        <th class="text-left text-[9px] font-mono text-zinc-600 uppercase tracking-[0.2em] p-4">Dev</th>
                        <th class="text-left text-[9px] font-mono text-zinc-600 uppercase tracking-[0.2em] p-4">Liga</th>
                        <th class="text-right text-[9px] font-mono text-zinc-600 uppercase tracking-[0.2em] p-4">XP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $i => $rankedUser)
                    @php
                        $isCurrentUser = auth()->check() && auth()->id() === $rankedUser->id;
                        $medals = ['🥇', '🥈', '🥉'];
                        $rank = $i + 1;
                    @endphp
                    <tr class="border-b border-white/5 transition-colors hover:bg-white/[0.02] ranking-row {{ $isCurrentUser ? 'bg-yellow-400/5 border-l-2 border-l-yellow-400' : '' }}">
                        <td class="p-4">
                            @if($rank <= 3)
                                <span class="text-xl">{{ $medals[$i] }}</span>
                            @else
                                <span class="text-xs font-black text-zinc-600">{{ $rank }}</span>
                            @endif
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl {{ $isCurrentUser ? 'bg-yellow-400 text-zinc-950' : 'bg-zinc-800 text-zinc-400' }} flex items-center justify-center text-sm font-black flex-shrink-0">
                                    {{ mb_substr($rankedUser->name, 0, 1) }}
                                </div>
                                <div>
                                    <a href="{{ route('user.profile', $rankedUser->id) }}" class="text-sm font-black text-white hover:text-yellow-400 transition-colors">{{ $rankedUser->name }}</a> @if($isCurrentUser)<span class="text-[8px] text-yellow-500 font-bold ml-1 uppercase tracking-widest">você</span>@endif
                                    <p class="text-[10px] text-zinc-600 font-mono">Nível {{ $rankedUser->level ?? 1 }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="p-4">
                            <span class="text-[9px] font-black uppercase tracking-widest px-2 py-1 rounded-full
                                @if(str_contains($rankedUser->league ?? '', 'Gold')) bg-yellow-400/10 text-yellow-500
                                @elseif(str_contains($rankedUser->league ?? '', 'Silver')) bg-zinc-400/10 text-zinc-400
                                @else bg-amber-700/10 text-amber-600 @endif">
                                {{ $rankedUser->league ?? 'Bronze' }}
                            </span>
                        </td>
                        <td class="p-4 text-right">
                            <span class="text-sm font-black text-white">{{ number_format($rankedUser->xp) }}</span>
                            <span class="text-[9px] text-zinc-600 ml-1">XP</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8 text-center">
        <a href="/" class="btn-outline inline-flex items-center gap-2">
            ← Voltar e Estudar
        </a>
    </div>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) return;

    // Header entrance
    gsap.from('.ranking-header', { y: 60, opacity: 0, duration: 1, ease: 'power3.out' });

    // Podium animation
    gsap.from('.podium-2', { y: 80, opacity: 0, duration: 0.8, delay: 0.3, ease: 'power3.out' });
    gsap.from('.podium-1', { y: 100, opacity: 0, duration: 0.9, delay: 0.5, ease: 'power3.out' });
    gsap.from('.podium-3', { y: 80, opacity: 0, duration: 0.8, delay: 0.4, ease: 'power3.out' });

    // Table rows stagger
    gsap.from('.ranking-row', {
        x: -20, opacity: 0, stagger: 0.04, duration: 0.5, delay: 0.8, ease: 'power2.out'
    });
});
</script>
@endsection
@endsection
