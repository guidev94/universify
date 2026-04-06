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
<body class="font-sans antialiased bg-zinc-950 text-white transition-colors duration-500">
    <div class="min-h-screen flex flex-col">
        <!-- Navegação Superior Profissional -->
        <nav class="sticky top-0 z-50 backdrop-blur-xl bg-zinc-950/80 border-b border-white/5 py-4">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                <!-- Logo Profissional -->
                <a href="/" class="group flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute -inset-1 bg-gradient-to-tr from-yellow-400 to-orange-500 rounded-xl blur opacity-25 group-hover:opacity-75 transition-opacity duration-500"></div>
                        <div class="relative w-12 h-12 bg-[#F7DF1E] rounded-xl flex items-center justify-end p-1.5 shadow-[0_10px_20px_rgba(247,223,30,0.3),inset_0_-2px_4px_rgba(0,0,0,0.2)] transform group-hover:scale-105 group-hover:-rotate-3 transition-all duration-300">
                            <span class="text-zinc-950 font-black text-2xl tracking-tighter leading-none select-none">JS</span>
                        </div>
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="text-2xl font-black tracking-tighter text-white group-hover:text-yellow-400 transition-colors">ACADEMY</span>
                        <span class="text-[9px] font-mono text-zinc-500 uppercase tracking-[0.4em] group-hover:text-zinc-300">Mastery 2026</span>
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
                        <a href="{{ route('login') }}" class="text-[10px] font-bold uppercase tracking-widest text-zinc-400 hover:text-white">Login</a>
                        <a href="{{ route('register') }}" class="px-5 py-2.5 rounded-xl bg-white text-zinc-950 text-[10px] font-black uppercase tracking-widest hover:bg-zinc-200 transition-all active:scale-95 shadow-lg shadow-white/10">Começar</a>
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

    <style>
        /* Melhorias no Modo Claro (Light Mode) */
        .light-mode {
            background-color: #f8fafc !important; /* Slate 50 */
            color: #0f172a !important; /* Slate 900 */
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
            color: #475569 !important; /* Slate 600 */
        }
        .light-mode .text-zinc-950 {
            color: #0f172a !important;
        }
        .light-mode .theme-icon-dark { display: none; }
        .light-mode .theme-icon-light { display: inline-block; }
        .light-mode .bg-zinc-900\/40, .light-mode .bg-zinc-950 {
            background-color: #ffffff !important;
        }
        .light-mode .border-white\/5, .light-mode .border-zinc-900 {
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
