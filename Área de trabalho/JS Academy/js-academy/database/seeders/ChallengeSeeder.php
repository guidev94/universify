<?php

namespace Database\Seeders;

use App\Models\LogicChallenge;
use Illuminate\Database\Seeder;

class ChallengeSeeder extends Seeder
{
    public function run(): void
    {
        $challenges = [
            [
                'title' => 'FizzBuzz Elite',
                'slug' => 'fizzbuzz-elite',
                'category' => 'Lógica Básica',
                'description' => 'Um clássico das entrevistas técnicas.',
                'task' => 'Crie uma função que retorne "Fizz" para múltiplos de 3, "Buzz" para múltiplos de 5 e "FizzBuzz" para múltiplos de ambos, para números de 1 a 100.',
                'starter_code' => "function fizzBuzz(n) {\n  // Seu código aqui\n}",
                'xp_reward' => 150,
                'diamond_reward' => 20,
                'difficulty' => 'Médio',
            ],
            [
                'title' => 'Inversor de Strings',
                'slug' => 'string-reverse',
                'category' => 'Algoritmos',
                'description' => 'Manipulação de texto sem métodos nativos.',
                'task' => 'Inverta a string "JavaScript" sem usar o método .reverse(). Use um loop.',
                'starter_code' => "const str = 'JavaScript';\nlet invertida = '';\n// Seu loop aqui\nconsole.log(invertida);",
                'xp_reward' => 100,
                'diamond_reward' => 15,
                'difficulty' => 'Fácil',
            ],
            [
                'title' => 'Maior Número',
                'slug' => 'max-number',
                'category' => 'Lógica',
                'description' => 'Encontre o valor máximo em uma lista.',
                'task' => 'Dado o array [10, 5, 20, 8, 12], encontre o maior número sem usar Math.max().',
                'starter_code' => "const nums = [10, 5, 20, 8, 12];\nlet maior = nums[0];\n// Seu código aqui\nconsole.log(maior);",
                'xp_reward' => 100,
                'diamond_reward' => 10,
                'difficulty' => 'Fácil',
            ],
            [
                'title' => 'Contador de Vogais',
                'slug' => 'vowel-count',
                'category' => 'Strings',
                'description' => 'Analise a composição de uma frase.',
                'task' => 'Conte quantas vogais existem na frase "Eu amo programar em JavaScript".',
                'starter_code' => "const frase = 'Eu amo programar em JavaScript';\nlet contador = 0;\n// Seu código aqui\nconsole.log(contador);",
                'xp_reward' => 120,
                'diamond_reward' => 15,
                'difficulty' => 'Fácil',
            ],
            [
                'title' => 'Palíndromo Pro',
                'slug' => 'palindrome-check',
                'category' => 'Algoritmos',
                'description' => 'Verificação simétrica de palavras.',
                'task' => 'Verifique se a palavra "arara" é um palíndromo. Logue true ou false.',
                'starter_code' => "const palavra = 'arara';\n// Seu código aqui",
                'xp_reward' => 150,
                'diamond_reward' => 20,
                'difficulty' => 'Médio',
            ],
            [
                'title' => 'Fatorial Recursivo',
                'slug' => 'recursive-factorial',
                'category' => 'Matemática',
                'description' => 'Entenda a recursão, um conceito avançado.',
                'task' => 'Calcule o fatorial de 5 (5 * 4 * 3 * 2 * 1) usando uma função recursiva.',
                'starter_code' => "function fatorial(n) {\n  // Seu código aqui\n}",
                'xp_reward' => 250,
                'diamond_reward' => 40,
                'difficulty' => 'Difícil',
            ],
        ];

        foreach ($challenges as $c) {
            LogicChallenge::updateOrCreate(['slug' => $c['slug']], $c);
        }
    }
}
