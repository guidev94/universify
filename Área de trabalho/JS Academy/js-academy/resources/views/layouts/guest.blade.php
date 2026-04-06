<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'JS Academy') }}</title>

    <!-- Estilos do Tailwind 4 (Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-zinc-950 text-white min-h-screen flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-md space-y-8">
        <!-- Logo -->
        <div class="text-center">
            <a href="/" class="group inline-flex flex-col items-center gap-3">
                <div class="relative">
                    <div class="absolute -inset-1 bg-gradient-to-tr from-yellow-400 to-orange-500 rounded-xl blur opacity-25 group-hover:opacity-75 transition-opacity duration-500"></div>
                    <div class="relative w-16 h-16 bg-[#F7DF1E] rounded-2xl flex items-center justify-center shadow-2xl transform group-hover:scale-110 transition-transform duration-300">
                        <span class="text-zinc-950 font-black text-3xl tracking-tighter leading-none select-none">JS</span>
                    </div>
                </div>
                <h2 class="mt-4 text-3xl font-black tracking-tight text-white uppercase italic">JS Academy</h2>
            </a>
        </div>

        <!-- Card de Autenticação (Bento Design) -->
        <div class="bento-card p-8 bg-zinc-900/40 border border-white/5 shadow-2xl backdrop-blur-xl">
            {{ $slot }}
        </div>
        
        <div class="text-center">
            <p class="text-xs font-mono text-zinc-500 uppercase tracking-widest">By AquinoDev • Secure & Encrypted</p>
        </div>
    </div>

    <style>
        .bento-card {
            border-radius: 2rem;
        }
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus {
            -webkit-text-fill-color: white;
            -webkit-box-shadow: 0 0 0px 1000px #18181b inset;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>
</body>
</html>
