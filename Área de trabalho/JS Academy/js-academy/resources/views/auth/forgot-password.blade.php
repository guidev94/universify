<x-guest-layout>
    <div class="mb-8">
        <h1 class="text-2xl font-black text-white tracking-tight">Recuperar senha</h1>
        <p class="text-zinc-500 text-sm mt-1">Informe seu e-mail e enviaremos um link de redefinicao.</p>
    </div>

    @if (session('status'))
    <div class="mb-6 p-4 rounded-xl bg-green-500/10 border border-green-500/20">
        <p class="text-sm text-green-400">{{ session('status') }}</p>
    </div>
    @endif

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-2">E-mail cadastrado</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                class="block w-full bg-zinc-950/50 border border-white/5 text-white focus:border-yellow-500 focus:ring-yellow-500 rounded-xl px-4 py-3 placeholder-zinc-600 @error('email') border-red-500/50 @enderror"
                placeholder="seu@email.com">
            <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-red-400 text-[11px]" />
        </div>

        <button type="submit" class="w-full py-4 bg-white text-zinc-950 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-yellow-400 transition-all active:scale-95 shadow-xl shadow-white/5">
            Enviar link de redefinicao
        </button>

        <div class="text-center pt-2">
            <a href="{{ route('login') }}" class="text-xs text-zinc-500 hover:text-white transition-colors">
                Voltar ao login
            </a>
        </div>
    </form>
</x-guest-layout>
