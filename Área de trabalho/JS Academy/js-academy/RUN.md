# 🚀 Como Rodar o JS Academy

Siga os passos abaixo para colocar a plataforma **JS Academy (Versão 2026 Ready)** para funcionar no seu ambiente local.

## 📋 Pré-requisitos
Antes de começar, certifique-se de ter instalado:
*   **PHP 8.2+**
*   **Composer** (Gerenciador de dependências PHP)
*   **Node.js 22+** e **NPM**
*   **SQLite3** (ou MySQL/Postgres se preferir alterar o `.env`)

---

## 🛠️ Passo a Passo de Instalação

### 1. Clonar e Acessar o Projeto
```bash
cd "seu-projeto"
```

### 2. Instalar Dependências
Instale as bibliotecas do backend (Laravel) e do frontend (Vite/Tailwind):
```bash
composer install
npm install
```

### 3. Configurar o Ambiente
Copie o arquivo de exemplo e gere a chave da aplicação:
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configurar o Banco de Dados (SQLite)
O projeto está configurado para usar SQLite por padrão para facilitar o rodar local:
```bash
# Cria o arquivo do banco se não existir
touch database/database.sqlite

# Executa as migrações e popula com dados iniciais (Tracks, Lessons, Admin)
php artisan migrate:fresh --seed
```

### 5. Compilar os Assets (Frontend)
Para ver as animações de 2026, Spring Physics e Glassmorphism:
```bash
npm run dev
```

### 6. Iniciar o Servidor Backend
Em um novo terminal, inicie o servidor do Laravel:
```bash
php artisan serve
```

---

## 🌐 Acesso ao Sistema
*   **Plataforma Aluno:** [http://127.0.0.1:8000](http://127.0.0.1:8000)
*   **Painel Administrativo:** [http://127.0.0.1:8000/admin](http://127.0.0.1:8000/admin)
    *   **User:** `admin@jsacademy.com`
    *   **Pass:** `password`

---

## 🧪 Rodando Testes (QA)
Para garantir que a gamificação e o editor estão funcionando:
```bash
# Testes Unitários e de Integração
php artisan test

# Testes de Interface (Browser) - Requer Laravel Dusk configurado
php artisan dusk
```

---
*Desenvolvido com foco em Performance, Segurança e Clean Code.*
