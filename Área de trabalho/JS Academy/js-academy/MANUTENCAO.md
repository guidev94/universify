# 🛠️ Guia de Manutenção e Evolução - JS Academy

Este documento serve como referência técnica para desenvolvedores que desejam manter ou expandir a plataforma JS Academy.

---

## 🏛️ Arquitetura do Sistema

A plataforma é construída sobre o stack **Laravel 11**, **PostgreSQL** e **Tailwind CSS v4**. O frontend utiliza **Alpine.js** para interatividade leve e **Monaco Editor** para o ambiente de codificação.

### 🗄️ Estrutura de Banco de Dados (Principais Modelos)
*   **User:** Gerencia autenticação e gamificação (XP, Diamantes, Streak).
*   **Track (Trilha):** Agrupamento de lições (Módulos).
*   **Lesson (Lição):** Conteúdo didático, tarefas, exemplos e código inicial.
*   **LogicChallenge:** Desafios isolados da Arena de Lógica.
*   **Progress:** Pivot entre User e Lesson para rastrear conclusão.

---

## 🚀 Como Adicionar Novo Conteúdo

### 1. Adicionar Nova Unidade/Trilha
Edite o arquivo `database/seeders/TrackSeeder.php`.
Adicione um novo array ao método `run()` seguindo o padrão:
```php
[
    'name' => 'Título da Unidade',
    'slug' => 'slug-unico',
    'description' => 'Descrição chamativa...',
    'badge' => ['name' => 'Nome da Medalha', 'icon' => '🏆', 'description' => '...'],
    'lessons' => [ ... ]
]
```

### 2. Adicionar Novo Desafio na Arena
Edite o arquivo `database/seeders/ChallengeSeeder.php`.
Após editar, execute:
```bash
php artisan db:seed --class=ChallengeSeeder
```

---

## 🛡️ Segurança e Proteções
*   **Anti-Cheat:** O sistema bloqueia `Ctrl+C`, `Ctrl+V`, `Ctrl+U` e Clique Direito via JavaScript (`desafios.blade.php` e `app.js`).
*   **SQL Injection:** Proteção nativa via Eloquent ORM (sempre use `where()` e evite `DB::raw()` com inputs de usuário).
*   **Sandbox de Código:** A execução via `eval()` ocorre apenas no lado do cliente (navegador). O código do usuário **nunca** é executado no servidor.

---

## 💅 Estilização (Tailwind v4)
Não existe arquivo `tailwind.config.js`. Todas as configurações e variáveis estão em `resources/css/app.css` usando a nova sintaxe de variáveis CSS `--color-*`.

---

## 📦 Comandos de Manutenção
*   **Limpar Tudo:** `php artisan optimize:clear`
*   **Atualizar Assets:** `npm run build`
*   **Reiniciar Banco (Cuidado):** `php artisan migrate:fresh --seed`

---
*Mantido por **AquinoDev** • 2026*
