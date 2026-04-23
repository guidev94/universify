@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <header class="text-center mb-16">
        <div class="inline-block px-4 py-1.5 bg-js-yellow/10 rounded-full border border-js-yellow/20 mb-6">
            <span class="text-[10px] font-black text-js-yellow uppercase tracking-[0.4em]">Hall da Fama</span>
        </div>
        <h1 class="heading-huge text-white">
            Lendas do <span class="bg-gradient-to-r from-js-yellow to-orange-500 bg-clip-text text-transparent italic">JavaScript</span> 🏆
        </h1>
        <p class="mt-4 text-zinc-500 font-mono text-xs uppercase tracking-widest">Os desenvolvedores que dominam a arena.</p>
    </header>

    <div class="bento-card border-white/5 shadow-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-zinc-950/50 border-b border-white/5 uppercase font-black text-[10px] tracking-widest text-zinc-500">
                        <th class="py-8 px-10 text-center w-24">Rank</th>
                        <th class="py-8 px-4 text-center w-20">Nível</th>
                        <th class="py-8 px-4">Desenvolvedor</th>
                        <th class="py-8 px-8 text-right w-48">XP Acumulado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $index => $u)
                    <tr class="group hover:bg-white/[0.02] transition-all border-b border-white/5 last:border-0 relative">
                        <td class="py-8 px-10 text-center relative">
                            @if($index == 0)
                                <div class="absolute inset-0 bg-js-yellow/5 blur-2xl"></div>
                                <span class="text-4xl drop-shadow-[0_0_10px_rgba(247,223,30,0.5)]">🥇</span>
                            @elseif($index == 1)
                                <span class="text-3xl">🥈</span>
                            @elseif($index == 2)
                                <span class="text-3xl">🥉</span>
                            @else
                                <span class="text-xl font-black text-zinc-700 group-hover:text-zinc-500 transition-colors">#{{ $index + 1 }}</span>
                            @endif
                        </td>
                        <td class="py-8 px-4 text-center">
                            <div class="w-12 h-12 rounded-2xl bg-zinc-800 border border-white/5 flex items-center justify-center mx-auto shadow-inner group-hover:border-js-yellow/30 transition-colors">
                                <span class="text-sm font-black text-white italic">{{ floor($u->xp / 1000) + 1 }}</span>
                            </div>
                        </td>
                        <td class="py-8 px-4">
                            <div class="flex items-center gap-6">
                                <div class="relative">
                                    <div class="w-14 h-14 rounded-[1.5rem] bg-gradient-to-tr from-zinc-800 to-zinc-700 border border-white/10 flex items-center justify-center text-2xl font-black text-white shadow-xl group-hover:scale-105 transition-all duration-500 group-hover:shadow-js-yellow/10">
                                        {{ substr($u->name, 0, 1) }}
                                    </div>
                                    @if($u->streak > 5)
                                    <div class="absolute -top-2 -right-2 w-7 h-7 bg-orange-500 rounded-full flex items-center justify-center text-[11px] shadow-lg animate-bounce border-2 border-zinc-950">🔥</div>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-lg font-black text-white group-hover:text-js-yellow transition-colors tracking-tighter">{{ $u->name }}</p>
                                    <div class="flex items-center gap-3 mt-1.5">
                                        <span class="px-2.5 py-0.5 rounded-lg bg-zinc-800 border border-white/5 text-[9px] font-black text-zinc-500 uppercase tracking-tighter">{{ $u->league ?? 'Bronze' }}</span>
                                        @if($u->streak > 0)
                                        <div class="flex items-center gap-1.5">
                                            <div class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-pulse"></div>
                                            <span class="text-[10px] text-orange-500 font-black uppercase">{{ $u->streak }} dias de foco</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="py-8 px-8 text-right">
                            <div class="flex flex-col items-end">
                                <span class="text-2xl font-mono font-black text-white tracking-tighter tabular-nums">{{ number_format($u->xp, 0, ',', '.') }}</span>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <div class="w-1.5 h-1.5 rounded-full bg-js-yellow shadow-[0_0_8px_rgba(247,223,30,0.5)]"></div>
                                    <span class="text-[9px] font-black text-zinc-500 uppercase tracking-widest">Mastery XP</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <footer class="mt-20 text-center border-t border-white/5 pt-12">
        <p class="text-zinc-700 text-[10px] uppercase font-mono tracking-[0.5em]">Global Database • Secure Sync • JS Academy</p>
    </footer>
</div>
@endsection
