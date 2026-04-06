# ⚙️ Parâmetros e Arquitetura do Sistema - JS Academy

Este documento detalha as configurações, variáveis e a lógica de negócio por trás do SaaS **JS Academy**.

## 🏗️ Stack Tecnológica
*   **Backend:** Laravel 11 (PHP 8.3)
*   **Frontend:** Tailwind CSS v4, GSAP (Animações), Monaco Editor (IDE).
*   **Admin:** FilamentPHP v3 (TALL Stack).
*   **Database:** Eloquent ORM (SQLite/MySQL).

---

## 💎 Sistema de Gamificação (Parâmetros)

### 📈 Experiência (XP)
*   **Conclusão de Lição:** +100 XP.
*   **Bônus de Meta Diária:** +10% de XP sobre o ganho do dia.
*   **Sequência de Acertos (Combo):** 10 acertos seguidos ativam o tema **Cyber-Gold**.

### 🔥 Ofensivas (Streaks)
*   **Incremento:** 1 dia por login consecutivo com lição completada.
*   **Streak Freeze:** Custa 50 Diamantes. Protege a ofensiva por 24h de inatividade.

### 🏆 Ligas (Rankings)
*   **Bronze:** 0 - 500 XP
*   **Prata:** 501 - 1500 XP
*   **Ouro:** 1501 - 5000 XP
*   **Diamante:** 5000+ XP

---

## 🛡️ Segurança e Regras de Negócio

### 🚫 Bloqueio de Copy-Paste
*   **Implementação:** Interceptação do evento `paste` no DOM e desativação via `editor.updateOptions({ readOnly: false, contextmenu: false })` no Monaco Editor.
*   **Alertas:** Sistema de Toast personalizado para incentivar a digitação.

### 🔑 Acesso Administrativo
*   **Middleware:** `AuthenticateAdmin`.
*   **Regra:** `email_verified_at` deve ser NOT NULL e o e-mail deve pertencer ao domínio `@jsacademy.com`.

### 🧪 Validador de Código
*   **Sandbox:** Execução via `iframe` com atributo `sandbox="allow-scripts"`.
*   **Test Cases:** O sistema compara o `output` do console do usuário com o `expected_output` definido no banco de dados.

---

## 🚀 Parâmetros de Performance (2026 Ready)
*   **Vite Chunking:** Separação de bibliotecas pesadas (Monaco, GSAP) em pedaços menores para carregamento instantâneo.
*   **Backdrop Filter:** Uso intensivo de hardware acceleration para efeitos de desfoque (Blur).
*   **Lazy Loading:** As lições da trilha 'The Path' são carregadas sob demanda.

---
*Documentação técnica para desenvolvedores e administradores do sistema.*
