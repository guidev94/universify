# JS Academy — Documentacao Tecnica

Plataforma gamificada de aprendizado JavaScript. Laravel 11, PostgreSQL, Tailwind CSS 4, Alpine.js, GSAP, Monaco Editor, Filament 3.

## Stack Tecnica

| Camada | Tecnologia | Versao |
|--------|-----------|--------|
| Backend | Laravel | 11.x |
| Database | PostgreSQL | 16 |
| Frontend | Tailwind CSS 4 + Alpine.js 3 | - |
| Editor | Monaco Editor | 0.55 |
| Animacoes | GSAP 3 + ScrollTrigger | 3.14 |
| Admin | Filament | 3.x |
| Assets | Vite | 6.x |

## Estrutura de Arquivos Principais

```
app/
├── Http/Controllers/
│   └── ProgressController.php    # Gamificacao: XP, streak, badges, skip
├── Models/
│   ├── User.php                  # Usuario com campos: xp, level, streak, diamonds, league
│   ├── Track.php                 # Trilha de aprendizado (tem badge e licoes)
│   ├── Lesson.php                # Licao com: content, task, starter_code, solution_code, tips
│   ├── Progress.php              # user_id + lesson_id + status ('completed')
│   ├── Badge.php                 # Conquista vinculada a uma track
│   ├── LogicChallenge.php        # Desafio de logica avulso
│   └── PageView.php              # Tracking de acessos
├── Filament/
│   ├── Resources/
│   │   ├── UserResource.php      # CRUD de usuarios com reset de senha
│   │   ├── LessonResource.php    # CRUD de licoes com todos os campos educacionais
│   │   ├── TrackResource.php     # CRUD de trilhas
│   │   ├── LogicChallengeResource.php  # CRUD de desafios
│   │   ├── PageViewResource.php  # Analytics de acessos com export CSV
│   │   └── ProgressResource.php  # Progresso dos alunos com export CSV
│   └── Widgets/
│       └── StatsOverviewWidget.php   # Dashboard: alunos, aulas, XP, views

database/
├── migrations/                   # Todas as migrations ordenadas por data
└── seeders/
    ├── TrackSeeder.php           # 8 unidades, 38 licoes com conteudo completo
    ├── ChallengeSeeder.php       # 12 desafios de logica
    └── DatabaseSeeder.php        # Usuario admin + chama os seeders

resources/
├── css/app.css                   # Design System: bento-card, btn-primary, input-field, dark/light mode
├── js/app.js                     # Monaco Editor, GSAP, tema, intro logica
└── views/
    ├── layouts/
    │   ├── app.blade.php         # Layout principal com nav, dark mode, @yield sections
    │   └── guest.blade.php       # Layout para auth (login, register)
    ├── welcome.blade.php         # Homepage: trilhas, editor, dashboard gamificado
    ├── ranking.blade.php         # Ranking com podio e tabela
    ├── desafios.blade.php        # Arena de desafios com Monaco
    ├── user/profile.blade.php    # Perfil publico do usuario
    └── auth/                     # Login, register, forgot-password (design JS Academy)
```

## Rotas Principais

| Metodo | URL | Descricao |
|--------|-----|-----------|
| GET | `/` | Homepage com trilhas e editor |
| GET | `/ranking` | Ranking de usuarios por XP |
| GET | `/desafios` | Arena de desafios de logica |
| GET | `/user/{id}` | Perfil publico do usuario |
| POST | `/progress/complete-lesson/{id}` | Marcar licao como concluida |
| POST | `/progress/update-xp` | Atualizar XP (desafios) |
| POST | `/progress/skip-track/{id}` | Pular trilha (custa 200 diamantes) |
| POST | `/progress/freeze-streak` | Congelar streak (custa 50 diamantes) |
| GET | `/progress/ranking-json` | Ranking JSON (Top 10) |
| GET | `/admin` | Painel Filament (requer @jsacademy.com) |

## Sistema de Gamificacao

