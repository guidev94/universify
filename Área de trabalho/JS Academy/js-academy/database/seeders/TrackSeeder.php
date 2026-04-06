<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Track;
use App\Models\Badge;

class TrackSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            [
                'name' => 'Unidade 1: Lógica Fundamental',
                'slug' => 'logica-fundamental',
                'description' => 'Domine o básico: variáveis, loops e funções.',
                'badge' => ['name' => 'Logic Master', 'icon' => '🧩', 'description' => 'Dominou a lógica básica.'],
                'lessons' => [
                    ['title' => 'Variáveis e Tipos', 'slug' => 'variaveis-tipos', 'content' => 'Explore let, const e os tipos primitivos.', 'starter_code' => 'let x = 10;', 'solution_code' => 'let x = 10; console.log(x);'],
                    ['title' => 'Projeto: Calculadora', 'slug' => 'projeto-calculadora', 'content' => 'Crie uma calculadora funcional.', 'starter_code' => 'function add(a, b) { }', 'solution_code' => 'function add(a, b) { return a + b; }'],
                ]
            ],
            [
                'name' => 'Unidade 2: DOM & LocalStorage',
                'slug' => 'dom-localstorage',
                'description' => 'Interaja com o navegador e persista dados.',
                'badge' => ['name' => 'DOM Ninja', 'icon' => '🌐', 'description' => 'Manipula o DOM como um mestre.'],
                'lessons' => [
                    ['title' => 'Selecionando Elementos', 'slug' => 'selecionando-elementos', 'content' => 'Use querySelector e getElementById.', 'starter_code' => '// Selecione o h1', 'solution_code' => 'const h1 = document.querySelector("h1");'],
                    ['title' => 'Projeto: TaskManager', 'slug' => 'projeto-taskmanager', 'content' => 'Gerenciador de tarefas com LocalStorage.', 'starter_code' => 'const save = (tasks) => { }', 'solution_code' => 'const save = (tasks) => localStorage.setItem("tasks", JSON.stringify(tasks));'],
                ]
            ],
            [
                'name' => 'Unidade 3: ES6+ (Arrow, Classes, Modules)',
                'slug' => 'es6-moderno',
                'description' => 'JS Moderno com as melhores features.',
                'badge' => ['name' => 'ES6 Pro', 'icon' => '🚀', 'description' => 'Maestria em JavaScript moderno.'],
                'lessons' => [
                    ['title' => 'Arrow Functions', 'slug' => 'arrow-functions', 'content' => 'Simplifique suas funções.', 'starter_code' => 'function sayHello() { }', 'solution_code' => 'const sayHello = () => "Hello";'],
                    ['title' => 'Projeto: Busca JS', 'slug' => 'projeto-busca', 'content' => 'Sistema de busca usando filter e map.', 'starter_code' => 'const search = (q) => { }', 'solution_code' => 'const search = (q) => items.filter(i => i.includes(q));'],
                ]
            ],
            [
                'name' => 'Unidade 4: Async/Await & APIs',
                'slug' => 'async-apis',
                'description' => 'Consuma dados reais com fetch e promises.',
                'badge' => ['name' => 'API Wizard', 'icon' => '📡', 'description' => 'Integração perfeita com serviços externos.'],
                'lessons' => [
                    ['title' => 'Fetch API', 'slug' => 'fetch-api', 'content' => 'Fazendo requisições assíncronas.', 'starter_code' => 'async function getData() { }', 'solution_code' => 'async function getData() { const res = await fetch("/"); return res.json(); }'],
                    ['title' => 'Projeto: Crypto Dashboard', 'slug' => 'projeto-crypto', 'content' => 'Dashboard de preços em tempo real.', 'starter_code' => 'const updatePrice = () => { }', 'solution_code' => 'const updatePrice = async () => { const p = await getPrice(); document.body.innerText = p; }'],
                ]
            ],
            [
                'name' => 'Unidade 5: Patterns & Refactoring',
                'slug' => 'patterns-refactoring',
                'description' => 'Código limpo e padrões de projeto.',
                'badge' => ['name' => 'Clean Coder', 'icon' => '✨', 'description' => 'Código legível e escalável.'],
                'lessons' => [
                    ['title' => 'Clean Code Principles', 'slug' => 'clean-code-intro', 'content' => 'Nomes claros e funções curtas.', 'starter_code' => 'function d(a, b) { return a + b; }', 'solution_code' => 'function addNumbers(num1, num2) { return num1 + num2; }'],
                    ['title' => 'Projeto: Checkout Clean Code', 'slug' => 'projeto-checkout', 'content' => 'Refatore um checkout complexo.', 'starter_code' => 'const process = () => { }', 'solution_code' => 'const processCheckout = (cart) => { validate(cart); save(cart); };'],
                ]
            ],
            [
                'name' => 'Unidade 6: Node & Testing (Intro)',
                'slug' => 'node-testing',
                'description' => 'Backend e Testes Automatizados.',
                'badge' => ['name' => 'Fullstack Hero', 'icon' => '🛡️', 'description' => 'Conhecimento de ponta a ponta.'],
                'lessons' => [
                    ['title' => 'Intro ao Node.js', 'slug' => 'node-intro', 'content' => 'Ambiente de execução backend.', 'starter_code' => 'const fs = require("fs");', 'solution_code' => 'const fs = require("fs"); fs.writeFileSync("test.txt", "JS Academy");'],
                    ['title' => 'Projeto: Validador de Dados', 'slug' => 'projeto-validador', 'content' => 'Crie testes para um validador.', 'starter_code' => 'test("isEmailValid", () => { })', 'solution_code' => 'test("isEmailValid", () => expect(isEmailValid("test@test.com")).toBe(true));'],
                ]
            ],
        ];

        foreach ($units as $unitData) {
            $track = Track::create([
                'name' => $unitData['name'],
                'slug' => $unitData['slug'],
                'description' => $unitData['description'],
            ]);

            Badge::create([
                'name' => $unitData['badge']['name'],
                'icon' => $unitData['badge']['icon'],
                'description' => $unitData['badge']['description'],
                'track_id' => $track->id,
            ]);

            foreach ($unitData['lessons'] as $index => $lessonData) {
                $track->lessons()->create([
                    'title' => $lessonData['title'],
                    'slug' => $lessonData['slug'],
                    'content' => $lessonData['content'],
                    'starter_code' => $lessonData['starter_code'],
                    'solution_code' => $lessonData['solution_code'],
                    'xp_reward' => ($index + 1) * 50,
                    'order' => $index + 1
                ]);
            }
        }
    }
}
