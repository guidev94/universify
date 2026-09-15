# LetsworkAnalyticsBundle

Dashboard analytics premium para Mautic 5 com IA (Gemini).

## Requisitos

- Mautic 5.x
- PHP 8.2+
- MySQL 8.0+
- API Key do Google Gemini (para funcionalidades de IA)

## Instalação

### 1. Copiar plugin

```bash
cp -r LetsworkAnalyticsBundle /path/to/mautic/docroot/plugins/
chown -R www-data:www-data /path/to/mautic/docroot/plugins/LetsworkAnalyticsBundle
```

### 2. Criar campos customizados

Execute o SQL de instalação no banco do Mautic:

```bash
mysql -u mautic_user -p mautic_db < plugins/LetsworkAnalyticsBundle/Install/install.sql
```

Ou crie os campos manualmente via **Mautic Admin > Campos Customizados**.

### 3. Configurar API Key Gemini

Adicione ao ambiente do container/servidor:

```
GEMINI_API_KEY=sua_api_key_aqui
```

### 4. Ativar plugin

```bash
php bin/console mautic:plugins:reload
php bin/console cache:clear
```

### 5. Acessar

Navegue para: `/s/analytics`

## Funcionalidades

- Pipeline comercial com KPIs clicáveis
- Temperatura de leads com score (0-20 Frio, 21-60 Morno, 61-100 Quente, 100+ Prioritário)
- Funil de vendas visual
- E-mail marketing (campanhas, disparos, taxa de abertura)
- Comportamento no site (page hits)
- Leads quentes sem follow-up
- 5 abas: Visão Geral, Conversão, Segmentos, Pipeline, Perdas
- Modal do lead com ações (email, follow-up, alterar status)
- IA Gemini em todas as abas (análise, sugestões, comunicação)
- Notificações de follow-up (1 dia antes)

## Comportamento sem campos

Se os campos customizados não existirem, o plugin mostra valores zerados sem crashar (usa `columnExists()` check).

## Estrutura

```
LetsworkAnalyticsBundle/
├── Config/config.php          # Rotas, menu, services
├── Controller/                # AnalyticsController
├── Model/AnalyticsModel.php   # Queries + IA
├── Assets/css/analytics.css   # UI
├── Assets/js/analytics.js     # Interações
├── Resources/views/           # Templates Twig
├── Install/install.sql        # SQL de setup
└── Translations/              # pt_BR + en_US
```
