# JS Academy 🚀 (Advanced Evolution)

Bem-vindo ao **JS Academy**, a plataforma gamificada definitiva para dominar o JavaScript.

## 🛠️ Novas Funcionalidades

- **IDE Integrada (Monaco Editor)**: Editor profissional com tema Dark ('vs-dark') e autocomplete configurado para JS/ES6+.
- **The Path**: Layout visual de trilha (Bento Grid 2025) com unidades estratégicas:
    1. **Introdução e Lógica**: Fundamentos com botão Skip.
    2. **DOM e Eventos**: Projetos reais (Validador de Form).
    3. **Async e APIs**: Consumo de dados externos.
    4. **Patterns e Clean Code**: Refatoração de Checkout.
- **Gamificação Avançada**:
    - **Streaks (Ofensivas)**: Contador de dias consecutivos com lógica de proteção (Streak Freeze).
    - **Weekly Leagues**: Ranking global baseado em XP para competição saudável.
    - **Diamonds**: Moeda virtual para adquirir Streak Freezes e outros benefícios.
- **Validador de Código**: Sistema de 'Test Cases' que valida o código do usuário em tempo real antes de completar as lições.
- **UI/UX Premium**: Design Bento Grid 2025 com animações fluidas via **GSAP**.

## 🚀 Como Rodar

1. **Dependências**:
   ```bash
   npm install
   composer install
   ```

2. **Banco de Dados**:
   Configure seu `.env` e rode as migrations:
   ```bash
   php artisan migrate
    php artisan db:seed
    ```

## 🔒 Credenciais Admin

Acesse `/admin` com as credenciais padrão:
- **E-mail**: `admin@jsacademy.com`
- **Senha**: `password`

## 🚀 Frontend:
    ```bash
    npm run dev
    ```

## 🔒 Segurança e Performance

- **CSRF**: Proteção integrada em todas as rotas de progresso.
- **XSS**: Sanitização de saída e editor seguro.
- **Performance**: Eager loading de dados do usuário e animações otimizadas.

---
*Built with Laravel, Monaco Editor, GSAP and Tailwind CSS.*
