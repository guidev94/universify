@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <header class="text-center mb-16">
        <h1 class="text-5xl md:text-7xl font-black text-white italic leading-tight">
            Lendas do <span class="bg-gradient-to-r from-yellow-400 to-orange-500 bg-clip-text text-transparent">JavaScript</span> 🏆
        </h1>
        <p class="mt-4 text-zinc-400">Os 20 melhores alunos que dominam a lógica e o código.</p>
    </header>

    <div class="bento-card p-6 bg-zinc-900/40 border-yellow-500/10 shadow-2xl overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-white/5 text-[10px] font-mono text-zinc-500 uppercase tracking-widest">
                    <th class="py-4 px-4 text-center">Pos</th>
                    <th class="py-4">Dev</th>
                    <th class="py-4">XP Total</th>
                    <th class="py-4 text-center">Nível</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $index => $u)
                <tr class="group hover:bg-white/5 transition-colors border-b border-white/5 last:border-0">
                    <td class="py-6 px-4 text-center">
                        <span class="text-xl font-black {{ $index < 3 ? 'text-yellow-400' : 'text-zinc-600' }}">#{{ $index + 1 }}</span>
                    </td>
                    <td class="py-6">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-zinc-800 border border-white/10 flex items-center justify-center text-lg font-bold group-hover:scale-110 transition-transform">
                                {{ substr($u->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white">{{ $u->name }}</p>
                                <p class="text-[9px] text-zinc-500 uppercase tracking-tight">{{ $u->league ?? 'Bronze' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="py-6">
                        <p class="text-sm font-mono font-bold text-yellow-500">{{ number_format($u->xp, 0, ',', '.') }} XP</p>
                    </td>
                    <td class="py-6 text-center">
                        <span class="px-3 py-1 rounded-full bg-zinc-800 border border-white/10 text-[10px] font-bold text-zinc-400 group-hover:border-yellow-500/30 transition-colors">
                            Lvl {{ floor($u->xp / 1000) + 1 }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
