<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>JS Academy 🚀 - Evolua seu JavaScript</title>

    <!-- Estilos do Tailwind 4 (Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-zinc-950 text-white transition-colors duration-500" x-data="{ registerModal: false, loginModal: false }">
    <div class="min-h-screen flex flex-col">
        <!-- Navegação Superior Profissional -->
        <nav class="sticky top-0 z-50 backdrop-blur-xl bg-zinc-950/80 border-b border-white/5 py-4">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                <!-- Logo Profissional (Unificado) -->
                <a href="/" class="group flex items-center gap-2.5">
                    <div class="relative flex items-center">
                        <div class="absolute -inset-1 bg-gradient-to-tr from-yellow-400 to-orange-500 rounded-xl blur opacity-20 group-hover:opacity-60 transition-opacity duration-500"></div>
                        <div class="relative w-10 h-10 bg-[#F7DF1E] rounded-xl flex items-center justify-center shadow-lg transform group-hover:rotate-6 transition-all duration-300">
                            <span class="text-zinc-950 font-black text-xl tracking-tighter leading-none select-none">JS</span>
                        </div>
                    </div>
                    <div class="flex flex-col leading-[0.8]">
                        <span class="text-2xl font-black tracking-tighter text-white group-hover:text-yellow-400 transition-colors uppercase italic">Academy</span>
                        <span class="text-[11px] font-mono text-zinc-500 uppercase tracking-[0.1em] font-bold group-hover:text-yellow-500 transition-colors">By AquinoDev</span>
                    </div>
                </a>

                <div class="hidden md:flex items-center gap-8 text-[11px] font-bold uppercase tracking-widest text-zinc-400">
                    <a href="/#o-caminho" class="hover:text-yellow-400 transition-colors">Trilhas</a>
                    <a href="{{ route('desafios') }}" class="hover:text-cyan-400 transition-colors">Desafios</a>
                    <a href="{{ route('ranking') }}" class="hover:text-orange-400 transition-colors">Ranking</a>
                </div>

                <div class="flex items-center gap-4">
                    <button id="theme-toggle" class="p-2 rounded-xl bg-white/5 border border-white/10 text-zinc-400 hover:text-white transition-all shadow-inner" title="Alternar Tema">
                        <span class="theme-icon-dark">🌙</span>
                        <span class="theme-icon-light hidden">☀️</span>
                    </button>

                    @guest
                        <button @click="loginModal = true" class="text-[10px] font-bold uppercase tracking-widest text-zinc-400 hover:text-white">Login</button>
                        <button @click="registerModal = true" class="px-5 py-2.5 rounded-xl bg-white text-zinc-950 text-[10px] font-black uppercase tracking-widest hover:bg-zinc-200 transition-all active:scale-95 shadow-lg shadow-white/10">Começar</button>
                    @else
                        <div class="flex items-center gap-3 pl-4 border-l border-white/10">
                            <div class="flex flex-col items-end leading-none mr-2">
                                <span class="text-[10px] font-bold text-white uppercase">{{ explode(' ', Auth::user()->name)[0] }}</span>
                                <span class="text-[8px] font-mono text-yellow-500 uppercase">Pro Member</span>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="p-2.5 rounded-xl bg-white/5 text-zinc-400 hover:text-red-500 transition-colors" title="Sair">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @endguest
                </div>
            </div>
        </nav>

        <!-- Conteúdo Principal -->
        <main class="flex-grow">
            @yield('content')
        </main>
    </div>

    <!-- Modal Registro -->
    <template x-if="registerModal">
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-zinc-950/80 backdrop-blur-sm" @click.self="registerModal = false">
            <div class="w-full max-w-md bg-zinc-900 border border-white/10 rounded-[2.5rem] p-8 shadow-2xl relative overflow-hidden" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <button @click="registerModal = false" class="absolute top-6 right-6 text-zinc-500 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                
                <div class="text-center mb-8">
                    <div class="w-12 h-12 bg-pink-500/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl">🚀</span>
                    </div>
                    <h3 class="text-2xl font-black text-white uppercase italic tracking-tighter">Criar Conta</h3>
                    <p class="text-sm text-zinc-500 mt-2">Torne-se um mestre em JavaScript.</p>
                </div>

                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf
                    <div class="space-y-1">
                        <label class="text-[10px] font-mono text-zinc-500 uppercase tracking-widest ml-1">Nome Completo</label>
                        <input type="text" name="name" required class="w-full bg-zinc-950 border border-white/5 rounded-2xl px-4 py-3 text-sm focus:border-pink-500 focus:ring-0 transition-all text-white" placeholder="Seu nome">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-mono text-zinc-500 uppercase tracking-widest ml-1">E-mail</label>
                        <input type="email" name="email" required class="w-full bg-zinc-950 border border-white/5 rounded-2xl px-4 py-3 text-sm focus:border-pink-500 focus:ring-0 transition-all text-white" placeholder="email@exemplo.com">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-mono text-zinc-500 uppercase tracking-widest ml-1">Senha</label>
                        <input type="password" name="password" required class="w-full bg-zinc-950 border border-white/5 rounded-2xl px-4 py-3 text-sm focus:border-pink-500 focus:ring-0 transition-all text-white" placeholder="••••••••">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-mono text-zinc-500 uppercase tracking-widest ml-1">Confirmar Senha</label>
                        <input type="password" name="password_confirmation" required class="w-full bg-zinc-950 border border-white/5 rounded-2xl px-4 py-3 text-sm focus:border-pink-500 focus:ring-0 transition-all text-white" placeholder="••••••••">
                    </div>

                    <button type="submit" class="w-full py-4 bg-pink-500 text-white rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:bg-pink-400 transition-all active:scale-95 shadow-xl shadow-pink-500/20 mt-4">
                        Começar Jornada
                    </button>
                    
                    <p class="text-center text-[10px] text-zinc-600 mt-6 uppercase tracking-widest">
                        Já tem conta? <button type="button" @click="registerModal = false; loginModal = true" class="text-pink-500 font-bold hover:underline">Fazer Login</button>
                    </p>
                </form>
            </div>
        </div>
    </template>

    <!-- Modal Login -->
    <template x-if="loginModal">
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-zinc-950/80 backdrop-blur-sm" @click.self="loginModal = false">
            <div class="w-full max-w-md bg-zinc-900 border border-white/10 rounded-[2.5rem] p-8 shadow-2xl relative overflow-hidden" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <button @click="loginModal = false" class="absolute top-6 right-6 text-zinc-500 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                
                <div class="text-center mb-8">
                    <div class="w-12 h-12 bg-yellow-400/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl">🔑</span>
                    </div>
                    <h3 class="text-2xl font-black text-white uppercase italic tracking-tighter">Login Academy</h3>
                    <p class="text-sm text-zinc-500 mt-2">Bem-vindo de volta, dev!</p>
                </div>

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf
                    <div class="space-y-1">
                        <label class="text-[10px] font-mono text-zinc-500 uppercase tracking-widest ml-1">E-mail</label>
                        <input type="email" name="email" required class="w-full bg-zinc-950 border border-white/5 rounded-2xl px-4 py-3 text-sm focus:border-yellow-500 focus:ring-0 transition-all text-white" placeholder="email@exemplo.com">
                    </div>
                    <div class="space-y-1">
                        <div class="flex justify-between items-center">
                            <label class="text-[10px] font-mono text-zinc-500 uppercase tracking-widest ml-1">Senha</label>
                            <a href="{{ route('password.request') }}" class="text-[9px] text-zinc-600 hover:text-zinc-400 uppercase tracking-widest">Esqueci a senha</a>
                        </div>
                        <input type="password" name="password" required class="w-full bg-zinc-950 border border-white/5 rounded-2xl px-4 py-3 text-sm focus:border-yellow-500 focus:ring-0 transition-all text-white" placeholder="••••••••">
                    </div>

                    <button type="submit" class="w-full py-4 bg-white text-zinc-950 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:bg-yellow-400 transition-all active:scale-95 shadow-xl shadow-white/5 mt-4">
                        Entrar Agora
                    </button>
                    
                    <p class="text-center text-[10px] text-zinc-600 mt-6 uppercase tracking-widest">
                        Novo por aqui? <button type="button" @click="loginModal = false; registerModal = true" class="text-yellow-500 font-bold hover:underline">Criar sua conta</button>
                    </p>
                </form>
            </div>
        </div>
    </template>

    <style>
        /* Melhorias no Modo Claro (Light Mode) */
        .light-mode {
            background-color: #f8fafc !important; 
            color: #0f172a !important; 
        }
        .light-mode nav {
            background-color: rgba(255, 255, 255, 0.8) !important;
            border-color: #e2e8f0 !important;
        }
        .light-mode .bento-card {
            background: rgba(255, 255, 255, 0.7) !important;
            border-color: #e2e8f0 !important;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1) !important;
        }
        .light-mode h1, .light-mode h2, .light-mode h3, .light-mode .text-white {
            color: #0f172a !important;
        }
        .light-mode p, .light-mode .text-zinc-400, .light-mode .text-zinc-500 {
            color: #475569 !important; 
        }
        .light-mode .text-zinc-950 {
            color: #0f172a !important;
        }
        .light-mode .theme-icon-dark { display: none; }
        .light-mode .theme-icon-light { display: inline-block; }
        .light-mode .bg-zinc-900\/40, .light-mode .bg-zinc-950, .light-mode .bg-zinc-900 {
            background-color: #ffffff !important;
        }
        .light-mode .border-white\/5, .light-mode .border-zinc-900, .light-mode .border-white\/10 {
            border-color: #e2e8f0 !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeBtn = document.getElementById('theme-toggle');
            const body = document.body;

            const updateTheme = (isLight) => {
                if (isLight) {
                    body.classList.add('light-mode');
                } else {
                    body.classList.remove('light-mode');
                }
                localStorage.setItem('theme', isLight ? 'light' : 'dark');
                window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: isLight ? 'light' : 'dark' } }));
            };

            themeBtn.addEventListener('click', () => {
                const isLight = !body.classList.contains('light-mode');
                updateTheme(isLight);
            });

            if (localStorage.getItem('theme') === 'light') {
                updateTheme(true);
            }
        });
    </script>
</body>
</html>
