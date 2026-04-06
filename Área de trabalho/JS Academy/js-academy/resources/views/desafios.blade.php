@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <header class="mb-16">
        <h1 class="text-6xl md:text-8xl font-black text-white italic leading-tight">
            Arena de <span class="bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent italic">Lógica</span> 🥊
        </h1>
        <p class="mt-4 text-xl text-zinc-400 max-w-2xl">
            Complete os desafios, ganhe diamantes e suba na liga global.
        </p>
    </header>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Desafio 1 -->
        <div class="bento-card p-6 bg-gradient-to-br from-cyan-500/10 to-transparent border-cyan-500/20 relative group overflow-hidden">
            <div class="flex justify-between items-center mb-6">
                <span class="text-[10px] font-mono text-cyan-500 uppercase font-bold tracking-[0.2em]">Lógica Básica</span>
                <span class="px-2 py-0.5 rounded-full bg-cyan-500/10 text-cyan-500 text-[8px] font-bold uppercase tracking-widest border border-cyan-500/20">+50 XP</span>
            </div>
            <h3 class="text-2xl font-bold text-white mb-2">FizzBuzz Elite</h3>
            <p class="text-zinc-400 text-sm mb-8 leading-relaxed">Crie uma função que retorne "Fizz" para múltiplos de 3, "Buzz" para múltiplos de 5 e "FizzBuzz" para ambos, de 1 a 100.</p>
            
            <div class="flex items-center justify-between mt-auto">
                <div class="flex items-center gap-2">
                    <span class="text-cyan-500 text-sm">💎</span>
                    <span class="text-xs font-bold text-zinc-300">10 Diamantes</span>
                </div>
                <button class="px-5 py-2.5 bg-cyan-500 text-zinc-950 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-cyan-400 transition-all active:scale-95 shadow-lg shadow-cyan-500/20">Iniciar Desafio</button>
            </div>
        </div>

        <!-- Desafio 2 -->
        <div class="bento-card p-6 bg-gradient-to-br from-pink-500/10 to-transparent border-pink-500/20 relative group overflow-hidden">
            <div class="flex justify-between items-center mb-6">
                <span class="text-[10px] font-mono text-pink-500 uppercase font-bold tracking-[0.2em]">Algoritmos</span>
                <span class="px-2 py-0.5 rounded-full bg-pink-500/10 text-pink-500 text-[8px] font-bold uppercase tracking-widest border border-pink-500/20">+150 XP</span>
            </div>
            <h3 class="text-2xl font-bold text-white mb-2">Inversor de Arrays</h3>
            <p class="text-zinc-400 text-sm mb-8 leading-relaxed">Inverta um array de qualquer tamanho sem usar o método nativo `.reverse()`. Mostre sua maestria lógica.</p>
            
            <div class="flex items-center justify-between mt-auto">
                <div class="flex items-center gap-2">
                    <span class="text-cyan-500 text-sm">💎</span>
                    <span class="text-xs font-bold text-zinc-300">25 Diamantes</span>
                </div>
                <button class="px-5 py-2.5 bg-pink-500 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-pink-400 transition-all active:scale-95 shadow-lg shadow-pink-500/20">Iniciar Desafio</button>
            </div>
        </div>

        <!-- Desafio 3 -->
        <div class="bento-card p-6 bg-gradient-to-br from-yellow-500/10 to-transparent border-yellow-500/20 relative group overflow-hidden">
            <div class="flex justify-between items-center mb-6">
                <span class="text-[10px] font-mono text-yellow-500 uppercase font-bold tracking-[0.2em]">Estruturas</span>
                <span class="px-2 py-0.5 rounded-full bg-yellow-500/10 text-yellow-500 text-[8px] font-bold uppercase tracking-widest border border-yellow-500/20">+200 XP</span>
            </div>
            <h3 class="text-2xl font-bold text-white mb-2">Validador de Palíndromos</h3>
            <p class="text-zinc-400 text-sm mb-8 leading-relaxed">Desenvolva uma lógica que verifique se uma frase é um palíndromo, ignorando espaços e caracteres especiais.</p>
            
            <div class="flex items-center justify-between mt-auto">
                <div class="flex items-center gap-2">
                    <span class="text-cyan-500 text-sm">💎</span>
                    <span class="text-xs font-bold text-zinc-300">40 Diamantes</span>
                </div>
                <button class="px-5 py-2.5 bg-yellow-500 text-zinc-950 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-yellow-400 transition-all active:scale-95 shadow-lg shadow-yellow-500/20">Iniciar Desafio</button>
            </div>
        </div>
    </div>
</div>
@endsection
