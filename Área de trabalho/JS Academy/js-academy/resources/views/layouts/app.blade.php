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

    <meta name="theme-color" content="#F7DF1E">
    <meta name="application-name" content="JS Academy">
    <meta name="author" content="AquinoDev">

    @yield('head')
</head>
<body
    class="font-sans antialiased bg-zinc-950 text-white min-h-screen"
    x-data="{
        loginModal: {{ ($errors->isNotEmpty() && old('_form_type') === 'login') ? 'true' : 'false' }},
        registerModal: {{ ($errors->isNotEmpty() && old('_form_type') === 'register') ? 'true' : 'false' }}
    }"
    @open-register.window="registerModal = true"
    @open-login.window="loginModal = true"
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
        <div class="hidden md:flex items-center gap-8 text-[10px] font-black uppercase tracking-widest">
            <a href="/#o-caminho" class="flex items-center gap-1.5 text-zinc-500 hover:text-white transition-colors {{ request()->is('/') ? 'text-white' : '' }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Trilhas
            </a>
            <a href="{{ route('desafios') }}" class="flex items-center gap-1.5 text-zinc-500 hover:text-white transition-colors {{ request()->is('desafios') ? 'text-white' : '' }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                Desafios
            </a>
            <a href="{{ route('ranking') }}" class="flex items-center gap-1.5 text-zinc-500 hover:text-white transition-colors {{ request()->is('ranking') ? 'text-white' : '' }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Ranking
            </a>
        </div>

        <!-- Autenticação e Tema -->
        <div class="flex items-center gap-6">
            {{-- Toggle de Som --}}
            <button id="sound-toggle" class="p-2.5 rounded-xl bg-white/5 border border-white/10 text-zinc-400 hover:text-white transition-all shadow-inner" title="Ativar/Desativar Sons">
                <svg id="sound-on-icon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <svg id="sound-off-icon" class="w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                </svg>
            </button>

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
                    <div class="flex flex-col items-end leading-none" data-diamonds="{{ Auth::user()->diamonds ?? 0 }}">
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

{{-- Modais de Autenticação globais --}}
<div x-show="loginModal" x-cloak class="fixed inset-0 z-[400] flex items-center justify-center p-4 bg-zinc-950/90 backdrop-blur-md" @click.self="loginModal = false">
    <div class="w-full max-w-md bg-zinc-900 border border-white/10 rounded-[2rem] p-8 shadow-2xl" @click.stop>
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-xl font-black text-white tracking-tight">Entrar na plataforma</h2>
                <p class="text-zinc-500 text-xs mt-0.5">Continue sua jornada JavaScript.</p>
            </div>
            <button @click="loginModal = false" class="p-2 text-zinc-600 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <x-auth-session-status :status="session('status')" class="mb-4" />
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_form_type" value="login">
            <div>
                <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-2">E-mail</label>
                <input type="email" name="email" required autofocus class="input-field" placeholder="seu@email.com" value="{{ old('email') }}">
                @error('email', 'login')<p class="text-red-400 text-[11px] mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Senha</label>
                    @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-[10px] text-zinc-500 hover:text-yellow-400 transition-colors">Esqueceu a senha?</a>
                    @endif
                </div>
                <input type="password" name="password" required class="input-field" placeholder="••••••••">
                @error('password', 'login')<p class="text-red-400 text-[11px] mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center gap-3">
                <input type="checkbox" name="remember" id="remember" class="w-4 h-4 rounded border-zinc-700 bg-zinc-900 text-yellow-400">
                <label for="remember" class="text-xs text-zinc-500">Manter conectado</label>
            </div>
            <button type="submit" class="btn-primary w-full py-4 text-sm">Entrar</button>
            <p class="text-center text-xs text-zinc-600">Não tem conta? <button type="button" @click="loginModal = false; registerModal = true" class="text-yellow-400 hover:text-yellow-300 font-bold">Criar agora — grátis</button></p>
        </form>
    </div>
</div>

<div x-show="registerModal" x-cloak class="fixed inset-0 z-[400] flex items-center justify-center p-4 bg-zinc-950/90 backdrop-blur-md" @click.self="registerModal = false">
    <div class="w-full max-w-md bg-zinc-900 border border-white/10 rounded-[2rem] p-8 shadow-2xl" @click.stop>
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-xl font-black text-white tracking-tight">Criar conta grátis</h2>
                <p class="text-zinc-500 text-xs mt-0.5">Comece a dominar JavaScript hoje.</p>
            </div>
            <button @click="registerModal = false" class="p-2 text-zinc-600 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_form_type" value="register">
            <div>
                <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-2">Nome</label>
                <input type="text" name="name" required autofocus class="input-field" placeholder="Seu nome" value="{{ old('name') }}">
                @error('name')<p class="text-red-400 text-[11px] mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-2">E-mail</label>
                <input type="email" name="email" required class="input-field" placeholder="seu@email.com" value="{{ old('email') }}">
                @error('email')<p class="text-red-400 text-[11px] mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-2">Senha</label>
                <input type="password" name="password" required class="input-field" placeholder="Mínimo 8 caracteres">
                @error('password')<p class="text-red-400 text-[11px] mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-2">Confirmar senha</label>
                <input type="password" name="password_confirmation" required class="input-field" placeholder="Repita a senha">
            </div>
            <button type="submit" class="btn-primary w-full py-4 text-sm">Criar minha conta grátis</button>
            <p class="text-center text-xs text-zinc-600">Já tem conta? <button type="button" @click="registerModal = false; loginModal = true" class="text-yellow-400 hover:text-yellow-300 font-bold">Entrar</button></p>
        </form>
    </div>
</div>

@yield('modals')

@yield('scripts')

</body>
</html>