- **XP**: ganho por completar licoes (`xp_reward` por licao) e desafios
- **Nivel**: `floor(xp / 1000) + 1` — sobe a cada 1000 XP
- **Streak**: contador de dias consecutivos de estudo
- **Streak Freeze**: 50 diamantes = 1 protecao de streak
- **Diamantes**: moeda secundaria, ganhos em licoes (`diamond_reward`)
- **Badges**: ganhos ao completar uma trilha inteira; tambem podem ser comprados (200 diamantes = pular trilha)

## Design System (app.css)

```css
/* Componentes principais */
.bento-card        /* Card com backdrop-blur, border sutil, rounded-[2rem] */
.bento-card-hover  /* Hover com translateY(-4px) e shadow */
.btn-primary       /* Botao branco com hover amarelo */
.btn-accent        /* Botao rosa */
.btn-outline       /* Botao transparente com borda */
.input-field       /* Input dark com focus amarelo */
.heading-huge      /* Titulo hero grande */
```

## Dark/Light Mode

- Toggle no header (botao com icone lua/sol SVG)
- Preferencia salva em `localStorage` key: `jsacademy_theme`
- Alpine store: `Alpine.store('theme').light` (boolean)
- CSS: classe `.light-mode` no `<body>` ativa ~80 overrides
- Monaco Editor sincronizado via evento `theme-changed`

## Painel Admin (/admin)

- Acesso: e-mail terminando em `@jsacademy.com`
- Credenciais padrao: `admin@jsacademy.com` / `password`
- **ALTERE A SENHA IMEDIATAMENTE EM PRODUCAO**
- Recursos: Usuarios, Trilhas, Licoes, Desafios, Analytics, Progresso

## Intro de Logica (primeira visita)

- Overlay fixo com explicacao de Entrada/Processamento/Saida
- Botao "Pular" e botao "Comecar"
- Auto-dismiss apos 8 segundos
- Controlado por `localStorage` key: `jsacademy_intro_seen`
- GSAP dismiss animation ao fechar

## Seeders e Dados

```bash
# Popular banco completo (dev)
php artisan db:seed

# Apenas trilhas/licoes
php artisan db:seed --class=TrackSeeder

# Apenas desafios
php artisan db:seed --class=ChallengeSeeder
```

## Testes (52 testes, 111 assertions)

```bash
# Todos os testes
php artisan test

# Apenas Unit (gamificacao)
php artisan test --testsuite=Unit

# Apenas Feature (rotas, auth)
php artisan test --testsuite=Feature
```

## Deploy Rapido

```bash
# 1. Variaveis de ambiente
cp .env.example .env
php artisan key:generate

# 2. Banco
php artisan migrate --force
php artisan db:seed --force

# 3. Assets
npm install && npm run build

# 4. Otimizacao
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Adicionando Novas Licoes

1. Edite `database/seeders/TrackSeeder.php`
2. Adicione um array na trilha correspondente com todos os campos obrigatorios:
   `title`, `slug`, `content`, `task`, `starter_code`, `solution_code`, `xp_reward`
3. Campos opcionais mas recomendados: `tips`, `examples`, `real_world_case`, `docs_link`
4. Execute: `php artisan db:seed --class=TrackSeeder`

## Adicionando Nova Trilha

1. No TrackSeeder, adicione ao array `$units`:

```php
[
    'name' => 'Unidade N: Nome da Trilha',
    'slug' => 'slug-unico',
    'description' => 'Descricao da trilha',
    'badge' => ['name' => 'Nome Badge', 'icon' => '⭐', 'description' => '...'],
    'lessons' => [ /* array de licoes */ ],
]
```

2. Execute o seeder

## Notas de Seguranca

- Endpoints de progresso nao exigem autenticacao (fallback para primeiro usuario) — planejado para single-player
- Admin restringe acesso por dominio de e-mail (`@jsacademy.com`)
- CSRF protegido em todos os formularios e fetch calls via `X-CSRF-TOKEN` header
- `eval()` no sandbox do editor e intencional — executa no browser do usuario, sem risco server-side
