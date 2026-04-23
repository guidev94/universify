<nav class="sticky top-0 z-50 backdrop-blur-xl bg-zinc-950/80 border-b border-white/5 py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">
        <!-- Logo e Título -->
        <a href="/" class="group flex items-center gap-2.5">
            <div class="relative flex items-center">
                <div class="absolute -inset-1 bg-gradient-to-tr from-js-yellow to-orange-500 rounded-xl blur opacity-20 group-hover:opacity-60 transition-opacity duration-500"></div>
                <div class="relative w-10 h-10 bg-js-yellow rounded-xl flex items-center justify-center shadow-lg transform group-hover:rotate-6 transition-all duration-300">
                    <span class="text-zinc-950 font-black text-xl tracking-tighter leading-none select-none">JS</span>
                </div>
            </div>
            <div class="flex flex-col leading-[0.8]">
                <span class="text-2xl font-black tracking-tighter text-white group-hover:text-js-yellow transition-colors uppercase italic">Academy</span>
                <span class="text-[11px] font-mono text-zinc-500 uppercase tracking-[0.1em] font-bold group-hover:text-js-yellow transition-colors">By AquinoDev</span>
            </div>
        </a>

        <!-- Links de Navegação -->
        <div class="hidden md:flex items-center gap-10 text-[10px] font-black uppercase tracking-widest text-zinc-500">
            <a href="/#o-caminho" class="hover:text-white transition-colors">Trilhas</a>
            <a href="{{ route('desafios') }}" class="hover:text-white transition-colors">Desafios</a>
            <a href="{{ route('ranking') }}" class="hover:text-white transition-colors">Ranking</a>
        </div>

        <!-- Autenticação e Tema -->
        <div class="flex items-center gap-6">
            <button id="theme-toggle" class="p-2.5 rounded-xl bg-white/5 border border-white/10 text-zinc-400 hover:text-white transition-all shadow-inner" title="Alternar Tema">
                <span class="theme-icon-dark">🌙</span>
                <span class="theme-icon-light hidden">☀️</span>
            </button>

            @guest
                <div class="flex items-center gap-6">
                    <button @click="loginModal = true" class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 hover:text-white transition-colors">Login</button>
                    <button @click="registerModal = true" class="btn-primary px-8 py-3">Começar</button>
                </div>
            @else
                <div class="flex items-center gap-4 pl-6 border-l border-white/10">
                    <div class="flex flex-col items-end leading-none">
                        <span class="text-[10px] font-black text-white uppercase tracking-tighter">{{ explode(' ', Auth::user()->name)[0] }}</span>
                        <span class="text-[8px] font-mono text-js-yellow uppercase font-bold mt-1">Master Account</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-2.5 rounded-xl bg-white/5 text-zinc-500 hover:text-red-500 transition-colors border border-white/5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        </button>
                    </form>
                </div>
            @endguest
        </div>
    </div>
</nav>