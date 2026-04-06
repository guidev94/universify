<x-guest-layout>
    <!-- Logo removido daqui pois já está no layout global de guest -->
    
    <div class="text-center mb-8">
        <h3 class="text-xl font-bold text-white">Bem-vindo de volta!</h3>
        <p class="text-sm text-zinc-500 mt-1">Acesse sua jornada de mestre JS.</p>
    </div>

    <!-- Status da Sessão -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Endereço de E-mail -->
        <div class="space-y-2">
            <x-input-label for="email" value="E-mail" class="text-zinc-400 text-xs uppercase font-mono tracking-widest" />
            <x-text-input id="email" class="block w-full bg-zinc-950/50 border-white/5 text-white focus:border-yellow-500 focus:ring-yellow-500 rounded-xl" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500 text-xs" />
        </div>

        <!-- Senha -->
        <div class="space-y-2">
            <x-input-label for="password" value="Senha" class="text-zinc-400 text-xs uppercase font-mono tracking-widest" />
            <x-text-input id="password" class="block w-full bg-zinc-950/50 border-white/5 text-white focus:border-yellow-500 focus:ring-yellow-500 rounded-xl"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500 text-xs" />
        </div>

        <!-- Lembrar-me -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded-lg border-white/5 bg-zinc-950 text-yellow-500 shadow-sm focus:ring-yellow-500" name="remember">
                <span class="ms-2 text-sm text-zinc-400">Lembrar-me</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-zinc-500 hover:text-white transition-colors" href="{{ route('password.request') }}">
                    Esqueceu a senha?
                </a>
            @endif
        </div>

        <div class="pt-2">
            <button class="w-full py-4 bg-white text-zinc-950 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-yellow-400 transition-all active:scale-95 shadow-xl shadow-white/5">
                Entrar na Academy
            </button>
        </div>

        <div class="text-center pt-4 border-t border-white/5">
            <p class="text-sm text-zinc-500">
                Não tem uma conta? 
                <a href="{{ route('register') }}" class="text-yellow-500 hover:underline font-bold">Criar Agora</a>
            </p>
        </div>
    </form>
</x-guest-layout>
