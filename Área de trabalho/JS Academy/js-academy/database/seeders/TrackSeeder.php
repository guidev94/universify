<?php

namespace Database\Seeders;

use App\Models\Badge;
use App\Models\Track;
use Illuminate\Database\Seeder;

class TrackSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            [
                'name' => 'Unidade 1: Fundamentos de Elite',
                'slug' => 'fundamentos-elite',
                'description' => 'Aprenda a base sólida que separa os amadores dos profissionais.',
                'badge' => ['name' => 'Base de Ferro', 'icon' => '🏗️', 'description' => 'Dominou os fundamentos.'],
                'lessons' => [
                    [
                        'title' => 'Variáveis: Onde tudo nasce',
                        'slug' => 'variaveis-elite',
                        'content' => "Variáveis são como etiquetas em gavetas onde guardamos informações. No JavaScript moderno, temos três formas de declarar:\n\n1. **const**: Use para valores que nunca mudam (ex: CPF, Data de Nascimento).\n2. **let**: Use para valores que podem ser alterados (ex: Saldo, Contador).\n3. **var**: Antigo, evite usar pois possui comportamentos imprevisíveis de escopo.",
                        'task' => 'Declare uma constante chamada "escola" com o valor "JS Academy" e uma variável "let" chamada "alunos" com o valor 10.',
                        'starter_code' => "// Digite seu código abaixo\n",
                        'solution_code' => 'const escola = "JS Academy"; let alunos = 10; console.log(escola, alunos);',
                        'tips' => 'Lembre-se: strings devem estar entre aspas ("texto").',
                        'examples' => "const PI = 3.14;\nlet contador = 1;\ncontador = 2;",
                        'real_world_case' => 'Em um sistema bancário, o número da conta é uma CONST, mas o saldo disponível é uma LET.',
                        'docs_link' => 'https://developer.mozilla.org/pt-BR/docs/Web/JavaScript/Guide/Grammar_and_types#declarando_vari%C3%A1veis',
                    ],
                    [
                        'title' => 'Tipos de Dados',
                        'slug' => 'tipagem-js',
                        'content' => "O JavaScript possui diversos tipos de dados fundamentais:\n\n- **String**: Textos (ex: \"Olá\").\n- **Number**: Números inteiros ou decimais (ex: 10, 5.5).\n- **Boolean**: Verdadeiro ou Falso (true/false).\n- **Null/Undefined**: Valores vazios ou não definidos.",
                        'task' => 'Crie uma variável para guardar um nome (String), uma para idade (Number) e uma para se está ativo (Boolean).',
                        'starter_code' => "// Ex: let nome = \"João\";\n",
                        'solution_code' => 'let nome = "Dev"; let idade = 20; let ativo = true;',
                        'tips' => 'Use typeof para verificar o tipo de uma variável no console.',
                        'examples' => "let texto = \"JS\"; // String\nlet num = 100; // Number\nlet check = false; // Boolean",
                        'real_world_case' => 'Formulários de cadastro capturam Strings (nome), Numbers (idade) e Booleans (aceite de termos).',
                        'docs_link' => 'https://developer.mozilla.org/pt-BR/docs/Web/JavaScript/Data_structures',
                    ],
                ],
            ],
            [
                'name' => 'Unidade 2: Lógica e Decisões',
                'slug' => 'logica-controle',
                'description' => 'Ensine seu programa a tomar decisões inteligentes.',
                'badge' => ['name' => 'Cérebro Lógico', 'icon' => '🧠', 'description' => 'Mestre das decisões.'],
                'lessons' => [
                    [
                        'title' => 'Condicionais IF/ELSE',
                        'slug' => 'if-else-master',
                        'content' => 'Estruturas de controle permitem que o código siga caminhos diferentes. Se (IF) uma condição for verdadeira, o primeiro bloco executa. Senão (ELSE), o segundo bloco assume.',
                        'task' => 'Crie uma variável "pontos". Se pontos for maior que 100, logue "Venceu", senão logue "Tente novamente".',
                        'starter_code' => "let pontos = 120;\n",
                        'solution_code' => 'let pontos = 120; if(pontos > 100) { console.log("Venceu"); } else { console.log("Tente novamente"); }',
                        'tips' => 'Use as chaves { } para agrupar o código que deve rodar dentro do IF.',
                        'examples' => "if (idade >= 18) {\n  console.log(\"Adulto\");\n} else {\n  console.log(\"Menor\");\n}",
                        'real_world_case' => 'Sites de compras verificam se você tem saldo suficiente antes de aprovar um pedido.',
                        'docs_link' => 'https://developer.mozilla.org/pt-BR/docs/Web/JavaScript/Reference/Statements/if...else',
                    ],
                ],
            ],
            [
                'name' => 'Unidade 3: Listas e Repetições',
                'slug' => 'arrays-loops',
                'description' => 'Trabalhe com grandes volumes de dados de forma eficiente.',
                'badge' => ['name' => 'Data Master', 'icon' => '📊', 'description' => 'Mestre das coleções.'],
                'lessons' => [
                    [
                        'title' => 'Arrays (Listas)',
                        'slug' => 'arrays-basico',
                        'content' => 'Arrays são listas ordenadas de valores. Você pode guardar strings, números e até outros objetos dentro deles.',
                        'task' => 'Crie um array chamado "linguagens" com "JS", "PHP" e "Python". Adicione "Go" ao final usando .push().',
                        'starter_code' => "const linguagens = [];\n",
                        'solution_code' => 'const linguagens = ["JS", "PHP", "Python"]; linguagens.push("Go"); console.log(linguagens);',
                        'tips' => 'Lembre-se que o primeiro item de um array fica na posição 0.',
                        'examples' => "const frutas = [\"Maçã\", \"Uva\"];\nfrutas.push(\"Pera\");",
                        'real_world_case' => 'Sua lista de contatos no WhatsApp é um grande Array de objetos.',
                        'docs_link' => 'https://developer.mozilla.org/pt-BR/docs/Web/JavaScript/Reference/Global_Objects/Array',
                    ],
                ],
            ],
            [
                'name' => 'Unidade 4: Funções e Automação',
                'slug' => 'funcoes-js',
                'description' => 'Crie blocos de código reutilizáveis e evite repetição.',
                'badge' => ['name' => 'Automator', 'icon' => '⚙️', 'description' => 'Mestre da reutilização.'],
                'lessons' => [
                    [
                        'title' => 'Criando Funções',
                        'slug' => 'funcoes-basico',
                        'content' => "Funções são pequenos 'motores' que recebem dados (parâmetros), processam e devolvem um resultado (return).",
                        'task' => 'Crie uma função chamada "dobro" que recebe um número e retorna ele multiplicado por 2.',
                        'starter_code' => "function dobro(n) {\n  // Seu código aqui\n}\n",
                        'solution_code' => 'function dobro(n) { return n * 2; } console.log(dobro(5));',
                        'tips' => 'Não esqueça da palavra-chave return para enviar o valor de volta.',
                        'examples' => "function soma(a, b) {\n  return a + b;\n}",
                        'real_world_case' => 'O botão de "Curtir" dispara uma função que envia seu ID para o servidor.',
                        'docs_link' => 'https://developer.mozilla.org/pt-BR/docs/Web/JavaScript/Guide/Functions',
                    ],
                ],
            ],
        ];

        foreach ($units as $unitData) {
            $track = Track::updateOrCreate(
                ['slug' => $unitData['slug']],
                [
                    'name' => $unitData['name'],
                    'description' => $unitData['description'],
                ]
            );

            Badge::updateOrCreate(
                ['track_id' => $track->id],
                [
                    'name' => $unitData['badge']['name'],
                    'icon' => $unitData['badge']['icon'],
                    'description' => $unitData['badge']['description'],
                ]
            );

            foreach ($unitData['lessons'] as $index => $lessonData) {
                $track->lessons()->updateOrCreate(
                    ['slug' => $lessonData['slug']],
                    [
                        'title' => $lessonData['title'],
                        'content' => $lessonData['content'],
                        'task' => $lessonData['task'],
                        'starter_code' => $lessonData['starter_code'],
                        'solution_code' => $lessonData['solution_code'],
                        'tips' => $lessonData['tips'] ?? null,
                        'examples' => $lessonData['examples'] ?? null,
                        'real_world_case' => $lessonData['real_world_case'] ?? null,
                        'docs_link' => $lessonData['docs_link'] ?? null,
                        'xp_reward' => 100,
                        'order' => $index + 1,
                    ]
                );
            }
        }
    }
}
