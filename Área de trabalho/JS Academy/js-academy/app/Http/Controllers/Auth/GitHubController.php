<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GitHubController extends Controller
{
    /**
     * Redireciona o usuário para a página de autenticação do GitHub.
     */
    public function redirect()
    {
        return Socialite::driver('github')->redirect();
    }

    /**
     * Obtém as informações do usuário do GitHub e faz o login ou registro.
     */
    public function callback()
    {
        try {
            $githubUser = Socialite::driver('github')->user();

            // Tenta encontrar o usuário pelo email
            $user = User::where('email', $githubUser->email)->first();

            if ($user) {
                Auth::login($user);
            } else {
                // Cria um novo usuário via GitHub
                $user = User::create([
                    'name' => $githubUser->name ?? $githubUser->nickname,
                    'email' => $githubUser->email,
                    'password' => bcrypt(Str::random(24)),
                    'xp' => 0,
                    'level' => 1,
                    'diamonds' => 50,
                    'league' => 'Bronze',
                ]);

                Auth::login($user);
            }

            return redirect()->intended('/');
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Falha na autenticação com GitHub.');
        }
    }
}
