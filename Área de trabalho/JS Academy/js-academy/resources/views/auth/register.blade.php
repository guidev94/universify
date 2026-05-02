<x-guest-layout>
<x-slot name="title">Criar conta grátis — JS Academy</x-slot>
    <div class="text-center mb-8">
        <h3 class="text-xl font-bold text-white">Criar sua conta</h3>
        <p class="text-sm text-zinc-500 mt-1">Torne-se um mestre em JavaScript.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-6">
        @csrf

        <!-- Nome -->
        <div class="space-y-2">
            <x-input-label for="name" value="Nome Completo" class="text-zinc-400 text-xs uppercase font-mono tracking-widest" />
            <x-text-input id="name" class="block w-full bg-zinc-950/50 border-white/5 text-white focus:border-yellow-500 focus:ring-yellow-500 rounded-xl" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2 text-red-500 text-xs" />
        </div>

        <!-- Endereço de E-mail -->
        <div class="space-y-2">
            <x-input-label for="email" value="E-mail" class="text-zinc-400 text-xs uppercase font-mono tracking-widest" />
            <x-text-input id="email" class="block w-full bg-zinc-950/50 border-white/5 text-white focus:border-yellow-500 focus:ring-yellow-500 rounded-xl" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500 text-xs" />
        </div>

        <!-- Senha -->
        <div class="space-y-2">
            <x-input-label for="password" value="Senha" class="text-zinc-400 text-xs uppercase font-mono tracking-widest" />
            <x-text-input id="password" class="block w-full bg-zinc-950/50 border-white/5 text-white focus:border-yellow-500 focus:ring-yellow-500 rounded-xl"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500 text-xs" />
        </div>

        <!-- Confirmar Senha -->
        <div class="space-y-2">
            <x-input-label for="password_confirmation" value="Confirmar Senha" class="text-zinc-400 text-xs uppercase font-mono tracking-widest" />
            <x-text-input id="password_confirmation" class="block w-full bg-zinc-950/50 border-white/5 text-white focus:border-yellow-500 focus:ring-yellow-500 rounded-xl"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-red-500 text-xs" />
        </div>

        <div class="pt-2">
            <button class="w-full py-4 bg-white text-zinc-950 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-pink-500 hover:text-white transition-all active:scale-95 shadow-xl shadow-white/5">
                Começar Jornada
            </button>
        </div>

        <div class="text-center pt-4 border-t border-white/5">
            <p class="text-sm text-zinc-500">
                Já possui registro? 
                <a href="{{ route('login') }}" class="text-yellow-500 hover:underline font-bold">Entrar</a>
            </p>
        </div>
    </form>
</x-guest-layout>
