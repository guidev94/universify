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

        $challenges = array_merge($challenges, [
            [
                'title' => 'Two Sum',
                'slug' => 'two-sum',
                'category' => 'Arrays',
                'description' => 'Clássico de entrevistas técnicas do Google/Meta.',
                'task' => 'Dado um array de números e um alvo, encontre dois índices cujos valores somam o alvo. Ex: twoSum([2, 7, 11, 15], 9) → [0, 1].',
                'starter_code' => "function twoSum(nums, target) {\n  // Use um Map para O(n)\n}",
                'xp_reward' => 200,
                'diamond_reward' => 30,
                'difficulty' => 'Médio',
            ],
            [
                'title' => 'Debounce Function',
                'slug' => 'debounce',
                'category' => 'Padrões',
                'description' => 'Padrão essencial para otimização de performance.',
                'task' => 'Implemente uma função debounce(fn, delay) que atrasa a execução de fn até delay ms após a última chamada.',
                'starter_code' => "function debounce(fn, delay) {\n  let timer;\n  return function(...args) {\n    // Sua implementação\n  };\n}",
                'xp_reward' => 250,
                'diamond_reward' => 40,
                'difficulty' => 'Difícil',
            ],
            [
                'title' => 'Deep Clone',
                'slug' => 'deep-clone',
                'category' => 'Objetos',
                'description' => 'Clonagem profunda sem JSON.parse hack.',
                'task' => 'Implemente deepClone(obj) que cria uma cópia profunda de qualquer objeto/array sem usar JSON.parse/JSON.stringify.',
                'starter_code' => "function deepClone(obj) {\n  // Sua implementação recursiva\n}",
                'xp_reward' => 300,
                'diamond_reward' => 50,
                'difficulty' => 'Difícil',
            ],
            [
                'title' => 'Flatten Array',
                'slug' => 'flatten-array',
                'category' => 'Arrays',
                'description' => 'Transforme um array multidimensional em linear.',
                'task' => 'Crie uma função flatten([[1,2],[3,[4,5]],6]) que retorna [1,2,3,4,5,6] sem usar Array.flat().',
                'starter_code' => "function flatten(arr) {\n  // Use recursão\n}",
                'xp_reward' => 200,
                'diamond_reward' => 30,
                'difficulty' => 'Médio',
            ],
            [
                'title' => 'Memoize',
                'slug' => 'memoize',
                'category' => 'Padrões',
                'description' => 'Otimização com cache de resultados.',
                'task' => 'Implemente memoize(fn) que cacheia os resultados de fn para os mesmos argumentos.',
                'starter_code' => "function memoize(fn) {\n  const cache = new Map();\n  return function(...args) {\n    // Verifique o cache\n  };\n}",
                'xp_reward' => 250,
                'diamond_reward' => 40,
                'difficulty' => 'Difícil',
            ],
            [
                'title' => 'Anagramas',
                'slug' => 'anagramas',
                'category' => 'Strings',
                'description' => 'Verificação de anagramas eficiente.',
                'task' => 'Crie isAnagram(str1, str2) que retorna true se as strings são anagramas. "listen" e "silent" são anagramas.',
                'starter_code' => "function isAnagram(str1, str2) {\n  // Normalize e compare\n}",
                'xp_reward' => 150,
                'diamond_reward' => 20,
                'difficulty' => 'Fácil',
            ],
        ]);

        foreach ($challenges as $c) {
            LogicChallenge::updateOrCreate(['slug' => $c['slug']], $c);
        }
    }
}
