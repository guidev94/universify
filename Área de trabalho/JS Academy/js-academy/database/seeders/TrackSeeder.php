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
                'name' => 'Unidade 1: Lógica de Ferro (Fundamentos)',
                'slug' => 'logica-ferro',
                'description' => 'A base científica da programação. Aprenda a estruturar o pensamento antes de escrever uma única linha.',
                'badge' => ['name' => 'Lógica de Ferro', 'icon' => '⛓️', 'description' => 'Mestre dos algoritmos básicos.'],
                'lessons' => [
                    [
                        'title' => 'O que é um Algoritmo?',
                        'slug' => 'conceito-algoritmo',
                        'content' => "Um algoritmo é uma sequência finita de instruções para resolver um problema. Pense nele como uma receita de bolo ou o GPS do seu carro.\n\n**O que aprender:**\n- Entrada de dados\n- Processamento\n- Saída de informação",
                        'task' => 'Declare duas variáveis "a" e "b" com valores 5 e 10. Crie uma variável "resultado" que some ambas e logue no console.',
                        'starter_code' => "// Sua receita começa aqui\n",
                        'solution_code' => 'let a = 5; let b = 10; let resultado = a + b; console.log(resultado);',
                        'tips' => 'Variáveis guardam o estado do seu algoritmo.',
                        'examples' => "let passo1 = \"Acordar\";\nlet passo2 = \"Escovar os dentes\";",
                        'real_world_case' => 'O algoritmo de busca do Google processa sua "entrada" (palavras) e gera uma "saída" (links relevantes).',
                        'docs_link' => 'https://developer.mozilla.org/pt-BR/docs/Learn/JavaScript/First_steps/What_is_JavaScript',
                    ],
                    [
                        'title' => 'Memória e Variáveis (let vs const)',
                        'slug' => 'memoria-variaveis',
                        'content' => "Variáveis reservam espaços na memória RAM. \n- **const**: Reserva um espaço que não pode ser reescrito (Segurança).\n- **let**: Permite que o valor mude durante a execução (Flexibilidade).",
                        'task' => 'Crie uma CONST para o seu nome e uma LET para sua idade. Mude a idade para um valor maior e logue ambos.',
                        'starter_code' => "// Use const e let corretamente\n",
                        'solution_code' => 'const nome = "Dev"; let idade = 20; idade = 21; console.log(nome, idade);',
                        'tips' => 'Sempre prefira const por padrão. Só use let se for realmente mudar o valor.',
                        'examples' => "const CPF = '123.456.789-00';\nlet saldo = 1000;",
                        'real_world_case' => 'Em um checkout de E-commerce, o ID do pedido é CONST, mas o status do pagamento é LET.',
                        'docs_link' => 'https://developer.mozilla.org/pt-BR/docs/Web/JavaScript/Guide/Grammar_and_types#declarando_vari%C3%A1veis',
                    ],
                    [
                        'title' => 'A Tabela Verdade (Booleanos)',
                        'slug' => 'tabela-verdade',
                        'content' => 'Computadores só entendem 0 e 1 (Verdadeiro ou Falso). Operadores lógicos (&&, ||, !) permitem combinar essas certezas.',
                        'task' => 'Crie duas variáveis booleanas: "temChave" e "sabeSenha". Logue true apenas se ambas forem verdadeiras.',
                        'starter_code' => "let temChave = true;\nlet sabeSenha = false;\n// Logue o resultado da combinação AND (&&)\n",
                        'solution_code' => 'let temChave = true; let sabeSenha = false; console.log(temChave && sabeSenha);',
                        'tips' => '&& (E) exige que tudo seja true. || (OU) exige que apenas um seja true.',
                        'examples' => "true && false // false\ntrue || false // true",
                        'real_world_case' => 'Para liberar um saque no caixa eletrônico: (Saldo > 0) && (Senha Correta).',
                        'docs_link' => 'https://developer.mozilla.org/pt-BR/docs/Web/JavaScript/Reference/Operators/Logical_Operators',
                    ],
                    [
                        'title' => 'O Fluxo de Decisão (IF/ELSE)',
                        'slug' => 'decisao-fluxo',
                        'content' => "O código não precisa ser uma linha reta. Com o IF, você cria bifurcações: 'Se isso acontecer, faça X, senão faça Y'.",
                        'task' => 'Se uma variável "temperatura" for maior que 30, logue "Calor", senão "Agradável".',
                        'starter_code' => "let temperatura = 32;\n",
                        'solution_code' => 'let temperatura = 32; if(temperatura > 30) { console.log("Calor"); } else { console.log("Agradável"); }',
                        'tips' => 'O bloco else é opcional, mas essencial para cobrir todas as possibilidades.',
                        'examples' => 'if(estaLogado) { abrirApp(); }',
                        'real_world_case' => 'O Netflix verifica se você é "Kids" ou "Adulto" para mostrar conteúdos diferentes.',
                        'docs_link' => 'https://developer.mozilla.org/pt-BR/docs/Web/JavaScript/Reference/Statements/if...else',
                    ],
                    [
                        'title' => 'Repetição: O Loop FOR',
                        'slug' => 'loop-for-deep',
                        'content' => 'Programadores odeiam tarefas repetitivas. O loop FOR automatiza processos: define onde começa, onde termina e o que muda a cada passo.',
                        'task' => 'Use um loop FOR para logar os números de 0 a 4.',
                        'starter_code' => "// Estrutura: for(inicio; condicao; incremento)\n",
                        'solution_code' => 'for(let i=0; i<5; i++) { console.log(i); }',
                        'tips' => 'A variável "i" é comumente usada como um "index" ou contador.',
                        'examples' => 'for(let i=0; i<10; i++) { ... }',
                        'real_world_case' => 'Percorrer a lista de compras para somar os preços de cada item.',
                        'docs_link' => 'https://developer.mozilla.org/pt-BR/docs/Web/JavaScript/Guide/Loops_and_iteration',
                    ],
                    [
                        'title' => 'Funções: Motores Reutilizáveis',
                        'slug' => 'funcoes-motores',
                        'content' => "Funções empacotam uma lógica para ser usada várias vezes. Elas podem receber 'combustível' (parâmetros) e devolver 'produtos' (return).",
                        'task' => 'Crie uma função "saudar" que recebe um nome e retorna "Olá, [nome]!". Logue o resultado.',
                        'starter_code' => "function saudar(nome) {\n  // Seu código\n}\n",
                        'solution_code' => 'function saudar(nome) { return "Olá, " + nome + "!"; } console.log(saudar("Gui"));',
                        'tips' => 'O "return" é o que a função entrega para quem a chamou.',
                        'examples' => 'function area(l) { return l * l; }',
                        'real_world_case' => 'Uma função "calcularDesconto()" que é usada em todas as páginas de um e-commerce.',
                        'docs_link' => 'https://developer.mozilla.org/pt-BR/docs/Web/JavaScript/Guide/Functions',
                    ],
                ],
            ],
            [
                'name' => 'Unidade 2: Coleções & Objetos (SaaS Ready)',
                'slug' => 'colecoes-saas',
                'description' => 'Como organizar dados de milhares de usuários em um sistema real.',
                'badge' => ['name' => 'Arquiteto de Dados', 'icon' => '🏛️', 'description' => 'Domina a estrutura de dados.'],
                'lessons' => [
                    [
                        'title' => 'Arrays e Métodos Modernos',
                        'slug' => 'arrays-modernos',
                        'content' => 'Arrays são listas. Hoje, usamos métodos como .push(), .pop() e .length para manipulá-las sem estresse.',
                        'task' => 'Crie um array "tecnologias" com JS e PHP. Adicione "Node" e logue o tamanho do array.',
                        'starter_code' => "const tecnologias = ['JS', 'PHP'];\n",
                        'solution_code' => "const tecnologias = ['JS', 'PHP']; tecnologias.push('Node'); console.log(tecnologias.length);",
                        'tips' => 'O método .push() insere no final da lista.',
                        'examples' => "lista.push('Novo');",
                        'real_world_case' => 'A lista de amigos sugeridos no Facebook é um grande Array que o sistema percorre.',
                        'docs_link' => 'https://developer.mozilla.org/pt-BR/docs/Web/JavaScript/Reference/Global_Objects/Array',
                    ],
                    [
                        'title' => 'Objetos: Modelando a Realidade',
                        'slug' => 'objetos-realidade',
                        'content' => 'Objetos agrupam características (propriedades) e ações (métodos) de algo do mundo real.',
                        'task' => 'Crie um objeto "carro" com marca, ano e uma função "buzinar" que logue "Beep!".',
                        'starter_code' => "const carro = {\n  // Propriedades aqui\n};\n",
                        'solution_code' => 'const carro = { marca: "Audi", ano: 2024, buzinar: () => console.log("Beep!") }; carro.buzinar();',
                        'tips' => 'Objetos usam a estrutura chave: valor.',
                        'examples' => "const user = { id: 1, nome: 'Ana' };",
                        'real_world_case' => 'Cada usuário logado no seu site é um Objeto com nome, email e nível de acesso.',
                        'docs_link' => 'https://developer.mozilla.org/pt-BR/docs/Web/JavaScript/Guide/Working_with_Objects',
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
