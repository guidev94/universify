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
                'name' => 'Unidade 1: Lógica Fundamental',
                'slug' => 'logica-fundamental',
                'description' => 'Domine o básico: variáveis, loops, condicionais e funções.',
                'badge' => ['name' => 'Logic Master', 'icon' => '🧩', 'description' => 'Dominou a lógica básica.'],
                'lessons' => [
                    [
                        'title' => 'Variáveis e Tipos',
                        'slug' => 'variaveis-tipos',
                        'content' => 'JavaScript possui três formas de declarar variáveis: var, let e const. Atualmente, recomendamos o uso de let para valores que mudam e const para valores fixos.',
                        'starter_code' => '// Declare uma variável chamada "idade" com o valor 25\n',
                        'solution_code' => 'let idade = 25; console.log(idade);',
                        'tips' => 'Use const se o valor não precisar ser reatribuído. Use let se você pretende mudar o valor depois.',
                        'examples' => 'const PI = 3.14;\nlet contador = 0;\ncontador++;',
                        'real_world_case' => 'Em um sistema de e-commerce, usamos const para o ID de um produto e let para a quantidade no carrinho.',
                        'docs_link' => 'https://developer.mozilla.org/pt-BR/docs/Web/JavaScript/Guide/Grammar_and_types#declarando_vari%C3%A1veis',
                    ],
                    [
                        'title' => 'Condicionais (If/Else)',
                        'slug' => 'condicionais',
                        'content' => 'Estruturas condicionais permitem que seu código tome decisões baseadas em valores booleanos (true/false).',
                        'starter_code' => 'let nota = 8;\n// Se a nota for maior ou igual a 7, logue "Aprovado"\n',
                        'solution_code' => 'let nota = 8; if(nota >= 7) { console.log("Aprovado"); }',
                        'tips' => 'O operador >= significa "maior ou igual".',
                        'examples' => 'if (idade >= 18) {\n  console.log("Adulto");\n} else {\n  console.log("Menor");\n}',
                        'real_world_case' => 'Sistemas de login usam condicionais para verificar se a senha digitada coincide com a salva no banco.',
                        'docs_link' => 'https://developer.mozilla.org/pt-BR/docs/Web/JavaScript/Reference/Statements/if...else',
                    ],
                    [
                        'title' => 'Loops (For)',
                        'slug' => 'loops-for',
                        'content' => 'Loops permitem repetir uma ação várias vezes. O loop "for" é o mais comum para contagens definidas.',
                        'starter_code' => '// Crie um loop que conte de 1 a 5 no console\n',
                        'solution_code' => 'for(let i=1; i<=5; i++) { console.log(i); }',
                        'tips' => 'A estrutura do for é: (início; condição; incremento).',
                        'examples' => 'for(let i=0; i<10; i++) {\n  console.log(i);\n}',
                        'real_world_case' => 'Redes sociais usam loops para percorrer a lista de posts e renderizá-los um por um no seu feed.',
                        'docs_link' => 'https://developer.mozilla.org/pt-BR/docs/Web/JavaScript/Guide/Loops_and_iteration#for_statement',
                    ],
                    [
                        'title' => 'Projeto: Calculadora',
                        'slug' => 'projeto-calculadora',
                        'content' => 'Funções são blocos de código que executam tarefas específicas. Vamos criar uma função que soma dois números.',
                        'starter_code' => 'function somar(a, b) {\n  // Seu código aqui\n}',
                        'solution_code' => 'function somar(a, b) { return a + b; }',
                        'tips' => 'A palavra-chave "return" envia o valor resultante para fora da função.',
                        'examples' => 'function saudacao(nome) {\n  return "Olá " + nome;\n}',
                        'real_world_case' => 'Calculadoras de frete em sites usam funções para processar o peso e a distância.',
                        'docs_link' => 'https://developer.mozilla.org/pt-BR/docs/Web/JavaScript/Guide/Functions',
                    ],
                ],
            ],
            [
                'name' => 'Unidade 2: DOM & LocalStorage',
                'slug' => 'dom-localstorage',
                'description' => 'Interaja com o navegador e persista dados.',
                'badge' => ['name' => 'DOM Ninja', 'icon' => '🌐', 'description' => 'Manipula o DOM como um mestre.'],
                'lessons' => [
                    [
                        'title' => 'Selecionando Elementos',
                        'slug' => 'selecionando-elementos',
                        'content' => 'O DOM permite que o JS acesse o HTML. querySelector é a ferramenta mais poderosa para isso.',
                        'starter_code' => '// Selecione o elemento h1 e mude seu texto para "JS Academy"\n',
                        'solution_code' => 'const h1 = document.querySelector("h1"); h1.innerText = "JS Academy";',
                        'tips' => 'Você pode usar seletores de CSS como "#id", ".classe" ou "tag".',
                        'examples' => 'const btn = document.querySelector(".btn-submit");\nbtn.style.color = "red";',
                        'real_world_case' => 'Quando você clica em "curtir", o JS seleciona o ícone e muda sua cor.',
                        'docs_link' => 'https://developer.mozilla.org/pt-BR/docs/Web/API/Document/querySelector',
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
                        'starter_code' => $lessonData['starter_code'],
                        'solution_code' => $lessonData['solution_code'],
                        'tips' => $lessonData['tips'] ?? null,
                        'examples' => $lessonData['examples'] ?? null,
                        'real_world_case' => $lessonData['real_world_case'] ?? null,
                        'docs_link' => $lessonData['docs_link'] ?? null,
                        'xp_reward' => 50,
                        'order' => $index + 1,
                    ]
                );
            }
        }
    }
}
