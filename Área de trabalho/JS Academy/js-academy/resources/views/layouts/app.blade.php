<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Plataforma profissional de aprendizado JavaScript gamificado. Aprenda lógica, ES6+, DOM, Async e Design Patterns com desafios reais.">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="{{ config('app.name') }} - Aprenda JavaScript como um Dev de Elite">
    <meta property="og:description" content="Trilhas de aprendizado gamificadas, desafios técnicos e ranking global. Evolua seu JavaScript ao próximo nível.">
    <meta property="og:type" content="website">
    <link rel="canonical" href="{{ url()->current() }}">

    <title>@yield('title', config('app.name', 'JS Academy'))</title>

    <!-- Estilos do Tailwind 4 (Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('head')
</head>
<body
    class="font-sans antialiased bg-zinc-950 text-white min-h-screen"
    x-data="{ loginModal: false, registerModal: false }"
    :class="{ 'light-mode': $store?.theme?.light }"
>

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
            <a href="/" class="hover:text-white transition-colors flex items-center gap-1.5">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/>
                </svg>
                Início
            </a>
            <a href="/#o-caminho" class="hover:text-white transition-colors">Trilhas</a>
            <a href="{{ route('desafios') }}" class="hover:text-white transition-colors">Desafios</a>
            <a href="{{ route('ranking') }}" class="hover:text-white transition-colors">Ranking</a>
        </div>

        <!-- Autenticação e Tema -->
        <div class="flex items-center gap-6">
            <button id="theme-toggle" class="p-2.5 rounded-xl bg-white/5 border border-white/10 text-zinc-400 hover:text-white transition-all shadow-inner" title="Alternar Tema">
                <span class="theme-icon-dark">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                    </svg>
                </span>
                <span class="theme-icon-light hidden">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                    </svg>
                </span>
            </button>

            @guest
                <div class="flex items-center gap-6">
                    <button @click="loginModal = true" class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 hover:text-white transition-colors">Login</button>
                    <button @click="registerModal = true" class="btn-primary px-8 py-3">Começar</button>
                </div>
            @else
                <div class="flex items-center gap-3 pl-6 border-l border-white/10">
                    <div class="flex flex-col items-end leading-none">
                        <span class="text-[10px] font-black text-white uppercase tracking-tighter">{{ explode(' ', Auth::user()->name)[0] }}</span>
                        <span class="text-[8px] font-mono text-zinc-500 uppercase tracking-wider mt-0.5">Nível {{ Auth::user()->level ?? 1 }} · {{ number_format(Auth::user()->xp) }} XP</span>
                    </div>
                    <a href="{{ route('user.profile', Auth::user()->id) }}" class="p-2.5 rounded-xl bg-white/5 text-zinc-500 hover:text-white transition-all border border-white/5 hover:border-white/10" title="Meu Perfil">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-2.5 rounded-xl bg-white/5 text-zinc-500 hover:text-red-400 transition-all border border-white/5 hover:border-red-500/20 hover:bg-red-500/5" title="Sair">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            @endguest
        </div>
    </div>
</nav>

<main>
    @yield('content')
</main>

@yield('modals')

@yield('scripts')

</body>
</html>
